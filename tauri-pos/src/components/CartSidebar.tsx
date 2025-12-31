
import React from 'react';
import type { CartItem } from '../types';
import { Receipt } from './Receipt';

interface CartSidebarProps {
    cart: CartItem[];
    // Order info
    orderNumber: string;
    customerName: string;
    setCustomerName: (name: string) => void;
    orderType: 'Dine In' | 'Take Away';
    setOrderType: (type: 'Dine In' | 'Take Away') => void;
    tableNumber: string;
    setTableNumber: (num: string) => void;

    // Actions
    updateQuantity: (productId: number, quantity: number) => void;
    updateItemNotes: (productId: number, notes: string) => void;

    // Calculation
    discount: number;
    setDiscount: (amount: number) => void;
    subtotal: number;
    tax: number;
    total: number;
    taxRate: number;

    // Checkout
    onCheckout: () => void;
    onSaveDraft: () => void;
    onClearCart: () => void;

    // Printing
    printOrder: any;
    printerSettings: any;
    settings: any;
    onSplitBill: () => void;
}

export const CartSidebar: React.FC<CartSidebarProps> = ({
    cart,
    orderNumber,
    customerName,
    setCustomerName,
    orderType,
    setOrderType,
    tableNumber,
    setTableNumber,
    updateQuantity,
    updateItemNotes,
    discount,
    setDiscount,
    subtotal,
    tax,
    total,
    taxRate,
    onCheckout,
    onSaveDraft,
    onClearCart,
    printOrder,
    printerSettings,
    settings,
    onSplitBill
}) => {
    return (
        <div className="w-[400px] bg-white border-l border-gray-200 flex flex-col h-full shadow-2xl z-20">
            {/* Cart Header */}
            <div className="p-5 border-b border-gray-100 bg-white">
                <div className="flex justify-between items-center mb-4">
                    <h2 className="text-xl font-bold text-gray-800">Keranjang</h2>
                    <span className="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded">Order #{orderNumber}</span>
                </div>

                {/* Customer Info Inputs */}
                <div className="space-y-3">
                    <input
                        type="text"
                        placeholder="Nama Pelanggan"
                        className="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all hover:bg-white focus:bg-white"
                        value={customerName}
                        onChange={(e) => setCustomerName(e.target.value)}
                    />

                    <div className="flex bg-gray-100 p-1 rounded-lg">
                        <button
                            className={`flex-1 py-1.5 text-sm font-medium rounded-md transition-all ${orderType === 'Dine In' ? 'bg-white text-primary-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
                            onClick={() => setOrderType('Dine In')}
                        >
                            Dine In
                        </button>
                        <button
                            className={`flex-1 py-1.5 text-sm font-medium rounded-md transition-all ${orderType === 'Take Away' ? 'bg-white text-primary-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
                            onClick={() => setOrderType('Take Away')}
                        >
                            Take Away
                        </button>
                    </div>

                    {orderType === 'Dine In' && (
                        <input
                            type="text"
                            placeholder="Nomor Meja"
                            className="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all hover:bg-white focus:bg-white"
                            value={tableNumber}
                            onChange={(e) => setTableNumber(e.target.value)}
                        />
                    )}
                </div>
            </div>

            {/* Cart Items List */}
            <div className="flex-1 overflow-y-auto p-4 space-y-3">
                {cart.length === 0 ? (
                    <div className="h-full flex flex-col items-center justify-center text-gray-400 opacity-50">
                        <span className="text-6xl mb-2">🛒</span>
                        <p>Keranjang kosong</p>
                    </div>
                ) : (
                    cart.map((item, index) => (
                        <div key={index} className="flex gap-3 items-start group hover:bg-gray-50 p-2 -mx-2 rounded-lg transition-colors">
                            {/* Qty Control */}
                            <div className="flex flex-col items-center border border-gray-200 rounded overflow-hidden shrink-0 bg-white">
                                <button onClick={() => updateQuantity(item.product.id, item.quantity + 1)} className="w-8 h-7 bg-gray-50 hover:bg-primary-50 hover:text-primary-600 text-green-600 font-bold transition-colors">+</button>
                                <span className="w-8 h-7 flex items-center justify-center text-sm font-medium bg-white border-y border-gray-100">{item.quantity}</span>
                                <button onClick={() => updateQuantity(item.product.id, item.quantity - 1)} className="w-8 h-7 bg-gray-50 hover:bg-red-50 hover:text-red-500 text-red-500 font-bold transition-colors">-</button>
                            </div>

                            <div className="flex-1 min-w-0 pt-1">
                                <div className="flex justify-between items-start">
                                    <h4 className="text-sm font-semibold text-gray-800 line-clamp-2">{item.product.name}</h4>
                                    <span className="text-sm font-bold text-gray-700 whitespace-nowrap ml-2">
                                        {item.subtotal.toLocaleString('id-ID')}
                                    </span>
                                </div>
                                <div className="text-xs text-gray-500 mt-0.5">@ {item.product.price.toLocaleString('id-ID')}</div>

                                <input
                                    type="text"
                                    placeholder="Catatan..."
                                    className="w-full mt-2 text-xs border-b border-gray-200 focus:border-primary-500 focus:outline-none py-1 bg-transparent text-gray-600 placeholder-gray-400 transition-colors"
                                    value={item.notes || ''}
                                    onChange={(e) => updateItemNotes(item.product.id, e.target.value)}
                                />
                            </div>
                        </div>
                    ))
                )}
            </div>

            {/* Cart Footer */}
            <div className="p-5 border-t border-gray-200 bg-gray-50">
                <div className="space-y-2 mb-4">
                    <div className="flex justify-between text-sm text-gray-600">
                        <span>Subtotal</span>
                        <span>Rp {subtotal.toLocaleString('id-ID')}</span>
                    </div>
                    <div className="flex justify-between text-sm text-gray-600 items-center">
                        <span>Diskon</span>
                        <div className="flex items-center gap-1 w-24">
                            <span className="text-xs">Rp</span>
                            <input
                                type="number"
                                value={discount}
                                onChange={e => setDiscount(Number(e.target.value))}
                                className="w-full bg-white border border-gray-300 rounded px-1 text-right text-sm py-0.5 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 outline-none"
                            />
                        </div>
                    </div>
                    <div className="flex justify-between text-sm text-gray-600">
                        <span>Pajak ({taxRate}%)</span>
                        <span>Rp {tax.toLocaleString('id-ID')}</span>
                    </div>
                    <div className="flex justify-between text-xl font-bold text-gray-900 pt-2 border-t border-gray-200">
                        <span>Total</span>
                        <span>Rp {total.toLocaleString('id-ID')}</span>
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-2 mb-2">
                    <button
                        onClick={onSaveDraft}
                        disabled={cart.length === 0}
                        className="bg-green-600 hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-xl shadow-lg hover:shadow-xl transition-all active:scale-95 flex items-center justify-center gap-2"
                    >
                        <span>💾</span> Simpan
                    </button>
                    <button
                        onClick={onSplitBill}
                        disabled={cart.length === 0}
                        className="bg-orange-500 hover:bg-orange-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-xl shadow-lg hover:shadow-xl transition-all active:scale-95 flex items-center justify-center gap-2"
                    >
                        <span>✂️</span> Split
                    </button>
                </div>

                <div className="grid grid-cols-2 gap-2">
                    <button
                        onClick={onCheckout}
                        disabled={cart.length === 0}
                        className="w-full bg-primary-600 hover:bg-primary-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-xl shadow-lg hover:shadow-xl transition-all active:scale-95 flex items-center justify-center gap-2"
                    >
                        <span>💳</span> Bayar
                    </button>
                    <button onClick={onClearCart} className="py-3.5 text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl font-bold transition-colors active:scale-95 text-sm">
                        Batal
                    </button>
                </div>
            </div>
            {/* Receipt Print Area (Always Configured) */}
            <div id="receipt-print-area" className="hidden">
                <Receipt order={printOrder} settings={settings} paperWidth={printerSettings.cashierPaperWidth} />
            </div>
        </div>
    );
};
