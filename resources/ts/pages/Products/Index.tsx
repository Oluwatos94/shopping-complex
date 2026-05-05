import { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { PaginatedProducts, Category, ProductSortOption } from '@/types/product';
import ProductGrid from '@/components/Products/partials/ProductGrid';
import FilterSidebar from '@/components/Products/partials/FilterSidebar';
import { useProducts } from '@/hooks/useProducts';
import AuthenticatedLayout from '@/components/Layout/AuthenticatedLayout';

interface ProductsPageProps {
    products: PaginatedProducts;
    categories: Category[];
}

export default function ProductsIndex({ products, categories }: ProductsPageProps) {
    const [showMobileFilters, setShowMobileFilters] = useState(false);
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
        { value: 'newest', label: 'Newest' },
        { value: 'price_asc', label: 'Price: Low to High' },
        { value: 'price_desc', label: 'Price: High to Low' },
        { value: 'name_asc', label: 'Name: A-Z' },
        { value: 'name_desc', label: 'Name: Z-A' },
    ];

    const { auth } = usePage<{ auth: { user: any } | null }>().props;

    return (
        <AuthenticatedLayout user={(auth as any)?.user} title="Products - Shopping Complex" className="!p-0 !max-w-none">
            <Head title="Products - Shopping Complex" />

            <div className="bg-gray-50">
                {/* Sticky search + sort bar */}
                <div className="bg-white border-b border-gray-200 sticky top-[57px] z-10">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                        <div className="flex items-center gap-3 mb-3">
                            <button
                                onClick={() => window.history.back()}
                                className="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors text-gray-600 flex-shrink-0"
                                aria-label="Go back"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <h1 className="text-base font-semibold text-gray-900">Products</h1>
                            <span className="text-sm text-gray-400 hidden sm:inline">
                                {products.total} {products.total === 1 ? 'product' : 'products'} found
                            </span>
                        </div>

                        {/* Search and Sort */}
                        <div className="flex flex-col sm:flex-row gap-3">
                            {/* Search Input */}
                            <div className="w-full sm:w-72">
                                <div className="relative">
                                    <input
                                        type="text"
                                        placeholder="Search products..."
                                        value={searchTerm}
                                        onChange={(e) => handleSearch(e.target.value)}
                                        className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent"
                                    />
                                    <svg
                                        className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                </div>
                            </div>

                            {/* Sort Dropdown */}
                            <div className="sm:w-52">
                                <select
                                    value={filters.sort_by || 'newest'}
                                    onChange={(e) => handleSortChange(e.target.value as ProductSortOption)}
                                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent"
                                >
                                    {sortOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Mobile Filter Button */}
                            <button
                                onClick={() => setShowMobileFilters(true)}
                                className="lg:hidden flex items-center justify-center gap-2 px-6 py-3 bg-primary-olive text-white rounded-lg hover:bg-primary-dark transition-colors"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                    />
                                </svg>
                                Filters
                            </button>
                        </div>
                    </div>
                </div>

                {/* Main Content */}
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div className="flex gap-8">
                        {/* Desktop Sidebar */}
                        <aside className="hidden lg:block w-64 flex-shrink-0">
                            <div className="sticky top-4 max-h-[calc(100vh-6rem)] overflow-y-auto pr-1">
                                <FilterSidebar
                                    categories={categories}
                                    selectedCategory={filters.category_id}
                                    minPrice={filters.min_price}
                                    maxPrice={filters.max_price}
                                    onCategoryChange={handleCategoryChange}
                                    onPriceChange={handlePriceChange}
                                    onClearFilters={clearFilters}
                                />
                            </div>
                        </aside>

                        {/* Product Grid */}
                        <main className="flex-1">
                            {/* Results Count */}
                            <div className="mb-6 flex items-center justify-between">
                                <p className="text-gray-600">
                                    Showing {products.from || 0}-{products.to || 0} of {products.total} products
                                </p>
                            </div>

                            {/* Grid */}
                            <ProductGrid products={products.data} loading={isLoading} />

                            {/* Pagination */}
                            {products.last_page > 1 && (
                                <div className="mt-8 flex justify-center">
                                    <nav className="flex items-center gap-2">
                                        <button
                                            disabled={products.current_page === 1}
                                            className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Previous
                                        </button>

                                        {/* Page Numbers */}
                                        {[...Array(products.last_page)].map((_, i) => {
                                            const page = i + 1;
                                            const isCurrentPage = page === products.current_page;
                                            const showPage =
                                                page === 1 ||
                                                page === products.last_page ||
                                                (page >= products.current_page - 1 && page <= products.current_page + 1);

                                            if (!showPage) {
                                                if (page === products.current_page - 2 || page === products.current_page + 2) {
                                                    return <span key={page} className="px-2">...</span>;
                                                }
                                                return null;
                                            }

                                            return (
                                                <button
                                                    key={page}
                                                    className={`px-4 py-2 border rounded-md ${
                                                        isCurrentPage
                                                            ? 'bg-primary-olive text-white border-primary-olive'
                                                            : 'border-gray-300 hover:bg-gray-50'
                                                    }`}
                                                >
                                                    {page}
                                                </button>
                                            );
                                        })}

                                        <button
                                            disabled={products.current_page === products.last_page}
                                            className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Next
                                        </button>
                                    </nav>
                                </div>
                            )}
                        </main>
                    </div>
                </div>

                {/* Mobile Filter Drawer */}
                {showMobileFilters && (
                    <div className="lg:hidden fixed inset-0 z-50">
                        {/* Overlay */}
                        <div
                            className="absolute inset-0 bg-black/50"
                            onClick={() => setShowMobileFilters(false)}
                        />

                        {/* Drawer */}
                        <div className="absolute right-0 top-0 bottom-0 w-full max-w-sm bg-white shadow-xl overflow-y-auto">
                            <div className="p-6">
                                {/* Close Button */}
                                <div className="flex items-center justify-between mb-6">
                                    <h2 className="text-lg font-serif font-semibold">Filters</h2>
                                    <button
                                        onClick={() => setShowMobileFilters(false)}
                                        className="p-2 hover:bg-gray-100 rounded-full"
                                    >
                                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
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
        </AuthenticatedLayout>
    );
}
