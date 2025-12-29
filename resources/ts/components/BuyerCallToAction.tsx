import React from 'react';
import { Link } from '@inertiajs/react';

const BuyerCallToAction: React.FC = () => {
    return (
        <section className="py-16 lg:py-20 bg-white">
            <div className="container mx-auto px-4">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    {/* Image Side */}
                    <div className="relative">
                        <div className="relative rounded-2xl overflow-hidden shadow-2xl">
                            <img
                                src="/images/shopping-image12.jpg"
                                alt="Shopping experience"
                                className="w-full h-auto object-cover"
                            />
                            <div className="absolute inset-0 bg-gradient-to-br from-primary-olive/20 to-transparent"></div>
                        </div>

                        {/* Floating Stats */}
                        <div className="absolute -bottom-6 -right-6 bg-white rounded-xl shadow-2xl p-6 max-w-xs">
                            <div className="flex items-center space-x-4">
                                <div className="bg-primary-olive/10 p-3 rounded-lg">
                                    <svg className="w-8 h-8 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <div className="text-2xl font-bold text-primary-dark">Best Deals</div>
                                    <div className="text-sm text-primary-brown">Save up to 50%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Content Side - Buyers */}
                    <div>
                        <div className="inline-block bg-primary-olive/10 px-4 py-2 rounded-full mb-6">
                            {/* <span className="text-primary-olive font-semibold">For Shoppers</span> */}
                        </div>

                        <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-primary-dark mb-6">
                            Discover Local Vendors{' '}
                            <span className="text-primary-olive">Near You</span>
                        </h2>

                        <p className="text-lg text-primary-brown mb-8 leading-relaxed">
                            Shop from verified local vendors in your area. Get exactly what you need,
                            delivered fast. Real-time connection, real-time delivery.
                        </p>

                        <div className="space-y-4 mb-8">
                            <div className="flex items-start space-x-4">
                                <div className="bg-primary-olive/10 p-3 rounded-lg mt-1 flex-shrink-0">
                                    <svg className="w-6 h-6 text-primary-olive" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 className="font-semibold text-primary-dark text-lg mb-1">Find Vendors Instantly</h4>
                                    <p className="text-primary-brown">
                                        See available vendors near you in real-time. No waiting, no searching—just instant connections
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start space-x-4">
                                <div className="bg-primary-olive/10 p-3 rounded-lg mt-1 flex-shrink-0">
                                    <svg className="w-6 h-6 text-primary-olive" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 className="font-semibold text-primary-dark text-lg mb-1">Chat Directly with Vendors</h4>
                                    <p className="text-primary-brown">
                                        Message vendors, ask questions, negotiate prices—all in real-time before you buy
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start space-x-4">
                                <div className="bg-primary-olive/10 p-3 rounded-lg mt-1 flex-shrink-0">
                                    <svg className="w-6 h-6 text-primary-olive" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 className="font-semibold text-primary-dark text-lg mb-1">Track Your Order Live</h4>
                                    <p className="text-primary-brown">
                                        Watch your order from preparation to delivery with live updates every step of the way
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-col sm:flex-row gap-4">
                            <Link
                                href="/products"
                                className="inline-flex items-center justify-center bg-primary-olive text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-primary-peach transition-all duration-300 transform hover:scale-105 shadow-lg"
                            >
                                Start Shopping
                                <svg className="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                            <Link
                                href="/how-it-works"
                                className="inline-flex items-center justify-center bg-transparent border-2 border-primary-olive text-primary-olive px-8 py-4 rounded-lg text-lg font-semibold hover:bg-primary-olive hover:text-white transition-all duration-300"
                            >
                                How It Works
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default BuyerCallToAction;
