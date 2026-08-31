import { useState, useEffect, useRef, useCallback } from 'react';
import { Head, router } from '@inertiajs/react';
import { PaginatedProducts, Category, ProductSortOption } from '@/types/product';
import ProductGrid from '@/components/Products/partials/ProductGrid';
import FilterSidebar from '@/components/Products/partials/FilterSidebar';
import { useProducts } from '@/hooks/useProducts';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { SearchOrigin, isSameCoordinate, originQueryParams } from '@/utils/geolocation';
import { useSearchOrigin } from '@/hooks/useSearchOrigin';
import { LocationField } from '@/components/Location';

interface ProductsPageProps {
    products: PaginatedProducts;
    categories: Category[];
}

const BATCH_SIZE = 20;

const NEAR_ME_RADIUS_KM = 30;

export default function ProductsIndex({ products, categories }: ProductsPageProps) {
    const [showMobileFilters, setShowMobileFilters] = useState(false);
    const [visibleCount, setVisibleCount] = useState(BATCH_SIZE);
    const sentinelRef = useRef<HTMLDivElement>(null);
    const { origin, locateDevice, confirm, clear, adoptDeviceOrigin } = useSearchOrigin();
    const [isDistanceFiltered, setIsDistanceFiltered] = useState(false);
    const appliedConfirmedOrigin = useRef(false);

    const navigateWithOrigin = useCallback((next: SearchOrigin | null) => {
        const params = new URLSearchParams(window.location.search);
        params.delete('latitude');
        params.delete('longitude');
        params.delete('accuracy');
        params.delete('radius');
        params.delete('page');

        if (next !== null) {
            const query = originQueryParams(next);
            params.set('latitude', String(query.latitude));
            params.set('longitude', String(query.longitude));
            if (query.accuracy !== undefined) params.set('accuracy', String(Math.round(query.accuracy)));
            params.set('radius', String(NEAR_ME_RADIUS_KM));
        }

        router.get(`/products?${params.toString()}`, {}, { preserveState: true, preserveScroll: false });
    }, []);

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const lat = params.get('latitude');
        const lon = params.get('longitude');
        const accuracy = params.get('accuracy');

        if (lat === null || lon === null) {
            setIsDistanceFiltered(false);
            adoptDeviceOrigin(null);

            return;
        }

        setIsDistanceFiltered(true);
        adoptDeviceOrigin({
            latitude: Number(lat),
            longitude: Number(lon),
            accuracy: accuracy === null ? null : Number(accuracy),
            source: 'device',
        });
    }, [products, adoptDeviceOrigin]);

    const handleConfirmOrigin = useCallback((latitude: number, longitude: number, label?: string) => {
        appliedConfirmedOrigin.current = true;
        navigateWithOrigin(confirm(latitude, longitude, label));
    }, [confirm, navigateWithOrigin]);

    const handleUseDevice = useCallback(() => {
        return locateDevice().then(navigateWithOrigin);
    }, [locateDevice, navigateWithOrigin]);

    const handleClearOrigin = useCallback(() => {
        clear();
        setIsDistanceFiltered(false);
        navigateWithOrigin(null);
    }, [clear, navigateWithOrigin]);

    useEffect(() => {

        if (appliedConfirmedOrigin.current || !isDistanceFiltered) return;
        if (origin === null || origin.source !== 'confirmed') return;

        appliedConfirmedOrigin.current = true;

        const params = new URLSearchParams(window.location.search);
        const lat = params.get('latitude');
        const lon = params.get('longitude');
        const inUrl = lat === null || lon === null ? null : { latitude: Number(lat), longitude: Number(lon) };

        if (isSameCoordinate(inUrl, origin) && params.get('accuracy') === null) return;

        navigateWithOrigin(origin);
    }, [origin, isDistanceFiltered]);

    useEffect(() => {
        setVisibleCount(BATCH_SIZE);
    }, [products.current_page]);

    useEffect(() => {
        if (visibleCount >= products.data.length) return;
        if (!('IntersectionObserver' in window)) {
            setVisibleCount(products.data.length);
            return;
        }
        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0]?.isIntersecting) {
                    setVisibleCount((prev) => Math.min(prev + BATCH_SIZE, products.data.length));
                }
            },
            { threshold: 0.1 }
        );
        if (sentinelRef.current) observer.observe(sentinelRef.current);
        return () => observer.disconnect();
    }, [visibleCount, products.data.length]);

    const handlePageChange = (page: number) => {
        const params = new URLSearchParams(window.location.search);
        params.set('page', String(page));
        router.get(`/products?${params.toString()}`, {}, { preserveState: true, preserveScroll: false });
    };

    const visibleProducts = products.data.slice(0, visibleCount);
    const allRevealed = visibleCount >= products.data.length;

    const {
        searchTerm,
        filters,
        isLoading,
        handleSearch,
        handleCategoryChange,
        handlePriceChange,
        handleSortChange,
        clearFilters,
    } = useProducts();

    const sortOptions: { value: ProductSortOption; label: string }[] = [
        { value: 'name_asc', label: 'Name: A-Z' },
        { value: 'name_desc', label: 'Name: Z-A' },
        { value: 'newest', label: 'Newest' },
        { value: 'price_asc', label: 'Price: Low to High' },
        { value: 'price_desc', label: 'Price: High to Low' },
    ];

    return (
        <div className="flex min-h-screen flex-col bg-brand-surface font-display text-brand-ink">
            <Head title="Products - jiidaa" />

            <Header />

            <main className="mx-auto w-full max-w-[1380px] flex-1 px-5 pb-20 pt-8 lg:px-10">
                {/* Page head */}
                <div className="mb-6 flex items-center gap-4">
                    <button
                        onClick={() => window.history.back()}
                        className="flex h-11 w-11 flex-none items-center justify-center rounded-full border border-brand-line bg-white text-brand-ink transition hover:bg-brand-surface"
                        aria-label="Go back"
                    >
                        <svg className="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>
                    <h1 className="font-serif text-[28px] font-bold tracking-tight sm:text-[34px]">Products</h1>
                    <span className="hidden text-[15px] font-medium text-brand-muted sm:inline">
                        {products.total} {products.total === 1 ? 'product' : 'products'} found
                    </span>
                </div>

                {/* Search row: what you are looking for, and where you are looking from. */}
                <div className="mb-7 flex flex-col gap-2 rounded-2xl border border-brand-line bg-white p-2 shadow-sm lg:flex-row lg:items-center">
                    <div className="flex flex-1 flex-col divide-y divide-brand-line lg:flex-row lg:items-center lg:divide-x lg:divide-y-0">
                        <div className="relative min-w-0 flex-1">
                            <svg className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#98A2B3" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                <circle cx="11" cy="11" r="7" />
                                <path d="M21 21l-4.3-4.3" />
                            </svg>
                            <input
                                type="text"
                                placeholder="Search products…"
                                value={searchTerm}
                                onChange={(e) => handleSearch(e.target.value)}
                                className="h-12 w-full rounded-xl bg-transparent pl-11 pr-4 text-[15px] text-brand-ink outline-none placeholder:text-brand-muted focus-visible:ring-2 focus-visible:ring-brand-green/25"
                            />
                        </div>

                        <LocationField
                            origin={origin}
                            onConfirm={handleConfirmOrigin}
                            onUseDevice={handleUseDevice}
                            onClear={handleClearOrigin}
                            className="flex-1 lg:max-w-[300px]"
                        />

                        <div className="relative lg:w-[196px]">
                            <select
                                value={filters.sort_by || 'name_asc'}
                                onChange={(e) => handleSortChange(e.target.value as ProductSortOption)}
                                aria-label="Sort products"
                                className="h-12 w-full appearance-none rounded-xl bg-transparent pl-4 pr-9 text-sm font-medium text-brand-ink outline-none focus-visible:ring-2 focus-visible:ring-brand-green/25"
                            >
                                {sortOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            <svg className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#667085" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                <path d="M6 9l6 6 6-6" />
                            </svg>
                        </div>
                    </div>

                    {/* Mobile filter button */}
                    <button
                        onClick={() => setShowMobileFilters(true)}
                        className="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-xl border border-brand-line bg-white px-5 text-[15px] font-semibold text-brand-ink transition hover:border-brand-green lg:hidden"
                    >
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filters
                    </button>
                </div>

                {/* Body: sidebar + grid */}
                <div className="grid grid-cols-1 items-start gap-9 lg:grid-cols-[280px_1fr]">
                    {/* Desktop Sidebar */}
                    <aside className="sticky top-24 hidden rounded-[18px] border border-brand-line bg-white p-6 lg:block">
                        <FilterSidebar
                            categories={categories}
                            selectedCategory={filters.category_id}
                            minPrice={filters.min_price}
                            maxPrice={filters.max_price}
                            onCategoryChange={handleCategoryChange}
                            onPriceChange={handlePriceChange}
                            onClearFilters={clearFilters}
                        />
                    </aside>

                    {/* Product grid */}
                    <section>
                        <div className="mb-4 text-[15px] font-medium text-brand-muted">
                            Showing {products.from || 0}-{products.to || 0} of {products.total} products
                        </div>

                        <ProductGrid products={visibleProducts} loading={isLoading} />

                        {/* Sentinel for progressive reveal */}
                        {!allRevealed && (
                            <div ref={sentinelRef} className="mt-8 flex justify-center">
                                <div className="flex items-center gap-2 text-sm text-brand-muted">
                                    <svg className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    Loading more products…
                                </div>
                            </div>
                        )}

                        {/* Pagination — only after all items on this page are revealed */}
                        {allRevealed && products.last_page > 1 && (
                            <div className="mt-10 flex justify-center">
                                <nav className="flex items-center gap-2">
                                    <button
                                        onClick={() => handlePageChange(products.current_page - 1)}
                                        disabled={products.current_page === 1}
                                        className="rounded-full border border-brand-line bg-white px-4 py-2 text-sm font-semibold text-brand-ink transition hover:bg-brand-surface disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Previous
                                    </button>

                                    {[...Array(products.last_page)].map((_, i) => {
                                        const page = i + 1;
                                        const isCurrentPage = page === products.current_page;
                                        const showPage =
                                            page === 1 ||
                                            page === products.last_page ||
                                            (page >= products.current_page - 1 && page <= products.current_page + 1);

                                        if (!showPage) {
                                            if (page === products.current_page - 2 || page === products.current_page + 2) {
                                                return <span key={page} className="px-2 text-brand-muted">...</span>;
                                            }
                                            return null;
                                        }

                                        return (
                                            <button
                                                key={page}
                                                onClick={() => handlePageChange(page)}
                                                className={`rounded-full border px-4 py-2 text-sm font-semibold transition ${
                                                    isCurrentPage
                                                        ? 'border-brand-ink bg-brand-ink text-white'
                                                        : 'border-brand-line bg-white text-brand-ink hover:bg-brand-surface'
                                                }`}
                                            >
                                                {page}
                                            </button>
                                        );
                                    })}

                                    <button
                                        onClick={() => handlePageChange(products.current_page + 1)}
                                        disabled={products.current_page === products.last_page}
                                        className="rounded-full border border-brand-line bg-white px-4 py-2 text-sm font-semibold text-brand-ink transition hover:bg-brand-surface disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Next
                                    </button>
                                </nav>
                            </div>
                        )}
                    </section>
                </div>
            </main>

            <Footer />

            {/* Mobile filter drawer */}
            {showMobileFilters && (
                <div className="fixed inset-0 z-50 lg:hidden">
                    <div className="absolute inset-0 bg-brand-ink/50" onClick={() => setShowMobileFilters(false)} />
                    <div className="absolute bottom-0 right-0 top-0 w-full max-w-sm overflow-y-auto bg-white shadow-xl">
                        <div className="p-6">
                            <div className="mb-4 flex justify-end">
                                <button
                                    onClick={() => setShowMobileFilters(false)}
                                    className="rounded-full p-2 text-brand-ink hover:bg-brand-surface"
                                    aria-label="Close filters"
                                >
                                    <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <FilterSidebar
                                categories={categories}
                                selectedCategory={filters.category_id}
                                minPrice={filters.min_price}
                                maxPrice={filters.max_price}
                                onCategoryChange={(cat) => {
                                    handleCategoryChange(cat);
                                    setShowMobileFilters(false);
                                }}
                                onPriceChange={(min, max) => {
                                    handlePriceChange(min, max);
                                    setShowMobileFilters(false);
                                }}
                                onClearFilters={() => {
                                    clearFilters();
                                    setShowMobileFilters(false);
                                }}
                            />
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
