import { useState } from 'react';
import { Category } from '@/types/product';

interface FilterSidebarProps {
    categories: Category[];
    selectedCategory?: number;
    minPrice?: number;
    maxPrice?: number;
    selectedRating?: number;
    onCategoryChange: (categoryId?: number) => void;
    onPriceChange: (min?: number, max?: number) => void;
    onRatingChange: (rating?: number) => void;
    onClearFilters: () => void;
}

export default function FilterSidebar({
    categories,
    selectedCategory,
    minPrice,
    maxPrice,
    selectedRating,
    onCategoryChange,
    onPriceChange,
    onRatingChange,
    onClearFilters,
}: FilterSidebarProps) {
    const [priceMin, setPriceMin] = useState(minPrice?.toString() || '');
    const [priceMax, setPriceMax] = useState(maxPrice?.toString() || '');

    const handlePriceSubmit = () => {
        const min = priceMin ? parseFloat(priceMin) : undefined;
        const max = priceMax ? parseFloat(priceMax) : undefined;
        onPriceChange(min, max);
    };

    const ratings = [5, 4, 3, 2, 1];

    return (
        <div className="space-y-6">
            {/* Clear Filters */}
            <div className="flex items-center justify-between">
                <h2 className="text-lg font-serif font-semibold text-gray-900">Filters</h2>
                <button
                    onClick={onClearFilters}
                    className="text-sm text-primary-olive hover:text-primary-dark transition-colors"
                >
                    Clear All
                </button>
            </div>

            {/* Categories */}
            <div className="border-b border-gray-200 pb-6">
                <h3 className="text-sm font-semibold text-gray-900 mb-4">Categories</h3>
                <div className="space-y-2">
                    <button
                        onClick={() => onCategoryChange(undefined)}
                        className={`block w-full text-left px-3 py-2 rounded-md transition-colors ${
                            !selectedCategory
                                ? 'bg-primary-olive text-white'
                                : 'text-gray-700 hover:bg-gray-100'
                        }`}
                    >
                        All Categories
                    </button>
                    {categories.map((category) => (
                        <button
                            key={category.id}
                            onClick={() => onCategoryChange(category.id)}
                            className={`block w-full text-left px-3 py-2 rounded-md transition-colors ${
                                selectedCategory === category.id
                                    ? 'bg-primary-olive text-white'
                                    : 'text-gray-700 hover:bg-gray-100'
                            }`}
                        >
                            <div className="flex items-center justify-between">
                                <span>{category.name}</span>
                                <span className="text-sm opacity-75">({category.products_count})</span>
                            </div>
                        </button>
                    ))}
                </div>
            </div>

            {/* Price Range */}
            <div className="border-b border-gray-200 pb-6">
                <h3 className="text-sm font-semibold text-gray-900 mb-4">Price Range</h3>
                <div className="space-y-3">
                    <div className="flex items-center gap-2">
                        <input
                            type="number"
                            placeholder="Min"
                            value={priceMin}
                            onChange={(e) => setPriceMin(e.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent"
                            min="0"
                        />
                        <span className="text-gray-500">-</span>
                        <input
                            type="number"
                            placeholder="Max"
                            value={priceMax}
                            onChange={(e) => setPriceMax(e.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent"
                            min="0"
                        />
                    </div>
                    <button
                        onClick={handlePriceSubmit}
                        className="w-full px-4 py-2 bg-primary-olive text-white rounded-md hover:bg-primary-dark transition-colors"
                    >
                        Apply
                    </button>
                </div>
            </div>

            {/* Rating */}
            <div className="border-b border-gray-200 pb-6">
                <h3 className="text-sm font-semibold text-gray-900 mb-4">Rating</h3>
                <div className="space-y-2">
                    <button
                        onClick={() => onRatingChange(undefined)}
                        className={`block w-full text-left px-3 py-2 rounded-md transition-colors ${
                            !selectedRating
                                ? 'bg-primary-olive text-white'
                                : 'text-gray-700 hover:bg-gray-100'
                        }`}
                    >
                        All Ratings
                    </button>
                    {ratings.map((rating) => (
                        <button
                            key={rating}
                            onClick={() => onRatingChange(rating)}
                            className={`block w-full text-left px-3 py-2 rounded-md transition-colors ${
                                selectedRating === rating
                                    ? 'bg-primary-olive text-white'
                                    : 'text-gray-700 hover:bg-gray-100'
                            }`}
                        >
                            <div className="flex items-center gap-2">
                                {[...Array(5)].map((_, i) => (
                                    <svg
                                        key={i}
                                        className={`w-4 h-4 ${
                                            i < rating ? 'text-yellow-400 fill-current' : 'text-gray-300'
                                        }`}
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                ))}
                                <span className="text-sm">& Up</span>
                            </div>
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}
