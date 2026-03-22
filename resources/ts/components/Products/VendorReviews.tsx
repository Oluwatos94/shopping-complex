import { useState } from 'react';
import { VendorReview, VendorRatingStats } from '@/types/product';

interface VendorReviewsProps {
    reviews: VendorReview[];
    stats: VendorRatingStats;
    vendorName: string;
    currentPage: number;
    lastPage: number;
    total: number;
    onPageChange: (page: number) => void;
}

export default function VendorReviews({
    reviews,
    stats,
    vendorName,
    currentPage,
    lastPage,
    total,
    onPageChange,
}: VendorReviewsProps) {
    const [sortBy, setSortBy] = useState<'newest' | 'highest' | 'lowest' | 'helpful'>('newest');

    // Sort reviews based on selected option
    const sortedReviews = [...reviews].sort((a, b) => {
        switch (sortBy) {
            case 'newest':
                return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
            case 'highest':
                return b.rating - a.rating;
            case 'lowest':
                return a.rating - b.rating;
            case 'helpful':
                return b.helpful_count - a.helpful_count;
            default:
                return 0;
        }
    });

    return (
        <div className="space-y-8">
            {/* Section Header */}
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-serif font-bold text-gray-900">
                        Vendor Reviews
                    </h2>
                    <p className="text-gray-600 mt-1">
                        Reviews for {vendorName} ({total} reviews)
                    </p>
                </div>
            </div>

            {/* Stats Summary */}
            <div className="bg-gray-50 rounded-xl p-6">
                <div className="flex flex-col lg:flex-row gap-8">
                    {/* Overall Rating */}
                    <div className="flex flex-col items-center lg:items-start">
                        <div className="text-5xl font-bold text-gray-900">
                            {stats.average.toFixed(1)}
                        </div>
                        <div className="flex items-center gap-1 mt-2">
                            {[...Array(5)].map((_, i) => (
                                <svg
                                    key={i}
                                    className={`w-5 h-5 ${
                                        i < Math.floor(stats.average)
                                            ? 'text-yellow-400 fill-current'
                                            : i < stats.average
                                            ? 'text-yellow-400 fill-current opacity-50'
                                            : 'text-gray-300'
                                    }`}
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            ))}
                        </div>
                        <p className="text-sm text-gray-500 mt-1">
                            Based on {stats.count} reviews
                        </p>
                    </div>

                    {/* Rating Distribution */}
                    <div className="flex-1 space-y-2">
                        {[5, 4, 3, 2, 1].map((rating) => {
                            const count = stats.distribution[rating as keyof typeof stats.distribution];
                            const percentage = stats.count > 0 ? (count / stats.count) * 100 : 0;

                            return (
                                <div key={rating} className="flex items-center gap-3">
                                    <span className="text-sm text-gray-600 w-8">{rating}</span>
                                    <svg className="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <div className="flex-1 h-2.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-yellow-400 rounded-full transition-all duration-500"
                                            style={{ width: `${percentage}%` }}
                                        />
                                    </div>
                                    <span className="text-sm text-gray-500 w-12 text-right">
                                        {count}
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>

            {/* Sort Options */}
            <div className="flex items-center justify-between">
                <p className="text-sm text-gray-500">
                    Showing {reviews.length} of {total} reviews
                </p>
                <select
                    value={sortBy}
                    onChange={(e) => setSortBy(e.target.value as typeof sortBy)}
                    className="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent"
                >
                    <option value="newest">Most Recent</option>
                    <option value="highest">Highest Rated</option>
                    <option value="lowest">Lowest Rated</option>
                    <option value="helpful">Most Helpful</option>
                </select>
            </div>

            {/* Reviews List */}
            <div className="space-y-6">
                {sortedReviews.length > 0 ? (
                    sortedReviews.map((review) => (
                        <ReviewCard key={review.id} review={review} />
                    ))
                ) : (
                    <div className="text-center py-12 bg-gray-50 rounded-xl">
                        <svg className="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <h3 className="text-lg font-medium text-gray-900 mb-1">No reviews yet</h3>
                        <p className="text-gray-500">Be the first to review this vendor!</p>
                    </div>
                )}
            </div>

            {/* Pagination */}
            {lastPage > 1 && (
                <div className="flex justify-center">
                    <nav className="flex items-center gap-2">
                        <button
                            onClick={() => onPageChange(currentPage - 1)}
                            disabled={currentPage === 1}
                            className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Previous
                        </button>

                        {[...Array(lastPage)].map((_, i) => {
                            const page = i + 1;
                            const isCurrentPage = page === currentPage;
                            const showPage =
                                page === 1 ||
                                page === lastPage ||
                                (page >= currentPage - 1 && page <= currentPage + 1);

                            if (!showPage) {
                                if (page === currentPage - 2 || page === currentPage + 2) {
                                    return <span key={page} className="px-2 text-gray-400">...</span>;
                                }
                                return null;
                            }

                            return (
                                <button
                                    key={page}
                                    onClick={() => onPageChange(page)}
                                    className={`px-4 py-2 border rounded-md transition-colors ${
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
                            onClick={() => onPageChange(currentPage + 1)}
                            disabled={currentPage === lastPage}
                            className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Next
                        </button>
                    </nav>
                </div>
            )}
        </div>
    );
}

// Review Card Component
function ReviewCard({ review }: { review: VendorReview }) {
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
                        <div className="w-10 h-10 rounded-full bg-primary-peach flex items-center justify-center">
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
                        <div className="mt-4 bg-gray-50 rounded-lg p-4 border-l-4 border-primary-olive">
                            <div className="flex items-center gap-2 mb-2">
                                <span className="text-sm font-semibold text-primary-olive">
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
