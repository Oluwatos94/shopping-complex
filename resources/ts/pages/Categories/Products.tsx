import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { Category, CategoryProduct, LaravelPaginated } from '@/types';

interface PageProps {
    [key: string]: unknown;
    category: Pick<Category, 'id' | 'name' | 'slug'>;
    products: LaravelPaginated<CategoryProduct>;
}

const CategoryProducts: React.FC = () => {
    const { category, products } = usePage<PageProps>().props;

    return (
        <div className="min-h-screen bg-white flex flex-col">
            <Header />

            <main className="flex-1">
                {/* Hero */}
                <section className="bg-gradient-to-br from-primary-dark to-primary-brown text-white py-14 lg:py-20">
                    <div className="container mx-auto px-4">
                        <Link
                            href="/categories"
                            className="inline-flex items-center text-primary-light hover:text-white transition-colors mb-4 text-sm"
                        >
                            <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                            All Categories
                        </Link>
                        <h1 className="text-3xl md:text-5xl font-bold mb-3">{category.name}</h1>
                        <p className="text-primary-light text-lg">
                            {products.data.length > 0
                                ? `${products.data.length} product${products.data.length !== 1 ? 's' : ''} available`
                                : 'No products in this category yet'}
                        </p>
                    </div>
                </section>

                {/* Products grid */}
                <section className="py-16 lg:py-20">
                    <div className="container mx-auto px-4">
                        {products.data.length === 0 ? (
                            <div className="text-center py-16">
                                <svg className="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <p className="text-primary-brown text-lg mb-6">No products in this category yet.</p>
                                <Link
                                    href="/categories"
                                    className="bg-primary-olive text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-peach transition-colors"
                                >
                                    Browse Other Categories
                                </Link>
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                                {products.data.map((product) => (
                                    <Link
                                        key={product.id}
                                        href={`/products/${product.slug}`}
                                        className="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 overflow-hidden border border-gray-100"
                                    >
                                        {/* Product media */}
                                        <div className="h-48 bg-primary-light overflow-hidden relative">
                                            {product.image ? (
                                                product.media_type === 'product_video' ? (
                                                    <>
                                                        <video
                                                            src={product.image}
                                                            className="w-full h-full object-cover"
                                                            muted
                                                            playsInline
                                                            preload="metadata"
                                                        />
                                                        <div className="absolute inset-0 flex items-center justify-center">
                                                            <div className="w-12 h-12 rounded-full bg-black/50 flex items-center justify-center group-hover:bg-black/70 transition-colors">
                                                                <svg className="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path d="M8 5v14l11-7z" />
                                                                </svg>
                                                            </div>
                                                        </div>
                                                    </>
                                                ) : (
                                                    <img
                                                        src={product.image}
                                                        alt={product.name}
                                                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                                    />
                                                )
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center bg-primary-olive/10">
                                                    <svg className="w-12 h-12 text-primary-olive/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            )}
                                        </div>

                                        <div className="p-3 md:p-4">
                                            <h3 className="font-semibold text-primary-dark text-sm md:text-base leading-snug mb-1 group-hover:text-primary-olive transition-colors line-clamp-2">
                                                {product.name}
                                            </h3>
                                            {product.vendor_name && (
                                                <p className="text-xs text-primary-brown mb-2 truncate">{product.vendor_name}</p>
                                            )}
                                            <p className="text-primary-olive font-bold text-base md:text-lg">
                                                ₦{Number(product.price).toLocaleString()}
                                            </p>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}

                        {/* Pagination */}
                        {(products.prev_page_url || products.next_page_url) && (
                            <div className="flex justify-center gap-4 mt-12">
                                {products.prev_page_url && (
                                    <Link
                                        href={products.prev_page_url}
                                        className="px-6 py-3 bg-primary-olive text-white rounded-lg font-medium hover:bg-primary-peach transition-colors"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {products.next_page_url && (
                                    <Link
                                        href={products.next_page_url}
                                        className="px-6 py-3 bg-primary-olive text-white rounded-lg font-medium hover:bg-primary-peach transition-colors"
                                    >
                                        Next
                                    </Link>
                                )}
                            </div>
                        )}
                    </div>
                </section>
            </main>

            <Footer />
        </div>
    );
};

export default CategoryProducts;
