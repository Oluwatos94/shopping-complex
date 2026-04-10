import { useState, useCallback } from 'react';
import { Head, Link } from '@inertiajs/react';
import { PaginatedProducts } from '@/types/product';
import { VendorProfile, VendorStats } from '@/types/vendor';
import VendorSidebar from '@/components/VendorSidebar';
import { getCsrfToken } from '@/utils/csrf';
import UploadProductFab from './partials/UploadProductFab';

interface Props {
    vendor: VendorProfile;
    products: PaginatedProducts;
    stats: VendorStats;
    isOwner: boolean;
    isFollowing: boolean;
}

export default function VendorProfilePage({ vendor, products, stats, isOwner, isFollowing: initialIsFollowing }: Props) {
    const [isFollowing, setIsFollowing] = useState(initialIsFollowing);
    const [followersCount, setFollowersCount] = useState(stats.followers_count);
    const [followLoading, setFollowLoading] = useState(false);

    const handleToggleFollow = useCallback(async () => {
        if (followLoading) return;

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
            // Silently fail
        } finally {
            setFollowLoading(false);
        }
    }, [vendor.id, followLoading]);

    return (
        <>
            <Head title={`${vendor.business_name} - Shopping Complex`} />

            <div className="min-h-screen bg-gray-50">
                {/* Sidebar - only for vendor owner */}
                {isOwner && <VendorSidebar vendorSlug={vendor.slug} businessName={vendor.business_name} businessLogo={vendor.business_logo} />}

                {/* Main Content */}
                <div className={isOwner ? 'md:ml-[100px]' : ''}>
                    {/* Cover / Header Area */}
                    <div className="bg-gradient-to-br from-primary-dark via-primary-brown to-primary-dark">
                        {/* Top Nav (only for visitors) */}
                        {!isOwner && (
                            <div className="container mx-auto px-4 py-4">
                                <div className="flex items-center justify-between">
                                    <Link href="/" className="flex items-center space-x-3">
                                        <img
                                            src="/logo/dark-mode-logo.svg"
                                            alt="Shopping Complex"
                                            className="h-8 w-auto"
                                        />
                                        <span className="text-primary-light font-bold text-lg">
                                            Shopping Complex
                                        </span>
                                    </Link>
                                    <Link
                                        href="/vendors"
                                        className="text-sm text-primary-light hover:text-white transition-colors"
                                    >
                                        Browse Vendors
                                    </Link>
                                </div>
                            </div>
                        )}

                        {/* Profile Section */}
                        <div className="pb-8 pt-6">
                            {/* Avatar */}
                            <div className="flex justify-center">
                                <div className="w-28 h-28 rounded-full border-4 border-white overflow-hidden bg-gray-200 shadow-lg">
                                    {vendor.business_logo ? (
                                        <img
                                            src={vendor.business_logo}
                                            alt={vendor.business_name}
                                            className="w-full h-full object-cover"
                                        />
                                    ) : (
                                        <div className="w-full h-full bg-primary-olive flex items-center justify-center">
                                            <span className="text-white text-3xl font-bold">
                                                {vendor.business_name.charAt(0).toUpperCase()}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Name & Verified Badge */}
                            <div className="text-center mt-4">
                                <div className="flex items-center justify-center gap-2">
                                    <h1 className="text-2xl font-serif font-bold text-white">
                                        {vendor.business_name}
                                    </h1>
                                    {vendor.is_verified && (
                                        <svg className="w-5 h-5 text-primary-peach" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                        </svg>
                                    )}
                                </div>
                                <p className="text-sm text-primary-light mt-1">@{vendor.name}</p>
                            </div>

                            {/* Stats Row */}
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

                            {/* Action Buttons */}
                            <div className="flex justify-center gap-3 mt-6">
                                {isOwner ? (
                                    <div className="px-8 py-2 bg-primary-peach/20 text-primary-light rounded-lg font-semibold text-sm">
                                        {followersCount} {followersCount === 1 ? 'Follower' : 'Followers'}
                                    </div>
                                ) : (
                                    <button
                                        onClick={handleToggleFollow}
                                        disabled={followLoading}
                                        className={`px-8 py-2 rounded-lg font-semibold text-sm transition-colors disabled:opacity-50 ${
                                            isFollowing
                                                ? 'bg-white/10 text-primary-light border border-primary-light hover:bg-white/20'
                                                : 'bg-primary-peach text-primary-dark hover:bg-primary-light'
                                        }`}
                                    >
                                        {followLoading ? '...' : isFollowing ? 'Following' : 'Follow'}
                                    </button>
                                )}
                                <Link
                                    href="/chat"
                                    className="px-8 py-2 border border-primary-light text-primary-light rounded-lg font-semibold text-sm hover:bg-white/10 transition-colors"
                                >
                                    Message
                                </Link>
                            </div>

                            {/* Bio */}
                            {vendor.business_description && (
                                <div className="max-w-lg mx-auto mt-5 px-4">
                                    <p className="text-sm text-primary-light text-center leading-relaxed">
                                        {vendor.business_description}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Products Tab */}
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
                                    const primaryImage = product.images?.find((img) => img.is_primary)?.url
                                        || product.images?.[0]?.url
                                        || '/images/placeholder.png';
                                    const price = Number(product.price);
                                    const salePrice = product.sale_price ? Number(product.sale_price) : null;
                                    const hasDiscount = salePrice && salePrice < price;

                                    return (
                                        <Link
                                            key={product.id}
                                            href={`/products/${product.slug}`}
                                            className="group block bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow"
                                        >
                                            <div className="relative aspect-square overflow-hidden bg-gray-100">
                                                <img
                                                    src={primaryImage}
                                                    alt={product.name}
                                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                                />
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
                                                            <span className="text-sm font-bold text-gray-900">
                                                                ₦{salePrice.toLocaleString()}
                                                            </span>
                                                            <span className="text-xs text-gray-400 line-through">
                                                                ₦{price.toLocaleString()}
                                                            </span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-sm font-bold text-gray-900">
                                                            ₦{price.toLocaleString()}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </Link>
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
                                    {isOwner
                                        ? 'Start adding products to your store.'
                                        : "This vendor hasn't listed any products."}
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
                </div>

                {/* Upload Product Modal + FAB - only for owner */}
                {isOwner && (
                    <UploadProductFab
                        productLimit={stats.plan_product_limit}
                        activeProductsCount={stats.products_count}
                    />
                )}
            </div>
        </>
    );
}
