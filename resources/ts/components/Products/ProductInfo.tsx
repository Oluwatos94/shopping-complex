import { Link } from '@inertiajs/react';
import { Product, VendorRatingStats } from '@/types/product';
import { Vendor } from '@/types/user';

interface ProductInfoProps {
    product: Product;
    vendor: Vendor;
    vendorStats: VendorRatingStats;
    whatsAppHref: string | null;
}

export default function ProductInfo({ product, vendor, vendorStats, whatsAppHref }: ProductInfoProps) {
    const price = Number(product.price);
    const salePrice = product.sale_price ? Number(product.sale_price) : null;
    const hasDiscount = salePrice && salePrice < price;
    const discountPercent = hasDiscount ? Math.round(((price - salePrice) / price) * 100) : 0;

    return (
        <div className="space-y-6">
            {/* Product Title & Category */}
            <div>
                {product.category && (
                    <Link
                        href={`/products?filter[category_id]=${product.category.id}`}
                        className="text-sm text-primary-olive hover:text-primary-peach transition-colors"
                    >
                        {product.category.name}
                    </Link>
                )}
                <h1 className="text-2xl md:text-3xl font-serif font-bold text-gray-900 mt-1">
                    {product.name}
                </h1>
            </div>

            <div className="flex items-center gap-4">
                <div className="flex items-center gap-1">
                    {[...Array(5)].map((_, i) => (
                        <svg
                            key={i}
                            className={`w-5 h-5 ${
                                i < Math.floor(vendorStats.average)
                                    ? 'text-yellow-400 fill-current'
                                    : i < vendorStats.average
                                    ? 'text-yellow-400 fill-current opacity-50'
                                    : 'text-gray-300'
                            }`}
                            viewBox="0 0 20 20"
                        >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    ))}
                </div>
                <span className="text-gray-600">
                    {vendorStats.average.toFixed(1)} ({vendorStats.count} vendor reviews)
                </span>
            </div>

            <div className="border-t border-b border-gray-200 py-6">
                <div className="flex items-baseline gap-4">
                    {hasDiscount && salePrice ? (
                        <>
                            <span className="text-3xl font-bold text-gray-900">
                                ₦{salePrice.toLocaleString()}
                            </span>
                            <span className="text-xl text-gray-500 line-through">
                                ₦{price.toLocaleString()}
                            </span>
                            <span className="bg-red-100 text-red-700 text-sm font-semibold px-3 py-1 rounded-full">
                                Save {discountPercent}%
                            </span>
                        </>
                    ) : (
                        <span className="text-3xl font-bold text-gray-900">
                            ₦{price.toLocaleString()}
                        </span>
                    )}
                </div>

            </div>

            <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-2">Description</h3>
                <p className="text-gray-600 leading-relaxed whitespace-pre-line">
                    {product.description}
                </p>
            </div>

            {/* Vendor Information Card */}
            <div className="bg-gray-50 rounded-xl p-5 space-y-4">
                <h3 className="text-sm font-medium text-gray-500 uppercase tracking-wide">
                    Sold by
                </h3>
                <div className="flex items-start gap-4">
                    <div className="flex-shrink-0">
                        {vendor.business_logo ? (
                            <img
                                src={vendor.business_logo}
                                alt={vendor.business_name}
                                className="w-14 h-14 rounded-full object-cover ring-2 ring-white"
                            />
                        ) : (
                            <div className="w-14 h-14 rounded-full bg-primary-olive flex items-center justify-center ring-2 ring-white">
                                <span className="text-white text-xl font-bold">
                                    {vendor.business_name.charAt(0).toUpperCase()}
                                </span>
                            </div>
                        )}
                    </div>

                    <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2">
                            <Link
                                href={`/vendors/${vendor.slug}`}
                                className="font-semibold text-gray-900 hover:text-primary-olive transition-colors truncate"
                            >
                                {vendor.business_name}
                            </Link>
                            {vendor.is_verified && (
                                <svg className="w-5 h-5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                </svg>
                            )}
                        </div>

                        {/* Vendor Stats */}
                        <div className="flex items-center gap-4 mt-1.5 text-sm text-gray-500">
                            <div className="flex items-center gap-1">
                                <svg className="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <span>{vendor.rating.toFixed(1)}</span>
                            </div>
                            <span className="text-gray-300">|</span>
                            <span>{vendor.products_count} products</span>
                        </div>

                    </div>
                </div>

                {/* Vendor Actions */}
                <div className="flex gap-3 pt-2">
                    <Link
                        href={`/vendors/${vendor.slug}`}
                        className="flex-1 px-4 py-2.5 text-center border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors font-medium text-sm"
                    >
                        View Store
                    </Link>
                    {whatsAppHref && (
                        <a
                            href={whatsAppHref}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex-1 px-4 py-2.5 bg-primary-olive text-white rounded-lg hover:bg-primary-dark transition-colors font-medium text-sm flex items-center justify-center gap-2"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            Message
                        </a>
                    )}
                </div>
            </div>


            {/* Product Features */}
            <div className="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                {/* Shipping / Pay on Delivery */}
                <div className="flex items-center gap-3 text-gray-600">
                    <div className="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <div className="text-sm">
                        {product.pay_on_delivery ? (
                            <>
                                <p className="font-medium text-gray-900">Pay on Delivery</p>
                                <p className="text-gray-500">Pay when you receive</p>
                            </>
                        ) : (
                            <>
                                <p className="font-medium text-gray-900">Pickup / Arrange Shipping</p>
                                <p className="text-gray-500">Contact vendor to arrange</p>
                            </>
                        )}
                    </div>
                </div>

                {/* Returns */}
                <div className="flex items-center gap-3 text-gray-600">
                    <div className="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div className="text-sm">
                        {product.is_returnable ? (
                            <>
                                <p className="font-medium text-gray-900">Easy Returns</p>
                                <p className="text-gray-500">Returns accepted</p>
                            </>
                        ) : (
                            <>
                                <p className="font-medium text-gray-900">No Returns</p>
                                <p className="text-gray-500">All sales are final</p>
                            </>
                        )}
                    </div>
                </div>

                {/* Support / Available Hours */}
                <div className="flex items-center gap-3 text-gray-600">
                    <div className="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div className="text-sm">
                        <p className="font-medium text-gray-900">Vendor Support</p>
                        <p className="text-gray-500">{vendor.available_hours ?? 'Contact vendor'}</p>
                    </div>
                </div>
            </div>

            {/* Share */}
            <div className="flex items-center gap-4 pt-4 border-t border-gray-200">
                <span className="text-sm font-medium text-gray-500">Share:</span>
                <div className="flex gap-2">
                    <button className="p-2 hover:bg-gray-100 rounded-lg transition-colors" aria-label="Share on Facebook">
                        <svg className="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                    </button>
                    <button className="p-2 hover:bg-gray-100 rounded-lg transition-colors" aria-label="Share on Twitter">
                        <svg className="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                        </svg>
                    </button>
                    <button className="p-2 hover:bg-gray-100 rounded-lg transition-colors" aria-label="Share on WhatsApp">
                        <svg className="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                    </button>
                    <button
                        className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        onClick={() => navigator.clipboard.writeText(window.location.href)}
                        aria-label="Copy link"
                    >
                        <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    );
}
