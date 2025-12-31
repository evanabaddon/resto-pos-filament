import { api } from './api';
import { dbService } from './db';


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

            if (products && Array.isArray(products)) {
                // Clear existing products to ensure local DB matches server filters (removing deleted/filtered items)
                await dbService.clearProducts();
                await dbService.upsertProducts(products);
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
                    console.log(`🚀 Uploading sale ID: ${sale.local_id}`);
                    const saleData = JSON.parse(sale.sale_data);

                    // Endpoint expects { orders: [...] }
                    const payload = { orders: [saleData] };
                    // console.log('📦 Upload Payload:', JSON.stringify(payload));

                    const response = await api.syncOfflineSale(payload);
                    console.log(`✅ Upload success for sale ${sale.local_id}:`, response.data);

                    await dbService.markSaleSynced(sale.local_id);
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

        // 4. Download Products (Get latest stock)
        await this.syncProducts();

        // 5. Download Sales History (Backup)
        await this.syncSalesHistory(activeShiftId);

        console.log('✅ MASTER SYNC COMPLETED');
    }
}

export const syncService = new SyncService();
