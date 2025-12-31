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
        <div className="fixed inset-0 z-[70] flex items-center justify-center bg-gray-900/40 dark:bg-black/60 backdrop-blur-sm animate-fade-in">
            <div className="bg-white dark:bg-gray-800 w-[500px] rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[80vh] animate-scale-up border border-gray-100 dark:border-gray-700">
                <div className="bg-primary-600 dark:bg-primary-700 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h2 className="text-white text-lg font-bold">✂️ Pecah Tagihan (Split Bill)</h2>
                        <p className="text-primary-100 text-xs">Pilih item yang ingin dibayar terpisah.</p>
                    </div>
                    <button onClick={onClose} className="text-white/80 hover:text-white text-2xl">×</button>
                </div>

                <div className="flex-1 overflow-y-auto p-4 space-y-3 bg-white dark:bg-gray-800">
                    {cart.map((item, index) => {
                        const isSelected = selectedItems.find(i => i.index === index);
                        return (
                            <div
                                key={index}
                                className={`border rounded-lg p-3 transition-colors cursor-pointer ${isSelected
                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 dark:border-primary-600'
                                    : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:text-gray-300'
                                    }`}
                                onClick={() => !isSelected && handleToggleItem(index, item.quantity)}
                            >
                                <div className="flex justify-between items-start">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2">
                                            <div className={`w-5 h-5 rounded border flex items-center justify-center ${isSelected
                                                ? 'bg-primary-600 border-primary-600 dark:bg-primary-500 dark:border-primary-500'
                                                : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700'
                                                }`}>
                                                {isSelected && <span className="text-white text-xs">✓</span>}
                                            </div>
                                            <h4 className="font-semibold text-gray-800 dark:text-white">{item.product.name}</h4>
                                        </div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 ml-7">
                                            @ {item.product.price.toLocaleString('id-ID')}
                                        </p>
                                    </div>

                                    {isSelected && (
                                        <div className="flex items-center gap-2 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 px-1 py-0.5" onClick={e => e.stopPropagation()}>
                                            <button
                                                onClick={() => handleUpdateQty(index, isSelected.quantity - 1, item.quantity)}
                                                className="w-6 h-6 flex items-center justify-center text-red-500 dark:text-red-400 font-bold hover:bg-red-50 dark:hover:bg-red-900/30 rounded"
                                            >
                                                -
                                            </button>
                                            <span className="text-sm font-bold w-6 text-center text-gray-900 dark:text-white">{isSelected.quantity}</span>
                                            <button
                                                onClick={() => handleUpdateQty(index, isSelected.quantity + 1, item.quantity)}
                                                className={`w-6 h-6 flex items-center justify-center font-bold rounded ${isSelected.quantity >= item.quantity
                                                    ? 'text-gray-300 dark:text-gray-500'
                                                    : 'text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30'
                                                    }`}
                                                disabled={isSelected.quantity >= item.quantity}
                                            >
                                                +
                                            </button>
                                        </div>
                                    )}
                                </div>
                                <div className="ml-7 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Total tersedia: {item.quantity}
                                </div>
                            </div>
                        );
                    })}
                </div>

                <div className="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <div className="flex justify-between items-center mb-4">
                        <span className="text-gray-600 dark:text-gray-300 font-medium">Total Split</span>
                        <span className="text-xl font-bold text-primary-600 dark:text-primary-400">Rp {splitTotal.toLocaleString('id-ID')}</span>
                    </div>

                    <div className="flex gap-3">
                        <button onClick={onClose} className="flex-1 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-bold rounded-lg hover:bg-white dark:hover:bg-gray-800 transition-colors">
                            Batal
                        </button>
                        <button
                            onClick={handleConfirm}
                            disabled={selectedItems.length === 0}
                            className="flex-1 py-2.5 bg-primary-600 text-white font-bold rounded-lg hover:bg-primary-700 disabled:bg-gray-300 disabled:dark:bg-gray-700 disabled:cursor-not-allowed shadow-lg transition-all active:scale-95"
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
