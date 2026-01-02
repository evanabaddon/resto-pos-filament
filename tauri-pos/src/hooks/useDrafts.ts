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

    // Helper to get consistent local DB string 'YYYY-MM-DD HH:mm:ss'
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

    const loadDrafts = useCallback(async (status: 'draft' | 'completed' | 'all' = transactionTab) => {
        setIsLoadingDrafts(true);
        setDrafts([]); // Clear drafts before loading

        try {
            // Try to load from server first
            const serverDrafts: OrderDraft[] = [];
            let isServerReachable = false;

            if (status === 'draft') {
                try {
                    const res = await api.getDrafts();
                    // If we get a response (status 200), server is reachable regardless of data content
                    isServerReachable = true;
                    console.log('🌍 Server reachable for drafts. Response Status:', res.status);

                    const draftsData = res.data?.drafts || res.data?.data || (Array.isArray(res.data) ? res.data : []);

                    if (Array.isArray(draftsData)) {
                        draftsData.forEach((d: any) => {
                            serverDrafts.push({
                                id: d.server_id || d.id,
                                source: 'server',
                                data: d.sale_data || d,
                                created_at: d.created_at
                            });
                        });
                    }
                } catch (e) {
                    console.log('Offline or server unreachable for drafts', e);
                }
            } else if (status === 'completed') {
                try {
                    const res = await api.getSalesHistory();
                    isServerReachable = true; // Reachable

                    if (res.data && Array.isArray(res.data.data)) {
                        res.data.data.forEach((d: any) => {
                            serverDrafts.push({
                                id: d.id,
                                source: 'server',
                                data: { ...d, items: d.items || [], customer_name: d.customer_name || 'Guest' },
                                created_at: d.created_at
                            });
                        });
                    }
                } catch (e) {
                    console.log('Offline or server unreachable for sales history', e);
                }
            }

            // Load local drafts/completed
            const localDbDrafts = await dbService.getDrafts(status);

            // Merge: Server drafts + Local drafts
            const allDrafts: OrderDraft[] = [];

            // Map known server IDs
            const serverIds = new Set(serverDrafts.map(d => d.id));

            // Add server drafts
            allDrafts.push(...serverDrafts);

            // ---------------------------------------------------------
            // SMART DEDUPLICATION LOGIC FOR LOCAL DRAFTS
            // ---------------------------------------------------------
            const uniqueLocalDraftsMap = new Map<string, any>();

            // Sort by Date DESC first, so we process NEWEST (usually Unsynced) first.
            localDbDrafts.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());

            localDbDrafts.forEach(d => {
                // 1. Online Filter: If reachable, hide ONLY 'synced_draft'.
                // We do NOT hide 'synced' (History) because server might be paginated/empty.
                // We rely on ID Deduplication for history.
                if (isServerReachable && d.status === 'synced_draft') {
                    return;
                }

                let data: any = {};
                try { data = JSON.parse(d.sale_data); } catch (e) { }

                // 2. ID Filter: Hide if server ID matches loaded server draft
                if (data.server_id && serverIds.has(data.server_id)) {
                    return;
                }

                // 3. Content Dedup (Offline cleanup):
                // Key = CustomerName + Total Amount.
                // We normalize total to Number to avoid string/float mismatch (e.g. "26400.00" vs 26400)
                const nameKey = (data.customer_name || 'Guest').trim().toLowerCase();
                const totalKey = Number(data.total || 0).toFixed(2); // Use 2 decimal fixed for reliable currency comparison
                const contentKey = `${nameKey}-${totalKey}`;

                if (uniqueLocalDraftsMap.has(contentKey)) {
                    const existing = uniqueLocalDraftsMap.get(contentKey);

                    // If existing in map is 'draft' (Newer/Unsynced), keep it. Skip current 'synced_draft'.
                    if (existing.status === 'draft' && (d.status === 'synced_draft' || d.status === 'synced')) {
                        return;
                    }

                    // If existing in map is 'synced_draft' (Older) and current is 'draft' (Newer), REPLACE it.
                    if ((existing.status === 'synced_draft' || existing.status === 'synced') && d.status === 'draft') {
                        uniqueLocalDraftsMap.set(contentKey, d);
                        return;
                    }
                } else {
                    uniqueLocalDraftsMap.set(contentKey, d);
                }
            });

            // Push filtered local drafts
            uniqueLocalDraftsMap.forEach(d => {
                let data: any = {};
                try { data = JSON.parse(d.sale_data); } catch (e) { }
                allDrafts.push({
                    id: d.local_id!,
                    source: 'local',
                    data: data,
                    created_at: d.created_at,
                    sync_status: d.status
                });
            });

            // Final Sort by Date Descending
            allDrafts.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());

            setDrafts(allDrafts);
            console.log(`✅ Loaded ${allDrafts.length} ${status} transactions.`);

        } catch (e) {
            console.error('Error loading drafts:', e);
            showNotification('Gagal memuat draft', 'error');
            setDrafts([]);
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
            created_at: getLocalDBString()
        };

        try {
            // If editing an existing draft, delete old one first
            if (activeDraft) {
                if (activeDraft.source === 'local') {
                    await dbService.deleteSale(activeDraft.id);
                } else {
                    // if server active, try delete or assume create new
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
                created_at: getLocalDBString()
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
