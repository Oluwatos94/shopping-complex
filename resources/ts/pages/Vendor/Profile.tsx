import { useState, useCallback } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { PaginatedProducts, Product, VendorReview, PaginatedVendorReviews } from '@/types/product';
import { VendorProfile, VendorStats } from '@/types/vendor';
import VendorSidebar from '@/components/VendorSidebar';
import { getCsrfToken } from '@/utils/csrf';
import UploadProductFab from './partials/UploadProductFab';
import EditProfileModal from './partials/EditProfileModal';
import ReviewCard from '@/components/Vendors/ReviewCard';

interface Props {
    vendor: VendorProfile;
    products: PaginatedProducts;
    stats: VendorStats;
    vendor_reviews: PaginatedVendorReviews;
    can_review: boolean;
    has_reviewed: boolean;
    isOwner: boolean;
    isFollowing: boolean;
}

export default function VendorProfilePage({
    vendor,
    products,
    stats,
    vendor_reviews,
    can_review,
    has_reviewed,
    isOwner,
    isFollowing: initialIsFollowing,
}: Props) {
    const { auth, geoapify_key: geoapifyKey } = usePage<{ auth: { user: { id: number; role: string } | null }; geoapify_key?: string }>().props;

    const [isFollowing, setIsFollowing] = useState(initialIsFollowing);
    const [followersCount, setFollowersCount] = useState(stats.followers_count);
    const [followLoading, setFollowLoading] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
    const [isEditOpen, setIsEditOpen] = useState(false);

    // Review form state
    const [reviewRating, setReviewRating] = useState(0);
    const [reviewHover, setReviewHover] = useState(0);
    const [reviewComment, setReviewComment] = useState('');
    const [reviewTitle, setReviewTitle] = useState('');
    const [reviewSubmitting, setReviewSubmitting] = useState(false);
    const [reviewDone, setReviewDone] = useState(false);
    const [reviewError, setReviewError] = useState<string | null>(null);

    const whatsAppHref = vendor.whatsapp_number
        ? `https://wa.me/${vendor.whatsapp_number.replace(/\D/g, '')}?text=${encodeURIComponent(`Hi ${vendor.business_name}, I found you on Shopping Complex.`)}`
        : null;

    const handleToggleFollow = useCallback(async () => {
        if (followLoading) return;

        if (!auth?.user) {
            router.visit('/login', { data: { redirect: `/vendors/${vendor.slug}` } });
            return;
        }

        setFollowLoading(true);
        try {
            const res = await fetch(`/vendors/${vendor.slug}/follow`, {
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
            });

            if (res.ok) {
                const data = await res.json();
                setIsFollowing(data.following);
                setFollowersCount(data.followers_count);
            }
        } catch {
            // silently fail
        } finally {
            setFollowLoading(false);
        }
    }, [vendor.id, vendor.slug, followLoading, auth]);

    const handleReviewSubmit = useCallback(async (e: React.FormEvent) => {
        e.preventDefault();
        if (!reviewRating || reviewSubmitting) return;

        setReviewSubmitting(true);
        setReviewError(null);

        try {
            const res = await fetch('/reviews', {
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    vendor_id: vendor.id,
                    rating: reviewRating,
                    title: reviewTitle || null,
                    comment: reviewComment || null,
                }),
            });

            if (res.ok) {
                setReviewDone(true);
            } else {
                const data = await res.json();
                setReviewError(data.message ?? 'Failed to submit review. Please try again.');
            }
        } catch {
            setReviewError('Something went wrong. Please try again.');
        } finally {
            setReviewSubmitting(false);
        }
    }, [vendor.id, reviewRating, reviewTitle, reviewComment, reviewSubmitting]);

    const reviews: VendorReview[] = vendor_reviews.reviews;

    return (
        <>
            <Head title={`${vendor.business_name} - Shopping Complex`} />

            <div className="min-h-screen bg-gray-50">
                {isOwner && <VendorSidebar vendorSlug={vendor.slug} businessName={vendor.business_name} businessLogo={vendor.business_logo} />}

                <div className={isOwner ? 'md:ml-[100px]' : ''}>
                    {/* Cover / Header Area */}
                    <div className="bg-primary-dark">
                        {/* Top Nav (visitors only) */}
                        {!isOwner && (
                            <div className="container mx-auto px-4 py-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <button
                                            onClick={() => window.history.back()}
                                            className="flex items-center justify-center w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 transition-colors text-white"
                                            aria-label="Go back"
                                        >
                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </button>
                                        <Link href="/" className="flex items-center space-x-3">
                                            <img src="/logo/dark-mode-logo.svg" alt="Shopping Complex" className="h-8 w-auto" />
                                            <span className="text-primary-light font-bold text-lg hidden sm:block">Shopping Complex</span>
                                        </Link>
                                    </div>
                                    <Link href="/vendors" className="text-sm text-primary-light hover:text-white transition-colors">
                                        Browse Vendors
                                    </Link>
                                </div>
                            </div>
                        )}

                        {/* Profile Section */}
                        <div className="pb-8 pt-6">
                            <div className="flex justify-center">
                                <div className="w-28 h-28 rounded-full border-4 border-white overflow-hidden bg-gray-200 shadow-lg">
                                    {vendor.business_logo ? (
                                        <img src={vendor.business_logo} alt={vendor.business_name} className="w-full h-full object-cover" />
                                    ) : (
                                        <div className="w-full h-full bg-primary-olive flex items-center justify-center">
                                            <span className="text-white text-3xl font-bold">{vendor.business_name.charAt(0).toUpperCase()}</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="text-center mt-4">
                                <div className="flex items-center justify-center gap-2">
                                    <h1 className="text-2xl font-serif font-bold text-white">{vendor.business_name}</h1>
                                    {vendor.is_verified && (
                                        <svg className="w-5 h-5 text-primary-peach" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                        </svg>
                                    )}
                                </div>
                                {(vendor.city || vendor.state) && (
                                    <p className="text-sm text-primary-light mt-1">{[vendor.city, vendor.state].filter(Boolean).join(', ')}</p>
                                )}
                            </div>

                            <div className="flex justify-center gap-10 mt-6">
                                <div className="text-center">
                                    <p className="text-xl font-bold text-white">{stats.products_count}</p>
                                    <p className="text-xs text-primary-light">Products</p>
                                </div>
                                <div className="text-center">
                                    <p className="text-xl font-bold text-white">{stats.reviews_count}</p>
                                    <p className="text-xs text-primary-light">Reviews</p>
                                </div>
                                <div className="text-center">
                                    <div className="flex items-center justify-center gap-1">
                                        <svg className="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        <p className="text-xl font-bold text-white">
                                            {stats.average_rating > 0 ? stats.average_rating.toFixed(1) : '-'}
                                        </p>
                                    </div>
                                    <p className="text-xs text-primary-light">Rating</p>
                                </div>
                            </div>

                            <div className="flex justify-center gap-3 mt-6">
                                {isOwner ? (
                                    <>
                                        <div className="px-8 py-2 bg-primary-peach/20 text-primary-light rounded-lg font-semibold text-sm">
                                            {followersCount} {followersCount === 1 ? 'Follower' : 'Followers'}
                                        </div>
                                        <button
                                            onClick={() => setIsEditOpen(true)}
                                            className="px-6 py-2 bg-white/10 text-white border border-white/40 rounded-lg font-semibold text-sm hover:bg-white/20 transition-all flex items-center gap-2"
                                        >
                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit Profile
                                        </button>
                                    </>
                                ) : (
                                    <button
                                        onClick={handleToggleFollow}
                                        disabled={followLoading}
                                        className={`px-8 py-2 rounded-lg font-semibold text-sm transition-all disabled:opacity-50 ${
                                            isFollowing
                                                ? 'bg-white/10 text-white border border-white/40 hover:bg-white/20'
                                                : 'bg-white text-primary-dark hover:bg-white/20 hover:text-white'
                                        }`}
                                    >
                                        {followLoading ? '...' : isFollowing ? 'Following' : 'Follow'}
                                    </button>
                                )}
                                {!isOwner && whatsAppHref && (
                                    <a
                                        href={whatsAppHref}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="px-8 py-2 border border-white/40 text-white rounded-lg font-semibold text-sm hover:bg-white/10 transition-all"
                                    >
                                        Message
                                    </a>
                                )}
                            </div>

                            {vendor.business_description && (
                                <div className="max-w-lg mx-auto mt-5 px-4">
                                    <p className="text-sm text-primary-light text-center leading-relaxed">
                                        {vendor.business_description}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Tab bar */}
                    <div className="border-b border-gray-200 bg-white">
                        <div className="container mx-auto px-4">
                            <div className="flex justify-center">
                                <button className="px-6 py-3 text-sm font-semibold text-primary-dark border-b-2 border-primary-olive">
                                    <span className="flex items-center gap-2">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                        Products
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Products Grid */}
                    <div className="container mx-auto px-4 py-8">
                        {products.data.length > 0 ? (
                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                {products.data.map((product) => {
                                    const primaryMedia = product.images?.find((img) => img.is_primary) ?? product.images?.[0];
                                    const primaryImage = primaryMedia?.url ?? '/images/placeholder.png';
                                    const isVideo = primaryMedia?.type === 'product_video';
                                    const price = Number(product.price);
                                    const salePrice = product.sale_price ? Number(product.sale_price) : null;
                                    const hasDiscount = salePrice && salePrice < price;

                                    return (
                                        <div key={product.id} className="relative group">
                                            <Link
                                                href={`/products/${product.slug}`}
                                                className="block bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow"
                                            >
                                                <div className="relative aspect-square overflow-hidden bg-gray-100">
                                                    {isVideo ? (
                                                        <video src={primaryImage} className="w-full h-full object-cover" muted playsInline preload="metadata" />
                                                    ) : (
                                                        <img src={primaryImage} alt={product.name} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                                    )}
                                                    {hasDiscount && salePrice && (
                                                        <span className="absolute top-2 left-2 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded">
                                                            {Math.round(((price - salePrice) / price) * 100)}% OFF
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="p-3">
                                                    <h3 className="text-sm font-medium text-gray-900 line-clamp-2 group-hover:text-primary-olive transition-colors">
                                                        {product.name}
                                                    </h3>
                                                    <div className="mt-1">
                                                        {hasDiscount && salePrice ? (
                                                            <div className="flex items-baseline gap-1">
                                                                <span className="text-sm font-bold text-gray-900">₦{salePrice.toLocaleString()}</span>
                                                                <span className="text-xs text-gray-400 line-through">₦{price.toLocaleString()}</span>
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm font-bold text-gray-900">₦{price.toLocaleString()}</span>
                                                        )}
                                                    </div>
                                                </div>
                                            </Link>
                                            {isOwner && (
                                                <button
                                                    onClick={() => setSelectedProduct(product)}
                                                    className="absolute top-2 right-2 w-8 h-8 bg-white/90 rounded-full shadow-md flex items-center justify-center hover:bg-white transition-colors opacity-0 group-hover:opacity-100 z-10"
                                                    title="Edit product"
                                                >
                                                    <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="text-center py-16">
                                <svg className="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <p className="text-gray-500 text-lg">No products yet</p>
                                <p className="text-gray-400 text-sm mt-1">
                                    {isOwner ? 'Start adding products to your store.' : "This vendor hasn't listed any products."}
                                </p>
                            </div>
                        )}

                        {/* Pagination */}
                        {products.last_page > 1 && (
                            <div className="flex justify-center gap-2 mt-8">
                                {Array.from({ length: products.last_page }, (_, i) => i + 1).map((page) => (
                                    <Link
                                        key={page}
                                        href={`/vendors/${vendor.slug}?page=${page}`}
                                        className={`w-10 h-10 flex items-center justify-center rounded-lg text-sm font-medium transition-colors ${
                                            page === products.current_page
                                                ? 'bg-primary-olive text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                                        }`}
                                    >
                                        {page}
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Reviews Section */}
                    <div className="border-t border-gray-200 bg-white">
                        <div className="container mx-auto px-4 py-8">
                            <h2 className="text-xl font-serif font-bold text-gray-900 mb-6">
                                Reviews {stats.reviews_count > 0 && <span className="text-base font-normal text-gray-500">({stats.reviews_count})</span>}
                            </h2>

                            {/* Write a Review Form */}
                            {!isOwner && (
                                <div className="mb-8">
                                    {!auth?.user ? (
                                        <div className="bg-gray-50 rounded-xl p-6 text-center border border-gray-200">
                                            <p className="text-gray-600 mb-3">Sign in to leave a review</p>
                                            <Link
                                                href={`/login?redirect=/vendors/${vendor.slug}`}
                                                className="inline-block bg-primary-olive text-white text-sm font-semibold px-6 py-2.5 rounded-lg hover:bg-primary-dark transition-colors"
                                            >
                                                Sign in
                                            </Link>
                                        </div>
                                    ) : reviewDone ? (
                                        <div className="bg-green-50 border border-green-200 rounded-xl p-5 text-center">
                                            <svg className="w-10 h-10 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p className="font-semibold text-green-800">Review submitted!</p>
                                            <p className="text-sm text-green-600 mt-1">It will appear after moderation.</p>
                                        </div>
                                    ) : has_reviewed ? (
                                        <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                                            You have already reviewed this vendor.
                                        </div>
                                    ) : can_review ? (
                                        <form onSubmit={handleReviewSubmit} className="bg-gray-50 rounded-xl p-6 border border-gray-200 space-y-4">
                                            <h3 className="font-semibold text-gray-900">Write a Review</h3>

                                            {/* Star Rating */}
                                            <div>
                                                <p className="text-sm text-gray-600 mb-2">Your rating <span className="text-red-500">*</span></p>
                                                <div className="flex gap-1">
                                                    {[1, 2, 3, 4, 5].map((star) => (
                                                        <button
                                                            key={star}
                                                            type="button"
                                                            onClick={() => setReviewRating(star)}
                                                            onMouseEnter={() => setReviewHover(star)}
                                                            onMouseLeave={() => setReviewHover(0)}
                                                            className="focus:outline-none"
                                                        >
                                                            <svg
                                                                className={`w-8 h-8 transition-colors ${
                                                                    star <= (reviewHover || reviewRating)
                                                                        ? 'text-yellow-400 fill-current'
                                                                        : 'text-gray-300'
                                                                }`}
                                                                viewBox="0 0 20 20"
                                                            >
                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                            </svg>
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>

                                            {/* Title */}
                                            <input
                                                type="text"
                                                value={reviewTitle}
                                                onChange={(e) => setReviewTitle(e.target.value)}
                                                placeholder="Review title (optional)"
                                                maxLength={255}
                                                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50"
                                            />

                                            {/* Comment */}
                                            <textarea
                                                value={reviewComment}
                                                onChange={(e) => setReviewComment(e.target.value)}
                                                placeholder="Share your experience with this vendor..."
                                                rows={3}
                                                maxLength={2000}
                                                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-primary-olive/50"
                                            />

                                            {reviewError && (
                                                <p className="text-sm text-red-600">{reviewError}</p>
                                            )}

                                            <button
                                                type="submit"
                                                disabled={!reviewRating || reviewSubmitting}
                                                className="bg-primary-olive text-white text-sm font-semibold px-6 py-2.5 rounded-lg hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                {reviewSubmitting ? 'Submitting...' : 'Submit Review'}
                                            </button>
                                        </form>
                                    ) : (
                                        <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800 flex items-start gap-3">
                                            <svg className="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>You need to message this vendor on WhatsApp before leaving a review.</span>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Existing Reviews */}
                            {reviews.length > 0 ? (
                                <div className="space-y-6">
                                    {reviews.map((review) => (
                                        <ReviewCard key={review.id} review={review} />
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-10 text-gray-400">
                                    <svg className="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <p className="text-sm">No reviews yet</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {isOwner && (
                    <UploadProductFab
                        productLimit={stats.plan_product_limit}
                        activeProductsCount={stats.products_count}
                        editProduct={selectedProduct}
                        onEditClose={() => setSelectedProduct(null)}
                    />
                )}
            </div>

            {isEditOpen && (
                <EditProfileModal
                    vendor={vendor}
                    geoapifyKey={geoapifyKey}
                    onClose={() => setIsEditOpen(false)}
                />
            )}
        </>
    );
}
