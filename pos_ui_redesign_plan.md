# POS UI/UX Optimization Plan

## 1. Layout Refinement (`pos-layout.blade.php`)
- [ ] **PWA & Mobile Meta Tags**: Add `apple-mobile-web-app-capable`, `theme-color`, etc. to make it feel like a native app.
- [ ] **Modern Polish**: Update the Top Navbar to use `backdrop-blur` and consistent shadows/borders.
- [ ] **Performance**: Ensure fonts and assets are loaded efficiently (already using Vite, which is good).

## 2. Main POS Page (`pos.blade.php`) - Cart Section
- [ ] **Visual Consistency**: The Cart section needs to match the new "Violet/Glass" theme of the product grid.
- [ ] **Cart Items**: Improve the look of list items (currently likely basic). Add hover effects and better spacing.
- [ ] **Empty State**: Make the "Keranjang Kosong" state more visually appealing.
- [ ] **Mobile Experience**: Verify the bottom navigation for switching between "Menu" and "Cart" is intuitive and labeled clearly using icons.

## 3. Code Optimization
- [ ] **JavaScript**: Consolidated inline scripts into AlpineJS components where possible for better maintainability.
- [ ] **Livewire**: Ensure `wire:navigate` is used for links where appropriate (though POS is mostly SPA-like).

## 4. Proposed Changes
- **Header**: Gradient Text or better Logo placement.
- **Cart**: "Paper" look or "Glass" look? "Glass" is preferred for consistency.
