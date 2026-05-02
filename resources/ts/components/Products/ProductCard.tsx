import { Link } from '@inertiajs/react';
import { Product } from '@/types/product';

interface ProductCardProps {
    product: Product;
}

export default function ProductCard({ product }: ProductCardProps) {
    const primaryMedia = product.images?.find((img) => img.is_primary) ?? product.images?.[0];
    const primaryImage = primaryMedia?.url ?? '/images/placeholder.png';
    const isVideo = primaryMedia?.type === 'product_video';

    // Convert prices to numbers (they come as strings from Laravel)
    const price = Number(product.price);
    const salePrice = product.sale_price ? Number(product.sale_price) : null;
    const hasDiscount = salePrice && salePrice < price;

    return (
        <Link
            href={`/products/${product.slug}`}
            className="group block bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200"
        >
            {/* Product Media */}
            <div className="relative aspect-square overflow-hidden bg-gray-100">
                {isVideo ? (
                    <video
                        src={primaryImage}
                        className="w-full h-full object-cover"
                        muted
                        playsInline
                        preload="metadata"
                    />
                ) : (
                    <img
                        src={primaryImage}
                        alt={product.name}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    />
                )}

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
            </div>

            {/* Product Info */}
            <div className="p-4">
                {product.vendor && (
                    <p className="text-xs text-gray-500 mb-1">
                        {product.vendor.business_name || product.vendor.name}
                    </p>
                )}

                <h3 className="font-medium text-gray-900 mb-2 line-clamp-2 group-hover:text-primary-olive transition-colors">
                    {product.name}
                </h3>

                {product.reviews_count && product.reviews_count > 0 && (
                    <div className="flex items-center gap-2 mb-2">
                        <div className="flex items-center">
                            {[...Array(5)].map((_, i) => (
                                <svg
                                    key={i}
                                    className={`w-4 h-4 ${
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

                <div className="flex items-baseline gap-2">
                    {hasDiscount && salePrice ? (
                        <>
                            <span className="text-lg font-bold text-gray-900">
                                ₦{salePrice.toLocaleString()}
                            </span>
                            <span className="text-sm text-gray-500 line-through">
                                ₦{price.toLocaleString()}
                            </span>
                        </>
                    ) : (
                        <span className="text-lg font-bold text-gray-900">
                            ₦{price.toLocaleString()}
                        </span>
                    )}
                </div>
            </div>
        </Link>
    );
}
