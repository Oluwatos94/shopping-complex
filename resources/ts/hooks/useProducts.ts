import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { ProductFilters, ProductSortOption } from '@/types/product';

export function useProducts() {
    const [searchTerm, setSearchTerm] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [filters, setFilters] = useState<ProductFilters>({});
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(searchTerm);
        }, 300);

        return () => clearTimeout(timer);
    }, [searchTerm]);

    // Update URL and fetch products when filters change
    useEffect(() => {
        if (debouncedSearch !== (filters.search || '')) {
            updateFilters({ search: debouncedSearch || undefined });
        }
    }, [debouncedSearch]);

    const updateFilters = useCallback((newFilters: Partial<ProductFilters>) => {
        const updatedFilters = { ...filters, ...newFilters };

        // Remove undefined values
        Object.keys(updatedFilters).forEach((key) => {
            if (updatedFilters[key as keyof ProductFilters] === undefined) {
                delete updatedFilters[key as keyof ProductFilters];
            }
        });

        setFilters(updatedFilters);

        // Build query string
        const params = new URLSearchParams();

        if (updatedFilters.search) {
            params.append('filter[name]', updatedFilters.search);
        }
        if (updatedFilters.category_id) {
            params.append('filter[category_id]', updatedFilters.category_id.toString());
        }
        if (updatedFilters.vendor_id) {
            params.append('filter[vendor_id]', updatedFilters.vendor_id.toString());
        }
        if (updatedFilters.min_price !== undefined) {
            params.append('filter[min_price]', updatedFilters.min_price.toString());
        }
        if (updatedFilters.max_price !== undefined) {
            params.append('filter[max_price]', updatedFilters.max_price.toString());
        }
        if (updatedFilters.in_stock_only) {
            params.append('filter[is_active]', '1');
        }
        if (updatedFilters.sort_by) {
            const sortMap: Record<ProductSortOption, string> = {
                price_asc: 'price',
                price_desc: '-price',
                name_asc: 'name',
                name_desc: '-name',
                rating: '-average_rating',
                newest: '-created_at',
                popular: '-reviews_count',
            };
            params.append('sort', sortMap[updatedFilters.sort_by]);
        }

        // Navigate with new filters
        setIsLoading(true);
        router.get(`/products?${params.toString()}`, {}, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setIsLoading(false),
        });
    }, [filters]);

    const handleSearch = useCallback((value: string) => {
        setSearchTerm(value);
    }, []);

    const handleCategoryChange = useCallback((categoryId?: number) => {
        updateFilters({ category_id: categoryId });
    }, [updateFilters]);

    const handlePriceChange = useCallback((min?: number, max?: number) => {
        updateFilters({ min_price: min, max_price: max });
    }, [updateFilters]);

    const handleRatingChange = useCallback((rating?: number) => {
        // Note: Backend needs to support min_rating filter
        // For now, this is a placeholder
        console.log('Rating filter:', rating);
    }, []);

    const handleSortChange = useCallback((sortBy: ProductSortOption) => {
        updateFilters({ sort_by: sortBy });
    }, [updateFilters]);

    const handleStockFilterChange = useCallback((inStockOnly: boolean) => {
        updateFilters({ in_stock_only: inStockOnly });
    }, [updateFilters]);

    const clearFilters = useCallback(() => {
        setSearchTerm('');
        setFilters({});
        setIsLoading(true);
        router.get('/products', {}, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setIsLoading(false),
        });
    }, []);

    return {
        searchTerm,
        filters,
        isLoading,
        handleSearch,
        handleCategoryChange,
        handlePriceChange,
        handleRatingChange,
        handleSortChange,
        handleStockFilterChange,
        clearFilters,
    };
}
