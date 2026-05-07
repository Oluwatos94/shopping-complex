import React, { useState } from 'react';
import { Link } from '@inertiajs/react';

const BuyerCallToAction: React.FC = () => {
    const [showModal, setShowModal] = useState(false);

    return (
        <>
        <section className="py-16 lg:py-20 bg-gradient-to-br from-[#fdf5ef] to-[#f5f0e8]">
            <div className="container mx-auto px-4">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    {/* Image Side */}
                    <div className="relative">
                        <div className="relative rounded-2xl overflow-hidden shadow-2xl">
                            <img
                                src="https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/shopping-image12.jpg"
                                alt="Shopping experience"
                                className="w-full h-auto object-cover"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-primary-dark/30 to-transparent"></div>
                        </div>
                    </div>

                    {/* Content Side - Buyers */}
                    <div>
                        <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-primary-dark mb-6">
                            Discover Vendors{' '}
                            <span className="text-primary-brown">Near You</span>
                        </h2>

                        <p className="text-lg text-primary-brown mb-8 leading-relaxed">
                            Shop from vendors in your area. Get exactly what you need,
                            delivered fast. Real-time connection, real-time delivery.
                        </p>

                        <div className="flex flex-row gap-3">
                            <Link
                                href="/products"
                                className="flex-1 inline-flex items-center justify-center bg-primary-dark text-white px-4 py-3.5 sm:px-8 sm:py-4 rounded-lg text-sm sm:text-lg font-semibold hover:bg-primary-brown transition-all duration-300 shadow-lg"
                            >
                                Start Shopping
                            </Link>
                            <button
                                onClick={() => setShowModal(true)}
                                className="flex-1 inline-flex items-center justify-center bg-transparent border-2 border-primary-dark text-primary-dark px-4 py-3.5 sm:px-8 sm:py-4 rounded-lg text-sm sm:text-lg font-semibold hover:bg-primary-dark hover:text-white transition-all duration-300"
                            >
                                Learn More
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {/* Buyer How-It-Works Modal */}
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
                            <h3 className="text-lg md:text-2xl font-bold text-white">How to Shop on jiidaa</h3>
                            <p className="text-primary-light text-xs md:text-sm mt-1">Find and buy from local vendors easily</p>
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

                    {/* WhatsApp discovery callout */}
                    <div className="mx-4 mt-5 md:mx-6 md:mt-6 bg-[#25D366]/10 border border-[#25D366]/30 rounded-xl p-4 flex gap-3">
                        <svg className="w-6 h-6 text-[#25D366] flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.526 5.845L.057 23.882l6.198-1.625A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.68.965.982-3.59-.234-.369A9.818 9.818 0 1112 21.818z"/>
                        </svg>
                        <div>
                            <p className="text-sm font-semibold text-gray-800">Discover vendors via WhatsApp</p>
                            <p className="text-sm text-gray-600 mt-0.5">You can also find and contact vendors directly through our WhatsApp bot — no app download needed.</p>
                        </div>
                    </div>

                    {/* Steps */}
                    <div className="px-4 py-5 md:px-6 md:py-6 space-y-5">
                        {[
                            {
                                step: 1,
                                title: 'Browse or Search',
                                desc: 'Visit the platform and explore products by category, or search for something specific. You can also message our WhatsApp bot with a product name to get instant vendor results.',
                            },
                            {
                                step: 2,
                                title: 'View Vendor Profiles',
                                desc: 'Click on any vendor to see their full catalogue, ratings, reviews, and location. Get all the information you need before making a decision.',
                            },
                            {
                                step: 3,
                                title: 'Chat Directly with the Vendor',
                                desc: 'Use the in-app chat or WhatsApp to ask questions, confirm availability, negotiate prices.',
                            },
                        ].map(({ step, title, desc }) => (
                            <div key={step} className="flex gap-4">
                                <div className="flex-shrink-0 w-9 h-9 rounded-full bg-primary-peach text-white flex items-center justify-center font-bold text-sm">
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
                            href="/products"
                            onClick={() => setShowModal(false)}
                            className="block w-full text-center bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-brown transition-colors duration-300"
                        >
                            Start Shopping Now
                        </Link>
                    </div>
                </div>
            </div>
        )}
        </>
    );
};

export default BuyerCallToAction;
