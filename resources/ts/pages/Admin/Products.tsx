import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/components/Admin/AdminLayout';

interface ProductMedia {
    url: string;
}

interface ProductVendor {
    id: number;
    name: string;
    business_name: string | null;
}

interface ProductCategory {
    id: number;
    name: string;
    slug: string;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    price: number;
    stock: number;
    is_active: boolean;
    created_at: string;
    vendor: ProductVendor | null;
    category: ProductCategory | null;
    media: ProductMedia[];
}

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    products: {
        data: Product[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    categories: Category[];
    totalPending: number;
}

type ViewMode = 'grid' | 'list';
type ProductStatus = 'pending' | 'active' | 'flagged';

const FLAG_REASONS = [
    'Misleading Description',
    'Counterfeit / Unauthorized Item',
    'Inappropriate Image Content',
    'Pricing Irregularity',
];

function formatCurrency(price: number): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(price);
}

function getProductStatus(product: Product, flaggedIds: Set<number>): ProductStatus {
    if (flaggedIds.has(product.id)) return 'flagged';
    if (product.is_active) return 'active';
    return 'pending';
}

function StatusBadge({ status }: { status: ProductStatus }) {
    if (status === 'active') {
        return (
            <span className="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest shadow">
                Active
            </span>
        );
    }
    if (status === 'flagged') {
        return (
            <span className="bg-red-100 text-red-700 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest shadow">
                Flagged
            </span>
        );
    }
    return (
        <span className="bg-primary-brown/15 text-primary-brown px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest shadow">
            Pending Review
        </span>
    );
}

function ProductCard({
    product,
    status,
    selected,
    onToggleSelect,
    onApprove,
    onDeactivate,
    onFlag,
    onUnflag,
    processing,
}: {
    product: Product;
    status: ProductStatus;
    selected: boolean;
    onToggleSelect: (id: number) => void;
    onApprove: (id: number) => void;
    onDeactivate: (id: number) => void;
    onFlag: (product: Product) => void;
    onUnflag: (id: number) => void;
    processing: number | null;
}) {
    const imageUrl = product.media[0]?.url ?? null;
    const vendorName = product.vendor?.business_name || product.vendor?.name || '—';
    const categoryName = product.category?.name ?? '—';
    const isProcessing = processing === product.id;

    return (
        <div
            className={`group bg-white rounded-xl overflow-hidden transition-all duration-300 ${
                selected
                    ? 'ring-2 ring-primary-olive'
                    : 'hover:ring-1 hover:ring-gray-200'
            }`}
        >
            {/* Image */}
            <div className="relative h-56">
                {imageUrl ? (
                    <img
                        src={imageUrl}
                        alt={product.name}
                        className="w-full h-full object-cover grayscale-[20%] group-hover:grayscale-0 group-hover:scale-105 transition-all duration-500"
                    />
                ) : (
                    <div className="w-full h-full bg-gray-100 flex items-center justify-center">
                        <svg className="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                )}

                {/* Status badge */}
                <div className="absolute top-4 left-4">
                    <StatusBadge status={status} />
                </div>

                {/* Select checkbox — pending only */}
                {status === 'pending' && (
                    <div className="absolute top-4 right-4">
                        <button
                            onClick={() => onToggleSelect(product.id)}
                            className={`w-6 h-6 rounded-md border-2 flex items-center justify-center transition-all ${
                                selected
                                    ? 'bg-primary-olive border-primary-olive'
                                    : 'bg-white/80 border-gray-300 hover:border-primary-olive'
                            }`}
                        >
                            {selected && (
                                <svg className="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                                </svg>
                            )}
                        </button>
                    </div>
                )}

                {/* Hover overlay */}
                <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center gap-3 transition-opacity">
                    <a
                        href={`/products/${product.slug}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="bg-white text-gray-900 p-3 rounded-xl hover:bg-primary-olive hover:text-white transition-colors"
                        title="View product"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </a>
                    {status !== 'flagged' && (
                        <button
                            onClick={() => onFlag(product)}
                            className="bg-white text-gray-900 p-3 rounded-xl hover:bg-red-500 hover:text-white transition-colors"
                            title="Flag product"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                        </button>
                    )}
                </div>
            </div>

            {/* Body */}
            <div className={`p-5 ${status === 'active' ? 'opacity-60' : ''}`}>
                <div className="flex justify-between items-start mb-1.5">
                    <h4 className="font-bold text-base text-gray-900 leading-snug flex-1 pr-2">
                        {product.name}
                    </h4>
                    <span className="font-bold text-primary-olive whitespace-nowrap text-sm">
                        {formatCurrency(product.price)}
                    </span>
                </div>

                <div className="flex items-center text-xs text-gray-400 gap-2 mb-4">
                    <span>{vendorName}</span>
                    <span className="w-1 h-1 bg-gray-300 rounded-full" />
                    <span>{categoryName}</span>
                </div>

                {/* Actions */}
                {status === 'active' && (
                    <div className="flex gap-2">
                        <button
                            disabled={isProcessing}
                            onClick={() => onDeactivate(product.id)}
                            className="flex-1 bg-gray-100 text-gray-600 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-gray-200 transition-colors disabled:opacity-50"
                        >
                            {isProcessing ? 'Updating…' : 'Deactivate'}
                        </button>
                        <button
                            onClick={() => onFlag(product)}
                            className="aspect-square bg-red-50 text-red-500 p-2.5 rounded-lg flex items-center justify-center hover:bg-red-500 hover:text-white transition-all"
                            title="Flag"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                        </button>
                    </div>
                )}

                {status === 'pending' && (
                    <div className="flex gap-2">
                        <button
                            disabled={isProcessing}
                            onClick={() => onDeactivate(product.id)}
                            className="flex-1 bg-gray-100 text-gray-500 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-gray-200 transition-colors disabled:opacity-50"
                        >
                            View Details
                        </button>
                        <button
                            disabled={isProcessing}
                            onClick={() => onApprove(product.id)}
                            className="aspect-square bg-primary-olive/10 text-primary-olive p-2.5 rounded-lg flex items-center justify-center hover:bg-primary-olive hover:text-white transition-all disabled:opacity-50"
                            title="Approve"
                        >
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
                            </svg>
                        </button>
                    </div>
                )}

                {status === 'flagged' && (
                    <div className="flex gap-2">
                        <button
                            onClick={() => onUnflag(product.id)}
                            className="flex-1 bg-gray-100 text-gray-600 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-gray-200 transition-colors"
                        >
                            Review Flags
                        </button>
                        <button
                            disabled={isProcessing}
                            onClick={() => onDeactivate(product.id)}
                            className="aspect-square bg-red-50 text-red-500 p-2.5 rounded-lg flex items-center justify-center hover:bg-red-500 hover:text-white transition-all disabled:opacity-50"
                            title="Remove product"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}

function ProductRow({
    product,
    status,
    selected,
    onToggleSelect,
    onApprove,
    onDeactivate,
    onFlag,
    onUnflag,
    processing,
}: {
    product: Product;
    status: ProductStatus;
    selected: boolean;
    onToggleSelect: (id: number) => void;
    onApprove: (id: number) => void;
    onDeactivate: (id: number) => void;
    onFlag: (product: Product) => void;
    onUnflag: (id: number) => void;
    processing: number | null;
}) {
    const imageUrl = product.media[0]?.url ?? null;
    const vendorName = product.vendor?.business_name || product.vendor?.name || '—';
    const categoryName = product.category?.name ?? '—';
    const isProcessing = processing === product.id;

    return (
        <div
            className={`group flex items-center gap-4 bg-white rounded-xl p-4 transition-all duration-200 ${
                selected ? 'ring-2 ring-primary-olive' : 'hover:ring-1 hover:ring-gray-200'
            }`}
        >
            {/* Checkbox */}
            {status === 'pending' ? (
                <button
                    onClick={() => onToggleSelect(product.id)}
                    className={`w-5 h-5 rounded-md border-2 flex-shrink-0 flex items-center justify-center transition-all ${
                        selected ? 'bg-primary-olive border-primary-olive' : 'border-gray-300 hover:border-primary-olive'
                    }`}
                >
                    {selected && (
                        <svg className="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                        </svg>
                    )}
                </button>
            ) : (
                <div className="w-5 h-5 flex-shrink-0" />
            )}

            {/* Image */}
            <div className="w-14 h-14 rounded-lg overflow-hidden flex-shrink-0">
                {imageUrl ? (
                    <img src={imageUrl} alt={product.name} className="w-full h-full object-cover grayscale-[20%] group-hover:grayscale-0 transition-all duration-300" />
                ) : (
                    <div className="w-full h-full bg-gray-100 flex items-center justify-center">
                        <svg className="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                )}
            </div>

            {/* Name + meta */}
            <div className="flex-1 min-w-0">
                <p className="font-bold text-sm text-gray-900 truncate">{product.name}</p>
                <p className="text-xs text-gray-400 mt-0.5">{vendorName} · {categoryName}</p>
            </div>

            {/* Price */}
            <span className="text-sm font-bold text-primary-olive whitespace-nowrap w-24 text-right">{formatCurrency(product.price)}</span>

            {/* Status badge */}
            <div className="w-32 flex justify-center">
                <StatusBadge status={status} />
            </div>

            {/* Actions */}
            <div className="flex items-center gap-2 flex-shrink-0">
                <a
                    href={`/products/${product.slug}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="p-2 rounded-lg text-gray-400 hover:bg-primary-olive/10 hover:text-primary-olive transition-colors"
                    title="View"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </a>
                {status === 'pending' && (
                    <button
                        disabled={isProcessing}
                        onClick={() => onApprove(product.id)}
                        className="p-2 rounded-lg text-primary-olive hover:bg-primary-olive hover:text-white transition-all disabled:opacity-50"
                        title="Approve"
                    >
                        <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
                        </svg>
                    </button>
                )}
                {status === 'active' && (
                    <button
                        disabled={isProcessing}
                        onClick={() => onDeactivate(product.id)}
                        className="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition-all disabled:opacity-50"
                        title="Deactivate"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </button>
                )}
                {status !== 'flagged' ? (
                    <button
                        onClick={() => onFlag(product)}
                        className="p-2 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all"
                        title="Flag"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                        </svg>
                    </button>
                ) : (
                    <button
                        disabled={isProcessing}
                        onClick={() => onDeactivate(product.id)}
                        className="p-2 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition-all disabled:opacity-50"
                        title="Remove"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                )}
            </div>
        </div>
    );
}

function FlagModal({
    product,
    onClose,
    onSubmit,
}: {
    product: Product | null;
    onClose: () => void;
    onSubmit: (id: number, reason: string, notes: string) => void;
}) {
    const [selectedReason, setSelectedReason] = useState<string>(FLAG_REASONS[0]);
    const [notes, setNotes] = useState('');

    if (!product) return null;

    const handleSubmit = () => {
        onSubmit(product.id, selectedReason, notes);
        setNotes('');
        setSelectedReason(FLAG_REASONS[0]);
        onClose();
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onClick={onClose} />
            <div className="relative bg-white w-full max-w-lg rounded-xl shadow-2xl overflow-hidden animate-dropdown-in">
                <div className="p-8">
                    <div className="flex justify-between items-center mb-6">
                        <h3 className="text-2xl font-bold text-gray-900">Flag for Review</h3>
                        <button
                            onClick={onClose}
                            className="p-2 rounded-full hover:bg-gray-100 text-gray-400 transition-colors"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <p className="text-sm text-gray-500 mb-6">
                        Flagging{' '}
                        <span className="font-semibold text-gray-700">{product.name}</span>
                    </p>

                    <div className="space-y-6">
                        <div>
                            <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">
                                Reason for Flagging
                            </label>
                            <div className="space-y-2">
                                {FLAG_REASONS.map((reason) => (
                                    <button
                                        key={reason}
                                        onClick={() => setSelectedReason(reason)}
                                        className={`w-full flex items-center justify-between p-4 rounded-lg border-2 text-sm font-semibold text-left transition-colors ${
                                            selectedReason === reason
                                                ? 'border-primary-olive bg-primary-olive/5 text-primary-olive'
                                                : 'border-gray-100 text-gray-500 hover:border-primary-olive/40'
                                        }`}
                                    >
                                        {reason}
                                        {selectedReason === reason && (
                                            <svg className="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
                                            </svg>
                                        )}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div>
                            <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">
                                Internal Notes
                            </label>
                            <textarea
                                value={notes}
                                onChange={(e) => setNotes(e.target.value)}
                                rows={3}
                                placeholder="Provide more context for the vendor or legal team..."
                                className="w-full bg-gray-50 border border-gray-200/60 rounded-lg p-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20 placeholder:text-gray-300 resize-none"
                            />
                        </div>
                    </div>
                </div>

                <div className="bg-gray-50 px-8 py-5 flex justify-end gap-3 border-t border-gray-100">
                    <button
                        onClick={onClose}
                        className="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleSubmit}
                        className="bg-red-500 text-white px-8 py-2.5 rounded-lg text-sm font-bold shadow-lg shadow-red-500/20 hover:brightness-110 transition-all"
                    >
                        Flag Product
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function Products({ products, categories, totalPending }: Props) {
    const [viewMode, setViewMode] = useState<ViewMode>('grid');
    const [selected, setSelected] = useState<number[]>([]);
    const [flagging, setFlagging] = useState<Product | null>(null);
    const [flaggedIds, setFlaggedIds] = useState<Set<number>>(new Set());
    const [processing, setProcessing] = useState<number | null>(null);
    const [bulkProcessing, setBulkProcessing] = useState(false);

    const [status, setStatus] = useState('');
    const [category, setCategory] = useState('');
    const [search, setSearch] = useState('');
    const [sortBy, setSortBy] = useState('newest');

    const applyFilters = (overrides: { status?: string; category?: string; search?: string; sortBy?: string; page?: number } = {}) => {
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

    const handleFlagSubmit = (id: number, _reason: string, _notes: string) => {
        setFlaggedIds((prev) => new Set([...prev, id]));
        setSelected((prev) => prev.filter((x) => x !== id));
    };

    const handleUnflag = (id: number) => {
        setFlaggedIds((prev) => {
            const next = new Set(prev);
            next.delete(id);
            return next;
        });
    };

    const pendingOnPage = products.data.filter((p) => !p.is_active && !flaggedIds.has(p.id));

    const sharedCardProps = (product: Product) => ({
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
                                onChange={(e) => { setSearch(e.target.value); applyFilters({ search: e.target.value }); }}
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
                    <>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            {products.data.map((product) => (
                                <ProductCard key={product.id} {...sharedCardProps(product)} />
                            ))}
                        </div>
                    </>
                ) : (
                    <>
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
                    </>
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
