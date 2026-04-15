import { ProductActionProps } from '@/types/product';
import { formatCurrency } from '@/utils/currency';
import StatusBadge from './StatusBadge';

export default function ProductCard({
    product,
    status,
    selected,
    onToggleSelect,
    onApprove,
    onDeactivate,
    onFlag,
    onUnflag,
    processing,
}: ProductActionProps) {
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
