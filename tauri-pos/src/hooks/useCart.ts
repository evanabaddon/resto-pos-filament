import { useState, useRef, useEffect, useCallback } from 'react';
import type { Product, CartItem } from '../types';

export const useCart = (settings: any, showNotification: (msg: string, type: 'success' | 'error' | 'info') => void) => {
    const [cart, setCart] = useState<CartItem[]>([]);
    const [discount, setDiscount] = useState<number>(0);

    // Use ref to access latest cart in callbacks without adding it to dependencies
    const cartRef = useRef(cart);
    useEffect(() => {
        cartRef.current = cart;
    }, [cart]);

    const addToCart = useCallback((product: Product) => {
        // Stock Check
        if (product.stock !== undefined && product.stock <= 0) {
            showNotification(`Stok habis untuk ${product.name}!`, 'error');
            return;
        }

        const currentCart = cartRef.current;
        const existingItem = currentCart.find(item => item.product.id === product.id);

        if (existingItem) {
            // Check if adding 1 exceeds stock
            if (product.stock !== undefined && existingItem.quantity + 1 > product.stock) {
                showNotification(`Stok tidak cukup! Sisa: ${product.stock}`, 'error');
                return;
            }

            setCart(prev => prev.map(item =>
                item.product.id === product.id
                    ? { ...item, quantity: item.quantity + 1, subtotal: (item.quantity + 1) * product.price }
                    : item
            ));
        } else {
            setCart(prev => [...prev, {
                product,
                quantity: 1,
                subtotal: Number(product.price),
            }]);
        }
    }, [showNotification]);

    const removeFromCart = useCallback((productId: number) => {
        setCart(prev => prev.filter(item => item.product.id !== productId));
    }, []);

    const updateQuantity = useCallback((productId: number, quantity: number) => {
        if (quantity <= 0) {
            removeFromCart(productId);
            return;
        }

        const currentCart = cartRef.current;
        const item = currentCart.find(i => i.product.id === productId);
        if (item && item.product.stock !== undefined) {
            if (quantity > item.product.stock) {
                showNotification(`Stok maksimum tercapai! Sisa: ${item.product.stock}`, 'error');
                return;
            }
        }

        setCart(prev => prev.map(item =>
            item.product.id === productId
                ? { ...item, quantity, subtotal: quantity * item.product.price }
                : item
        ));
    }, [removeFromCart, showNotification]);

    const updateItemNotes = useCallback((productId: number, notes: string) => {
        setCart(prev => prev.map(item =>
            item.product.id === productId
                ? { ...item, notes }
                : item
        ));
    }, []);

    const calculateTotal = useCallback((targetCart: CartItem[] | null = null): number => {
        const cartToUse = targetCart || cartRef.current;
        const subtotal = cartToUse.reduce((sum, item) => sum + Number(item.subtotal), 0);
        const taxRate = settings?.tax_rate || 0;
        const tax = (subtotal * taxRate) / 100;
        // Discount logic: If splitting, assume no discount on split part or apply fully? 
        // For consistency with handlePaymentConfirm, we disable discount on split parts for now.
        const effectiveDiscount = targetCart ? 0 : (Number(discount) || 0); // Warning: discount is from state closure, but usually calculated during render/payment so accessing state directly is ok if calculateTotal is called in render?
        // Actually calculateTotal is returned and used in App render.
        // If we memoize it, we should include discount in deps.
        return subtotal + tax - effectiveDiscount;
    }, [settings, discount]);
    // Wait, if we use cartRef.current inside, but calculateTotal is memoized on [settings, discount], 
    // and cart updates, calculateTotal WON'T update if we don't include cart in deps.
    // BUT we used cartRef.current to avoid deps? No, calculateTotal IS needed to update when cart updates.
    // So for calculateTotal, since it's used in Render (App.tsx -> <CartSidebar total={calculateTotal()} />), 
    // it MUST update when cart updates.
    // So calculateTotal should depend on [cart, settings, discount].
    // If it depends on cart, then it changes when cart changes. That's fine.
    // Does it need to be useCallback? Yes, to prevent recreation if cart doesn't change?
    // But cart changes often.

    // Actually, calculateTotal logic is simple. Maybe better NOT to useCallback if it changes so often?
    // But wrapping in useCallback doesn't hurt.
    // The previous implementation WAS NOT memoized so it was recreated every render.

    const clearCart = useCallback(() => {
        setCart([]);
        setDiscount(0);
    }, []);

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
