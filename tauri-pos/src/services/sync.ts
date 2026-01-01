import { api } from './api';
import { dbService } from './db';
import { imageCacheService } from './imageCache';


class SyncService {
    private isProductSyncing = false;
    private isSalesSyncing = false;
    private isShiftSyncing = false;

    // Shift Sync: Local Shifts -> Server
    async syncShifts() {
        if (this.isShiftSyncing) return;
        this.isShiftSyncing = true;
        console.log('🔄 Checking for pending shifts...');

        try {
            const pendingShifts = await dbService.getPendingShifts();
            if (pendingShifts.length === 0) return;

            console.log(`⬆️ Syncing ${pendingShifts.length} shifts...`);

            // Upload to Server
            const res = await api.syncShifts(pendingShifts);

            if (res.data && res.data.success) {
                // Update Local Status
                for (const result of res.data.results) {
                    await dbService.updateShiftSyncStatus(result.local_id, result.server_id);
                    console.log(`✅ Shift ${result.local_id} synced (Server ID: ${result.server_id})`);
                }
            }
        } catch (error) {
            console.error('❌ Shift sync failed:', error);
        } finally {
            this.isShiftSyncing = false;
        }
    }

    // Download Current Open Shift from Server
    async syncCurrentShift() {
        console.log('⬇️ Checking for current open shift on server...');
        try {
            const res = await api.getCurrentShift();
            if (res.data && res.data.shift) {
                const serverShift = res.data.shift;
                console.log(`📥 Found open shift on server: ID ${serverShift.id}`);

                // Save to local DB
                const localId = await dbService.saveServerShift(serverShift);

                if (localId) {
                    // Return the local shift object
                    return await dbService.getOpenShift();
                }
            } else {
                console.log('ℹ️ No open shift found on server');
            }
            return null;
        } catch (error) {
            console.error('❌ Failed to sync current shift:', error);
            return null;
        }
    }

    // Down Sync: Get data from Server -> Local DB
    async syncProducts() {
        if (this.isProductSyncing) {
            console.warn('⏳ Product sync is already running, skipping.');
            return;
        }
        this.isProductSyncing = true;

        console.log('⬇️ Starting Product Sync...');
        try {
            const res = await api.getProducts();
            const { products, categories, payment_methods } = res.data;

            console.log(`📡 Received ${products?.length || 0} products from server`);

            if (products && Array.isArray(products) && products.length > 0) {
                // Clear existing products to ensure local DB matches server filters (removing deleted/filtered items)
                await dbService.clearProducts();
                await dbService.upsertProducts(products);

                // Download product images
                await this.downloadProductImages(products);
            }
            if (categories && Array.isArray(categories)) {
                await dbService.upsertCategories(categories);
            }
            if (payment_methods && Array.isArray(payment_methods)) {
                await dbService.upsertPaymentMethods(payment_methods);
                console.log(`💳 Synced ${payment_methods.length} payment methods`);
            }

            console.log(`✅ Synced ${products?.length || 0} products successfully`);
        } catch (error) {
            console.error('❌ Product sync failed:', error);
        } finally {
            this.isProductSyncing = false;
        }
    }

    // Download product images
    private async downloadProductImages(products: any[]) {
        try {
            const apiUrl = localStorage.getItem('pos_api_url') || 'http://localhost:8000/api';
            const baseUrl = apiUrl.replace('/api', '');

            const imagesToDownload = products
                .filter(p => p.image && !p.image.startsWith('http'))
                .map(p => {
                    // Laravel stores images in storage folder, so we need to add /storage/ prefix
                    // if the path doesn't already include it
                    let imagePath = p.image;
                    if (!imagePath.startsWith('storage/')) {
                        imagePath = `storage/${imagePath}`;
                    }

                    return {
                        url: `${baseUrl}/${imagePath}`,
                        filename: p.image.split('/').pop() || `product_${p.id}.png`
                    };
                });

            if (imagesToDownload.length > 0) {
                console.log(`📥 Downloading ${imagesToDownload.length} product images...`);
                await imageCacheService.downloadImages(imagesToDownload, 5);
                console.log(`✅ Product images downloaded`);
            }
        } catch (error) {
            console.error('❌ Failed to download product images:', error);
            // Don't fail the entire sync if images fail
        }
    }

    async syncSettings() {
        try {
            console.log('⚙️ Syncing settings...');
            const res = await api.getSettings();
            if (res.data) {
                // Remove 'order' from storage to re-render receipt with new settings?
                // For now just store config
                localStorage.setItem('pos_settings', JSON.stringify(res.data));
                console.log('✅ Settings synced:', res.data);
            }
        } catch (error) {
            console.error('❌ Sync settings failed:', error);
        }
    }

    // Up Sync: Pending Sales -> Server
    async syncSales() {
        if (this.isSalesSyncing) {
            console.warn('⚠️ Sales sync is already running, skipping.');
            return;
        }

        console.log('🔄 Checking for offline sales to sync...');
        this.isSalesSyncing = true;

        try {
            const pendingSales = await dbService.getPendingSales();
            console.log(`📊 Found ${pendingSales.length} pending sales`);

            if (pendingSales.length === 0) return;

            console.log(`⬆️ Syncing ${pendingSales.length} offline sales...`);

            for (const sale of pendingSales) {
                try {
                    console.log(`🚀 Uploading sale ID: ${sale.local_id}, status: ${sale.status}`);
                    const saleData = JSON.parse(sale.sale_data);

                    // Map local status to server status
                    // Local: 'paid' | 'pending' | 'draft'
                    // Server: 'completed' | 'draft'
                    if (sale.status === 'paid' || sale.status === 'pending') {
                        saleData.status = 'completed'; // Paid transactions
                    } else {
                        saleData.status = 'draft'; // Draft transactions
                    }

                    // Endpoint expects { orders: [...] }
                    const payload = { orders: [saleData] };
                    // console.log('📦 Upload Payload:', JSON.stringify(payload));

                    const response = await api.syncOfflineSale(payload);
                    console.log(`✅ Upload success for sale ${sale.local_id}:`, response.data);

                    if (sale.status === 'draft') {
                        // If it was a draft, KEEP IT local but mark as synced_draft
                        // This allows offline access to this draft later (e.g. to pay it)
                        await dbService.markSaleAsSyncedDraft(sale.local_id);
                    } else {
                        // If paid, keep record but mark as synced
                        await dbService.markSaleSynced(sale.local_id);
                    }
                } catch (err: any) {
                    console.error(`❌ Failed to sync sale ${sale.local_id}:`, err);

                    // Error Handling Strategy:
                    // 1. If 4xx (Client Error) -> Mark as Error (Requires User Intervention)
                    // 2. If 500 (Server Error) -> Keep Pending (Retry later) ?? Or Mark Error?
                    //    Usually 500 on valid data means server bug, retry might not fix it. 
                    //    But 503 (Service Unavailable) should retry.
                    //    Let's mark 400-499 as Error.

                    if (err.response) {
                        const status = err.response.status;
                        const msg = err.response.data?.message || err.message || 'Unknown Server Error';

                        if (status >= 400 && status < 500) {
                            console.warn(`⚠️ Sale ${sale.local_id} marked as ERROR due to ${status}`);
                            await dbService.markSaleError(sale.local_id, `Server Error ${status}: ${msg}`);
                        } else {
                            console.warn(`⏳ Sale ${sale.local_id} kept as PENDING (Status ${status})`);
                        }
                    } else if (err.request) {
                        // Network error (no response) - Keep Pending
                        console.warn(`⏳ Sale ${sale.local_id} kept as PENDING (Network Error)`);
                    } else {
                        // Other error - Mark Error
                        await dbService.markSaleError(sale.local_id, `Client Error: ${err.message}`);
                    }
                }
            }

            await dbService.clearSyncedSales();

        } catch (error: any) {
            console.error('Sales sync critical failure:', error);
        } finally {
            this.isSalesSyncing = false;
        }
    }

    // Down Sync: Get today's sales history from server for local backup
    async syncSalesHistory(currentShiftId?: number) {
        console.log('📥 Syncing sales history from server...');
        try {
            const res = await api.getSalesHistory();
            if (res.data && res.data.success) {
                const sales = res.data.sales || [];
                console.log(`📊 Received ${sales.length} sales from server`);

                if (sales.length > 0) {
                    await dbService.saveServerSalesHistory(sales, currentShiftId);
                }
            }
        } catch (error) {
            console.error('❌ Sales history sync failed:', error);
        }
    }

    // MASTER SYNC FUNCTION
    async syncAll(activeShiftId?: number) {
        console.log('🔄 STARTING MASTER SYNC...');

        // 1. Settings (Quick)
        await this.syncSettings();

        // 2. Upload Sales (CRITICAL: Do this before downloading products to ensure stock is deducted)
        await this.syncSales();

        // 3. Upload Shifts
        await this.syncShifts();

        // 4. Download Current Shift (if no active shift locally)
        if (!activeShiftId) {
            await this.syncCurrentShift();
        }

        // 5. Download Products (Get latest stock)
        await this.syncProducts();

        // 6. Download Sales History (Backup)
        await this.syncSalesHistory(activeShiftId);

        console.log('✅ MASTER SYNC COMPLETED');
    }
}

export const syncService = new SyncService();
