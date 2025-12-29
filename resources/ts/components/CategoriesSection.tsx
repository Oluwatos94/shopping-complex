import React, { useRef } from 'react';
import { Link } from '@inertiajs/react';
import { LandingCategory } from '@/types/landing';

const CategoriesSection: React.FC = () => {
    const scrollContainerRef = useRef<HTMLDivElement>(null);

    const categories: LandingCategory[] = [
        {
            id: 1,
            name: 'Electronics',
            icon: '📱',
            description: '85 vendors',
            vendorCount: 85,
        },
        {
            id: 2,
            name: 'Fashion',
            icon: '👗',
            description: '120 vendors',
            vendorCount: 120,
        },
        {
            id: 3,
            name: 'Groceries',
            icon: '🍎',
            description: '95 vendors',
            vendorCount: 95,
        },
        {
            id: 4,
            name: 'Health & Beauty',
            icon: '💄',
            description: '72 vendors',
            vendorCount: 72,
        },
        {
            id: 5,
            name: 'Home & Living',
            icon: '🏠',
            description: '65 vendors',
            vendorCount: 65,
        },
        {
            id: 6,
            name: 'Sports',
            icon: '⚽',
            description: '48 vendors',
            vendorCount: 48,
        },
        {
            id: 7,
            name: 'Books',
            icon: '📚',
            description: '55 vendors',
            vendorCount: 55,
        },
        {
            id: 8,
            name: 'Services',
            icon: '🔧',
            description: '110 vendors',
            vendorCount: 110,
        },
    ];

    const scroll = (direction: 'left' | 'right') => {
        if (scrollContainerRef.current) {
            const scrollAmount = 320; // Width of card + gap
            const newScrollPosition = scrollContainerRef.current.scrollLeft + (direction === 'left' ? -scrollAmount : scrollAmount);
            scrollContainerRef.current.scrollTo({
                left: newScrollPosition,
                behavior: 'smooth'
            });
        }
    };

    return (
        <section className="bg-gradient-to-b from-white to-primary-light py-16 lg:py-24">
            <div className="container mx-auto px-4">
                <div className="text-center mb-12">
                    <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-primary-dark mb-4">
                        Shop by Category
                    </h2>
                    <p className="text-lg text-primary-brown max-w-2xl mx-auto">
                        Discover thousands of products across different categories
                    </p>
                </div>

                {/* Carousel Container */}
                <div className="relative">
                    {/* Left Arrow */}
                    <button
                        onClick={() => scroll('left')}
                        className="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 z-10 bg-white hover:bg-primary-olive hover:text-white text-primary-dark rounded-full p-3 shadow-xl transition-all duration-300 transform hover:scale-110"
                        aria-label="Scroll left"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    {/* Scrollable Categories */}
                    <div
                        ref={scrollContainerRef}
                        className="flex gap-6 overflow-x-auto scrollbar-hide scroll-smooth pb-4"
                        style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
                    >
                        {categories.map((category) => (
                            <Link
                                key={category.id}
                                href={`/categories/${category.id}/vendors`}
                                className="group relative flex-shrink-0 w-72 bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2"
                            >
                                {/* Tall Category Image/Icon Background */}
                                <div className="relative h-96 bg-gradient-to-br from-primary-olive/10 to-primary-peach/10 flex items-center justify-center overflow-hidden">
                                    <div className="absolute inset-0 bg-primary-dark/5 group-hover:bg-primary-dark/10 transition-colors duration-300"></div>
                                    <div className="text-8xl transform group-hover:scale-110 transition-transform duration-300 relative z-10">
                                        {category.icon}
                                    </div>

                                    {/* Gradient Overlay at bottom */}
                                    <div className="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-black/40 to-transparent"></div>
                                </div>

                                {/* Category Info */}
                                <div className="p-6 bg-white">
                                    <h3 className="text-2xl font-bold text-primary-dark mb-2 group-hover:text-primary-olive transition-colors">
                                        {category.name}
                                    </h3>
                                    <p className="text-base text-primary-brown">
                                        {category.description}
                                    </p>
                                </div>

                                {/* Hover Arrow */}
                                <div className="absolute top-4 right-4 bg-white/90 backdrop-blur-sm rounded-full p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 shadow-lg">
                                    <svg
                                        className="w-5 h-5 text-primary-olive"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M9 5l7 7-7 7"
                                        />
                                    </svg>
                                </div>
                            </Link>
                        ))}
                    </div>

                    {/* Right Arrow */}
                    <button
                        onClick={() => scroll('right')}
                        className="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 z-10 bg-white hover:bg-primary-olive hover:text-white text-primary-dark rounded-full p-3 shadow-xl transition-all duration-300 transform hover:scale-110"
                        aria-label="Scroll right"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <div className="text-center mt-12">
                    <Link
                        href="/categories"
                        className="inline-flex items-center bg-primary-olive text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-primary-peach transition-all duration-300 transform hover:scale-105 shadow-lg"
                    >
                        View All Categories
                        <svg className="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>
    );
};

export default CategoriesSection;
