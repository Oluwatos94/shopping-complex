import { useState, useCallback, useRef, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { PaginatedProducts, Product, VendorReview, PaginatedVendorReviews } from '@/types/product';
import { VendorProfile, VendorStats } from '@/types/vendor';
import VendorSidebar from '@/components/VendorSidebar';
import { getCsrfToken } from '@/utils/csrf';
import { recordVendorContact } from '@/utils/contact';
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
    const { auth } = usePage<{ auth: { user: { id: number; role: string } | null } }>().props;

    const [isFollowing, setIsFollowing] = useState(initialIsFollowing);
    const [followersCount, setFollowersCount] = useState(stats.followers_count);
    const [followLoading, setFollowLoading] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
    const [isEditOpen, setIsEditOpen] = useState(() => new URLSearchParams(window.location.search).get('edit') === '1');
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const [bannerLightboxOpen, setBannerLightboxOpen] = useState(false);

    const [descExpanded, setDescExpanded] = useState(false);
    const [descOverflows, setDescOverflows] = useState(false);
    const descRef = useRef<HTMLParagraphElement>(null);

    useEffect(() => {
        const el = descRef.current;
        if (el) {
            setDescOverflows(el.scrollHeight > el.clientHeight + 1);
        }
    }, [vendor.business_description]);

    // Review form state
    const [reviewRating, setReviewRating] = useState(0);
    const [reviewHover, setReviewHover] = useState(0);
    const [reviewComment, setReviewComment] = useState('');
    const [reviewTitle, setReviewTitle] = useState('');
    const [reviewSubmitting, setReviewSubmitting] = useState(false);
    const [reviewDone, setReviewDone] = useState(false);
    const [reviewError, setReviewError] = useState<string | null>(null);

    const whatsAppHref = vendor.whatsapp_number
        ? `https://wa.me/${vendor.whatsapp_number.replace(/\D/g, '')}?text=${encodeURIComponent(`Hi ${vendor.business_name}, I found you on jiidaa.`)}`
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
                router.reload({ only: ['vendor_reviews', 'stats', 'can_review', 'has_reviewed'] });
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

    const location = [vendor.city, vendor.state].filter(Boolean).join(', ');

    return (
        <>
            <Head title={`${vendor.business_name} - jiidaa`} />

            <div className="min-h-screen bg-brand-surface font-display text-brand-ink">
                {isOwner && <VendorSidebar businessName={vendor.business_name} businessLogo={vendor.business_logo} />}

                <div className={isOwner ? 'md:ml-[260px]' : ''}>
                    {/* Top bar (visitors only — owners get the sidebar) */}
                    {!isOwner && (
                        <div className="bg-white border-b border-brand-line">
                            <div className="px-4 sm:px-10 h-16 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <button
                                        onClick={() => window.history.back()}
                                        className="w-9 h-9 flex items-center justify-center rounded-full border border-brand-line text-brand-ink hover:bg-brand-surface transition-colors flex-none"
                                        aria-label="Go back"
                                    >
                                        <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                            <path d="M15 18l-6-6 6-6" />
                                        </svg>
                                    </button>
                                    <Link href="/" className="flex items-center">
                                        <img src="/logo/Logo.svg" alt="jiidaa" className="h-9 w-auto" />
                                    </Link>
                                </div>
                                <Link href="/vendors" className="text-sm font-semibold text-brand-muted hover:text-brand-ink transition-colors">
                                    Browse Vendors
                                </Link>
                            </div>
                        </div>
                    )}

                    {/* Banner strip */}
                    <div className="px-4 sm:px-10 pt-6">
                        <div className="relative h-44 sm:h-56 rounded-3xl overflow-hidden bg-gradient-to-br from-[#16243A] via-[#22384F] to-[#33526E]">
                            {vendor.banner_image && (
                                <img
                                    src={vendor.banner_image}
                                    alt="Store banner"
                                    onClick={() => setBannerLightboxOpen(true)}
                                    className="absolute inset-0 w-full h-full object-cover cursor-pointer transition-opacity hover:opacity-90"
                                />
                            )}
                            {isOwner && (
                                <button
                                    onClick={() => setIsEditOpen(true)}
                                    className="absolute top-4 right-4 inline-flex items-center gap-2 bg-brand-ink/60 backdrop-blur-sm border border-white/20 text-white px-4 py-2 rounded-full text-sm font-semibold hover:bg-brand-ink/80 transition-colors"
                                    title="Change banner"
                                >
                                    <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" />
                                        <circle cx="9" cy="9" r="2" />
                                        <path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21" />
                                    </svg>
                                    {vendor.banner_image ? 'Change banner' : 'Add banner'}
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Profile card */}
                    <div className="px-4 sm:px-10">
                        <div className="relative -mt-12 bg-white border border-brand-line rounded-3xl shadow-[0_8px_30px_rgba(11,31,58,0.07)] px-5 sm:px-8 pb-7 flex flex-col sm:flex-row items-center sm:items-end gap-5 sm:gap-7 text-center sm:text-left">
                            {/* Avatar */}
                            <div className="-mt-14 sm:-mt-12 flex-none">
                                <button
                                    type="button"
                                    onClick={() => vendor.business_logo && setLightboxOpen(true)}
                                    className={`w-28 h-28 rounded-full border-4 border-white overflow-hidden shadow-[0_8px_22px_rgba(11,31,58,0.18)] ${vendor.business_logo ? 'cursor-pointer hover:opacity-90 transition-opacity' : 'cursor-default'}`}
                                    aria-label="View profile photo"
                                >
                                    {vendor.business_logo ? (
                                        <img src={vendor.business_logo} alt={vendor.business_name} className="w-full h-full object-cover" />
                                    ) : (
                                        <div className="w-full h-full bg-gradient-to-br from-brand-green to-brand-green-dark flex items-center justify-center">
                                            <span className="text-white text-4xl font-extrabold">{vendor.business_name.charAt(0).toUpperCase()}</span>
                                        </div>
                                    )}
                                </button>
                            </div>

                            {/* Identity + actions */}
                            <div className="flex-1 min-w-0 w-full pt-1 sm:pt-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">
                                <div className="min-w-0">
                                    <div className="flex items-center justify-center sm:justify-start gap-2">
                                        <h1 className="font-serif text-3xl font-bold tracking-tight text-brand-ink">{vendor.business_name}</h1>
                                        {vendor.is_verified && (
                                            <svg className="w-5 h-5 text-brand-green flex-none" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                            </svg>
                                        )}
                                    </div>

                                    {location && (
                                        <div className="mt-1.5 inline-flex items-center gap-1.5 text-sm text-brand-muted">
                                            <svg className="w-4 h-4 text-brand-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                                                <circle cx="12" cy="10" r="2.6" />
                                            </svg>
                                            {location}
                                        </div>
                                    )}

                                    {/* Stats */}
                                    <div className="mt-4 flex justify-center sm:justify-start gap-6 sm:gap-10">
                                        <div className="flex flex-col gap-0.5">
                                            <span className="text-2xl font-extrabold leading-none text-brand-ink">{stats.products_count}</span>
                                            <span className="text-xs font-medium text-brand-muted">Products</span>
                                        </div>
                                        <div className="flex flex-col gap-0.5">
                                            <span className="text-2xl font-extrabold leading-none text-brand-ink">{followersCount}</span>
                                            <span className="text-xs font-medium text-brand-muted">{followersCount === 1 ? 'Follower' : 'Followers'}</span>
                                        </div>
                                        <div className="flex flex-col gap-0.5">
                                            <span className="text-2xl font-extrabold leading-none text-brand-ink">{stats.reviews_count}</span>
                                            <span className="text-xs font-medium text-brand-muted">Reviews</span>
                                        </div>
                                        <div className="flex flex-col gap-0.5">
                                            <span className="inline-flex items-center gap-1 text-2xl font-extrabold leading-none text-brand-ink">
                                                <svg className="w-5 h-5 text-brand-star fill-current" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                                {stats.average_rating > 0 ? stats.average_rating.toFixed(1) : '–'}
                                            </span>
                                            <span className="text-xs font-medium text-brand-muted">Rating</span>
                                        </div>
                                    </div>
                                </div>

                                {/* Action buttons */}
                                <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto flex-none">
                                    {isOwner ? (
                                        <>
                                            <button
                                                onClick={() => setIsEditOpen(true)}
                                                className="inline-flex items-center justify-center gap-2 bg-white border border-brand-line text-brand-ink px-5 py-3 rounded-full text-sm font-bold hover:border-brand-ink transition-colors"
                                            >
                                                <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z" />
                                                </svg>
                                                Edit Profile
                                            </button>
                                        </>
                                    ) : (
                                        <>
                                            <button
                                                onClick={handleToggleFollow}
                                                disabled={followLoading}
                                                className={`inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full text-sm font-bold transition-colors disabled:opacity-50 ${
                                                    isFollowing
                                                        ? 'bg-white border border-brand-line text-brand-ink hover:border-brand-ink'
                                                        : 'bg-brand-green text-white hover:bg-brand-green-dark'
                                                }`}
                                            >
                                                <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                                    <circle cx="9" cy="7" r="4" />
                                                    {!isFollowing && <path d="M19 8v6M22 11h-6" />}
                                                </svg>
                                                {followLoading ? '...' : isFollowing ? 'Following' : 'Follow'}
                                            </button>
                                            {whatsAppHref && (
                                                <a
                                                    href={whatsAppHref}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    onClick={() => recordVendorContact(vendor.slug)}
                                                    className="inline-flex items-center justify-center gap-2 bg-white border border-brand-line text-brand-ink px-6 py-3 rounded-full text-sm font-bold hover:border-brand-ink transition-colors"
                                                >
                                                    <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z" />
                                                    </svg>
                                                    Message
                                                </a>
                                            )}
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>

                        {vendor.business_description && (
                            <div className="mt-4 bg-white border border-brand-line rounded-2xl shadow-[0_8px_30px_rgba(11,31,58,0.07)] px-5 sm:px-7 py-5">
                                <p
                                    ref={descRef}
                                    className={`text-[15px] leading-relaxed text-brand-muted whitespace-pre-line ${descExpanded ? '' : 'line-clamp-3'}`}
                                >
                                    {vendor.business_description}
                                </p>
                                {descOverflows && (
                                    <button
                                        onClick={() => setDescExpanded((v) => !v)}
                                        className="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-brand-green hover:text-brand-green-dark transition-colors"
                                    >
                                        {descExpanded ? 'Show less' : 'Show more'}
                                        <svg
                                            className={`w-4 h-4 transition-transform ${descExpanded ? 'rotate-180' : ''}`}
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.4} strokeLinecap="round" strokeLinejoin="round"
                                        >
                                            <path d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Tabs */}
                    <div className="mt-7 bg-white border-b border-brand-line">
                        <div className="flex justify-center">
                            <button className="inline-flex items-center gap-2 px-2 py-5 border-b-[3px] border-brand-green text-base font-bold text-brand-ink">
                                <svg className="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                    <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                    <rect x="14" y="14" width="7" height="7" rx="1.5" />
                                    <rect x="3" y="14" width="7" height="7" rx="1.5" />
                                </svg>
                                Products
                            </button>
                        </div>
                    </div>

                    {/* Products grid */}
                    <div className="px-4 sm:px-10 py-9">
                        {products.data.length > 0 ? (
                            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-5">
                                {products.data.map((product) => {
                                    const primaryMedia = product.images?.find((img) => img.is_primary) ?? product.images?.[0];
                                    const primaryImage = primaryMedia?.url ?? '/images/placeholder.png';
                                    const isVideo = primaryMedia?.type === 'product_video';
                                    const price = Number(product.price);

                                    return (
                                        <div key={product.id} className="relative group">
                                            <Link
                                                href={`/products/${product.slug}`}
                                                className="flex flex-col bg-white border border-brand-line rounded-2xl overflow-hidden transition duration-150 hover:-translate-y-1 hover:border-[#D7DCE3] hover:shadow-[0_18px_38px_rgba(11,31,58,0.12)]"
                                            >
                                                <div className="relative aspect-square overflow-hidden bg-brand-surface">
                                                    {isVideo ? (
                                                        <>
                                                            <video src={primaryImage} className="w-full h-full object-cover" muted playsInline preload="metadata" />
                                                            <div className="absolute inset-0 flex items-center justify-center bg-black/20">
                                                                <div className="w-10 h-10 bg-black/60 rounded-full flex items-center justify-center">
                                                                    <svg className="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                                                        <path d="M8 5v14l11-7z" />
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </>
                                                    ) : (
                                                        <img src={primaryImage} alt={product.name} className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                                    )}
                                                </div>
                                                <div className="p-4 flex flex-col gap-1.5">
                                                    <h3 className="text-[15px] font-semibold leading-snug text-brand-ink line-clamp-2">
                                                        {product.name}
                                                    </h3>
                                                    <span className="text-lg font-extrabold text-brand-ink">₦{price.toLocaleString()}</span>
                                                </div>
                                            </Link>
                                            {isOwner && (
                                                <button
                                                    onClick={() => setSelectedProduct(product)}
                                                    className="absolute top-2.5 right-2.5 w-8 h-8 bg-white/90 rounded-full shadow-md flex items-center justify-center text-brand-muted hover:text-brand-ink hover:bg-white transition-colors opacity-0 group-hover:opacity-100 z-10"
                                                    title="Edit product"
                                                >
                                                    <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z" />
                                                    </svg>
                                                </button>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="text-center py-16">
                                <svg className="w-16 h-16 text-brand-line mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <p className="text-brand-ink text-lg font-semibold">No products yet</p>
                                <p className="text-brand-muted text-sm mt-1">
                                    {isOwner ? 'Start adding products to your store.' : "This vendor hasn't listed any products."}
                                </p>
                            </div>
                        )}

                        {/* Pagination */}
                        {products.last_page > 1 && (
                            <div className="flex justify-center gap-2 mt-9">
                                {Array.from({ length: products.last_page }, (_, i) => i + 1).map((page) => (
                                    <Link
                                        key={page}
                                        href={`/vendors/${vendor.slug}?page=${page}`}
                                        className={`w-10 h-10 flex items-center justify-center rounded-xl text-sm font-semibold transition-colors ${
                                            page === products.current_page
                                                ? 'bg-brand-green text-white'
                                                : 'bg-white text-brand-ink hover:bg-brand-surface border border-brand-line'
                                        }`}
                                    >
                                        {page}
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Reviews */}
                    <div className="border-t border-brand-line bg-white">
                        <div className="max-w-5xl mx-auto px-4 sm:px-10 py-10">
                            <h2 className="font-serif text-2xl font-bold text-brand-ink mb-6">
                                Reviews {stats.reviews_count > 0 && <span className="text-base font-normal text-brand-muted">({stats.reviews_count})</span>}
                            </h2>

                            {/* Write a Review */}
                            {!isOwner && (
                                <div className="mb-8">
                                    {!auth?.user ? (
                                        <div className="bg-brand-surface rounded-2xl p-6 text-center border border-brand-line">
                                            <p className="text-brand-muted mb-3">Sign in to leave a review</p>
                                            <Link
                                                href={`/login?redirect=/vendors/${vendor.slug}`}
                                                className="inline-block bg-brand-green text-white text-sm font-bold px-6 py-2.5 rounded-full hover:bg-brand-green-dark transition-colors"
                                            >
                                                Sign in
                                            </Link>
                                        </div>
                                    ) : reviewDone ? (
                                        <div className="bg-brand-green/10 border border-brand-green/30 rounded-2xl p-5 text-center">
                                            <svg className="w-10 h-10 text-brand-green mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p className="font-semibold text-brand-green-dark">Review submitted!</p>
                                            <p className="text-sm text-brand-muted mt-1">Thanks for sharing your experience.</p>
                                        </div>
                                    ) : has_reviewed ? (
                                        <div className="bg-brand-surface border border-brand-line rounded-2xl p-4 text-sm text-brand-muted">
                                            You have already reviewed this vendor.
                                        </div>
                                    ) : can_review ? (
                                        <form onSubmit={handleReviewSubmit} className="bg-brand-surface rounded-2xl p-6 border border-brand-line space-y-4">
                                            <h3 className="font-semibold text-brand-ink">Write a Review</h3>

                                            {/* Star Rating */}
                                            <div>
                                                <p className="text-sm text-brand-muted mb-2">Your rating <span className="text-brand-danger">*</span></p>
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
                                                                        ? 'text-brand-star fill-current'
                                                                        : 'text-brand-line'
                                                                }`}
                                                                viewBox="0 0 20 20"
                                                            >
                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                            </svg>
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>

                                            <input
                                                type="text"
                                                value={reviewTitle}
                                                onChange={(e) => setReviewTitle(e.target.value)}
                                                placeholder="Review title (optional)"
                                                maxLength={255}
                                                className="w-full rounded-xl border border-brand-line px-4 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-green/40"
                                            />

                                            <textarea
                                                value={reviewComment}
                                                onChange={(e) => setReviewComment(e.target.value)}
                                                placeholder="Share your experience with this vendor..."
                                                rows={3}
                                                maxLength={2000}
                                                className="w-full rounded-xl border border-brand-line px-4 py-2.5 text-sm bg-white resize-none focus:outline-none focus:ring-2 focus:ring-brand-green/40"
                                            />

                                            {reviewError && (
                                                <p className="text-sm text-brand-danger">{reviewError}</p>
                                            )}

                                            <button
                                                type="submit"
                                                disabled={!reviewRating || reviewSubmitting}
                                                className="bg-brand-green text-white text-sm font-bold px-6 py-2.5 rounded-full hover:bg-brand-green-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                {reviewSubmitting ? 'Submitting...' : 'Submit Review'}
                                            </button>
                                        </form>
                                    ) : (
                                        <div className="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-sm text-amber-800 flex items-start gap-3">
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
                                <div className="text-center py-10 text-brand-muted">
                                    <svg className="w-12 h-12 mx-auto mb-3 text-brand-line" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    onClose={() => setIsEditOpen(false)}
                />
            )}

            {lightboxOpen && vendor.business_logo && (
                <div
                    className="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-6"
                    onClick={() => setLightboxOpen(false)}
                >
                    <div
                        className="relative"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <button
                            type="button"
                            onClick={() => setLightboxOpen(false)}
                            className="absolute -top-3 -right-3 z-10 w-8 h-8 bg-white rounded-full flex items-center justify-center text-brand-muted hover:text-brand-ink shadow-md transition-colors"
                            aria-label="Close"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <img
                            src={vendor.business_logo}
                            alt={vendor.business_name}
                            className="w-64 h-64 rounded-2xl object-cover shadow-2xl"
                        />
                        <p className="text-center text-white/80 text-sm mt-3">{vendor.business_name}</p>
                    </div>
                </div>
            )}

            {bannerLightboxOpen && vendor.banner_image && (
                <div
                    className="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-6"
                    onClick={() => setBannerLightboxOpen(false)}
                >
                    <div
                        className="relative"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <button
                            type="button"
                            onClick={() => setBannerLightboxOpen(false)}
                            className="absolute -top-3 -right-3 z-10 w-8 h-8 bg-white rounded-full flex items-center justify-center text-brand-muted hover:text-brand-ink shadow-md transition-colors"
                            aria-label="Close"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <img
                            src={vendor.banner_image}
                            alt="Store banner"
                            className="max-w-[90vw] max-h-[80vh] rounded-2xl object-contain shadow-2xl"
                        />
                        <p className="text-center text-white/80 text-sm mt-3">{vendor.business_name}</p>
                    </div>
                </div>
            )}
        </>
    );
}
