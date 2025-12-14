const DB_NAME = 'resto_pos_db';
const DB_VERSION = 1;
const STORE_PRODUCTS = 'products';
const STORE_OFFLINE_SALES = 'offline_sales';

const OfflinePOS = {
    db: null,
    isOnline: navigator.onLine,

    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onerror = (event) => {
                console.error("Database error: " + event.target.errorCode);
                reject(event.target.error);
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                console.log("OfflinePOS DB initialized");
                this.setupNetworkListeners();
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                // Create Products Store (keyPath: id)
                if (!db.objectStoreNames.contains(STORE_PRODUCTS)) {
                    const productStore = db.createObjectStore(STORE_PRODUCTS, { keyPath: "id" });
                    productStore.createIndex("name", "name", { unique: false });
                    productStore.createIndex("category_id", "category_id", { unique: false });
                }
                // Create Offline Sales Store (autoIncrement key)
                if (!db.objectStoreNames.contains(STORE_OFFLINE_SALES)) {
                    db.createObjectStore(STORE_OFFLINE_SALES, { autoIncrement: true });
                }
            };
        });
    },

    setupNetworkListeners() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.syncOfflineSales();
            window.dispatchEvent(new CustomEvent('pos-network-status', { detail: { online: true } }));
        });
        window.addEventListener('offline', () => {
            this.isOnline = false;
            window.dispatchEvent(new CustomEvent('pos-network-status', { detail: { online: false } }));
        });
    },

    async saveProducts(products) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_PRODUCTS], "readwrite");
            const store = transaction.objectStore(STORE_PRODUCTS);

            // Clear old data first? Or merge?
            // Clearing is safer to remove deleted items
            store.clear().onsuccess = () => {
                products.forEach(product => {
                    store.add(product);
                });
            };

            transaction.oncomplete = () => {
                console.log(`Saved ${products.length} products to offline DB`);
                resolve(true);
            };
            transaction.onerror = (e) => reject(e);
        });
    },

    async searchProducts(query) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_PRODUCTS], "readonly");
            const store = transaction.objectStore(STORE_PRODUCTS);
            const results = [];

            // If query is empty, return all (or limit?)
            if (!query) {
                const request = store.getAll();
                request.onsuccess = () => resolve(request.result);
                return;
            }

            const lowerQuery = query.toLowerCase();

            // Cursor for manual filtering (IndexedDB fuzzy search is limited)
            store.openCursor().onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    const product = cursor.value;
                    if (product.name.toLowerCase().includes(lowerQuery)) {
                        results.push(product);
                    }
                    cursor.continue();
                } else {
                    resolve(results);
                }
            };
        });
    },

    async saveOfflineSale(saleData) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_OFFLINE_SALES], "readwrite");
            const store = transaction.objectStore(STORE_OFFLINE_SALES);

            const sale = {
                ...saleData,
                created_at: new Date().toISOString(),
                synced: false
            };

            const request = store.add(sale);

            request.onsuccess = () => {
                console.log("Offline sale saved");
                resolve(request.result); // Returns key
            };
            request.onerror = (e) => reject(e);
        });
    },

    async getOfflineSales() {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_OFFLINE_SALES], "readonly");
            const store = transaction.objectStore(STORE_OFFLINE_SALES);
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = (e) => reject(e);
        });
    },

    async getOfflineSalesWithKeys() {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_OFFLINE_SALES], "readonly");
            const store = transaction.objectStore(STORE_OFFLINE_SALES);
            const items = [];

            const request = store.openCursor();
            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    items.push({ key: cursor.key, data: cursor.value });
                    cursor.continue();
                } else {
                    resolve(items);
                }
            };
            request.onerror = (e) => reject(e);
        });
    },

    async deleteOfflineSale(key) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_OFFLINE_SALES], "readwrite");
            const store = transaction.objectStore(STORE_OFFLINE_SALES);
            const request = store.delete(key);

            request.onsuccess = () => resolve();
            request.onerror = (e) => reject(e);
        });
    },

    async countOfflineSales() {
        if (!this.db) await this.init();
        return new Promise((resolve) => {
            const store = this.db.transaction([STORE_OFFLINE_SALES], "readonly").objectStore(STORE_OFFLINE_SALES);
            const req = store.count();
            req.onsuccess = () => resolve(req.result);
        });
    },

    async clearOfflineSales() {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_OFFLINE_SALES], "readwrite");
            transaction.objectStore(STORE_OFFLINE_SALES).clear();
            transaction.oncomplete = () => resolve();
        });
    },

    // To be implemented: Sync logic calling Livewire or API
    async syncOfflineSales() {
        const count = await this.countOfflineSales();
        if (count > 0) {
            console.log(`Attempting to sync ${count} offline sales...`);
            window.dispatchEvent(new CustomEvent('pos-sync-needed'));
        }
    }
};

window.OfflinePOS = OfflinePOS;
