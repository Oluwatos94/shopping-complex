import { useState } from 'react';
import { Category } from '@/types/product';

interface FilterSidebarProps {
    categories: Category[];
    selectedCategory?: number;
    minPrice?: number;
    maxPrice?: number;
    onCategoryChange: (categoryId?: number) => void;
    onPriceChange: (min?: number, max?: number) => void;
    onClearFilters: () => void;
}

export default function FilterSidebar({
    categories,
    selectedCategory,
    minPrice,
    maxPrice,
    onCategoryChange,
    onPriceChange,
    onClearFilters,
}: FilterSidebarProps) {
    const [priceMin, setPriceMin] = useState(minPrice?.toString() || '');
    const [priceMax, setPriceMax] = useState(maxPrice?.toString() || '');

    const handlePriceSubmit = () => {
        const min = priceMin ? parseFloat(priceMin) : undefined;
        const max = priceMax ? parseFloat(priceMax) : undefined;
        onPriceChange(min, max);
    };

    const totalCount = categories.reduce((sum, c) => sum + (c.products_count || 0), 0);

    const categoryButton = (id: number | undefined, name: string, count: number) => {
        const active = id === undefined ? !selectedCategory : selectedCategory === id;
        return (
            <button
                key={id ?? 'all'}
                onClick={() => onCategoryChange(id)}
                className={`flex w-full items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-left text-[15px] transition ${
                    active ? 'bg-[#E7F8EE] font-bold text-brand-ink' : 'font-medium text-brand-muted hover:bg-brand-surface'
                }`}
            >
                <span className={`h-1.5 w-1.5 flex-none rounded-full ${active ? 'bg-brand-green' : 'bg-transparent'}`} />
                <span className="flex-1">{name}</span>
                <span className={`text-[13px] font-semibold ${active ? 'text-brand-green-dark' : 'text-brand-muted/70'}`}>
                    {count}
                </span>
            </button>
        );
    };

    return (
        <div className="font-display">
            {/* Header */}
            <div className="mb-5 flex items-center justify-between">
                <h2 className="font-serif text-[21px] font-bold text-brand-ink">Filters</h2>
                <button
                    onClick={onClearFilters}
                    className="text-[14px] font-bold text-brand-green transition hover:text-brand-green-dark"
                >
                    Clear All
                </button>
            </div>

            {/* Categories */}
            <div className="mb-3 text-[13px] font-bold uppercase tracking-[0.08em] text-brand-muted/70">Categories</div>
            <div className="mb-6 flex flex-col gap-0.5">
                {categoryButton(undefined, 'All Categories', totalCount)}
                {categories.map((category) => categoryButton(category.id, category.name, category.products_count || 0))}
            </div>

            <div className="mb-6 h-px bg-brand-line" />

            {/* Price Range */}
            <div className="mb-3.5 text-[13px] font-bold uppercase tracking-[0.08em] text-brand-muted/70">Price Range</div>
            <div className="mb-4 flex items-center gap-2.5">
                <input
                    type="number"
                    placeholder="Min"
                    value={priceMin}
                    onChange={(e) => setPriceMin(e.target.value)}
                    min="0"
                    className="h-12 w-full rounded-xl border border-brand-line bg-brand-surface px-3.5 text-[15px] text-brand-ink outline-none transition focus:border-brand-green focus:ring-2 focus:ring-brand-green/20"
                />
                <span className="font-semibold text-brand-muted/70">–</span>
                <input
                    type="number"
                    placeholder="Max"
                    value={priceMax}
                    onChange={(e) => setPriceMax(e.target.value)}
                    min="0"
                    className="h-12 w-full rounded-xl border border-brand-line bg-brand-surface px-3.5 text-[15px] text-brand-ink outline-none transition focus:border-brand-green focus:ring-2 focus:ring-brand-green/20"
                />
            </div>
            <button
                onClick={handlePriceSubmit}
                className="h-[50px] w-full rounded-xl bg-brand-ink text-[15px] font-bold text-white transition hover:bg-brand-ink/90"
            >
                Apply
            </button>
        </div>
    );
}
