import React from 'react';
import { Link } from '@inertiajs/react';

const ImageShowcaseSection: React.FC = () => {
    return (
        <section className="py-16 lg:py-20 bg-white">
            <div className="container mx-auto px-4">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    {/* Image Side */}
                    <div className="relative">
                        <div className="relative rounded-2xl overflow-hidden shadow-2xl">
                            <img
                                src="https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/images/shopping-image3.jpg"
                                alt="Fresh grocery shopping experience"
                                className="w-full h-auto object-cover"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-primary-dark/60 to-transparent"></div>
                        </div>

                        {/* Floating Stats Cards */}
                        <div className="absolute -bottom-6 -right-6 bg-white rounded-xl shadow-xl p-6 max-w-xs">
                            <div className="flex items-center space-x-4">
                                <div className="bg-primary-olive/10 p-3 rounded-lg">
                                    <svg className="w-8 h-8 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <div className="text-2xl font-bold text-primary-dark">5000+</div>
                                    <div className="text-sm text-primary-brown">Products Available</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Content Side */}
                    <div>
                        <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-primary-dark mb-6">
                            Fresh Products,{' '}
                            <span className="text-primary-olive">Delivered Fast</span>
                        </h2>

                        <p className="text-lg text-primary-brown mb-6 leading-relaxed">
                            From fresh groceries to electronics, fashion, and more—discover everything you need
                            from trusted local vendors. Shop with confidence knowing every product is verified
                            and every vendor is rated by real customers.
                        </p>

                        <div className="space-y-4 mb-8">
                            <div className="flex items-start space-x-3">
                                <div className="bg-primary-olive/10 p-2 rounded-lg mt-1">
                                    <svg className="w-5 h-5 text-primary-olive" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 className="font-semibold text-primary-dark mb-1">Quality Guaranteed</h4>
                                    <p className="text-primary-brown text-sm">All products are verified and quality-checked by our vendors</p>
                                </div>
                            </div>

                            <div className="flex items-start space-x-3">
                                <div className="bg-primary-olive/10 p-2 rounded-lg mt-1">
                                    <svg className="w-5 h-5 text-primary-olive" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 className="font-semibold text-primary-dark mb-1">Fast Delivery</h4>
                                    <p className="text-primary-brown text-sm">Get your orders delivered quickly with real-time tracking</p>
                                </div>
                            </div>

                            <div className="flex items-start space-x-3">
                                <div className="bg-primary-olive/10 p-2 rounded-lg mt-1">
                                    <svg className="w-5 h-5 text-primary-olive" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 className="font-semibold text-primary-dark mb-1">Secure Payments</h4>
                                    <p className="text-primary-brown text-sm">Safe and secure payment options for your peace of mind</p>
                                </div>
                            </div>
                        </div>

                        <Link
                            href="/products"
                            className="inline-flex items-center bg-primary-olive text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-primary-peach transition-all duration-300 transform hover:scale-105 shadow-lg"
                        >
                            Start Shopping
                            <svg className="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default ImageShowcaseSection;
