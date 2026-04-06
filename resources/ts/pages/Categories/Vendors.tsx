import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import Header from '@/components/Header';
import Footer from '@/components/Footer';

interface Vendor {
    id: number;
    name: string;
    slug: string;
    profileImage: string | null;
    products: { id: number; name: string; price: number }[];
}

interface PaginatedVendors {
    data: Vendor[];
    current_page: number;
    last_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface PageProps {
    [key: string]: unknown;
    category: Category;
    vendors: PaginatedVendors;
}

const CategoryVendors: React.FC = () => {
    const { category, vendors } = usePage<PageProps>().props;

    return (
        <div className="min-h-screen bg-white flex flex-col">
            <Header />

            <main className="flex-1">
                {/* Hero */}
                <section className="bg-gradient-to-br from-primary-dark to-primary-brown text-white py-14 lg:py-20">
                    <div className="container mx-auto px-4">
                        <Link href="/categories" className="inline-flex items-center text-primary-light hover:text-white transition-colors mb-4 text-sm">
                            <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                            All Categories
                        </Link>
                        <h1 className="text-3xl md:text-5xl font-bold mb-3">{category.name}</h1>
                        <p className="text-primary-light text-lg">
                            {vendors.data.length > 0
                                ? `${vendors.data.length} vendor${vendors.data.length !== 1 ? 's' : ''} available`
                                : 'No vendors found in this category yet'}
                        </p>
                    </div>
                </section>

                {/* Vendors grid */}
                <section className="py-16 lg:py-20">
                    <div className="container mx-auto px-4">
                        {vendors.data.length === 0 ? (
                            <div className="text-center py-16">
                                <svg className="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <p className="text-primary-brown text-lg mb-6">No vendors in this category yet.</p>
                                <Link href="/categories" className="bg-primary-olive text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-peach transition-colors">
                                    Browse Other Categories
                                </Link>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                {vendors.data.map((vendor) => (
                                    <Link
                                        key={vendor.id}
                                        href={`/vendors/${vendor.slug}`}
                                        className="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 overflow-hidden border border-gray-100"
                                    >
                                        {/* Profile image */}
                                        <div className="h-44 bg-primary-light overflow-hidden">
                                            {vendor.profileImage ? (
                                                <img
                                                    src={vendor.profileImage}
                                                    alt={vendor.name}
                                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                                />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center bg-primary-olive/10">
                                                    <span className="text-5xl font-bold text-primary-olive/40">
                                                        {vendor.name.charAt(0)}
                                                    </span>
                                                </div>
                                            )}
                                        </div>

                                        <div className="p-4">
                                            <h3 className="font-bold text-primary-dark text-lg mb-2 group-hover:text-primary-olive transition-colors">
                                                {vendor.name}
                                            </h3>

                                            {vendor.products.length > 0 && (
                                                <div className="space-y-1">
                                                    {vendor.products.map((product) => (
                                                        <div key={product.id} className="flex justify-between text-sm">
                                                            <span className="text-primary-brown truncate">{product.name}</span>
                                                            <span className="text-primary-olive font-medium ml-2 flex-shrink-0">
                                                                ₦{Number(product.price).toLocaleString()}
                                                            </span>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}

                                            <div className="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                                                <span className="text-xs text-primary-brown">View store</span>
                                                <svg className="w-4 h-4 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}

                        {/* Pagination */}
                        {(vendors.prev_page_url || vendors.next_page_url) && (
                            <div className="flex justify-center gap-4 mt-12">
                                {vendors.prev_page_url && (
                                    <Link
                                        href={vendors.prev_page_url}
                                        className="px-6 py-3 bg-primary-olive text-white rounded-lg font-medium hover:bg-primary-peach transition-colors"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {vendors.next_page_url && (
                                    <Link
                                        href={vendors.next_page_url}
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

export default CategoryVendors;
