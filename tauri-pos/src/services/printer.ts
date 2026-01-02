import { invoke } from '@tauri-apps/api/core';

export interface PrinterTemplates {
    payment: string;
    kitchen: string;
    shift_report: string;
}

export interface PrinterSettings {
    cashierPrinter: string;
    cashierPaperWidth: '58mm' | '80mm';
    autoPrint: boolean;
    typeMappings: {
        productType: 'raw' | 'produced' | 'retail' | 'bar';
        printerName: string;
        paperWidth: '58mm' | '80mm';
    }[];
    templates?: PrinterTemplates;
}

const DEFAULT_TEMPLATES = {
    payment: `{{c:{{store_name}}}}
{{c:{{store_address}}}}
{{c:{{store_phone}}}}
{{line}}
{{lr:Tgl:|{{date}}}}
{{lr:No:|{{invoice_number}}}}
{{lr:Kasir:|{{cashier_name}}}}
{{line}}
{{items}}
{{line}}
{{lr:Subtotal:|{{subtotal}}}}
{{lr:{{tax_label}}|{{tax}}}}
{{lr:{{discount_label}}|{{discount}}}}
{{line}}
{{lr:TOTAL:|{{total}}}}
{{line}}
{{c:{{footer}}}}
.`,
    kitchen: `{{c:{{store_name}}}}
{{c:TIKET PESANAN}}
{{line}}
{{lr:Tgl:|{{date}}}}
{{lr:No:|{{invoice_number}}}}
{{lr:Meja:|{{table_number}}}}
{{line}}
{{items}}
{{line}}
.`,
    shift_report: `{{c:{{store_name}}}}
{{c:SHIFT REPORT}}
{{line}}
{{lr:Kasir:|{{cashier_name}}}}
{{lr:Mulai:|{{opened_at}}}}
{{lr:Tutup:|{{closed_at}}}}
{{line}}
{{lr:Modal Awal:|{{cash_in_hand}}}}
{{lr:Total Cash:|{{total_cash_sales}}}}
{{line}}
{{lr:Seharusnya:|{{expected_cash}}}}
{{lr:Aktual:|{{cash_out}}}}
{{lr:Selisih:|{{difference}}}}
{{line}}
{{c:Admin Signature}}


{{line}}
.`
};

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

    // Helper to format receipt using template
    renderTemplate: (template: string, data: any, width: '58mm' | '80mm' = '58mm', format: 'escpos' | 'html' = 'escpos'): string => {
        // ESC/POS Constants
        const ESC = '\x1b';
        const GS = '\x1d';

        let BOLD_ON = ESC + 'E' + '\x01';
        let BOLD_OFF = ESC + 'E' + '\x00';
        let SIZE_NORMAL = GS + '!' + '\x00'; // 0
        let SIZE_2X = GS + '!' + '\x11'; // Double Width & Height (16 + 1)

        // Strip control characters for length calculation
        let strip = (s: string) => s.replace(/[\u001b\u001d][@!E][\x00-\x1f]?/g, '');

        if (format === 'html') {
            BOLD_ON = '<b class="font-extrabold">';
            BOLD_OFF = '</b>';
            SIZE_2X = '<span class="text-xl font-bold">'; // Visually double-ish
            SIZE_NORMAL = '</span>';
            strip = (s: string) => s.replace(/<[^>]*>/g, '');
        }

        const chars = width === '80mm' ? 48 : 32;
        const lineStr = '-'.repeat(chars);

        // Helpers
        const center = (text: string) => {
            const stripped = strip(text);
            const spaces = Math.max(0, Math.floor((chars - stripped.length) / 2));
            return ' '.repeat(spaces) + text;
        };
        const right = (text: string) => {
            const stripped = strip(text);
            const spaces = Math.max(0, chars - stripped.length);
            return ' '.repeat(spaces) + text;
        };
        const justify = (left: string, rightVal: string) => {
            const strippedLeft = strip(left);
            const strippedRight = strip(rightVal);
            const pad = Math.max(0, chars - strippedLeft.length - strippedRight.length);
            return left + ' '.repeat(pad) + rightVal;
        };
        // Legacy kv helper mapped to justify
        const kv = justify;

        let output = template;

        // 1. Handle special placeholders first

        // {{line}}
        output = output.replace(/{{line}}/g, lineStr);

        // {{items}}
        if (output.includes('{{items}}') && Array.isArray(data.items)) {
            let itemsTxt = '';
            data.items.forEach((item: any) => {
                // If it's a kitchen ticket, we typically just show Qty x Name, maybe notes
                // If payment, Qty x Price ... Subtotal
                if (data.is_ticket) {
                    itemsTxt += `${item.quantity} x ${item.product_name || item.product?.name}\n`;
                    if (item.notes) itemsTxt += `   (Catatan: ${item.notes})\n`;
                } else {
                    itemsTxt += `${item.product_name || item.product?.name}\n`;
                    itemsTxt += kv(`${item.quantity} x ${(item.price || 0).toLocaleString('id-ID')}`, (item.subtotal || 0).toLocaleString('id-ID')) + '\n';
                }
            });
            output = output.replace('{{items}}', itemsTxt.trimEnd());
        }

        // 2. Simple replacements (Variables)
        const replacements: Record<string, string> = {
            '{{store_name}}': data.store_name || 'RESTO POS', // Removed center() here to allow custom alignment
            '{{store_address}}': data.store_address || '',     // Removed center()
            '{{store_phone}}': data.store_phone || '',         // Removed center()
            '{{footer}}': data.receipt_footer || 'Terima Kasih', // Removed center()
            '{{date}}': new Date().toLocaleString('id-ID'),
            '{{invoice_number}}': data.invoice_number || '-',
            '{{cashier_name}}': data.cashier_name || 'Admin',
            '{{table_number}}': data.table_number || '-',
            '{{subtotal}}': (data.subtotal || 0).toLocaleString('id-ID'),
            '{{tax}}': (data.tax || 0).toLocaleString('id-ID'),
            '{{tax_label}}': `Pajak (${data.tax_rate || 0}%):`,
            '{{discount}}': data.discount > 0 ? `(${data.discount.toLocaleString('id-ID')})` : '',
            '{{discount_label}}': data.discount > 0 ? 'Diskon:' : '',
            '{{total}}': (data.total || 0).toLocaleString('id-ID'),
            // Shift Report Specific
            '{{opened_at}}': data.opened_at ? new Date(data.opened_at).toLocaleString('id-ID') : '-',
            '{{closed_at}}': new Date().toLocaleString('id-ID'),
            '{{cash_in_hand}}': (data.cash_in_hand || 0).toLocaleString('id-ID'),
            '{{total_cash_sales}}': (data.total_cash_sales || 0).toLocaleString('id-ID'),
            '{{expected_cash}}': (data.expected_cash || 0).toLocaleString('id-ID'),
            '{{cash_out}}': (data.cash_out || 0).toLocaleString('id-ID'),
            '{{difference}}': (data.difference || 0) >= 0 ? `+${(data.difference || 0).toLocaleString('id-ID')}` : (data.difference || 0).toLocaleString('id-ID'),
        };

        for (const [key, val] of Object.entries(replacements)) {
            // Use global replace
            output = output.split(key).join(val);
        }

        // 2.5 Process Style Tags (Before Alignment)
        // {{b: Text}} -> Bold
        output = output.replace(/{{b:(.*?)}}/g, (_, content) => `${BOLD_ON}${content}${BOLD_OFF}`);

        // {{size:N: Text}} -> Size (1=Normal, 2=Big)
        output = output.replace(/{{size:(\d+):(.*?)(?:}})/g, (_, size, content) => {
            const s = size === '2' ? SIZE_2X : SIZE_NORMAL;
            return `${s}${content}${SIZE_NORMAL}`;
        });


        // 3. Process Alignment Tags (after variables are substituted)
        // {{c: Text}} -> Center
        // {{r: Text}} -> Right
        // {{lr: Left | Right}} -> Justify

        // Helper to process lines
        const processAlignment = (text: string) => {
            // We loop until no more tags found to handle nesting if any (though nesting alignment doesn't make sense)
            // But strict regex is better.

            // Left-Right: {{lr: A | B}}
            text = text.replace(/{{lr:(.*?)\|(.*?)}}/g, (_, left, rightVal) => justify(left.trim(), rightVal.trim()));

            // Center: {{c: Text}}
            text = text.replace(/{{c:(.*?)}}/g, (_, content) => center(content.trim()));

            // Right: {{r: Text}}
            text = text.replace(/{{r:(.*?)}}/g, (_, content) => right(content.trim()));

            return text;
        };

        output = processAlignment(output);

        // Clean up empty lines from conditional tags if value is empty/null (basic)
        // e.g. if discount is 0, discount_label is empty, we might leave a space.
        // For now, simple replacement is robust enough for our default template.

        // Remove empty placeholder lines if any remain (optional, be careful)

        return output;
    },

    generateReceiptText: (order: any, settings: any, width: '58mm' | '80mm' = '58mm', isTicket = false): string => {
        const template = isTicket
            ? (settings?.templates?.kitchen || DEFAULT_TEMPLATES.kitchen)
            : (settings?.templates?.payment || DEFAULT_TEMPLATES.payment);

        const data = {
            ...order,
            is_ticket: isTicket,
            store_name: settings?.store_name,
            store_address: settings?.store_address,
            store_phone: settings?.store_phone,
            receipt_footer: settings?.receipt_footer,
            tax_rate: settings?.tax_rate,
        };

        return printerService.renderTemplate(template, data, width);
    },

    generateShiftReportText: (shiftData: any, settings: any, width: '58mm' | '80mm' = '58mm'): string => {
        const template = settings?.templates?.shift_report || DEFAULT_TEMPLATES.shift_report;

        const data = {
            ...shiftData,
            store_name: settings?.store_name,
        };

        return printerService.renderTemplate(template, data, width);
    }
};

export { DEFAULT_TEMPLATES };
