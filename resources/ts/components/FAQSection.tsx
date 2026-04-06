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
        question: 'How do I track my order?',
        answer: 'Once a vendor confirms and dispatches your order, you will receive live updates through the platform. You can track the status from preparation all the way to delivery — similar to how you would track a ride on Uber.',
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
        answer: 'You can reach us through the Contact page, via email at support@shoppingcomplex.com, or by messaging our WhatsApp number. Our support team is available 24/7 to help with any issues.',
    },
    {
        question: 'Is Shopping Complex available in my city?',
        answer: 'Shopping Complex is expanding across cities rapidly. Vendor availability depends on your location — the more vendors that register in your area, the richer the experience. If you do not see many vendors near you yet, check back soon or encourage local businesses to sign up.',
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

            {/* Community Banner */}
            <section className="bg-gradient-to-r from-primary-olive to-primary-brown pt-16 lg:pt-20 pb-16 lg:pb-20">
                <div className="container mx-auto px-4 text-center text-white">
                    <h3 className="text-3xl font-bold mb-4">Join Our Growing Community</h3>
                    <p className="text-primary-light text-lg mb-8 max-w-2xl mx-auto">
                        Over 10,000 customers trust Shopping Complex for their shopping needs.
                        Experience the future of shopping today.
                    </p>
                    <div className="flex flex-col sm:flex-row gap-4 justify-center">
                        <div className="bg-white/20 backdrop-blur-sm rounded-lg px-6 py-4">
                            <div className="text-3xl font-bold">4.8/5</div>
                            <div className="text-sm text-primary-light">Average Rating</div>
                        </div>
                        <div className="bg-white/20 backdrop-blur-sm rounded-lg px-6 py-4">
                            <div className="text-3xl font-bold">98%</div>
                            <div className="text-sm text-primary-light">Satisfaction Rate</div>
                        </div>
                        <div className="bg-white/20 backdrop-blur-sm rounded-lg px-6 py-4">
                            <div className="text-3xl font-bold">24/7</div>
                            <div className="text-sm text-primary-light">Customer Support</div>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
};

export default FAQSection;
