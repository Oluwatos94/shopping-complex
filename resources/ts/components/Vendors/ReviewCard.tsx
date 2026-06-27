import { VendorReview } from '@/types/product';

export default function ReviewCard({ review }: { review: VendorReview }) {
    const formatDate = (d: string) =>
        new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

    return (
        <div className="border-b border-gray-100 pb-6 last:border-0 last:pb-0">
            <div className="flex items-start gap-3">
                <div className="flex-shrink-0 w-9 h-9 rounded-full bg-brand-green flex items-center justify-center">
                    <span className="text-white text-sm font-semibold">
                        {review.customer?.name?.charAt(0).toUpperCase() ?? 'U'}
                    </span>
                </div>
                <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-2">
                        <span className="font-semibold text-sm text-gray-900">{review.customer?.name ?? 'Anonymous'}</span>
                        <span className="text-xs text-gray-400">{formatDate(review.created_at)}</span>
                    </div>
                    <div className="flex items-center gap-0.5 mt-1">
                        {[1, 2, 3, 4, 5].map((s) => (
                            <svg key={s} className={`w-3.5 h-3.5 ${s <= review.rating ? 'text-yellow-400 fill-current' : 'text-gray-200'}`} viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        ))}
                    </div>
                    {review.title && <p className="text-sm font-medium text-gray-800 mt-2">{review.title}</p>}
                    {review.comment && <p className="text-sm text-gray-600 mt-1 leading-relaxed">{review.comment}</p>}
                    {review.vendor_response && (
                        <div className="mt-3 bg-gray-50 rounded-lg p-3 border-l-4 border-brand-green">
                            <p className="text-xs font-semibold text-brand-green mb-1">Vendor response</p>
                            <p className="text-xs text-gray-600">{review.vendor_response}</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
