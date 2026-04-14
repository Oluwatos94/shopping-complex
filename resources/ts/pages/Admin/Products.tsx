import { Head, router } from '@inertiajs/react';
import { useCallback, useRef, useState } from 'react';
import AdminLayout from '@/components/Admin/AdminLayout';
import ProductCard from '@/components/Admin/product/partials/ProductCard';
import ProductRow from '@/components/Admin/product/partials/ProductRow';
import FlagModal from '@/components/Admin/product/partials/FlagModal';
import { AdminProduct, Category, FilterOverrides, Paginated, ProductStatus, ViewMode } from '@/types/product';

interface Props {
    products: Paginated<AdminProduct>;
    categories: Category[];
    totalPending: number;
}

function getProductStatus(product: AdminProduct, flaggedIds: Set<number>): ProductStatus {
    if (flaggedIds.has(product.id)) return 'flagged';
    if (product.is_active) return 'active';
    return 'pending';
}

export default function Products({ products, categories, totalPending }: Props) {
    const [viewMode, setViewMode] = useState<ViewMode>('grid');
    const [selected, setSelected] = useState<number[]>([]);
    const [flagging, setFlagging] = useState<AdminProduct | null>(null);
    const [flaggedIds, setFlaggedIds] = useState<Set<number>>(new Set());
    const [processing, setProcessing] = useState<number | null>(null);
    const [bulkProcessing, setBulkProcessing] = useState(false);

    const [status, setStatus] = useState('');
    const [category, setCategory] = useState('');
    const [search, setSearch] = useState('');
    const [sortBy, setSortBy] = useState('newest');

    const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const applyFilters = (overrides: FilterOverrides = {}) => {
        const params: Record<string, string | number> = {};
        const s = overrides.status !== undefined ? overrides.status : status;
        const c = overrides.category !== undefined ? overrides.category : category;
        const q = overrides.search !== undefined ? overrides.search : search;
        const srt = overrides.sortBy !== undefined ? overrides.sortBy : sortBy;
        if (s) params.status = s;
        if (c) params.category = c;
        if (q) params.search = q;
        if (srt && srt !== 'newest') params.sort = srt;
        if (overrides.page) params.page = overrides.page;
        router.get('/admin/products', params, { preserveScroll: true });
    };

    const debouncedApplySearch = useCallback((value: string) => {
        if (searchTimer.current) clearTimeout(searchTimer.current);
        searchTimer.current = setTimeout(() => applyFilters({ search: value }), 400);
    }, []);

    const resetFilters = () => {
        setStatus('');
        setCategory('');
        setSearch('');
        setSortBy('newest');
        router.get('/admin/products', {}, { preserveScroll: true });
    };

    const toggleSelect = (id: number) => {
        setSelected((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
    };

    const handleApprove = (id: number) => {
        setProcessing(id);
        router.patch(`/admin/products/${id}`, { is_active: true }, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(null);
                setSelected((prev) => prev.filter((x) => x !== id));
            },
        });
    };

    const handleDeactivate = (id: number) => {
        setProcessing(id);
        router.patch(`/admin/products/${id}`, { is_active: false }, {
            preserveScroll: true,
            onFinish: () => setProcessing(null),
        });
    };

    const handleBulkApprove = () => {
        const ids = selected.length > 0
            ? selected
            : products.data.filter((p) => !p.is_active).map((p) => p.id);

        if (ids.length === 0) return;
        setBulkProcessing(true);
        router.post('/admin/products/bulk-approve', { ids }, {
            preserveScroll: true,
            onFinish: () => {
                setBulkProcessing(false);
                setSelected([]);
            },
        });
    };

    const handleFlagSubmit = (id: number, reason: string, notes: string) => {
        router.post(`/admin/products/${id}/flag`, { reason, notes }, {
            preserveScroll: true,
            onSuccess: () => {
                setFlaggedIds((prev) => new Set([...prev, id]));
                setSelected((prev) => prev.filter((x) => x !== id));
            },
        });
    };

    const handleUnflag = (id: number) => {
        setFlaggedIds((prev) => {
            const next = new Set(prev);
            next.delete(id);
            return next;
        });
    };

    const pendingOnPage = products.data.filter((p) => !p.is_active && !flaggedIds.has(p.id));

    const sharedCardProps = (product: AdminProduct) => ({
        product,
        status: getProductStatus(product, flaggedIds),
        selected: selected.includes(product.id),
        onToggleSelect: toggleSelect,
        onApprove: handleApprove,
        onDeactivate: handleDeactivate,
        onFlag: setFlagging,
        onUnflag: handleUnflag,
        processing,
    });

    return (
        <>
            <Head title="Product Moderation — Admin" />
            <AdminLayout>
                {/* Page Header */}
                <div className="flex items-end justify-between mb-12">
                    <div>
                        <p className="text-[10px] tracking-[0.2em] font-bold text-primary-olive uppercase mb-2">
                            Inventory Stewardship
                        </p>
                        <h2 className="text-4xl font-extrabold tracking-tight text-gray-900">
                            Product Moderation
                        </h2>
                    </div>
                    <div className="flex items-center gap-4">
                        {/* Search */}
                        <div className="relative">
                            <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                type="text"
                                placeholder="Search products..."
                                value={search}
                                onChange={(e) => { setSearch(e.target.value); debouncedApplySearch(e.target.value); }}
                                className="bg-white border border-gray-200/60 rounded-full py-2 pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20 placeholder:text-gray-300 w-52"
                            />
                        </div>

                        {/* View toggle */}
                        <div className="flex bg-gray-100 p-1 rounded-xl">
                            <button
                                onClick={() => setViewMode('grid')}
                                className={`p-2 rounded-lg transition-colors ${viewMode === 'grid' ? 'bg-white shadow-sm text-primary-olive' : 'text-gray-400 hover:text-gray-600'}`}
                                title="Grid view"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                            <button
                                onClick={() => setViewMode('list')}
                                className={`p-2 rounded-lg transition-colors ${viewMode === 'list' ? 'bg-white shadow-sm text-primary-olive' : 'text-gray-400 hover:text-gray-600'}`}
                                title="List view"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                            </button>
                        </div>

                        {/* Bulk Approve */}
                        <button
                            disabled={bulkProcessing || pendingOnPage.length === 0}
                            onClick={handleBulkApprove}
                            className="bg-primary-olive text-white px-5 py-3 rounded-lg font-bold flex items-center gap-2 hover:brightness-110 transition-all shadow-lg shadow-primary-olive/20 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
                            </svg>
                            {bulkProcessing
                                ? 'Approving…'
                                : selected.length > 0
                                ? `Approve (${selected.length})`
                                : 'Bulk Approve'}
                        </button>
                    </div>
                </div>

                {/* Filters & Stats Bento */}
                <div className="grid grid-cols-12 gap-5 mb-10">
                    <div className="col-span-12 lg:col-span-8 bg-gray-50 rounded-xl p-6 border border-gray-100 flex items-center justify-between flex-wrap gap-6">
                        <div className="flex gap-8 flex-wrap">
                            {/* Status */}
                            <div className="space-y-1 min-w-[120px]">
                                <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">
                                    Status
                                </label>
                                <select
                                    value={status}
                                    onChange={(e) => { setStatus(e.target.value); applyFilters({ status: e.target.value }); }}
                                    className="block bg-transparent border-none p-0 font-bold text-gray-900 focus:ring-0 cursor-pointer text-sm"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="inactive">Pending Review</option>
                                    <option value="active">Active</option>
                                </select>
                            </div>

                            <div className="w-px h-10 bg-gray-200" />

                            {/* Category */}
                            <div className="space-y-1 min-w-[140px]">
                                <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">
                                    Category
                                </label>
                                <select
                                    value={category}
                                    onChange={(e) => { setCategory(e.target.value); applyFilters({ category: e.target.value }); }}
                                    className="block bg-transparent border-none p-0 font-bold text-gray-900 focus:ring-0 cursor-pointer text-sm"
                                >
                                    <option value="">All Categories</option>
                                    {categories.map((cat) => (
                                        <option key={cat.id} value={cat.slug}>{cat.name}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="w-px h-10 bg-gray-200" />

                            {/* Sort By */}
                            <div className="space-y-1 min-w-[130px]">
                                <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">
                                    Sort By
                                </label>
                                <select
                                    value={sortBy}
                                    onChange={(e) => { setSortBy(e.target.value); applyFilters({ sortBy: e.target.value }); }}
                                    className="block bg-transparent border-none p-0 font-bold text-gray-900 focus:ring-0 cursor-pointer text-sm"
                                >
                                    <option value="newest">Newest First</option>
                                    <option value="price_desc">Highest Price</option>
                                    <option value="price_asc">Lowest Price</option>
                                </select>
                            </div>
                        </div>

                        <button
                            onClick={resetFilters}
                            className="text-gray-400 hover:text-primary-olive flex items-center gap-1.5 text-sm font-medium transition-colors"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Reset Filters
                        </button>
                    </div>

                    {/* Live Queue stat */}
                    <div className="col-span-12 lg:col-span-4 bg-gradient-to-br from-primary-dark to-primary-brown text-white rounded-xl p-6 flex flex-col justify-between shadow-lg shadow-primary-dark/20">
                        <div className="flex justify-between items-start">
                            <svg className="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span className="bg-white/15 text-white px-2 py-1 rounded-lg text-[10px] font-bold tracking-wider">
                                LIVE QUEUE
                            </span>
                        </div>
                        <div>
                            <h3 className="text-4xl font-bold tracking-tight mt-4">{totalPending}</h3>
                            <p className="text-sm opacity-75 font-medium mt-1">Products awaiting verification</p>
                        </div>
                    </div>
                </div>

                {/* Product Grid / List */}
                {products.data.length === 0 ? (
                    <div className="py-24 text-center">
                        <svg className="w-12 h-12 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <p className="text-gray-400 font-medium">No products match the current filters.</p>
                    </div>
                ) : viewMode === 'grid' ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {products.data.map((product) => (
                            <ProductCard key={product.id} {...sharedCardProps(product)} />
                        ))}
                    </div>
                ) : (
                    <div>
                        {/* List header */}
                        <div className="flex items-center gap-4 px-4 mb-2">
                            <div className="w-5" />
                            <div className="w-14" />
                            <div className="flex-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Product</div>
                            <div className="w-24 text-right text-[10px] font-bold text-gray-400 uppercase tracking-wider">Price</div>
                            <div className="w-32 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status</div>
                            <div className="w-24 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Actions</div>
                        </div>
                        <div className="space-y-2">
                            {products.data.map((product) => (
                                <ProductRow key={product.id} {...sharedCardProps(product)} />
                            ))}
                        </div>
                    </div>
                )}

                {/* Pagination */}
                {products.data.length > 0 && (
                    <div className="mt-16 flex flex-col items-center gap-5">
                        <p className="text-xs text-gray-400 font-medium uppercase tracking-widest">
                            Showing {products.data.length} of {products.total.toLocaleString()} products
                        </p>
                        {products.last_page > 1 && (
                            <div className="flex gap-3">
                                <button
                                    disabled={products.current_page === 1}
                                    onClick={() => applyFilters({ page: products.current_page - 1 })}
                                    className="px-6 py-2.5 rounded-full border border-gray-200 bg-white font-bold text-gray-600 text-xs uppercase tracking-widest hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                                >
                                    Previous
                                </button>
                                <button
                                    disabled={products.current_page === products.last_page}
                                    onClick={() => applyFilters({ page: products.current_page + 1 })}
                                    className="px-6 py-2.5 rounded-full border border-gray-200 bg-white font-bold text-gray-600 text-xs uppercase tracking-widest hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                                >
                                    Load More
                                </button>
                            </div>
                        )}
                    </div>
                )}
            </AdminLayout>

            <FlagModal
                product={flagging}
                onClose={() => setFlagging(null)}
                onSubmit={handleFlagSubmit}
            />
        </>
    );
}
