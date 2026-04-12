import { useState, useCallback } from 'react';
import { Head, router } from '@inertiajs/react';
import { Product, VendorReview, VendorRatingStats, PaginatedVendorReviews } from '@/types/product';
import { Vendor, User } from '@/types/user';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import Breadcrumb, { BreadcrumbItem } from '@/components/Products/Breadcrumb';
import ImageGallery from '@/components/Products/ImageGallery';
import ProductInfo from '@/components/Products/ProductInfo';
import VendorReviews from '@/components/Products/VendorReviews';
import RelatedProducts from '@/components/Products/RelatedProducts';
import MessageModal from '@/components/Chat/MessageModal';

interface ProductShowProps {
    product: Product;
    vendor: Vendor;
    vendor_stats: VendorRatingStats;
    vendor_reviews: PaginatedVendorReviews;
    related_products: Product[];
    auth?: {
        user: User | null;
    };
}

export default function ProductShow({
    product,
    vendor,
    vendor_stats,
    vendor_reviews,
    related_products,
    auth,
}: ProductShowProps) {
    const [isMessageModalOpen, setIsMessageModalOpen] = useState(false);

    // Build breadcrumb items
    const breadcrumbItems: BreadcrumbItem[] = [
        { label: 'Products', href: '/products' },
    ];

    if (product.category) {
        breadcrumbItems.push({
            label: product.category.name,
            href: `/products?filter[category_id]=${product.category.id}`,
        });
    }

    breadcrumbItems.push({ label: product.name });

    const handleMessageVendor = useCallback(() => {
        if (!auth?.user) {
            router.visit('/login', {
                data: { redirect: `/products/${product.slug}` },
            });
            return;
        }

        // Open message modal or redirect to chat
        setIsMessageModalOpen(true);
        // You could also redirect directly to chat:
        // router.visit(`/chat?vendor=${vendor.id}`);
    }, [auth, product.slug, vendor.id]);

    const handleReviewPageChange = useCallback((page: number) => {
        router.get(
            `/products/${product.slug}`,
            { review_page: page },
            {
                preserveState: true,
                preserveScroll: false,
                only: ['vendor_reviews'],
            }
        );

        // Scroll to reviews section
        const reviewsSection = document.getElementById('reviews-section');
        if (reviewsSection) {
            reviewsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, [product.id]);

    return (
        <>
            <Head title={`${product.name} - Shopping Complex`}>
                <meta name="description" content={product.description.substring(0, 160)} />
                <meta property="og:title" content={product.name} />
                <meta property="og:description" content={product.description.substring(0, 160)} />
                {product.images?.[0]?.url && (
                    <meta property="og:image" content={product.images[0].url} />
                )}
            </Head>

            <div className="min-h-screen bg-gray-50 flex flex-col">
                <Header />

                <main className="flex-1">
                    {/* Breadcrumb */}
                    <div className="bg-white border-b border-gray-200">
                        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                            <Breadcrumb items={breadcrumbItems} />
                        </div>
                    </div>

                    {/* Product Details Section */}
                    <section className="bg-white">
                        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                                {/* Left Column - Image Gallery */}
                                <div className="lg:sticky lg:top-4 lg:self-start">
                                    <ImageGallery
                                        images={product.images || []}
                                        productName={product.name}
                                    />
                                </div>

                                {/* Right Column - Product Info */}
                                <div>
                                    <ProductInfo
                                        product={product}
                                        vendor={vendor}
                                        vendorStats={vendor_stats}
                                        onMessageVendor={handleMessageVendor}
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Vendor Reviews Section */}
                    <section id="reviews-section" className="bg-gray-50 border-t border-gray-200">
                        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                            <VendorReviews
                                reviews={vendor_reviews.reviews}
                                stats={vendor_stats}
                                vendorName={vendor.business_name}
                                currentPage={vendor_reviews.meta.current_page}
                                lastPage={vendor_reviews.meta.last_page}
                                total={vendor_reviews.meta.total}
                                onPageChange={handleReviewPageChange}
                            />
                        </div>
                    </section>

                    {/* Related Products Section */}
                    {related_products.length > 0 && (
                        <section className="bg-white border-t border-gray-200">
                            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                                <RelatedProducts products={related_products} />
                            </div>
                        </section>
                    )}

                    {/* More from Vendor Section */}
                    {vendor.products_count > 1 && (
                        <section className="bg-gray-50 border-t border-gray-200">
                            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                                <RelatedProducts
                                    products={related_products.filter(p => p.vendor_id === vendor.id)}
                                    title={`More from ${vendor.business_name}`}
                                />
                            </div>
                        </section>
                    )}
                </main>

                <Footer />

                {/* Message Vendor Modal */}
                {isMessageModalOpen && (
                    <MessageModal
                        vendor={vendor}
                        initialMessage={`Hi, I'm interested in "${product.name}". `}
                        onClose={() => setIsMessageModalOpen(false)}
                    />
                )}
            </div>
        </>
    );
}

