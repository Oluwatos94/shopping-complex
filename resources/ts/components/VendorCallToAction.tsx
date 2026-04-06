import React, { useState } from 'react';
import { Link } from '@inertiajs/react';

const VendorCallToAction: React.FC = () => {
    const [showModal, setShowModal] = useState(false);

    return (
        <>
        <section className="py-16 lg:py-20 bg-white">
            <div className="container mx-auto px-4">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    {/* Content Side - Vendors */}
                    <div className="order-2 lg:order-1">
                        <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-primary-dark mb-6">
                            Reach Unlimited Customers at Your{' '}
                            <span className="text-primary-olive">Nearest Location</span>
                        </h2>

                        <p className="text-lg text-primary-brown mb-8 leading-relaxed">
                            Register as a vendor and get access to unlimited potential customers in your area.
                            No barriers. No limits. Just real customers looking for your products right now.
                        </p>

                        <div className="space-y-4 mb-8">
                            <div className="flex items-start space-x-4">
                                <div className="bg-primary-olive/10 p-3 rounded-lg mt-1 flex-shrink-0">
                                    <svg className="w-6 h-6 text-primary-olive" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 className="font-semibold text-primary-dark text-lg mb-1">Location-Based Discovery</h4>
                                    <p className="text-primary-brown">
                                        Get found by customers near you instantly when your location is on—just like Uber or Bolt
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
                                    <h4 className="font-semibold text-primary-dark text-lg mb-1">No Follower Barriers</h4>
                                    <p className="text-primary-brown">
                                        Your visibility is not limited by followers or engagement
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
                                    <h4 className="font-semibold text-primary-dark text-lg mb-1">Unlimited Potential</h4>
                                    <p className="text-primary-brown">
                                        Connect with every customer searching in your area—no caps, no algorithms holding you back
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-col sm:flex-row gap-4">
                            <Link
                                href="/vendor/register"
                                className="inline-flex items-center justify-center bg-primary-olive text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-primary-peach transition-all duration-300 transform hover:scale-105 shadow-lg"
                            >
                                Register as Vendor
                                <svg className="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                            <button
                                onClick={() => setShowModal(true)}
                                className="inline-flex items-center justify-center bg-transparent border-2 border-primary-olive text-primary-olive px-8 py-4 rounded-lg text-lg font-semibold hover:bg-primary-olive hover:text-white transition-all duration-300"
                            >
                                Learn More
                            </button>
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
                        <div className="hidden sm:block absolute -top-6 -left-6 bg-primary-olive text-white px-6 py-3 rounded-xl shadow-2xl font-bold text-lg">
                            500+ Active Vendors
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {/* Vendor How-It-Works Modal */}
        {showModal && (
            <div
                className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                onClick={() => setShowModal(false)}
            >
                <div
                    className="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                    onClick={(e) => e.stopPropagation()}
                >
                    {/* Header */}
                    <div className="bg-gradient-to-r from-primary-dark to-primary-brown px-4 py-4 md:px-6 md:py-5 rounded-t-2xl flex items-center justify-between gap-3">
                        <div>
                            <h3 className="text-lg md:text-2xl font-bold text-white">How Vendors Use Shopping Complex</h3>
                            <p className="text-primary-light text-xs md:text-sm mt-1">Everything you need to start selling</p>
                        </div>
                        <button
                            onClick={() => setShowModal(false)}
                            className="text-white/70 hover:text-white transition-colors flex-shrink-0"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {/* Steps */}
                    <div className="px-4 py-5 md:px-6 md:py-6 space-y-5">
                        {[
                            {
                                step: 1,
                                title: 'Create Your Account',
                                desc: 'Register on Shopping Complex and choose the Vendor account type. Fill in your business details — name, category, and location.',
                            },
                            {
                                step: 2,
                                title: 'Complete Your Profile & Upload Products',
                                desc: 'Add a profile photo, business description, and upload your product catalogue with images and prices. The more detailed your profile, the more customers you attract.',
                            },
                            {
                                step: 3,
                                title: 'Go Live & Get Discovered',
                                desc: 'Toggle your availability to "Online" and start appearing in searches for customers near you — just like a driver going online on Uber. No followers needed; proximity does the work.',
                            },
                            {
                                step: 4,
                                title: 'Receive Orders & Chat with Customers',
                                desc: 'Get notified instantly when a customer contacts you. Chat directly, confirm orders, negotiate, and arrange delivery — all within the platform.',
                            },
                            {
                                step: 5,
                                title: 'Track Performance with Analytics',
                                desc: 'Use your vendor dashboard to monitor how many customers viewed your profile, searched for you via WhatsApp, and how your products are performing.',
                            },
                        ].map(({ step, title, desc }) => (
                            <div key={step} className="flex gap-4">
                                <div className="flex-shrink-0 w-9 h-9 rounded-full bg-primary-olive text-white flex items-center justify-center font-bold text-sm">
                                    {step}
                                </div>
                                <div>
                                    <h4 className="font-semibold text-primary-dark mb-1">{title}</h4>
                                    <p className="text-primary-brown text-sm leading-relaxed">{desc}</p>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* CTA */}
                    <div className="px-4 pb-4 md:px-6 md:pb-6">
                        <Link
                            href="/vendor/register"
                            onClick={() => setShowModal(false)}
                            className="block w-full text-center bg-primary-olive text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-peach transition-colors duration-300"
                        >
                            Register as a Vendor
                        </Link>
                    </div>
                </div>
            </div>
        )}
        </>
    );
};

export default VendorCallToAction;
