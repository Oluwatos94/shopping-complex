import React, { useRef } from 'react';
import { Link } from '@inertiajs/react';

interface Category {
    id: number;
    name: string;
    image: string;
    description: string;
}

const categories: Category[] = [
    {
        id: 8,
        name: 'Fashion & Clothing',
        image: '/images/cat8.jpg',
        description: '120 vendors',
    },
    {
        id: 4,
        name: 'Groceries & Food',
        image: '/images/cat4.jpg',
        description: '95 vendors',
    },
    {
        id: 5,
        name: 'Health & Beauty',
        image: '/images/cat5.jpg',
        description: '72 vendors',
    },
    {
        id: 6,
        name: 'Accessories & Lifestyle',
        image: '/images/cat6.jpg',
        description: '65 vendors',
    },
    {
        id: 7,
        name: 'Electronics & Repairs',
        image: '/images/cat7.jpg',
        description: '85 vendors',
    },
    {
        id: 2,
        name: 'Books & Education',
        image: '/images/cat2.jpg',
        description: '55 vendors',
    },
    {
        id: 1,
        name: 'Services',
        image: '/images/cat1.jpg',
        description: '110 vendors',
    },
];

const CategoriesSection: React.FC = () => {
    const scrollContainerRef = useRef<HTMLDivElement>(null);

    const scroll = (direction: 'left' | 'right') => {
        if (scrollContainerRef.current) {
            const scrollAmount = 320;
            scrollContainerRef.current.scrollTo({
                left: scrollContainerRef.current.scrollLeft + (direction === 'left' ? -scrollAmount : scrollAmount),
                behavior: 'smooth',
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

                <div className="relative">
                    {/* Left Arrow */}
                    <button
                        onClick={() => scroll('left')}
                        className="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 z-10 bg-white hover:bg-primary-olive hover:text-white text-primary-dark rounded-full p-3 shadow-xl transition-all duration-300 hover:scale-110"
                        aria-label="Scroll left"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    {/* Scrollable track */}
                    <div
                        ref={scrollContainerRef}
                        className="flex gap-6 overflow-x-auto pb-4"
                        style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
                    >
                        {categories.map((category) => (
                            <Link
                                key={category.id}
                                href={`/categories/${category.id}/vendors`}
                                className="group relative flex-shrink-0 w-72 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2"
                            >
                                {/* Image */}
                                <div className="relative h-96 overflow-hidden">
                                    <img
                                        src={category.image}
                                        alt={category.name}
                                        className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                    />
                                    {/* Dark gradient overlay */}
                                    <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />

                                    {/* Category name overlaid on image */}
                                    <div className="absolute bottom-0 inset-x-0 p-6">
                                        <h3 className="text-xl font-bold text-white mb-1 group-hover:text-primary-peach transition-colors duration-300">
                                            {category.name}
                                        </h3>
                                        <p className="text-sm text-white/70">{category.description}</p>
                                    </div>
                                </div>

                                {/* Hover arrow badge */}
                                <div className="absolute top-4 right-4 bg-white/90 backdrop-blur-sm rounded-full p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 shadow-lg">
                                    <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </Link>
                        ))}
                    </div>

                    {/* Right Arrow */}
                    <button
                        onClick={() => scroll('right')}
                        className="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 z-10 bg-white hover:bg-primary-olive hover:text-white text-primary-dark rounded-full p-3 shadow-xl transition-all duration-300 hover:scale-110"
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
                        className="inline-flex items-center bg-primary-olive text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-primary-peach transition-all duration-300 hover:scale-105 shadow-lg"
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
