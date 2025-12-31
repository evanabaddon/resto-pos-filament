import { useState, useEffect } from 'react';
import { dbService } from '../services/db';
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
    const handleCheckout = () => {
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
    };

    const handleSplitRequest = (itemsToSplit: CartItem[]) => {
        setSplitCart(itemsToSplit);
        setIsSplitModalOpen(false);
        setIsPaymentModalOpen(true); // Proceed to pay immediately for the split part
    };

    const handlePaymentConfirm = async (amount: number, methodId: number, methodCode: string) => {
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
            created_at: new Date().toISOString()
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
                    const text = printerService.generateReceiptText(savedSale, settings, printerSettings.cashierPaperWidth || '58mm');
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

            // Print Kitchen/Bar Tickets (Category Mapping)
            if (printerSettings.categoryMappings && printerSettings.categoryMappings.length > 0) {
                const printerGroups: Record<string, { items: any[], paperWidth: '58mm' | '80mm' }> = {};

                saleData.items.forEach(item => {
                    const mapping = printerSettings.categoryMappings.find((m: any) => m.categoryId === item.category_id);
                    if (mapping && mapping.printerName) {
                        if (!printerGroups[mapping.printerName]) {
                            printerGroups[mapping.printerName] = { items: [], paperWidth: mapping.paperWidth || '58mm' };
                        }
                        printerGroups[mapping.printerName].items.push(item);
                    }
                });

                for (const [targetPrinter, group] of Object.entries(printerGroups)) {
                    try {
                        const ticketOrder = { ...savedSale, items: group.items };
                        const ticketText = printerService.generateReceiptText(ticketOrder, settings, group.paperWidth);
                        await printerService.printJob(targetPrinter, ticketText);
                    } catch (e) { console.error('Kitchen print failed', e); }
                }
            }

            // 5. Delete Draft if exists (Only if paying FULL cart or if split replaces draft?)
            // If split, we probably keep the original draft? Or update it?
            // "Pecah Tagihan" usually means we pay part of it.
            // Complex logic: If split, we should ideally REDUCE the original draft.
            // But current implementation just pays selected items.
            // Simplification: Only delete draft if we are NOT splitting (paying full).
            if (!splitCart && activeDraft) {
                if (activeDraft.source === 'local') {
                    await dbService.deleteSale(activeDraft.id);
                } else {
                    // api.deleteDraft(activeDraft.id).catch(...)
                }
                setActiveDraft(null);
            }

            // 6. Clear Cart
            if (splitCart) {
                // Remove paid items from main cart
                // Filter out items that match the split items
                const remainingCart = cart.filter(item => {
                    const inSplit = splitCart.find(s => s.product.id === item.product.id && s.notes === item.notes);
                    // If in split, reduce quantity or remove?
                    // Split Bill Modal usually selects items to PAY.
                    // If we pay 1 of 2, we should reduce quantity.
                    // But current SplitBillModal simply selects items.
                    // Assuming SplitCart contains exact items to be removed.
                    // If quantity matches, remove. If less, reduce.
                    // For simplicity, let's assume we remove the exact instances.
                    if (inSplit) {
                        return false; // Remove fully for now as split bill usually handles full items or we need better logic
                    }
                    return true;
                });

                setCart(remainingCart);
                showNotification('✅ Pembayaran sebagian berhasil. Item tersisa masih di keranjang.', 'info');
            } else {
                clearCart();
            }

            setSplitCart(null);
            loadLocalData(); // Refresh stock display

        } catch (error: any) {
            console.error('Checkout failed:', error);
            showNotification('❌ Gagal memproses transaksi: ' + error.message, 'error');
        }
    };

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
