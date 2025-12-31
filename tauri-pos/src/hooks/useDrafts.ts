import { useState, useCallback } from 'react';
import { dbService } from '../services/db';
import { api } from '../services/api';
import type { OrderDraft, CartItem } from '../types';

export const useDrafts = (
    settings: any,
    showNotification: (msg: string, type: 'success' | 'error' | 'info') => void
) => {
    const [drafts, setDrafts] = useState<OrderDraft[]>([]);
    const [isLoadingDrafts, setIsLoadingDrafts] = useState(false);
    const [activeDraft, setActiveDraft] = useState<{ id: number, source: 'local' | 'server' } | null>(null);

    const [transactionTab, setTransactionTab] = useState<'draft' | 'completed'>('draft');
    const [isJoinModalOpen, setIsJoinModalOpen] = useState(false);

    const loadDrafts = useCallback(async (status: 'draft' | 'completed' = transactionTab) => {
        setIsLoadingDrafts(true);
        setDrafts([]);

        const allDrafts: OrderDraft[] = [];

        try {
            // 1. Local Drafts/Completed
            const localDrafts = await dbService.getDrafts(status);
            localDrafts.forEach(d => {
                allDrafts.push({
                    id: d.local_id!,
                    source: 'local',
                    data: JSON.parse(d.sale_data),
                    created_at: d.created_at
                });
            });

            // 2. Server Drafts (only if tab is 'draft')
            if (status === 'draft') {
                try {
                    const res = await api.getDrafts();
                    if (res.data && res.data.success) {
                        res.data.drafts.forEach((d: any) => {
                            allDrafts.push({
                                id: d.server_id,
                                source: 'server',
                                data: d.sale_data,
                                created_at: d.created_at
                            });
                        });
                    }
                } catch (e) {
                    console.log('Offline or server unreachable for drafts');
                }
            }

            // Sort by Date Descending
            allDrafts.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
            setDrafts(allDrafts);
        } catch (e) {
            console.error('Error loading drafts:', e);
            showNotification('Gagal memuat draft', 'error');
        } finally {
            setIsLoadingDrafts(false);
        }
    }, [transactionTab, showNotification]);

    const saveDraft = useCallback(async (
        cart: CartItem[],
        customerName: string,
        tableNumber: string,
        orderType: string,
        discount: number,
        calculateTotal: (cart: CartItem[]) => number
    ) => {
        if (cart.length === 0) {
            showNotification('Keranjang kosong!', 'error');
            return false;
        }

        if (!customerName.trim()) {
            showNotification('Nama Pelanggan wajib diisi!', 'error');
            return false;
        }

        const subtotal = cart.reduce((sum, item) => sum + Number(item.subtotal), 0);
        const taxRate = settings?.tax_rate || 0;
        const tax = (subtotal * taxRate) / 100;
        const total = calculateTotal(cart);

        const saleData = {
            items: cart.map(item => ({
                product_id: item.product.id,
                quantity: item.quantity,
                price: item.product.price,
                subtotal: item.subtotal,
                notes: item.notes || null,
                product_name: item.product.name,
                category_id: item.product.category_id
            })),
            subtotal,
            tax,
            discount,
            total,
            payment_method: null,
            customer_name: customerName,
            order_type: orderType,
            table_number: tableNumber,
            member_id: null,
            created_at: new Date().toISOString()
        };

        try {
            // If editing an existing draft, delete old one first
            if (activeDraft) {
                if (activeDraft.source === 'local') {
                    await dbService.deleteSale(activeDraft.id);
                } else {
                    // If server draft, we might not be able to delete it easily if offline or inconsistent
                    // But ideally we should. For now, let's just create a NEW draft and user calls "Save"
                    // Actually, if we are "Saving", we usually overwrite. 
                    // Let's assume we Create New and user must manually delete old if they want? 
                    // Or we try to delete old one.
                    try {
                        await api.deleteDraft(activeDraft.id);
                    } catch (e) { console.error("Failed delete old draft", e) }
                }
            }

            await dbService.saveOfflineSale(saleData, true); // true = isDraft
            showNotification('✅ Draft Tersimpan (Offline)', 'success');
            return true;
        } catch (error: any) {
            console.error('Save draft error:', error);
            showNotification('Gagal menyimpan draft: ' + error.message, 'error');
            return false;
        }
    }, [settings, activeDraft, showNotification]);

    const deleteDraft = useCallback(async (draft: OrderDraft) => {
        try {
            if (draft.source === 'local') {
                await dbService.deleteSale(draft.id);
            } else {
                await api.deleteDraft(draft.id);
            }
            showNotification('Draft dihapus', 'success');
            return true; // Return success
        } catch (e) {
            console.error('Failed delete draft', e);
            showNotification('Gagal menghapus draft', 'error');
            return false;
        }
    }, [showNotification]);

    const handleMergeDrafts = useCallback(async (selectedDraftIds: number[]) => {
        if (selectedDraftIds.length < 2) {
            showNotification('⚠️ Pilih minimal 2 draft untuk digabungkan', 'error');
            return false;
        }

        try {
            const selectedDrafts = drafts.filter(d => selectedDraftIds.includes(d.id));
            if (selectedDrafts.length !== selectedDraftIds.length) {
                showNotification('⚠️ Beberapa draft tidak ditemukan', 'error');
                return false;
            }

            let combinedItems: any[] = [];
            let combinedSubtotal = 0;
            const customerName = selectedDrafts[0].data.customer_name || 'Gabungan';
            const tableNumber = selectedDrafts[0].data.table_number || '';
            const orderType = selectedDrafts[0].data.order_type || 'Dine In';

            selectedDrafts.forEach(draft => {
                if (draft.data.items) {
                    draft.data.items.forEach((item: any) => {
                        // Check if existing in combined
                        const existing = combinedItems.find(i => i.product_id === item.product_id && i.notes === item.notes);
                        if (existing) {
                            existing.quantity += Number(item.quantity);
                            existing.subtotal += Number(item.subtotal);
                        } else {
                            combinedItems.push({ ...item, quantity: Number(item.quantity), subtotal: Number(item.subtotal) });
                        }
                    });
                }
            });

            // Recalculate Totals
            combinedSubtotal = combinedItems.reduce((acc, item) => acc + item.subtotal, 0);
            const tax = (combinedSubtotal * (settings?.tax_rate || 0)) / 100;
            const total = combinedSubtotal + tax; // No discount initially on merged

            const mergedSaleData = {
                items: combinedItems,
                subtotal: combinedSubtotal,
                tax: tax,
                discount: 0,
                total: total,
                payment_method: null,
                customer_name: customerName + (selectedDrafts.length > 1 ? ' (Merged)' : ''),
                order_type: orderType,
                table_number: tableNumber,
                created_at: new Date().toISOString()
            };

            // 1. Save NEW Draft
            await dbService.saveOfflineSale(mergedSaleData, true);

            // 2. Delete OLD Drafts
            for (const draft of selectedDrafts) {
                if (draft.source === 'local') {
                    await dbService.deleteSale(draft.id);
                } else {
                    await api.deleteDraft(draft.id);
                }
            }

            showNotification('✅ Draft Berhasil Digabungkan!', 'success');
            return true;
        } catch (err: any) {
            console.error('Merge failed:', err);
            showNotification('❌ Gagal menggabungkan transaksi: ' + err.message, 'error');
            return false;
        }
    }, [drafts, settings, showNotification]);

    // Reload when tab changes
    // But we probably want to trigger this manually or via useEffect in the hook?
    // Let's expose loadDrafts and let the consumer call it, or auto-load when tab changes?
    // Auto-load is better for UX.
    // useEffect(() => { loadDrafts(transactionTab); }, [transactionTab]);
    // But we need to be careful about infinite loops if dependencies change.
    // Let's kept it manual for now or let App trigger it? 
    // App currently triggers it on mount and on tab change. 
    // Let's adding the useEffect here to simplify App.

    return {
        drafts,
        setDrafts,
        isLoadingDrafts,
        loadDrafts,
        activeDraft,
        setActiveDraft,
        saveDraft,
        deleteDraft,
        handleMergeDrafts,
        transactionTab,
        setTransactionTab,
        isJoinModalOpen,
        setIsJoinModalOpen
    };
};
