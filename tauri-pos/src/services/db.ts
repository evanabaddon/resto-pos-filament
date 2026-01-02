import Database from '@tauri-apps/plugin-sql';
import type { Product, Category } from '../types';
import { imageCacheService } from './imageCache';

const DB_NAME = 'resto_pos.db';

class DatabaseService {
    db: Database | null = null;
    isInitialized = false;

    async init() {
        if (this.isInitialized) return;

        try {
            this.db = await Database.load(`sqlite:${DB_NAME}`);
            await this.createTables();
            this.isInitialized = true;
            console.log('📦 Database initialized');
        } catch (error) {
            console.error('❌ Failed to init database:', error);
            throw error;
        }
    }

    async createTables() {
        if (!this.db) return;

        // Products Table
        await this.db.execute(`
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                price REAL NOT NULL,
                category_id INTEGER,
                stock INTEGER DEFAULT 0,
                prepared_stock INTEGER DEFAULT 0,
                enable_stock_alert BOOLEAN DEFAULT 0,
                image TEXT,
                description TEXT,
                updated_at TEXT
            )
        `);

        // Migration: Add columns if not exist (quick fix for dev)
        try { await this.db.execute('ALTER TABLE products ADD COLUMN prepared_stock INTEGER DEFAULT 0'); } catch { }
        try { await this.db.execute('ALTER TABLE products ADD COLUMN enable_stock_alert BOOLEAN DEFAULT 0'); } catch { }

        // Categories Table
        await this.db.execute(`
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL
            )
        `);

        // Members Table (Local Cache) - Optional for now
        await this.db.execute(`
            CREATE TABLE IF NOT EXISTS members (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                phone TEXT,
                points_balance INTEGER DEFAULT 0,
                tier_name TEXT
            )
        `);

        // Payment Methods Table
        // Drop old table if exists (migration from old schema)
        try { await this.db.execute('DROP TABLE IF EXISTS payment_methods'); } catch { }

        await this.db.execute(`
            CREATE TABLE IF NOT EXISTS payment_methods (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                code TEXT,
                is_active INTEGER DEFAULT 1
            )
        `);

        // Sales Table (Pending Upload)
        await this.db.execute(`
            CREATE TABLE IF NOT EXISTS offline_sales (
                local_id INTEGER PRIMARY KEY AUTOINCREMENT,
                shift_id INTEGER, -- Link to Shift
                sale_data TEXT NOT NULL, -- JSON Stringified
                status TEXT DEFAULT 'pending', -- pending, synced, error, draft
                error_message TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                synced_at TEXT
            )
        `);

        // Migration: Add shift_id if not exists
        try { await this.db.execute('ALTER TABLE offline_sales ADD COLUMN shift_id INTEGER'); } catch { }
        // Migration: Add error_message if not exists (Conflict Resolution)
        try { await this.db.execute('ALTER TABLE offline_sales ADD COLUMN error_message TEXT'); } catch { }

        // Shifts Table (Local Cash Session)
        await this.db.execute(`
            CREATE TABLE IF NOT EXISTS shifts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                cashier_name TEXT,
                cash_in_hand REAL DEFAULT 0,
                cash_out REAL DEFAULT 0,
                opened_at TEXT,
                closed_at TEXT,
                total_cash_sales REAL DEFAULT 0,
                total_cash_expenses REAL DEFAULT 0,
                total_cash_purchases REAL DEFAULT 0,
                expected_cash REAL DEFAULT 0,
                difference REAL DEFAULT 0,
                status TEXT CHECK(status IN ('open', 'closed')) DEFAULT 'open',
                synced INTEGER DEFAULT 0,
                server_id INTEGER
            )
        `);

        // Migration: Add server_id if not exists
        try { await this.db.execute('ALTER TABLE shifts ADD COLUMN server_id INTEGER'); } catch { }
    }

    // --- PRODUCTS ---
    async upsertProducts(products: Product[]) {
        if (!this.db) return;

        try {
            // Batch size to avoid parameter limits (SQLite limit usually 32766, safe bet 50 items * 7 params = 350)
            const BATCH_SIZE = 50;

            for (let i = 0; i < products.length; i += BATCH_SIZE) {
                const batch = products.slice(i, i + BATCH_SIZE);
                const values: any[] = [];
                const placeholders: string[] = [];

                batch.forEach(p => {
                    values.push(p.id, p.name, p.price, p.category_id, p.stock, p.prepared_stock || 0, p.enable_stock_alert ? 1 : 0, p.image, p.description);
                    placeholders.push('(?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\'now\'))');
                });

                const query = `
                    INSERT INTO products (id, name, price, category_id, stock, prepared_stock, enable_stock_alert, image, description, updated_at) 
                    VALUES ${placeholders.join(', ')}
                    ON CONFLICT(id) DO UPDATE SET 
                        name = excluded.name,
                        price = excluded.price,
                        category_id = excluded.category_id,
                        stock = excluded.stock,
                        prepared_stock = excluded.prepared_stock,
                        enable_stock_alert = excluded.enable_stock_alert,
                        image = excluded.image,
                        description = excluded.description,
                        updated_at = datetime('now')
                `;

                // Execute the batch
                await this.db.execute(query, values);
            }

            console.log(`✅ Upserted ${products.length} products`);
        } catch (e) {
            console.error('Upsert products failed:', e);
        }
    }

    async clearProducts() {
        if (!this.db) return;
        try {
            await this.db.execute('DELETE FROM products');
            console.log('🧹 Products table cleared');
        } catch (e) {
            console.error('Failed to clear products:', e);
        }
    }

    async upsertPaymentMethods(methods: any[]) {
        if (!this.db || !methods.length) return;
        try {
            for (const m of methods) {
                await this.db.execute(`
                    INSERT INTO payment_methods (id, name, code, is_active) 
                    VALUES ($1, $2, $3, $4)
                    ON CONFLICT(id) DO UPDATE SET name = $2, code = $3, is_active = $4
                `, [m.id, m.name, m.code, (m.is_active !== undefined && m.is_active !== null) ? (m.is_active ? 1 : 0) : 1]);
            }
        } catch (e) {
            console.error('Failed to upsert payment methods:', e);
        }
    }

    async getPaymentMethods() {
        if (!this.db) return [];
        return await this.db.select<any[]>('SELECT * FROM payment_methods');
    }

    async getProducts(): Promise<Product[]> {
        if (!this.db) return [];
        return await this.db.select<Product[]>('SELECT * FROM products ORDER BY name ASC');
    }

    // --- CATEGORIES ---
    async upsertCategories(categories: Category[]) {
        if (!this.db) return;
        try {
            // Transaction removed to avoid locking issues in concurrent dev mode
            for (const c of categories) {
                await this.db.execute(
                    `INSERT INTO categories (id, name) VALUES ($1, $2)
                      ON CONFLICT(id) DO UPDATE SET name = excluded.name`,
                    [c.id, c.name]
                );
            }
            console.log(`✅ Upserted ${categories.length} categories`);
        } catch (e) {
            console.error('Upsert categories failed:', e);
        }
    }

    async getCategories(): Promise<Category[]> {
        if (!this.db) return [];
        return await this.db.select<Category[]>('SELECT * FROM categories ORDER BY name ASC');
    }

    // --- SALES ---
    async saveOfflineSale(saleData: any, isDraft = false, shiftId?: number) {
        if (!this.db) return;
        const json = JSON.stringify(saleData);
        // Use 'paid' for completed sales, 'draft' for drafts, 'pending' for sync queue
        const status = isDraft ? 'draft' : 'paid';

        // Fix Timezone: Use provided created_at (Local Time) or generate one locally if missing
        // Do NOT use datetime('now') as it is UTC.
        let createdAt = saleData.created_at;
        if (!createdAt) {
            const d = new Date();
            const pad = (n: number) => n.toString().padStart(2, '0');
            createdAt = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
        }

        const result = await this.db.execute(
            `INSERT INTO offline_sales(sale_data, status, shift_id, created_at) VALUES($1, $2, $3, $4)`,
            [json, status, shiftId || null, createdAt]
        );
        console.log(`✅ Offline sale saved (${status}). LastInsertId: `, result.lastInsertId);

        // DECREMENT LOCAL STOCK (for both drafts and completed sales)
        if (saleData.items && Array.isArray(saleData.items)) {
            for (const item of saleData.items) {
                if (item.product_id && item.quantity) {
                    try {
                        await this.db.execute(
                            `UPDATE products SET stock = stock - $1 WHERE id = $2 AND stock IS NOT NULL`,
                            [item.quantity, item.product_id]
                        );
                        console.log(`📈 Decremented stock for product ${item.product_id} by ${item.quantity}`);
                    } catch (err) {
                        console.error(`Failed to decrement stock for ${item.product_id}:`, err);
                    }
                }
            }
        }

        return result.lastInsertId;
    }

    async getPendingSales() {
        if (!this.db) return [];
        // Return 'paid' (completed offline), 'pending' (old status), and 'draft' for sync
        // Exclude 'error' and 'synced'
        return await this.db.select<{ local_id: number, sale_data: string, created_at: string, status: string }[]>(
            `SELECT * FROM offline_sales WHERE status IN('paid', 'pending', 'draft') ORDER BY created_at ASC`
        );
    }

    async getSyncIssues() {
        if (!this.db) return [];
        return await this.db.select<{ local_id: number, sale_data: string, created_at: string, error_message: string }[]>(
            `SELECT * FROM offline_sales WHERE status = 'error' ORDER BY created_at ASC`
        );
    }

    async markSaleError(localId: number, message: string) {
        if (!this.db) return;
        await this.db.execute(`UPDATE offline_sales SET status = 'error', error_message = $1 WHERE local_id = $2`, [message, localId]);
    }

    async retrySale(localId: number) {
        if (!this.db) return;
        await this.db.execute(`UPDATE offline_sales SET status = 'pending', error_message = NULL WHERE local_id = $1`, [localId]);
    }

    async getDrafts(status: 'draft' | 'completed' | 'all' = 'draft') {
        if (!this.db) return [];

        let query = 'SELECT * FROM offline_sales WHERE ';
        if (status === 'draft') {
            // Include 'draft', 'synced_draft', and legacy 'synced' drafts
            query += "(status IN ('draft', 'synced_draft') OR (status = 'synced' AND json_extract(sale_data, '$.status') = 'draft'))";
        } else if (status === 'completed') {
            // Include 'paid' (offline completed) and 'synced' (uploaded sales history)
            // Exclude drafts that might be marked synced but are drafts
            query += "status IN ('paid', 'synced') AND (json_extract(sale_data, '$.status') IS NULL OR json_extract(sale_data, '$.status') != 'draft')";
        } else {
            query += "status IN ('draft', 'paid', 'synced', 'synced_draft')";
        }
        query += ' ORDER BY created_at DESC';

        return await this.db.select<{ local_id: number, sale_data: string, created_at: string, status: string }[]>(query);
    }

    async markSaleAsSyncedDraft(localId: number) {
        if (!this.db) return;
        try {
            await this.db.execute("UPDATE offline_sales SET status = 'synced_draft' WHERE local_id = $1", [localId]);
        } catch (e) {
            console.error('Failed to mark sale as synced_draft:', e);
        }
    }

    async deleteSale(localId: number) {
        if (!this.db) return;

        // RESTORE STOCK BEFORE DELETING
        try {
            const sale = await this.db.select<{ sale_data: string }[]>(`SELECT sale_data FROM offline_sales WHERE local_id = $1`, [localId]);
            if (sale && sale.length > 0) {
                const saleData = JSON.parse(sale[0].sale_data);
                if (saleData.items && Array.isArray(saleData.items)) {
                    for (const item of saleData.items) {
                        if (item.product_id && item.quantity) {
                            await this.db.execute(
                                `UPDATE products SET stock = stock + $1 WHERE id = $2 AND stock IS NOT NULL`,
                                [item.quantity, item.product_id]
                            );
                            console.log(`📈 Restored stock for product ${item.product_id} by ${item.quantity}`);
                        }
                    }
                }
            }
        } catch (e) {
            console.error('Failed to restore stock during deleteSale:', e);
        }

        await this.db.execute(`DELETE FROM offline_sales WHERE local_id = $1`, [localId]);
    }

    async markSaleSynced(localId: number) {
        if (!this.db) return;
        await this.db.execute(`UPDATE offline_sales SET status = 'synced', synced_at = datetime('now'), error_message = NULL WHERE local_id = $1`, [localId]);
    }

    // Cleanup local drafts that match specific content (Zombie Killer)
    async cleanupMatchingDrafts(customerName: string, total: number) {
        if (!this.db) return;
        try {
            const drafts = await this.getDrafts('draft'); // Get 'draft' and 'synced_draft'
            const normName = (customerName || '').trim().toLowerCase();
            const normTotal = Number(total).toFixed(2);

            for (const d of drafts) {
                let data: any = {};
                try { data = JSON.parse(d.sale_data); } catch (e) { }

                const dName = (data.customer_name || '').trim().toLowerCase();
                const dTotal = Number(data.total || 0).toFixed(2);

                if (dName === normName && dTotal === normTotal) {
                    console.log(`🧹 Cleaning up zombie draft: ${d.local_id} (${dName} - ${dTotal})`);
                    await this.deleteSale(d.local_id);
                }
            }
        } catch (e) {
            console.error('Failed to cleanup matching drafts:', e);
        }
    }

    async clearSyncedSales() {
        if (!this.db) return;
        await this.db.execute(`DELETE FROM offline_sales WHERE status = 'synced'`);
    }

    async saveServerSalesHistory(sales: any[], currentShiftId?: number) {
        if (!this.db || !sales.length) return;
        console.log(`📥 Saving ${sales.length} server sales to local backup...`);

        try {
            for (const sale of sales) {
                // Check if already exists (by server ID in sale_data)
                const existing = await this.db.select<any[]>(`
                    SELECT local_id FROM offline_sales 
                    WHERE json_extract(sale_data, '$.server_id') = $1
                `, [sale.id]);

                if (existing.length > 0) {
                    console.log(`⏭️ Sale ${sale.id} already exists locally, skipping`);
                    continue;
                }

                // Save as 'synced' status (already on server, just for local backup)
                const saleData = {
                    server_id: sale.id,
                    invoice_number: sale.invoice_number,
                    items: sale.items,
                    subtotal: sale.subtotal,
                    tax: sale.tax,
                    discount: sale.discount,
                    total: sale.total,
                    payment_method_id: sale.payment_method_id,
                    payment_method: sale.payment_method,
                    status: sale.status,
                    created_at: sale.created_at,
                    customer_name: sale.customer_name,
                    order_type: sale.order_type,
                    table_number: sale.table_number
                };

                // Assign to current shift if provided
                await this.db.execute(`
                    INSERT INTO offline_sales (sale_data, status, shift_id, created_at, synced_at) 
                    VALUES ($1, 'synced', $2, $3, datetime('now'))
                `, [JSON.stringify(saleData), currentShiftId || null, sale.created_at]);
            }
            console.log(`✅ Server sales history saved to local backup`);
        } catch (e) {
            console.error('Failed to save server sales history:', e);
        }
    }

    // --- SHIFTS ---
    async createShift(shiftData: { cashier_name: string, cash_in_hand: number, user_id?: number }) {
        if (!this.db) return;

        // Use local datetime instead of UTC to match server timezone
        const now = new Date();
        const localDateTime = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 19).replace('T', ' ');

        const result = await this.db.execute(
            `INSERT INTO shifts (cashier_name, cash_in_hand, user_id, opened_at, status) 
             VALUES ($1, $2, $3, $4, 'open')`,
            [shiftData.cashier_name, shiftData.cash_in_hand, shiftData.user_id || null, localDateTime]
        );
        return result.lastInsertId;
    }

    async getOpenShift() {
        if (!this.db) return null;
        const shifts = await this.db.select<any[]>('SELECT * FROM shifts WHERE status = "open" ORDER BY id DESC LIMIT 1');
        return shifts.length > 0 ? shifts[0] : null;
    }

    async getShiftSalesTotal(shiftId: number) {
        if (!this.db) return 0;
        // Calculate sum of total from CASH sales linked to this shift
        // PLUS cash sales from server today (even if no shift_id)
        try {
            // First, check if we have any sales at all for this shift
            const allSales = await this.db.select<{ total: number, count: number }[]>(`
                SELECT 
                    SUM(json_extract(sale_data, '$.total')) as total,
                    COUNT(*) as count
                FROM offline_sales 
                WHERE shift_id = $1 
                  AND status IN ('pending', 'synced')
            `, [shiftId]);

            console.log(`📊 Shift ${shiftId} - Total sales: ${allSales[0]?.count || 0}, Total amount: ${allSales[0]?.total || 0}`);

            // Try to get cash payment method
            const cashMethod = await this.db.select<{ id: number }[]>(`
                SELECT id FROM payment_methods WHERE code = 'cash' LIMIT 1
            `);

            if (!cashMethod || cashMethod.length === 0) {
                console.warn('⚠️ Cash payment method not found in DB, using ALL sales as fallback');
                return allSales[0]?.total || 0;
            }

            const cashMethodId = cashMethod[0].id;
            console.log(`💰 Cash payment method ID: ${cashMethodId}`);

            // Count cash sales for this shift
            const cashSales = await this.db.select<{ total: number, count: number }[]>(`
                SELECT 
                    SUM(json_extract(sale_data, '$.total')) as total,
                    COUNT(*) as count
                FROM offline_sales 
                WHERE shift_id = $1 
                  AND status IN ('pending', 'synced')
                  AND json_extract(sale_data, '$.payment_method_id') = $2
            `, [shiftId, cashMethodId]);

            let cashTotal = cashSales[0]?.total || 0;
            let cashCount = cashSales[0]?.count || 0;

            console.log(`💵 Cash sales (with shift_id): ${cashCount} transactions, Total: ${cashTotal}`);

            // ALSO count cash sales from server today (even without shift_id)
            // These are sales that were made before shift was opened in Tauri
            const serverCashSales = await this.db.select<{ total: number, count: number }[]>(`
                SELECT 
                    SUM(json_extract(sale_data, '$.total')) as total,
                    COUNT(*) as count
                FROM offline_sales 
                WHERE json_extract(sale_data, '$.server_id') IS NOT NULL
                  AND status = 'synced'
                  AND date(created_at) = date('now')
                  AND json_extract(sale_data, '$.payment_method_id') = $1
                  AND (shift_id IS NULL OR shift_id != $2)
            `, [cashMethodId, shiftId]);

            const serverCashTotal = serverCashSales[0]?.total || 0;
            const serverCashCount = serverCashSales[0]?.count || 0;

            if (serverCashCount > 0) {
                console.log(`☁️ Server cash sales (today, no shift): ${serverCashCount} transactions, Total: ${serverCashTotal}`);
                cashTotal += serverCashTotal;
                cashCount += serverCashCount;
            }

            console.log(`💵 TOTAL Cash sales: ${cashCount} transactions, Total: ${cashTotal}`);

            // If no cash sales found but we have sales, it might be old data without payment_method_id
            if (cashCount === 0 && (allSales[0]?.count || 0) > 0) {
                console.warn('⚠️ No sales with payment_method_id found, using ALL sales (might be old data)');
                return allSales[0]?.total || 0;
            }

            return cashTotal;
        } catch (e) {
            console.error('❌ Failed to calc shift total:', e);
            return 0;
        }
    }

    async getPendingShifts() {
        if (!this.db) return [];
        return await this.db.select<any[]>('SELECT * FROM shifts WHERE synced = 0');
    }

    async updateShiftSyncStatus(localId: number, serverId: number) {
        if (!this.db) return;
        await this.db.execute('UPDATE shifts SET synced = 1, server_id = $1 WHERE id = $2', [serverId, localId]);
    }

    async getShiftByServerId(serverId: number) {
        if (!this.db) return null;
        const shifts = await this.db.select<any[]>('SELECT * FROM shifts WHERE server_id = $1 LIMIT 1', [serverId]);
        return shifts.length > 0 ? shifts[0] : null;
    }

    async saveServerShift(shiftData: any) {
        if (!this.db) return;

        // Check if already exists
        const existing = await this.getShiftByServerId(shiftData.id);
        if (existing) {
            console.log(`⏭️ Shift ${shiftData.id} already exists locally, skipping`);
            return existing.id;
        }

        // Insert new shift from server
        const result = await this.db.execute(
            `INSERT INTO shifts (server_id, cashier_name, cash_in_hand, opened_at, status, synced) 
             VALUES ($1, $2, $3, $4, 'open', 1)`,
            [shiftData.id, shiftData.user_name, shiftData.cash_in_hand, shiftData.opened_at]
        );

        console.log(`✅ Server shift ${shiftData.id} saved locally with ID ${result.lastInsertId}`);
        return result.lastInsertId;
    }

    async closeShift(id: number, closeData: { cash_out: number, total_cash_sales: number, expected_cash: number, difference: number }) {
        if (!this.db) return;

        // Use local datetime instead of UTC
        const now = new Date();
        const localDateTime = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 19).replace('T', ' ');

        await this.db.execute(
            `UPDATE shifts SET 
                cash_out = $1, 
                total_cash_sales = $2, 
                expected_cash = $3, 
                difference = $4,
                status = 'closed',
                closed_at = $5,
                synced = 0
             WHERE id = $6`,
            [closeData.cash_out, closeData.total_cash_sales, closeData.expected_cash, closeData.difference, localDateTime, id]
        );
    }

    // Clear all data (when switching servers)
    async clearAllData() {
        if (!this.db) return;

        console.log('🧹 Clearing all local data...');

        try {
            // Clear all tables
            await this.db.execute('DELETE FROM products');
            await this.db.execute('DELETE FROM categories');
            await this.db.execute('DELETE FROM payment_methods');
            await this.db.execute('DELETE FROM offline_sales');
            await this.db.execute('DELETE FROM shifts');
            await this.db.execute('DELETE FROM members');

            // Clear image cache
            await imageCacheService.clearCache();

            console.log('✅ All local data cleared');
        } catch (e) {
            console.error('❌ Failed to clear data:', e);
            throw e;
        }
    }
}

export const dbService = new DatabaseService();
export default dbService;
