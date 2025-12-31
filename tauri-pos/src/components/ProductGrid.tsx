
import React from 'react';
import type { Product, Category } from '../types';

interface ProductGridProps {
    categories: Category[];
    selectedCategory: number | 'SEMUA';
    setSelectedCategory: (id: number | 'SEMUA') => void;
    filteredProducts: Product[];
    addToCart: (product: Product) => void;
}

export const ProductGrid: React.FC<ProductGridProps> = ({
    categories,
    selectedCategory,
    setSelectedCategory,
    filteredProducts,
    addToCart
}) => {
    return (
        <div className="flex-1 flex flex-col min-w-0 bg-gray-50">
            {/* Categories */}
            <div className="bg-white border-b border-gray-200 px-6 py-3 flex gap-2 overflow-x-auto whitespace-nowrap scrollbar-hide sticky top-0 z-10">
                <button
                    className={`px-4 py-1.5 rounded-full text-sm font-medium transition-colors ${selectedCategory === 'SEMUA'
                        ? 'bg-primary-600 text-white shadow-md'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        }`}
                    onClick={() => setSelectedCategory('SEMUA')}
                >
                    Semua Menu
                </button>
                {categories.map(category => (
                    <button
                        key={category.id}
                        className={`px-4 py-1.5 rounded-full text-sm font-medium transition-colors ${selectedCategory === category.id
                            ? 'bg-primary-600 text-white shadow-md'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
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
                    {filteredProducts.map(product => (
                        <div
                            key={product.id}
                            onClick={() => addToCart(product)}
                            className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden cursor-pointer hover:shadow-md hover:-translate-y-1 transition-all group select-none"
                        >
                            <div className="h-32 w-full bg-gray-200 relative overflow-hidden">
                                {product.image ? (
                                    <img src={product.image} alt={product.name} className="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center text-4xl bg-gray-100 text-gray-300">
                                        🍽️
                                    </div>
                                )}
                                {product.stock !== undefined && (
                                    <div className="absolute top-2 right-2 flex flex-col items-end gap-1">
                                        <div className={`text-white text-xs px-2 py-0.5 rounded backdrop-blur-sm ${(product.stock || 0) <= 0 ? 'bg-red-600/90' : 'bg-black/60'
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
                                <h3 className="font-semibold text-gray-800 text-sm line-clamp-2 min-h-[2.5rem]">{product.name}</h3>
                                <p className="text-primary-600 font-bold mt-1">Rp {product.price.toLocaleString('id-ID')}</p>
                            </div>
                        </div>
                    ))}

                    {filteredProducts.length === 0 && (
                        <div className="col-span-full flex flex-col items-center justify-center py-20 text-gray-400">
                            <span className="text-6xl mb-4">🔍</span>
                            <p className="text-lg font-medium">Menu tidak ditemukan</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};
