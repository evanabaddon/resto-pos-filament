import { invoke } from '@tauri-apps/api/core';

export interface PrinterSettings {
    cashierPrinter: string;
    cashierPaperWidth: '58mm' | '80mm';
    autoPrint: boolean;
    typeMappings: {
        productType: 'raw' | 'produced' | 'retail' | 'bar';
        printerName: string;
        paperWidth: '58mm' | '80mm';
    }[];
}

export const printerService = {
    // Get list of printers from backend
    getPrinters: async (): Promise<string[]> => {
        try {
            return await invoke('get_printers');
        } catch (error) {
            console.error('Failed to get printers:', error);
            return [];
        }
    },

    // Send text content to a specific printer
    printJob: async (printerName: string, content: string): Promise<string> => {
        try {
            return await invoke('print_job', { printerName, content });
        } catch (error) {
            console.error(`Failed to print to ${printerName}:`, error);
            throw error;
        }
    },

    // Helper to format receipt as simple text (fallback for direct print)
    // Supports 58mm (32 chars) and 80mm (48 chars)
    generateReceiptText: (order: any, settings: any, width: '58mm' | '80mm' = '58mm'): string => {
        const chars = width === '80mm' ? 48 : 32;
        const line = '-'.repeat(chars);
        const center = (text: string) => {
            const spaces = Math.max(0, Math.floor((chars - text.length) / 2));
            return ' '.repeat(spaces) + text;
        };
        const kv = (key: string, val: string) => {
            const pad = Math.max(0, chars - key.length - val.length);
            return key + ' '.repeat(pad) + val;
        };

        let txt = '';
        txt += center(settings?.store_name || 'RESTO POS') + '\n';
        txt += center(settings?.store_address || '') + '\n';
        txt += center(settings?.store_phone || '') + '\n';
        txt += line + '\n';
        txt += kv('Tgl:', new Date().toLocaleString('id-ID')) + '\n';
        txt += kv('No:', order.invoice_number || 'OFFLINE') + '\n';
        txt += kv('Kasir:', order.cashier_name || 'Admin') + '\n';
        txt += line + '\n';

        order.items.forEach((item: any) => {
            txt += `${item.product_name}\n`;
            txt += kv(`${item.quantity} x ${item.price.toLocaleString('id-ID')}`, item.subtotal.toLocaleString('id-ID')) + '\n';
        });

        txt += line + '\n';
        txt += kv('Subtotal:', order.subtotal.toLocaleString('id-ID')) + '\n';
        if (order.tax > 0) txt += kv(`Pajak (${settings?.tax_rate || 0}%):`, order.tax.toLocaleString('id-ID')) + '\n';
        if (order.discount > 0) txt += kv('Diskon:', `(${order.discount.toLocaleString('id-ID')})`) + '\n';
        txt += line + '\n';
        txt += kv('TOTAL:', order.total.toLocaleString('id-ID')) + '\n';
        txt += line + '\n';
        txt += center(settings?.receipt_footer || 'Terima Kasih') + '\n';
        txt += '\n\n\n.'; // Feed
        return txt;
    },

    // Generate Shift Report Text
    generateShiftReportText: (shiftData: any, settings: any, width: '58mm' | '80mm' = '58mm'): string => {
        const chars = width === '80mm' ? 48 : 32;
        const line = '-'.repeat(chars);
        const center = (text: string) => {
            const spaces = Math.max(0, Math.floor((chars - text.length) / 2));
            return ' '.repeat(spaces) + text;
        };
        const kv = (key: string, val: string) => {
            const pad = Math.max(0, chars - key.length - val.length);
            return key + ' '.repeat(pad) + val;
        };

        let txt = '';
        txt += center(settings?.store_name || 'RESTO POS') + '\n';
        txt += center('SHIFT REPORT') + '\n';
        txt += line + '\n';
        txt += kv('Kasir:', shiftData.cashier_name || 'Admin') + '\n';
        txt += kv('Mulai:', new Date(shiftData.opened_at).toLocaleString('id-ID')) + '\n';
        txt += kv('Tutup:', new Date().toLocaleString('id-ID')) + '\n';
        txt += line + '\n';

        txt += kv('Modal Awal:', (shiftData.cash_in_hand || 0).toLocaleString('id-ID')) + '\n';
        txt += kv('Total Cash:', (shiftData.total_cash_sales || 0).toLocaleString('id-ID')) + '\n';
        txt += line + '\n';

        txt += kv('Seharusnya:', (shiftData.expected_cash || 0).toLocaleString('id-ID')) + '\n';
        txt += kv('Aktual:', (shiftData.cash_out || 0).toLocaleString('id-ID')) + '\n';

        const diff = shiftData.difference || 0;
        const diffStr = (diff >= 0 ? '+' : '') + diff.toLocaleString('id-ID');
        txt += kv('Selisih:', diffStr) + '\n';

        txt += line + '\n';
        txt += center('Admin Signature') + '\n\n\n';
        txt += line + '\n';
        txt += '\n\n\n.'; // Feed
        return txt;
    }
};
