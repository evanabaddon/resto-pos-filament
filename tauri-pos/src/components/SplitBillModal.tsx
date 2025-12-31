import React, { useState, useEffect } from 'react';
import type { CartItem } from '../types';

interface SplitBillModalProps {
    isOpen: boolean;
    cart: CartItem[];
    onClose: () => void;
    onSplit: (itemsToSplit: CartItem[]) => void;
}

const SplitBillModal: React.FC<SplitBillModalProps> = ({ isOpen, cart, onClose, onSplit }) => {
    const [selectedItems, setSelectedItems] = useState<{ index: number, quantity: number }[]>([]);

    useEffect(() => {
        if (isOpen) {
            setSelectedItems([]);
        }
    }, [isOpen]);

    if (!isOpen) return null;

    const handleToggleItem = (index: number, maxQty: number) => {
        const existing = selectedItems.find(i => i.index === index);
        if (existing) {
            // If exists, remove it (toggle off)
            setSelectedItems(selectedItems.filter(i => i.index !== index));
        } else {
            // Add with full quantity initially
            setSelectedItems([...selectedItems, { index, quantity: maxQty }]);
        }
    };

    const handleUpdateQty = (index: number, newQty: number, maxQty: number) => {
        if (newQty < 1) {
            // Remove if qty goes to 0
            setSelectedItems(selectedItems.filter(i => i.index !== index));
            return;
        }
        if (newQty > maxQty) return;

        setSelectedItems(selectedItems.map(i =>
            i.index === index ? { ...i, quantity: newQty } : i
        ));
    };

    const handleConfirm = () => {
        if (selectedItems.length === 0) return;

        // Construct the split cart
        const splitCart: CartItem[] = selectedItems.map(sel => {
            const originalItem = cart[sel.index];
            return {
                ...originalItem,
                quantity: sel.quantity,
                subtotal: originalItem.product.price * sel.quantity
            };
        });

        onSplit(splitCart);
    };

    // Calculate totals for preview
    const splitTotal = selectedItems.reduce((acc, sel) => {
        const item = cart[sel.index];
        return acc + (item.product.price * sel.quantity);
    }, 0);

    return (
        <div className="fixed inset-0 z-[70] flex items-center backdrop-blur-sm justify-center bg-black/50 backdrop-blur-sm">
            <div className="bg-white w-[500px] rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[80vh]">
                <div className="bg-primary-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h2 className="text-white text-lg font-bold">✂️ Pecah Tagihan (Split Bill)</h2>
                        <p className="text-primary-100 text-xs">Pilih item yang ingin dibayar terpisah.</p>
                    </div>
                    <button onClick={onClose} className="text-white/80 hover:text-white text-2xl">×</button>
                </div>

                <div className="flex-1 overflow-y-auto p-4 space-y-3">
                    {cart.map((item, index) => {
                        const isSelected = selectedItems.find(i => i.index === index);
                        return (
                            <div
                                key={index}
                                className={`border rounded-lg p-3 transition-colors cursor-pointer ${isSelected ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:bg-gray-50'}`}
                                onClick={() => !isSelected && handleToggleItem(index, item.quantity)}
                            >
                                <div className="flex justify-between items-start">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2">
                                            <div className={`w-5 h-5 rounded border flex items-center justify-center ${isSelected ? 'bg-primary-600 border-primary-600' : 'border-gray-300 bg-white'}`}>
                                                {isSelected && <span className="text-white text-xs">✓</span>}
                                            </div>
                                            <h4 className="font-semibold text-gray-800">{item.product.name}</h4>
                                        </div>
                                        <p className="text-xs text-gray-500 ml-7">
                                            @ {item.product.price.toLocaleString('id-ID')}
                                        </p>
                                    </div>

                                    {isSelected && (
                                        <div className="flex items-center gap-2 bg-white rounded-lg border border-gray-200 px-1 py-0.5" onClick={e => e.stopPropagation()}>
                                            <button
                                                onClick={() => handleUpdateQty(index, isSelected.quantity - 1, item.quantity)}
                                                className="w-6 h-6 flex items-center justify-center text-red-500 font-bold hover:bg-red-50 rounded"
                                            >
                                                -
                                            </button>
                                            <span className="text-sm font-bold w-6 text-center">{isSelected.quantity}</span>
                                            <button
                                                onClick={() => handleUpdateQty(index, isSelected.quantity + 1, item.quantity)}
                                                className={`w-6 h-6 flex items-center justify-center font-bold rounded ${isSelected.quantity >= item.quantity ? 'text-gray-300' : 'text-green-600 hover:bg-green-50'}`}
                                                disabled={isSelected.quantity >= item.quantity}
                                            >
                                                +
                                            </button>
                                        </div>
                                    )}
                                </div>
                                <div className="ml-7 mt-1 text-xs text-gray-500">
                                    Total tersedia: {item.quantity}
                                </div>
                            </div>
                        );
                    })}
                </div>

                <div className="p-4 border-t border-gray-200 bg-gray-50">
                    <div className="flex justify-between items-center mb-4">
                        <span className="text-gray-600 font-medium">Total Split</span>
                        <span className="text-xl font-bold text-primary-600">Rp {splitTotal.toLocaleString('id-ID')}</span>
                    </div>

                    <div className="flex gap-3">
                        <button onClick={onClose} className="flex-1 py-2.5 border border-gray-300 text-gray-700 font-bold rounded-lg hover:bg-white transition-colors">
                            Batal
                        </button>
                        <button
                            onClick={handleConfirm}
                            disabled={selectedItems.length === 0}
                            className="flex-1 py-2.5 bg-primary-600 text-white font-bold rounded-lg hover:bg-primary-700 disabled:bg-gray-300 disabled:cursor-not-allowed shadow-lg transition-all active:scale-95"
                        >
                            Proses Split ({selectedItems.length} Item)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SplitBillModal;
