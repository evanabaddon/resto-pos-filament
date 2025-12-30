import React from 'react';

interface ReceiptProps {
    order: any;
    settings?: any; // To pass store name, address, etc later
    paperWidth?: '58mm' | '80mm';
}

export const Receipt = React.forwardRef<HTMLDivElement, ReceiptProps>(({ order, settings, paperWidth = '58mm' }, ref) => {
    if (!order) return null;

    const total = order.total || 0;
    const date = new Date(order.created_at).toLocaleString('id-ID');

    return (
        <div ref={ref} className="hidden print:block p-2 text-xs font-mono leading-tight" style={{ width: paperWidth }}>
            {/* Header */}
            <div className="text-center mb-4">
                <h2 className="text-xl font-bold mb-1">{settings?.store_name || 'RESTO POS'}</h2>
                {settings?.store_address && <p>{settings.store_address}</p>}
                {settings?.store_phone && <p>Telp: {settings.store_phone}</p>}
            </div>

            {/* Meta */}
            <div className="border-b border-dashed border-black pb-2 mb-2">
                <div className="flex justify-between">
                    <span>Tgl:</span>
                    <span>{date}</span>
                </div>
                <div className="flex justify-between">
                    <span>Kasir:</span>
                    <span>{order.cashier_name || 'Admin'}</span>
                </div>
                <div className="flex justify-between">
                    <span>No:</span>
                    <span>{order.invoice_number || 'OFFLINE'}</span>
                </div>
                {order.table_number && (
                    <div className="flex justify-between font-bold">
                        <span>Meja:</span>
                        <span>{order.table_number}</span>
                    </div>
                )}
            </div>

            {/* Items */}
            <div className="border-b border-dashed border-black pb-2 mb-2">
                {order.items.map((item: any, i: number) => (
                    <div key={i} className="mb-1">
                        <div className="font-bold">{item.product_name || item.name}</div>
                        <div className="flex justify-between">
                            <span>{item.quantity} x {Number(item.price).toLocaleString()}</span>
                            <span>{Number(item.subtotal).toLocaleString()}</span>
                        </div>
                    </div>
                ))}
            </div>

            {/* Totals */}
            <div className="flex justify-between font-bold text-sm mb-1">
                <span>TOTAL:</span>
                <span>Rp {total.toLocaleString()}</span>
            </div>

            {(order.tax > 0) && (
                <div className="flex justify-between text-xs">
                    <span>Pajak:</span>
                    <span>Rp {order.tax.toLocaleString()}</span>
                </div>
            )}
            {(order.discount > 0) && (
                <div className="flex justify-between text-xs">
                    <span>Diskon:</span>
                    <span>(Rp {order.discount.toLocaleString()})</span>
                </div>
            )}

            {/* Footer */}
            <div className="text-center mt-4 border-t border-dashed border-black pt-2">
                <p>{settings?.receipt_footer || 'Terima Kasih'}</p>
                <p className="text-[10px] mt-1 text-gray-500">Powered by SRLY-POS</p>
            </div>
        </div>
    );
});

Receipt.displayName = 'Receipt';
