
import React, { useState, useEffect, memo } from 'react';
import type { Product, Category } from '../types';
import { imageCacheService } from '../services/imageCache';
import { convertFileSrc } from '@tauri-apps/api/core';

interface ProductGridProps {
    categories: Category[];
    selectedCategory: number | 'SEMUA';
    setSelectedCategory: (id: number | 'SEMUA') => void;
    filteredProducts: Product[];
    addToCart: (product: Product) => void;
}

const ProductCard = memo(({ product, addToCart }: { product: Product; addToCart: (p: Product) => void }) => {
    const [imageUrl, setImageUrl] = useState<string | null>(null);

    useEffect(() => {
        let isMounted = true;
        const loadImage = async () => {
            if (!product.image) return;

            const filename = product.image.split('/').pop();
            if (!filename) return;

            try {
                // Try local cache
                const localPath = await imageCacheService.getLocalImagePath(filename);
                if (localPath && isMounted) {
                    setImageUrl(convertFileSrc(localPath));
                    return;
                }
            } catch (e) {
                console.error("Cache check failed", e);
            }

            // Fallback to server
            if (isMounted) {
                const apiUrl = localStorage.getItem('pos_api_url') || 'http://localhost:8000/api';
                const baseUrl = apiUrl.replace('/api', '');
                let imagePath = product.image;
                if (!imagePath.startsWith('storage/')) {
                    imagePath = `storage/${imagePath}`;
                }
                setImageUrl(`${baseUrl}/${imagePath}`);
            }
        };

        loadImage();
        return () => { isMounted = false; };
    }, [product.image, product.id]);

    return (
        <div
            onClick={() => addToCart(product)}
            className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden cursor-pointer hover:shadow-md hover:-translate-y-1 transition-all group select-none flex flex-col h-full"
        >
            <div className="h-32 w-full bg-gray-200 dark:bg-gray-700 relative overflow-hidden flex-shrink-0">
                {imageUrl ? (
                    <img
                        src={imageUrl}
                        alt={product.name}
                        loading="lazy"
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform"
                        onError={(e) => {
                            e.currentTarget.style.display = 'none';
                            const placeholder = e.currentTarget.nextElementSibling as HTMLElement;
                            if (placeholder) placeholder.classList.remove('hidden');
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
            <div className="p-3 flex flex-col flex-grow">
                <h3 className="font-semibold text-gray-800 dark:text-gray-100 text-sm line-clamp-2 min-h-[2.5rem] mb-auto">{product.name}</h3>
                <p className="text-primary-600 dark:text-primary-400 font-bold mt-1">Rp {product.price.toLocaleString('id-ID')}</p>
            </div>
        </div>
    );
});

export const ProductGrid: React.FC<ProductGridProps> = memo(({
    categories,
    selectedCategory,
    setSelectedCategory,
    filteredProducts,
    addToCart
}) => {
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
                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-12 gap-4 pb-20">
                    {filteredProducts.map(product => (
                        <ProductCard key={product.id} product={product} addToCart={addToCart} />
                    ))}

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
});
