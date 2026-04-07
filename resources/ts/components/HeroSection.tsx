import React from 'react';
import { Link } from '@inertiajs/react';

interface Props {
    platformWhatsApp: string;
}

const mockVendors = [
    { initial: 'M', name: "Mama's Fresh Store",    category: 'Groceries & Food',     distance: '0.3km', online: true },
    { initial: 'T', name: 'TechHub Repairs',        category: 'Electronics & Repairs', distance: '1.2km', online: true },
    { initial: 'A', name: 'Adire Fashion House',    category: 'Fashion & Clothing',    distance: '1.8km', online: true },
    { initial: 'N', name: 'Nature Beauty Supplies', category: 'Health & Beauty',       distance: '2.4km', online: false },
];

const HeroSection: React.FC<Props> = ({ platformWhatsApp }) => {
    const whatsAppHref = platformWhatsApp
        ? `https://wa.me/${platformWhatsApp.replace(/[^0-9]/g, '')}`
        : 'https://wa.me/';

    return (
        <section className="relative bg-white overflow-hidden">

            {/* Decorative background blobs */}
            <div className="absolute top-0 right-0 w-72 h-72 lg:w-[480px] lg:h-[480px] bg-primary-olive/8 rounded-full -translate-y-1/3 translate-x-1/3 pointer-events-none" />
            <div className="absolute bottom-0 left-0 w-56 h-56 bg-primary-peach/10 rounded-full translate-y-1/3 -translate-x-1/4 pointer-events-none" />
            <div className="absolute top-1/2 left-1/3 w-32 h-32 bg-primary-light/60 rounded-full pointer-events-none hidden lg:block" />

            <div className="container mx-auto px-4 py-16 lg:py-0 lg:min-h-[92vh] flex items-center">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-10 w-full items-center">

                    {/* ── Left: Content ── */}
                    <div>

                        {/* Live badge */}
                        <div className="inline-flex items-center gap-2 bg-primary-olive/10 text-primary-olive border border-primary-olive/20 px-4 py-2 rounded-full text-sm font-semibold mb-6">
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            500+ Vendors Live Now
                        </div>

                        <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-primary-dark leading-tight mb-5">
                            Your Local Vendors,{' '}
                            <span className="relative inline-block">
                                <span className="text-primary-olive">One Message</span>
                                <svg className="absolute -bottom-1 left-0 w-full" viewBox="0 0 200 8" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M2 6 Q50 2 100 5 Q150 8 198 3" stroke="#6B7C3E" strokeWidth="2.5" strokeLinecap="round" fill="none"/>
                                </svg>
                            </span>{' '}
                            Away
                        </h1>

                        <p className="text-lg text-primary-brown leading-relaxed mb-8 max-w-lg">
                            Discover verified local vendors in real-time — through our platform or directly on
                            WhatsApp. No barriers, no algorithms. Just instant connections to what you need.
                        </p>

                        {/* CTAs */}
                        <div className="flex flex-col sm:flex-row gap-3 mb-8">
                            <Link
                                href="/products"
                                className="flex-1 inline-flex items-center justify-center bg-primary-dark text-white px-6 py-4 rounded-xl text-base font-semibold hover:bg-primary-brown transition-colors duration-200 shadow-lg shadow-primary-dark/20"
                            >
                                Start Shopping
                                <svg className="w-4 h-4 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>

                            <a
                                href={whatsAppHref}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex-1 inline-flex items-center justify-center bg-[#25D366] hover:bg-[#1ebe5d] text-white px-6 py-4 rounded-xl text-base font-semibold transition-colors duration-200 shadow-lg shadow-[#25D366]/25"
                            >
                                <svg className="w-5 h-5 mr-2 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.526 5.845L.057 23.882l6.198-1.625A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.68.965.982-3.59-.234-.369A9.818 9.818 0 1112 21.818z"/>
                                </svg>
                                Discover Vendors via WhatsApp
                            </a>
                        </div>

                        {/* Trust pills */}
                        <div className="flex flex-wrap gap-x-6 gap-y-2 text-sm text-primary-brown">
                            <span className="flex items-center gap-1.5">
                                <svg className="w-4 h-4 text-primary-olive" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                </svg>
                                Free to shop
                            </span>
                            <span className="flex items-center gap-1.5">
                                <svg className="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                4.8/5 rating
                            </span>
                            <span className="flex items-center gap-1.5">
                                <svg className="w-4 h-4 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                10,000+ customers
                            </span>
                        </div>
                    </div>

                    {/* ── Right: Vendor discovery widget ── */}
                    <div className="flex justify-center">
                        <div className="relative w-full max-w-sm">

                            {/* Floating "order placed" toast */}
                            <div className="absolute -top-4 -left-4 bg-white rounded-xl shadow-xl border border-gray-100 px-4 py-3 flex items-center gap-3 z-20 hidden sm:flex">
                                <div className="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                    <svg className="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold text-gray-800">Order confirmed!</div>
                                    <div className="text-xs text-gray-400">Just now · Groceries</div>
                                </div>
                            </div>

                            {/* Floating delivery badge */}
                            <div className="absolute top-1/2 -translate-y-1/2 -right-5 bg-primary-dark text-white rounded-xl shadow-xl px-4 py-3 z-20 hidden sm:block">
                                <div className="text-lg font-bold">98%</div>
                                <div className="text-xs text-primary-light">On-time delivery</div>
                            </div>

                            {/* Main card — the discovery widget */}
                            <div className="bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden">

                                {/* Widget header */}
                                <div className="bg-primary-dark px-4 py-3 flex items-center gap-3">
                                    <img src="/logo/dark-mode-logo.svg" alt="Shopping Complex" className="h-7 w-auto" />
                                    <span className="text-white font-semibold text-sm flex-1">Vendors near you</span>
                                    <div className="flex items-center gap-1.5 bg-green-500/20 text-green-400 text-xs font-medium px-2.5 py-1 rounded-full">
                                        <span className="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                                        3 online
                                    </div>
                                </div>

                                {/* Vendor list */}
                                <div className="divide-y divide-gray-50">
                                    {mockVendors.map((vendor, i) => (
                                        <div key={i} className="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors cursor-pointer">
                                            <div className="relative flex-shrink-0">
                                                <div className="w-10 h-10 rounded-full bg-primary-olive flex items-center justify-center text-white font-bold text-sm">
                                                    {vendor.initial}
                                                </div>
                                                <span className={`absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white ${vendor.online ? 'bg-green-500' : 'bg-gray-300'}`} />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="text-sm font-semibold text-gray-800 truncate">{vendor.name}</div>
                                                <div className="text-xs text-gray-400">{vendor.category}</div>
                                            </div>
                                            <div className="text-right flex-shrink-0">
                                                <div className="text-xs font-medium text-primary-olive">{vendor.distance}</div>
                                                <div className={`text-xs ${vendor.online ? 'text-green-500' : 'text-gray-400'}`}>
                                                    {vendor.online ? 'Online' : 'Away'}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* WhatsApp-style input */}
                                <div className="bg-[#f0f2f5] px-3 py-3 flex items-center gap-2 border-t border-gray-100">
                                    <div className="flex-1 bg-white rounded-full px-4 py-2.5 text-sm text-gray-400 border border-gray-200">
                                        "I need fresh tomatoes near me..."
                                    </div>
                                    <a
                                        href={whatsAppHref}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="w-10 h-10 rounded-full bg-[#25D366] hover:bg-[#1ebe5d] transition-colors flex items-center justify-center flex-shrink-0 shadow-md"
                                        aria-label="Send on WhatsApp"
                                    >
                                        <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    );
};

export default HeroSection;
