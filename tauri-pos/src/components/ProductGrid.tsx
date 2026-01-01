
import React from 'react';
import type { Product, Category } from '../types';

interface ProductGridProps {
    categories: Category[];
    selectedCategory: number | 'SEMUA';
    setSelectedCategory: (id: number | 'SEMUA') => void;
    filteredProducts: Product[];
    addToCart: (product: Product) => void;
}

export const ProductGrid: React.FC<ProductGridProps> = React.memo(({
    categories,
    selectedCategory,
    setSelectedCategory,
    filteredProducts,
    addToCart
}) => {
    // Get API base URL for images
    const getImageUrl = (imagePath: string | undefined) => {
        if (!imagePath) return null;

        // If already a full URL, return as is
        if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
            return imagePath;
        }

        // Get base URL from localStorage or use default
        const apiUrl = localStorage.getItem('pos_api_url') || 'http://localhost:8000/api';
        const baseUrl = apiUrl.replace('/api', ''); // Remove /api to get server base

        // Ensure path starts with /
        const path = imagePath.startsWith('/') ? imagePath : `/${imagePath}`;

        return `${baseUrl}${path}`;
    };

    return (
        <div className="flex flex-col h-full min-w-0 bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
            {/* Categories */}
            <div className="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-3 flex gap-2 overflow-x-auto whitespace-nowrap scrollbar-hide transition-colors duration-200 flex-shrink-0">
                <button
                    className={`px-4 py-1.5 rounded-full text-sm font-medium transition-all ${selectedCategory === 'SEMUA'
                        ? 'bg-primary-600 text-white shadow-md dark:bg-primary-500'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                        }`}
                    onClick={() => setSelectedCategory('SEMUA')}
                >
                    SEMUA
                </button>
                {categories.map(category => (
                    <button
                        key={category.id}
                        className={`px-4 py-1.5 rounded-full text-sm font-medium transition-all ${selectedCategory === category.id
                            ? 'bg-primary-600 text-white shadow-md dark:bg-primary-500'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                            }`}
                        onClick={() => setSelectedCategory(category.id)}
                    >
                        {category.name}
                    </button>
                ))}
            </div>

            {/* Products Grid */}
            <div className="flex-1 overflow-y-auto p-6">
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 pb-20">
                    {filteredProducts.map(product => {
                        const imageUrl = getImageUrl(product.image);

                        return (
                            <div
                                key={product.id}
                                onClick={() => addToCart(product)}
                                className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden cursor-pointer hover:shadow-md hover:-translate-y-1 transition-all group select-none"
                            >
                                <div className="h-32 w-full bg-gray-200 dark:bg-gray-700 relative overflow-hidden">
                                    {imageUrl ? (
                                        <img
                                            src={imageUrl}
                                            alt={product.name}
                                            className="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                            onError={(e) => {
                                                // Fallback to placeholder if image fails to load
                                                e.currentTarget.style.display = 'none';
                                                e.currentTarget.nextElementSibling?.classList.remove('hidden');
                                            }}
                                        />
                                    ) : null}
                                    <div className={`w-full h-full flex items-center justify-center text-4xl bg-gray-100 dark:bg-gray-700 text-gray-300 dark:text-gray-600 ${imageUrl ? 'hidden' : ''}`}>
                                        🍽️
                                    </div>
                                    {product.stock !== undefined && (
                                        <div className="absolute top-2 right-2 flex flex-col items-end gap-1">
                                            <div className={`text-white text-xs px-2 py-0.5 rounded backdrop-blur-sm ${(product.stock || 0) <= 0 ? 'bg-red-600/90' : 'bg-black/60 dark:bg-black/40'
                                                }`}>
                                                Stok: {product.stock}
                                            </div>
                                            {(product.prepared_stock || 0) > 0 && (
                                                <div className="bg-blue-600/80 text-white text-[10px] px-2 py-0.5 rounded backdrop-blur-sm">
                                                    Siap: {product.prepared_stock}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                                <div className="p-3">
                                    <h3 className="font-semibold text-gray-800 dark:text-gray-100 text-sm line-clamp-2 min-h-[2.5rem]">{product.name}</h3>
                                    <p className="text-primary-600 dark:text-primary-400 font-bold mt-1">Rp {product.price.toLocaleString('id-ID')}</p>
                                </div>
                            </div>
                        );
                    })}

                    {filteredProducts.length === 0 && (
                        <div className="col-span-full flex flex-col items-center justify-center py-20 text-gray-400 dark:text-gray-500">
                            <span className="text-6xl mb-4">🔍</span>
                            <p className="text-lg font-medium">Menu tidak ditemukan</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}); // End of memo
