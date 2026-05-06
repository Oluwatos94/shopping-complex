import { Head } from '@inertiajs/react';
import { Product, VendorRatingStats } from '@/types/product';
import { Vendor } from '@/types/user';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import Breadcrumb, { BreadcrumbItem } from '@/components/Products/Breadcrumb';
import ImageGallery from '@/components/Products/partials/ImageGallery';
import ProductInfo from '@/components/Products/ProductInfo';
import RelatedProducts from '@/components/Products/RelatedProducts';

interface ProductShowProps {
    product: Product;
    vendor: Vendor;
    vendor_stats: VendorRatingStats;
    related_products: Product[];
}

export default function ProductShow({
    product,
    vendor,
    vendor_stats,
    related_products,
}: ProductShowProps) {
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

    const whatsAppHref = vendor.whatsapp_number
        ? `https://wa.me/${vendor.whatsapp_number.replace(/\D/g, '')}?text=${encodeURIComponent(`Hi ${vendor.business_name}, I'm interested in "${product.name}".`)}`
        : null;

    return (
        <>
            <Head title={`${product.name} - jiidaa`}>
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
                        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center gap-3">
                            <button
                                onClick={() => window.history.back()}
                                className="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors text-gray-600 flex-shrink-0"
                                aria-label="Go back"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <Breadcrumb items={breadcrumbItems} />
                        </div>
                    </div>

                    {/* Product Details */}
                    <section className="bg-white">
                        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                                <div className="lg:sticky lg:top-4 lg:self-start">
                                    <ImageGallery
                                        images={product.images || []}
                                        productName={product.name}
                                    />
                                </div>
                                <div>
                                    <ProductInfo
                                        product={product}
                                        vendor={vendor}
                                        vendorStats={vendor_stats}
                                        whatsAppHref={whatsAppHref}
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Related Products */}
                    {related_products.length > 0 && (
                        <section className="bg-white border-t border-gray-200">
                            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                                <RelatedProducts products={related_products} />
                            </div>
                        </section>
                    )}

                    {/* More from Vendor */}
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
            </div>
        </>
    );
}
