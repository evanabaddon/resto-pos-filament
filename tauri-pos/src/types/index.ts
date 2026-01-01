export interface Product {
    id: number;
    name: string;
    price: number;
    category_id: number;
    image?: string;
    stock?: number;
    prepared_stock?: number;
    enable_stock_alert?: boolean;
    description?: string;
}

export interface Category {
    id: number;
    name: string;
}

export interface Member {
    id: number;
    name: string;
    phone?: string;
    points_balance?: number;
}

export interface CartItem {
    product: Product;
    quantity: number;
    subtotal: number;
    notes?: string;
}

export interface Sale {
    local_id?: number;
    sale_data: string; // JSON
    status: 'pending' | 'synced';
    created_at: string;
}

export interface OrderDraft {
    id: number;
    source: 'local' | 'server';
    data: any;
    created_at: string;
    // status local DB, useful for UI indicators (e.g. 'draft' vs 'synced_draft')
    sync_status?: string;
}
