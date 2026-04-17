import React, { useState } from 'react';
import { Link } from '@inertiajs/react';

interface FAQItem {
    question: string;
    answer: string;
}

const faqs: FAQItem[] = [
    {
        question: 'How do I find vendors on Shopping Complex?',
        answer: 'You can browse vendors by category, search by product name, or filter by location. Every vendor profile shows their catalogue, ratings, and availability status in real-time so you always know who is ready to serve you.',
    },
    {
        question: 'How does the WhatsApp bot work?',
        answer: 'Send a message to our platform WhatsApp number and you will get a welcome menu. From there you can type a product name or category, and the bot will return a list of nearby vendors that carry what you are looking for — along with their contact details.',
    },
    {
        question: 'Is it free to shop on Shopping Complex?',
        answer: 'Yes, shopping is completely free for buyers. There are no fees to browse, contact vendors, or place orders. Vendors pay a subscription to list their products and get discovered.',
    },

    {
        question: 'How do I become a vendor?',
        answer: 'Click "Become a Vendor" on the homepage, fill in your business details, and submit for review. Once approved, you can upload your products and go live immediately. The whole process takes just a few minutes.',
    },
    {
        question: 'What payment methods are accepted?',
        answer: 'Payment arrangements are made directly between you and the vendor through the chat. This gives both parties flexibility to use bank transfers, cash on delivery, mobile payments, or any method that works for them.',
    },
    {
        question: 'How do I contact customer support?',
        answer: 'You can reach us through the Contact page, via email at support.shoppingComplex@gmail.com, or by messaging our WhatsApp number. Our support team is available 24/7 to help with any issues.',
    },
    {
        question: 'Is Shopping Complex available in my city?',
        answer: 'Shopping Complex is expanding across cities rapidly. Vendor availability depends on your location the more vendors that register in your area, the richer the experience. If you do not see many vendors near you yet, check back soon or encourage local businesses to sign up.',
    },
];

const FAQSection: React.FC = () => {
    const [openIndex, setOpenIndex] = useState<number | null>(null);

    const toggle = (idx: number) => {
        setOpenIndex((prev) => (prev === idx ? null : idx));
    };

    return (
        <>
            <section className="bg-white pt-10 lg:pt-12 pb-16 lg:pb-24">
                <div className="container mx-auto px-4">
                    <div className="text-center mb-12">
                        <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-primary-dark mb-4">
                            Frequently Asked Questions
                        </h2>
                        <p className="text-lg text-primary-brown max-w-2xl mx-auto">
                            Everything you need to know about Shopping Complex. Can't find an answer?{' '}
                            <Link href="/contact" className="text-primary-olive hover:underline font-medium">
                                Contact us
                            </Link>
                            .
                        </p>
                    </div>

                    <div className="max-w-3xl mx-auto divide-y divide-gray-100">
                        {faqs.map((faq, idx) => (
                            <div key={idx}>
                                <button
                                    className="w-full flex items-center justify-between gap-4 py-5 text-left"
                                    onClick={() => toggle(idx)}
                                    aria-expanded={openIndex === idx}
                                >
                                    <span className="font-semibold text-primary-dark text-base md:text-lg">
                                        {faq.question}
                                    </span>
                                    <span className="flex-shrink-0">
                                        <svg
                                            className={`w-5 h-5 text-primary-olive transition-transform duration-300 ${openIndex === idx ? 'rotate-45' : ''}`}
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                        </svg>
                                    </span>
                                </button>

                                <div
                                    className={`overflow-hidden transition-all duration-300 ease-in-out ${
                                        openIndex === idx ? 'max-h-96 pb-5' : 'max-h-0'
                                    }`}
                                >
                                    <p className="text-primary-brown leading-relaxed">{faq.answer}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Community Section — redesigned */}
            <section className="bg-primary-dark overflow-hidden relative pt-16 lg:pt-20 pb-16 lg:pb-20">
                {/* Decorative blobs */}
                <div className="absolute top-0 right-0 w-96 h-96 bg-primary-olive/10 rounded-full translate-x-1/2 -translate-y-1/2 pointer-events-none" />
                <div className="absolute bottom-0 left-0 w-72 h-72 bg-primary-peach/8 rounded-full -translate-x-1/3 translate-y-1/3 pointer-events-none" />

                <div className="container mx-auto px-4 relative z-10">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

                        {/* Left: text + stats + CTAs */}
                        <div>
                            <span className="inline-block bg-primary-olive/20 text-primary-light text-xs font-semibold uppercase tracking-widest px-3 py-1.5 rounded-full mb-5">
                                Our Community
                            </span>
                            <h3 className="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-5 leading-tight">
                                Join a Thriving<br />
                                <span className="text-primary-peach">Local Community</span>
                            </h3>
                            <p className="text-primary-light text-lg mb-10 leading-relaxed">
                                Thousands of buyers and vendors are already connecting in real-time.
                                Be part of a platform built around local commerce.
                            </p>

                            {/* Stats — vertical list style, not boxes */}
                            <div className="space-y-5 mb-10">
                                {[
                                    { value: '10,000+', label: 'Active shoppers discovering local vendors', color: 'text-primary-peach' },
                                    { value: '500+',    label: 'Verified vendors listed across multiple cities', color: 'text-primary-olive' },
                                    { value: '4.8 / 5', label: 'Average platform rating from real customers', color: 'text-yellow-400' },
                                ].map(({ value, label, color }) => (
                                    <div key={value} className="flex items-center gap-4">
                                        <span className={`text-3xl font-black ${color} leading-none w-28 flex-shrink-0`}>{value}</span>
                                        <span className="text-primary-light text-sm leading-snug border-l border-white/10 pl-4">{label}</span>
                                    </div>
                                ))}
                            </div>

                            {/* CTAs */}
                            <div className="flex flex-row gap-3">
                                <Link
                                    href="/vendor/register"
                                    className="flex-1 inline-flex items-center justify-center bg-primary-olive text-white px-4 py-3.5 sm:px-7 rounded-lg text-sm sm:text-base font-semibold hover:bg-primary-peach transition-colors duration-300 shadow-lg"
                                >
                                    Become a Vendor
                                </Link>
                                <Link
                                    href="/products"
                                    className="flex-1 inline-flex items-center justify-center bg-transparent border-2 border-white/30 text-white px-4 py-3.5 sm:px-7 rounded-lg text-sm sm:text-base font-semibold hover:border-white hover:bg-white/10 transition-all duration-300"
                                >
                                    Start Shopping
                                </Link>
                            </div>
                        </div>

                        {/* Right: activity cards */}
                        <div className="grid grid-cols-2 gap-4">
                            {[
                                {
                                    icon: (
                                        <svg className="w-6 h-6 text-primary-peach" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    ),
                                    value: '10,000+',
                                    label: 'Active Shoppers',
                                },
                                {
                                    icon: (
                                        <svg className="w-6 h-6 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    ),
                                    value: '500+',
                                    label: 'Verified Vendors',
                                },
                                {
                                    icon: (
                                        <svg className="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    ),
                                    value: '4.8 / 5',
                                    label: 'Average Rating',
                                },
                                {
                                    icon: (
                                        <svg className="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    ),
                                    value: '24 / 7',
                                    label: 'Customer Support',
                                },
                            ].map(({ icon, value, label }) => (
                                <div key={label} className="bg-white/8 border border-white/10 rounded-2xl p-5 flex flex-col gap-3">
                                    <div className="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                                        {icon}
                                    </div>
                                    <div>
                                        <div className="text-2xl font-black text-white">{value}</div>
                                        <div className="text-sm text-primary-light mt-0.5">{label}</div>
                                    </div>
                                </div>
                            ))}
                        </div>

                    </div>
                </div>
            </section>
        </>
    );
};

export default FAQSection;
