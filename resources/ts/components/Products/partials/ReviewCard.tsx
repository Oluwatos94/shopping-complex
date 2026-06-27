import { useState } from 'react';
import { VendorReview } from '@/types/product';

export default function ReviewCard({ review }: { review: VendorReview }) {
    const [helpfulVote, setHelpfulVote] = useState<boolean | null>(null);

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    return (
        <div className="border-b border-gray-200 pb-6 last:border-0 last:pb-0">
            {/* Review Header */}
            <div className="flex items-start gap-4">
                {/* Customer Avatar */}
                <div className="flex-shrink-0">
                    {review.customer?.profile_image ? (
                        <img
                            src={review.customer.profile_image}
                            alt={review.customer.name}
                            className="w-10 h-10 rounded-full object-cover"
                        />
                    ) : (
                        <div className="w-10 h-10 rounded-full bg-gradient-to-br from-brand-green to-brand-green-dark flex items-center justify-center">
                            <span className="text-white font-semibold">
                                {review.customer?.name?.charAt(0).toUpperCase() || 'U'}
                            </span>
                        </div>
                    )}
                </div>

                {/* Review Content */}
                <div className="flex-1 min-w-0">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <h4 className="font-semibold text-gray-900">
                                {review.customer?.name || 'Anonymous'}
                            </h4>
                            <div className="flex items-center gap-2 mt-0.5">
                                {/* Rating Stars */}
                                <div className="flex items-center">
                                    {[...Array(5)].map((_, i) => (
                                        <svg
                                            key={i}
                                            className={`w-4 h-4 ${
                                                i < review.rating
                                                    ? 'text-yellow-400 fill-current'
                                                    : 'text-gray-300'
                                            }`}
                                            viewBox="0 0 20 20"
                                        >
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    ))}
                                </div>
                                <span className="text-sm text-gray-500">
                                    {formatDate(review.created_at)}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Review Title */}
                    {review.title && (
                        <h5 className="font-medium text-gray-900 mt-3">
                            {review.title}
                        </h5>
                    )}

                    {/* Review Comment */}
                    {review.comment && (
                        <p className="text-gray-600 mt-2 leading-relaxed">
                            {review.comment}
                        </p>
                    )}

                    {/* Vendor Response */}
                    {review.vendor_response && (
                        <div className="mt-4 bg-gray-50 rounded-lg p-4 border-l-4 border-brand-green">
                            <div className="flex items-center gap-2 mb-2">
                                <span className="text-sm font-semibold text-brand-green">
                                    Vendor Response
                                </span>
                                {review.vendor_responded_at && (
                                    <span className="text-xs text-gray-500">
                                        {formatDate(review.vendor_responded_at)}
                                    </span>
                                )}
                            </div>
                            <p className="text-sm text-gray-600">
                                {review.vendor_response}
                            </p>
                        </div>
                    )}

                    {/* Helpful Votes */}
                    <div className="flex items-center gap-4 mt-4">
                        <span className="text-sm text-gray-500">Was this helpful?</span>
                        <div className="flex items-center gap-2">
                            <button
                                onClick={() => setHelpfulVote(helpfulVote === true ? null : true)}
                                className={`flex items-center gap-1 px-3 py-1.5 rounded-md text-sm transition-colors ${
                                    helpfulVote === true
                                        ? 'bg-green-100 text-green-700'
                                        : 'hover:bg-gray-100 text-gray-600'
                                }`}
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                </svg>
                                <span>Yes ({review.helpful_count + (helpfulVote === true ? 1 : 0)})</span>
                            </button>
                            <button
                                onClick={() => setHelpfulVote(helpfulVote === false ? null : false)}
                                className={`flex items-center gap-1 px-3 py-1.5 rounded-md text-sm transition-colors ${
                                    helpfulVote === false
                                        ? 'bg-red-100 text-red-700'
                                        : 'hover:bg-gray-100 text-gray-600'
                                }`}
                            >
                                <svg className="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                </svg>
                                <span>No ({review.not_helpful_count + (helpfulVote === false ? 1 : 0)})</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
