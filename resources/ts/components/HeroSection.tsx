import React from 'react';
import { Link } from '@inertiajs/react';

const HeroSection: React.FC = () => {
    return (
        <section className="relative bg-gradient-to-br from-primary-dark via-primary-brown to-primary-dark text-white overflow-hidden">
            <div className="absolute inset-0 opacity-10">
                <div className="absolute inset-0 bg-[url('/images/shopping-image1.jpg')] bg-cover bg-center"></div>
            </div>

            <div className="container mx-auto px-4 py-20 lg:py-32 relative z-10">
                <div className="max-w-4xl mx-auto text-center">
                    <h1 className="text-4xl md:text-6xl lg:text-7xl font-bold mb-6 leading-tight">
                        Connect with{' '}
                        <span className="text-primary-peach">Local Vendors</span>
                        {' '}in Real-Time
                    </h1>

                    <p className="text-lg md:text-xl lg:text-2xl text-primary-light mb-8 max-w-2xl mx-auto">
                        Your one-stop marketplace connecting customers with trusted vendors instantly.
                        Shop, discover, and get what you need delivered right to your doorstep.
                    </p>

                    <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
                        <Link
                            href="/products"
                            className="bg-primary-olive text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-primary-peach transition-all duration-300 transform hover:scale-105 shadow-lg w-full sm:w-auto text-center"
                        >
                            Explore Products
                        </Link>

                        <Link
                            href="/vendor/register"
                            className="bg-transparent border-2 border-primary-light text-primary-light px-8 py-4 rounded-lg text-lg font-semibold hover:bg-primary-light hover:text-primary-dark transition-all duration-300 w-full sm:w-auto text-center"
                        >
                            Become a Vendor
                        </Link>
                    </div>

                    <div className="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                        <div className="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                            <div className="text-4xl font-bold text-primary-peach mb-2">500+</div>
                            <div className="text-primary-light">Trusted Vendors</div>
                        </div>
                        <div className="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                            <div className="text-4xl font-bold text-primary-peach mb-2">10k+</div>
                            <div className="text-primary-light">Happy Customers</div>
                        </div>
                        <div className="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                            <div className="text-4xl font-bold text-primary-peach mb-2">50+</div>
                            <div className="text-primary-light">Product Categories</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default HeroSection;
