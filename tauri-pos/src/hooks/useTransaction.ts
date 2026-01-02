import { useState, useEffect, useCallback } from 'react';
import { dbService } from '../services/db';
import { api } from '../services/api';
import { printerService } from '../services/printer';
import { syncService } from '../services/sync';
import type { CartItem } from '../types';
import type { PrinterSettings } from '../services/printer';

interface UseTransactionProps {
    cart: CartItem[];
    customerName: string;
    tableNumber: string;
    orderType: string;
    discount: number;
    settings: any;
    printerSettings: PrinterSettings;
    activeShift: any;
    showNotification: (msg: string, type: 'success' | 'error' | 'info') => void;
    clearCart: () => void;
    setCart: (cart: CartItem[]) => void;
    activeDraft: any;
    setActiveDraft: (draft: any) => void;
    loadLocalData: () => void;
    // Shift Modal Control
    setIsShiftModalOpen: (open: boolean) => void;
    setShiftModalMode: (mode: 'open' | 'close') => void;
}

export const useTransaction = ({
    cart,
    customerName,
    tableNumber,
    orderType,
    discount,
    settings,
    printerSettings,
    activeShift,
    showNotification,
    clearCart,
    setCart,
    activeDraft,
    setActiveDraft,
    loadLocalData,
    setIsShiftModalOpen,
    setShiftModalMode
}: UseTransactionProps) => {
    const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);
    const [isSplitModalOpen, setIsSplitModalOpen] = useState(false);
    const [splitCart, setSplitCart] = useState<CartItem[] | null>(null);
    const [printOrder, setPrintOrder] = useState<any>(null); // For Auto Print

    // Handlers
    const handleCheckout = useCallback(() => {
        if (!activeShift) {
            showNotification('⚠️ Harap Buka Shift Terlebih Dahulu!', 'error');
            setShiftModalMode('open');
            setIsShiftModalOpen(true);
            return;
        }

        if (cart.length === 0) {
            showNotification('Cart is empty!', 'error');
            return;
        }

        if (!customerName.trim()) {
            showNotification('⚠️ Harap isi Nama Pelanggan untuk melanjutkan pembayaran!', 'error');
            return;
        }

        setIsPaymentModalOpen(true);
    }, [activeShift, cart.length, customerName, showNotification, setIsShiftModalOpen, setShiftModalMode]);

    const handleSplitRequest = useCallback((itemsToSplit: CartItem[]) => {
        if (!activeShift) {
            showNotification('⚠️ Harap Buka Shift Terlebih Dahulu!', 'error');
            setShiftModalMode('open');
            setIsShiftModalOpen(true);
            return;
        }
        setSplitCart(itemsToSplit);
        setIsSplitModalOpen(false);
        setIsPaymentModalOpen(true); // Proceed to pay immediately for the split part
    }, [activeShift, showNotification, setShiftModalMode, setIsShiftModalOpen]);

    const getLocalDBString = useCallback(() => {
        // Use Intl to force correct timezone parts exactly as shown in UI
        const d = new Date();
        const parts = new Intl.DateTimeFormat('en-GB', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit',
            hour12: false
        }).formatToParts(d);

        const p: any = {};
        parts.forEach(({ type, value }) => p[type] = value);
        return `${p.year}-${p.month}-${p.day} ${p.hour}:${p.minute}:${p.second}`;
    }, []);

    const handlePaymentConfirm = useCallback(async (amount: number, methodId: number, methodCode: string) => {
        if (!activeShift) {
            showNotification('⚠️ Gagal: Tidak ada shift aktif!', 'error');
            setIsPaymentModalOpen(false);
            return;
        }

        setIsPaymentModalOpen(false);

        // Determine which cart to pay (Main or Split)
        const cartToProcess = splitCart || cart;
        const subtotal = cartToProcess.reduce((sum, item) => sum + Number(item.subtotal), 0);
        const taxRate = settings?.tax_rate || 0;

        // Discount applied ONLY to full cart currently, unless we implement split discount
        // But for split, we usually disable discount to simplify math.
        const effectiveDiscount = splitCart ? 0 : discount;

        // Recalculate tax
        const tax = (subtotal * taxRate) / 100;
        const total = subtotal + tax - effectiveDiscount;

        const change = amount - total;

        const saleData = {
            items: cartToProcess.map(item => ({
                product_id: item.product.id,
                quantity: item.quantity,
                price: item.product.price,
                subtotal: item.subtotal,
                notes: item.notes || null,
                product_name: item.product.name, // Snapshot name
                category_id: item.product.category_id
            })),
            subtotal,
            tax,
            discount: effectiveDiscount,
            total,
            payment_method_id: methodId,
            payment_method: methodCode, // Snapshot name
            amount_paid: amount,
            change: change,
            customer_name: customerName,
            order_type: orderType,
            table_number: orderType === 'Dine In' ? tableNumber : null,
            shift_id: activeShift.id,
            created_at: getLocalDBString()
        };

        try {
            // 1. Save Offline
            const savedSaleId = await dbService.saveOfflineSale(saleData);

            // Construct sale object for printing (include the new ID)
            const savedSale = { ...saleData, local_id: savedSaleId, invoice_number: `OFFLINE-${savedSaleId}` };

            // 2. Decrement Local Stock (Optimistic) - Handled inside saveOfflineSale
            // await dbService.decrementStock(cartToProcess);

            // 3. Trigger Sync
            syncService.syncSales().catch(err => console.error('Background sync failed:', err));

            showNotification('✅ Pembayaran Berhasil!', 'success');

            // 4. Print
            // Print Cashier Receipt
            if (printerSettings.cashierPrinter) {
                try {
                    const text = printerService.generateReceiptText(savedSale, { ...settings, templates: printerSettings.templates }, printerSettings.cashierPaperWidth || '58mm');
                    await printerService.printJob(printerSettings.cashierPrinter, text);

                    if (printerSettings.autoPrint) {
                        setPrintOrder(savedSale);
                    }
                } catch (e) {
                    console.error('Printing failed:', e);
                    showNotification('⚠️ Gagal mencetak struk (Tapi data tersimpan).', 'error');
                }
            } else {
                // Fallback browser print
                setPrintOrder(savedSale);
            }

            // 5. Kitchen Printing (Incremental logic handles printed status)
            // Even though we prioritize printing at "Save Draft", user might pay directly.
            // This ensures any unprinted items (added at payment) are printed.
            try {
                await printerService.printKitchenTickets(
                    cartToProcess,
                    { ...settings, table_number: tableNumber, customer_name: customerName, order_type: orderType },
                    printerSettings,
                    [],
                    savedSale.invoice_number
                );
            } catch (e) {
                console.error('Kitchen print failed:', e);
            }

            // 6. Delete Draft & Clean Zombies
            await dbService.cleanupMatchingDrafts(customerName, total);

            if (!splitCart && activeDraft) {
                if (activeDraft.source === 'local') {
                    await dbService.deleteSale(activeDraft.id);
                } else {
                    try {
                        await api.deleteDraft(activeDraft.id);
                        console.log('✅ Deleted server draft after payment:', activeDraft.id);
                    } catch (e) {
                        console.error('⚠️ Failed to delete server draft after checkout:', e);
                    }
                }
                setActiveDraft(null);
            }

            // 7. Clear Cart
            if (splitCart) {
                // Remove paid items from main cart
                const remainingCart = cart.filter(item => {
                    const inSplit = splitCart.find(s => s.product.id === item.product.id && s.notes === item.notes);
                    if (inSplit) return false;
                    return true;
                });

                setCart(remainingCart);
                showNotification('✅ Pembayaran sebagian berhasil. Item tersisa masih di keranjang.', 'info');
                setSplitCart(null);
            } else {
                clearCart();
            }

            loadLocalData(); // Refresh stock display

        } catch (error: any) {
            console.error('Checkout failed:', error);
            showNotification('❌ Gagal memproses transaksi: ' + error.message, 'error');
        }
    }, [splitCart, cart, settings, discount, customerName, orderType, tableNumber, activeShift, printerSettings, showNotification, activeDraft, setActiveDraft, setCart, clearCart, loadLocalData]);

    // Auto Print Effect
    useEffect(() => {
        if (printOrder) {
            setTimeout(() => {
                window.print();
                setPrintOrder(null); // Reset after print dialog opens
            }, 500);
        }
    }, [printOrder]);

    return {
        isPaymentModalOpen,
        setIsPaymentModalOpen,
        isSplitModalOpen,
        setIsSplitModalOpen,
        splitCart,
        setSplitCart,
        handleCheckout,
        handleSplitRequest,
        handlePaymentConfirm,
        printOrder,
        setPrintOrder
    };
};
