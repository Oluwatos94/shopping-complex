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
            className="group flex flex-col overflow-hidden rounded-[18px] border border-brand-line bg-white font-display transition duration-150 hover:-translate-y-1 hover:border-[#D7DCE3] hover:shadow-[0_18px_38px_rgba(11,31,58,0.12)]"
        >
            {/* Product Media */}
            <div className="relative aspect-square overflow-hidden bg-brand-surface">
                {isVideo ? (
                    <>
                        <video
                            src={primaryImage}
                            className="h-full w-full object-cover"
                            muted
                            playsInline
                            preload="metadata"
                        />
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-black/50 transition-colors group-hover:bg-black/70">
                                <svg className="ml-0.5 h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </div>
                        </div>
                    </>
                ) : (
                    <img
                        src={primaryImage}
                        alt={product.name}
                        className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                )}

                <div className="absolute left-2.5 top-2.5 flex flex-col gap-1.5">
                    {hasDiscount && salePrice && (
                        <span className="rounded-md bg-brand-danger px-2 py-1 text-xs font-bold text-white">
                            {Math.round(((price - salePrice) / price) * 100)}% OFF
                        </span>
                    )}
                    {product.is_featured && (
                        <span className="rounded-md bg-brand-green px-2 py-1 text-xs font-bold text-white">
                            Featured
                        </span>
                    )}
                </div>
            </div>

            {/* Product Info */}
            <div className="flex flex-col gap-1.5 p-4 pb-5">
                {product.vendor && (
                    <div className="flex items-center justify-between gap-1">
                        <p className="truncate text-xs font-bold uppercase tracking-[0.06em] text-brand-muted/80">
                            {product.vendor.business_name || product.vendor.name}
                        </p>
                        {product.distance_formatted && (
                            <span className="flex flex-shrink-0 items-center gap-0.5 text-xs font-semibold text-brand-green-dark">
                                <svg className="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                                </svg>
                                {product.distance_formatted}
                            </span>
                        )}
                    </div>
                )}

                <h3 className="line-clamp-2 min-h-[42px] text-[17px] font-semibold leading-snug text-brand-ink transition-colors group-hover:text-brand-green-dark">
                    {product.name}
                </h3>

                {product.reviews_count && product.reviews_count > 0 ? (
                    <div className="flex items-center gap-2">
                        <div className="flex items-center">
                            {[...Array(5)].map((_, i) => (
                                <svg
                                    key={i}
                                    className={`h-4 w-4 ${
                                        i < Math.floor(product.average_rating) ? 'fill-current text-brand-star' : 'text-brand-line'
                                    }`}
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            ))}
                        </div>
                        <span className="text-xs text-brand-muted">({product.reviews_count})</span>
                    </div>
                ) : null}

                <div className="mt-0.5 flex items-baseline gap-2">
                    {hasDiscount && salePrice ? (
                        <>
                            <span className="text-[19px] font-extrabold text-brand-ink">₦{salePrice.toLocaleString()}</span>
                            <span className="text-sm text-brand-muted line-through">₦{price.toLocaleString()}</span>
                        </>
                    ) : (
                        <span className="text-[19px] font-extrabold text-brand-ink">₦{price.toLocaleString()}</span>
                    )}
                </div>
            </div>
        </Link>
    );
}
