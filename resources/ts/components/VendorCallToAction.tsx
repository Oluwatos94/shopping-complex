import React from 'react';
import { Link } from '@inertiajs/react';

const VendorCallToAction: React.FC = () => {
    return (
        <section className="py-16 lg:py-20 bg-gradient-to-br from-primary-dark to-primary-brown">
            <div className="container mx-auto px-4">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    {/* Content Side - Vendors */}
                    <div className="text-white order-2 lg:order-1">

                        <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold mb-6">
                            Reach Unlimited Customers at Your{' '}
                            <span className="text-primary-peach">Nearest Location</span>
                        </h2>

                        <p className="text-lg text-primary-light mb-8 leading-relaxed">
                            Register as a vendor and get access to unlimited potential customers in your area.
                            No barriers. No limits. Just real customers looking for your products right now.
                        </p>

                        <div className="space-y-4 mb-8">
                            <div className="flex items-start space-x-4">
                                <div className="bg-primary-peach/20 p-3 rounded-lg mt-1 flex-shrink-0">
                                    <svg className="w-6 h-6 text-primary-peach" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 className="font-semibold text-white text-lg mb-1">Location-Based Discovery</h4>
                                    <p className="text-primary-light">
                                        Get found by customers near you instantly when your location is on—just like Uber or Bolt
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start space-x-4">
                                <div className="bg-primary-peach/20 p-3 rounded-lg mt-1 flex-shrink-0">
                                    <svg className="w-6 h-6 text-primary-peach" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 className="font-semibold text-white text-lg mb-1">No Follower Barriers</h4>
                                    <p className="text-primary-light">
                                        Your visibility is not limited by followers or engagement
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start space-x-4">
                                <div className="bg-primary-peach/20 p-3 rounded-lg mt-1 flex-shrink-0">
                                    <svg className="w-6 h-6 text-primary-peach" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 className="font-semibold text-white text-lg mb-1">Unlimited Potential</h4>
                                    <p className="text-primary-light">
                                        Connect with every customer searching in your area—no caps, no algorithms holding you back
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-col sm:flex-row gap-4">
                            <Link
                                href="/vendor/register"
                                className="inline-flex items-center justify-center bg-primary-peach text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-primary-olive transition-all duration-300 transform hover:scale-105 shadow-lg"
                            >
                                Register as Vendor
                                <svg className="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                            <Link
                                href="/vendor/learn-more"
                                className="inline-flex items-center justify-center bg-transparent border-2 border-primary-light text-primary-light px-8 py-4 rounded-lg text-lg font-semibold hover:bg-white hover:text-primary-dark transition-all duration-300"
                            >
                                Learn More
                            </Link>
                        </div>
                    </div>

                    {/* Image Side */}
                    <div className="relative order-1 lg:order-2">
                        <div className="relative rounded-2xl overflow-hidden shadow-2xl">
                            <img
                                src="/images/shopping-image6.jpg"
                                alt="Vendor clothing store"
                                className="w-full h-auto object-cover"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-primary-dark/60 to-transparent"></div>
                        </div>

                        {/* Floating Badge */}
                        <div className="absolute -top-6 -left-6 bg-primary-peach text-white px-6 py-3 rounded-xl shadow-2xl font-bold text-lg">
                            500+ Active Vendors
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default VendorCallToAction;
