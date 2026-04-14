import { ProductActionProps } from '@/types/product';
import { formatCurrency } from '@/utils/currency';
import StatusBadge from './StatusBadge';

export default function ProductRow({
    product,
    status,
    selected,
    onToggleSelect,
    onApprove,
    onDeactivate,
    onFlag,
    processing,
}: ProductActionProps) {
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
