import { useState } from 'react';
import type { Product, CartItem } from '../types';

export const useCart = (settings: any, showNotification: (msg: string, type: 'success' | 'error' | 'info') => void) => {
    const [cart, setCart] = useState<CartItem[]>([]);
    const [discount, setDiscount] = useState<number>(0);

    const addToCart = (product: Product) => {
        // Stock Check
        if (product.stock !== undefined && product.stock <= 0) {
            showNotification(`Stok habis untuk ${product.name}!`, 'error');
            return;
        }

        const existingItem = cart.find(item => item.product.id === product.id);

        if (existingItem) {
            // Check if adding 1 exceeds stock
            if (product.stock !== undefined && existingItem.quantity + 1 > product.stock) {
                showNotification(`Stok tidak cukup! Sisa: ${product.stock}`, 'error');
                return;
            }

            setCart(cart.map(item =>
                item.product.id === product.id
                    ? { ...item, quantity: item.quantity + 1, subtotal: (item.quantity + 1) * product.price }
                    : item
            ));
        } else {
            setCart([...cart, {
                product,
                quantity: 1,
                subtotal: Number(product.price),
            }]);
        }
    };

    const removeFromCart = (productId: number) => {
        setCart(cart.filter(item => item.product.id !== productId));
    };

    const updateQuantity = (productId: number, quantity: number) => {
        if (quantity <= 0) {
            removeFromCart(productId);
            return;
        }

        const item = cart.find(i => i.product.id === productId);
        if (item && item.product.stock !== undefined) {
            if (quantity > item.product.stock) {
                showNotification(`Stok maksimum tercapai! Sisa: ${item.product.stock}`, 'error');
                return;
            }
        }

        setCart(cart.map(item =>
            item.product.id === productId
                ? { ...item, quantity, subtotal: quantity * item.product.price }
                : item
        ));
    };

    const updateItemNotes = (productId: number, notes: string) => {
        setCart(cart.map(item =>
            item.product.id === productId
                ? { ...item, notes }
                : item
        ));
    };

    const calculateTotal = (targetCart: CartItem[] | null = null): number => {
        const cartToUse = targetCart || cart;
        const subtotal = cartToUse.reduce((sum, item) => sum + Number(item.subtotal), 0);
        const taxRate = settings?.tax_rate || 0;
        const tax = (subtotal * taxRate) / 100;
        // Discount logic: If splitting, assume no discount on split part or apply fully? 
        // For consistency with handlePaymentConfirm, we disable discount on split parts for now.
        const effectiveDiscount = targetCart ? 0 : (Number(discount) || 0);

        return subtotal + tax - effectiveDiscount;
    };

    const clearCart = () => {
        setCart([]);
        setDiscount(0);
    };

    return {
        cart,
        setCart,
        addToCart,
        removeFromCart,
        updateQuantity,
        updateItemNotes,
        calculateTotal,
        clearCart,
        discount,
        setDiscount
    };
};
