import { useState, useRef, useCallback } from 'react';
import { Link } from '@inertiajs/react';
import { Product } from '@/types/product';

interface RelatedProductsProps {
    products: Product[];
    title?: string;
}

export default function RelatedProducts({ products, title = 'You May Also Like' }: RelatedProductsProps) {
    const scrollContainerRef = useRef<HTMLDivElement>(null);
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(true);

    const checkScrollPosition = useCallback(() => {
        if (!scrollContainerRef.current) return;

        const { scrollLeft, scrollWidth, clientWidth } = scrollContainerRef.current;
        setCanScrollLeft(scrollLeft > 0);
        setCanScrollRight(scrollLeft < scrollWidth - clientWidth - 10);
    }, []);

    const scroll = useCallback((direction: 'left' | 'right') => {
        if (!scrollContainerRef.current) return;

        const scrollAmount = 320; // Approximate card width + gap
        const newScrollLeft =
            direction === 'left'
                ? scrollContainerRef.current.scrollLeft - scrollAmount
                : scrollContainerRef.current.scrollLeft + scrollAmount;

        scrollContainerRef.current.scrollTo({
            left: newScrollLeft,
            behavior: 'smooth',
        });

        // Update scroll buttons state after animation
        setTimeout(checkScrollPosition, 300);
    }, [checkScrollPosition]);

    if (products.length === 0) return null;

    return (
        <div className="space-y-6">
            {/* Section Header */}
            <div className="flex items-center justify-between">
                <h2 className="text-2xl font-serif font-bold text-gray-900">
                    {title}
                </h2>

                {/* Navigation Buttons */}
                <div className="hidden sm:flex items-center gap-2">
                    <button
                        onClick={() => scroll('left')}
                        disabled={!canScrollLeft}
                        className="p-2 rounded-full border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                        aria-label="Scroll left"
                    >
                        <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        onClick={() => scroll('right')}
                        disabled={!canScrollRight}
                        className="p-2 rounded-full border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                        aria-label="Scroll right"
                    >
                        <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            {/* Carousel Container */}
            <div className="relative">
                {/* Left Gradient */}
                {canScrollLeft && (
                    <div className="absolute left-0 top-0 bottom-0 w-16 bg-gradient-to-r from-white to-transparent z-10 pointer-events-none" />
                )}

                {/* Products Scroll Container */}
                <div
                    ref={scrollContainerRef}
                    onScroll={checkScrollPosition}
                    className="flex gap-4 overflow-x-auto scrollbar-hide scroll-smooth pb-4 -mx-4 px-4 sm:-mx-0 sm:px-0"
                >
                    {products.map((product) => (
                        <RelatedProductCard key={product.id} product={product} />
                    ))}
                </div>

                {/* Right Gradient */}
                {canScrollRight && (
                    <div className="absolute right-0 top-0 bottom-0 w-16 bg-gradient-to-l from-white to-transparent z-10 pointer-events-none" />
                )}
            </div>

            {/* View All Link */}
            <div className="text-center pt-2">
                <Link
                    href="/products"
                    className="inline-flex items-center gap-2 text-primary-olive hover:text-primary-dark font-medium transition-colors"
                >
                    View All Products
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </Link>
            </div>
        </div>
    );
}

// Individual Product Card for Carousel
function RelatedProductCard({ product }: { product: Product }) {
    const primaryImage = product.images?.find((img) => img.is_primary)?.url || product.images?.[0]?.url || '/images/placeholder.png';

    const price = Number(product.price);
    const salePrice = product.sale_price ? Number(product.sale_price) : null;
    const hasDiscount = salePrice && salePrice < price;

    return (
        <Link
            href={`/products/${product.id}`}
            className="flex-shrink-0 w-64 group"
        >
            <div className="bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-100 hover:border-primary-peach">
                {/* Product Image */}
                <div className="relative aspect-square overflow-hidden bg-gray-100">
                    <img
                        src={primaryImage}
                        alt={product.name}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    />

                    {/* Badges */}
                    <div className="absolute top-2 left-2 flex flex-col gap-1">
                        {hasDiscount && salePrice && (
                            <span className="bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded">
                                {Math.round(((price - salePrice) / price) * 100)}% OFF
                            </span>
                        )}
                        {product.is_featured && (
                            <span className="bg-primary-olive text-white text-xs font-semibold px-2 py-1 rounded">
                                Featured
                            </span>
                        )}
                    </div>

                    {/* Quick Wishlist Button */}
                    <button
                        onClick={(e) => {
                            e.preventDefault();
                            // Add to wishlist logic
                        }}
                        className="absolute top-2 right-2 bg-white/90 hover:bg-white p-2 rounded-full shadow-sm opacity-0 group-hover:opacity-100 transition-opacity"
                        aria-label="Add to wishlist"
                    >
                        <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </button>
                </div>

                {/* Product Info */}
                <div className="p-4">
                    {/* Vendor Name */}
                    {product.vendor && (
                        <p className="text-xs text-gray-500 mb-1 truncate">
                            {product.vendor.business_name || product.vendor.name}
                        </p>
                    )}

                    {/* Product Name */}
                    <h3 className="font-medium text-gray-900 mb-2 line-clamp-2 group-hover:text-primary-olive transition-colors text-sm">
                        {product.name}
                    </h3>

                    {/* Rating */}
                    {product.reviews_count && product.reviews_count > 0 && (
                        <div className="flex items-center gap-1 mb-2">
                            <div className="flex items-center">
                                {[...Array(5)].map((_, i) => (
                                    <svg
                                        key={i}
                                        className={`w-3.5 h-3.5 ${
                                            i < Math.floor(product.average_rating)
                                                ? 'text-yellow-400 fill-current'
                                                : 'text-gray-300'
                                        }`}
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                ))}
                            </div>
                            <span className="text-xs text-gray-500">({product.reviews_count})</span>
                        </div>
                    )}

                    {/* Price */}
                    <div className="flex items-baseline gap-2">
                        {hasDiscount && salePrice ? (
                            <>
                                <span className="text-base font-bold text-gray-900">
                                    ${salePrice.toFixed(2)}
                                </span>
                                <span className="text-sm text-gray-500 line-through">
                                    ${price.toFixed(2)}
                                </span>
                            </>
                        ) : (
                            <span className="text-base font-bold text-gray-900">
                                ${price.toFixed(2)}
                            </span>
                        )}
                    </div>

                    {/* Stock Warning */}
                    {product.stock > 0 && product.stock <= 5 && (
                        <p className="text-xs text-orange-600 mt-2">
                            Only {product.stock} left
                        </p>
                    )}
                </div>
            </div>
        </Link>
    );
}
