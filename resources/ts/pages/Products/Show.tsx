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

    // Handle message vendor
    const handleMessageVendor = useCallback(() => {
        if (!auth?.user) {
            // Redirect to login if not authenticated
            router.visit('/login', {
                data: { redirect: `/products/${product.id}` },
            });
            return;
        }

        // Open message modal or redirect to chat
        setIsMessageModalOpen(true);
        // You could also redirect directly to chat:
        // router.visit(`/chat?vendor=${vendor.id}`);
    }, [auth, product.id, vendor.id]);

    // Handle review page change
    const handleReviewPageChange = useCallback((page: number) => {
        router.get(
            `/products/${product.id}`,
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
                        productName={product.name}
                        onClose={() => setIsMessageModalOpen(false)}
                    />
                )}
            </div>
        </>
    );
}

// Message Vendor Modal Component
interface MessageModalProps {
    vendor: Vendor;
    productName: string;
    onClose: () => void;
}

function MessageModal({ vendor, productName, onClose }: MessageModalProps) {
    const [message, setMessage] = useState(`Hi, I'm interested in "${productName}". `);
    const [isSending, setIsSending] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSending(true);

        try {
            // Send message via Inertia or API
            router.post('/chat/start', {
                vendor_id: vendor.id,
                message: message,
            }, {
                onSuccess: () => {
                    onClose();
                    // Optionally redirect to chat
                },
                onError: () => {
                    setIsSending(false);
                },
            });
        } catch (error) {
            setIsSending(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-black/50 transition-opacity"
                onClick={onClose}
            />

            {/* Modal */}
            <div className="flex min-h-full items-center justify-center p-4">
                <div className="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
                    {/* Header */}
                    <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <div className="flex items-center gap-3">
                            {vendor.business_logo ? (
                                <img
                                    src={vendor.business_logo}
                                    alt={vendor.business_name}
                                    className="w-10 h-10 rounded-full object-cover"
                                />
                            ) : (
                                <div className="w-10 h-10 rounded-full bg-primary-olive flex items-center justify-center">
                                    <span className="text-white font-semibold">
                                        {vendor.business_name.charAt(0).toUpperCase()}
                                    </span>
                                </div>
                            )}
                            <div>
                                <h3 className="font-semibold text-gray-900">
                                    Message {vendor.business_name}
                                </h3>
                                <p className="text-sm text-gray-500 flex items-center gap-1">
                                    <span className={`w-2 h-2 rounded-full ${vendor.is_online ? 'bg-green-500' : 'bg-gray-400'}`} />
                                    {vendor.is_online ? 'Online now' : 'Usually responds within 24 hours'}
                                </p>
                            </div>
                        </div>
                        <button
                            onClick={onClose}
                            className="p-2 hover:bg-gray-100 rounded-full transition-colors"
                            aria-label="Close"
                        >
                            <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {/* Body */}
                    <form onSubmit={handleSubmit} className="p-6">
                        <div className="mb-4">
                            <label htmlFor="message" className="block text-sm font-medium text-gray-700 mb-2">
                                Your Message
                            </label>
                            <textarea
                                id="message"
                                rows={4}
                                value={message}
                                onChange={(e) => setMessage(e.target.value)}
                                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent resize-none"
                                placeholder="Type your message..."
                                required
                            />
                        </div>

                        <p className="text-xs text-gray-500 mb-6">
                            By sending a message, you agree to our terms of service and privacy policy.
                        </p>

                        <div className="flex gap-3">
                            <button
                                type="button"
                                onClick={onClose}
                                className="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={isSending || !message.trim()}
                                className="flex-1 px-4 py-3 bg-primary-olive text-white rounded-lg hover:bg-primary-dark transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                {isSending ? (
                                    <>
                                        <svg className="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                        Sending...
                                    </>
                                ) : (
                                    <>
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        Send Message
                                    </>
                                )}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
