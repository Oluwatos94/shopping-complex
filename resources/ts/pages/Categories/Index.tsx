import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import Header from '@/components/Header';
import Footer from '@/components/Footer';

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
}

interface PageProps {
    [key: string]: unknown;
    categories: Category[];
}

// Map category IDs to local images (same as the landing page section)
const categoryImages: Record<number, string> = {
    1: '/images/cat1.jpg',
    2: '/images/cat2.jpg',
    4: '/images/cat4.jpg',
    5: '/images/cat5.jpg',
    6: '/images/cat6.jpg',
    7: '/images/cat7.jpg',
    8: '/images/cat8.jpg',
};

const Categories: React.FC = () => {
    const { categories } = usePage<PageProps>().props;

    return (
        <div className="min-h-screen bg-white flex flex-col">
            <Header />

            <main className="flex-1">
                {/* Hero */}
                <section className="bg-gradient-to-br from-primary-dark to-primary-brown text-white py-14 lg:py-20">
                    <div className="container mx-auto px-4 text-center">
                        <h1 className="text-3xl md:text-5xl font-bold mb-4">Shop by Category</h1>
                        <p className="text-lg text-primary-light max-w-2xl mx-auto">
                            Explore hundreds of vendors across every category. Find exactly what you need, from local sellers near you.
                        </p>
                    </div>
                </section>

                {/* Categories grid */}
                <section className="py-16 lg:py-20">
                    <div className="container mx-auto px-4">
                        {categories.length === 0 ? (
                            <p className="text-center text-primary-brown text-lg">No categories available yet.</p>
                        ) : (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                {categories.map((category) => (
                                    <Link
                                        key={category.id}
                                        href={`/categories/${category.id}/vendors`}
                                        className="group relative rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2"
                                    >
                                        <div className="relative h-64 overflow-hidden">
                                            {categoryImages[category.id] ? (
                                                <img
                                                    src={categoryImages[category.id]}
                                                    alt={category.name}
                                                    className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                                />
                                            ) : (
                                                <div className="w-full h-full bg-primary-olive/20 flex items-center justify-center">
                                                    <svg className="w-16 h-16 text-primary-olive/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                                    </svg>
                                                </div>
                                            )}
                                            <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />
                                            <div className="absolute bottom-0 inset-x-0 p-5">
                                                <h3 className="text-xl font-bold text-white group-hover:text-primary-peach transition-colors duration-300">
                                                    {category.name}
                                                </h3>
                                                {category.description && (
                                                    <p className="text-sm text-white/70 mt-1">{category.description}</p>
                                                )}
                                            </div>
                                        </div>

                                        {/* Hover arrow */}
                                        <div className="absolute top-4 right-4 bg-white/90 backdrop-blur-sm rounded-full p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 shadow-lg">
                                            <svg className="w-4 h-4 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>
                </section>
            </main>

            <Footer />
        </div>
    );
};

export default Categories;
