import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { Category } from '@/types';

interface PageProps {
    [key: string]: unknown;
    categories: Category[];
}

// Map category IDs to local images (same as the landing page section)
const categoryImages: Record<number, string> = {
    1:  'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/cat1.jpg',
    2:  'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/cat2.jpg',
    4:  'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/cat4.jpg',
    5:  'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/cat5.jpg',
    6:  'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/cat6.jpg',
    7:  'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/cat7.jpg',
    8:  'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/cat8.jpg',
    9:  'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/funiture.jpg',
    10: 'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/O&E.jpg',
    11: 'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/autoTools.jpg',
    12: 'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/A&G.jpg',
    13: 'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/Catering.jpg',
    14: 'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/artisan.jpg',
    15: 'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/footwear.jpg',
    16: 'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/Bag.jpg',
    17: 'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/Kid.jpg',
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
                                        href={`/categories/${category.id}/products`}
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
