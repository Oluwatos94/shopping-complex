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
                            Register as a vendor and get access to customers in your area.
                            No barriers. No limits. Just real customers looking for your products right now.
                        </p>

                        <div className="flex flex-row gap-3">
                            <Link
                                href="/vendor/register"
                                className="flex-1 inline-flex items-center justify-center bg-primary-dark text-white px-4 py-3.5 sm:px-8 sm:py-4 rounded-lg text-sm sm:text-lg font-semibold hover:bg-primary-brown transition-all duration-300 shadow-lg"
                            >
                                Register as Vendor
                            </Link>
                            <button
                                onClick={() => setShowModal(true)}
                                className="flex-1 inline-flex items-center justify-center bg-transparent border-2 border-primary-dark text-primary-dark px-4 py-3.5 sm:px-8 sm:py-4 rounded-lg text-sm sm:text-lg font-semibold hover:bg-primary-dark hover:text-white transition-all duration-300"
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
                                desc: 'Register on Shopping Complex and click "Become a Vendor". Fill in your business details name, category, and location.',
                            },
                            {
                                step: 2,
                                title: 'Complete Your Profile & Upload Products',
                                desc: 'Add a profile photo, business description, and upload your product catalogue with images and prices. The more detailed your profile, the more customers you attract.',
                            },
                            {
                                step: 3,
                                title: 'Go Live & Get Discovered',
                                desc: 'Toggle your availability to "Online" and start appearing in searches for customers near you. No followers needed; proximity does the work.',
                            },
                            {
                                step: 4,
                                title: 'Chat with Customers',
                                desc: 'Get notified instantly when a customer contacts you. Chat directly on whatsapp or within the platform, negotiate, and arrange delivery.',
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
                            className="block w-full text-center bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-brown transition-colors duration-300"
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
