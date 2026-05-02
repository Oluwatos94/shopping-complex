import React from 'react';
import { Link } from '@inertiajs/react';

const HeroSection: React.FC = () => {
    return (
        <section className="relative bg-gradient-to-br from-white via-[#FAFAF7] to-[#F5F3EE] overflow-hidden">
            <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-primary-olive/5 rounded-full -translate-y-1/2 translate-x-1/3 pointer-events-none" />
            <div className="absolute bottom-0 left-0 w-80 h-80 bg-primary-peach/10 rounded-full translate-y-1/2 -translate-x-1/4 pointer-events-none" />

            <div className="container mx-auto px-4 pt-16 pb-6 lg:pt-24 lg:pb-8 relative">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

                    {/* Left: Content */}
                    <div>
                        <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-primary-dark leading-tight mb-6 max-w-3xl">
                            Get connected to{' '}
                            <span className="relative inline-block">
                                <span className="text-primary-olive">nearest vendors.</span>
                                <svg className="absolute -bottom-1 left-0 w-full" viewBox="0 0 200 8" fill="none" aria-hidden="true">
                                    <path d="M2 6 Q50 2 100 5 Q150 8 198 3" stroke="#6B7C3E" strokeWidth="2.5" strokeLinecap="round" fill="none"/>
                                </svg>
                            </span>{' '}
                            Shop in one click.
                        </h1>

                        <p className="text-lg text-primary-brown leading-relaxed mb-10 max-w-xl">
                            Discover local vendors in real-time through our platform or directly on WhatsApp. Instant connections to what you need.
                        </p>

                        <Link
                            href="/products"
                            className="inline-flex items-center justify-center bg-primary-dark text-white px-8 py-4 rounded-xl text-base font-semibold hover:bg-primary-brown transition-colors duration-200 shadow-lg shadow-primary-dark/20"
                        >
                            Start Shopping
                        </Link>
                    </div>

                    {/* Right: Decorative UI mockups */}
                    <div className="relative hidden lg:block h-[440px]">
                        {/* Center card */}
                        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-10">
                            <div className="h-36 bg-gradient-to-br from-primary-olive/15 to-primary-peach/20 flex items-center justify-center">
                                <div className="w-14 h-14 bg-white/60 rounded-full flex items-center justify-center shadow-sm">
                                    <svg className="w-7 h-7 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                            </div>
                            <div className="p-4">
                                <div className="flex items-center justify-between mb-2">
                                    <div className="h-3 bg-gray-200 rounded w-24" />
                                    <div className="flex items-center gap-1">
                                        <span className="w-2 h-2 bg-green-400 rounded-full animate-pulse" />
                                        <span className="text-xs text-gray-400">Active</span>
                                    </div>
                                </div>
                                <div className="h-2 bg-gray-100 rounded w-full mb-1.5" />
                                <div className="h-2 bg-gray-100 rounded w-2/3 mb-3" />
                                <div className="flex items-center gap-0.5 mb-3">
                                    {[...Array(5)].map((_, i) => (
                                        <div key={i} className="w-3 h-3 bg-primary-peach rounded-sm" />
                                    ))}
                                    <span className="text-xs text-gray-400 ml-1">5.0</span>
                                </div>
                                <div className="h-8 bg-primary-dark rounded-lg" />
                            </div>
                        </div>

                        {/* Floating: location badge */}
                        <div className="absolute top-10 left-4 w-48 bg-white rounded-xl shadow-lg border border-gray-100 p-3 z-20 -rotate-3">
                            <div className="flex items-center gap-2.5">
                                <div className="w-9 h-9 rounded-lg bg-primary-peach/30 flex items-center justify-center flex-shrink-0">
                                    <svg className="w-4 h-4 text-primary-brown" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-xs font-semibold text-primary-dark">Vendor nearby</p>
                                    <p className="text-xs text-gray-400">0.3 km away</p>
                                </div>
                            </div>
                        </div>

                        {/* Floating: WhatsApp badge */}
                        <div className="absolute bottom-16 right-4 w-52 bg-white rounded-xl shadow-lg border border-gray-100 p-3 z-20 rotate-2">
                            <div className="flex items-center gap-2.5">
                                <div className="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0">
                                    <svg className="w-5 h-5 text-green-600" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.526 5.845L.057 23.882l6.198-1.625A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.68.965.982-3.59-.234-.369A9.818 9.818 0 1112 21.818z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-xs font-semibold text-primary-dark">Message on WhatsApp</p>
                                    <p className="text-xs text-gray-400">Direct connection</p>
                                </div>
                            </div>
                        </div>

                        {/* Floating: verified badge */}
                        <div className="absolute top-1/3 right-6 bg-white rounded-xl shadow-lg border border-gray-100 px-3 py-2 z-20 flex items-center gap-2">
                            <div className="w-6 h-6 bg-primary-peach/30 rounded-full flex items-center justify-center">
                                <svg className="w-3.5 h-3.5 text-primary-brown" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <span className="text-xs font-semibold text-primary-dark">Verified Vendor</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Feature strip */}
            <div className="border-t border-gray-100 bg-white/60">
                <div className="container mx-auto px-4 py-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="flex items-start gap-3">
                            <div className="w-10 h-10 bg-primary-olive/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 className="font-semibold text-primary-dark text-sm">Location-Based Discovery</h3>
                                <p className="text-xs text-gray-500 mt-0.5 leading-relaxed">Find vendors closest to you, sorted by distance in real-time.</p>
                            </div>
                        </div>
                        <div className="flex items-start gap-3">
                            <div className="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg className="w-5 h-5 text-green-600" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.526 5.845L.057 23.882l6.198-1.625A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.68.965.982-3.59-.234-.369A9.818 9.818 0 1112 21.818z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 className="font-semibold text-primary-dark text-sm">WhatsApp Direct Connect</h3>
                                <p className="text-xs text-gray-500 mt-0.5 leading-relaxed">Message any vendor instantly on WhatsApp — no extra app needed.</p>
                            </div>
                        </div>
                        <div className="flex items-start gap-3">
                            <div className="w-10 h-10 bg-primary-peach/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg className="w-5 h-5 text-primary-brown" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div>
                                <h3 className="font-semibold text-primary-dark text-sm">Browse & Shop Instantly</h3>
                                <p className="text-xs text-gray-500 mt-0.5 leading-relaxed">Explore products from many local vendors all in one place.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default HeroSection;
