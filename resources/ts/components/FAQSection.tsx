import React, { useState } from 'react';

interface FAQItem {
    question: string;
    answer: string;
}

const faqs: FAQItem[] = [
    {
        question: 'How do I find vendors on Jiidaa?',
        answer: 'Enable location and Jiidaa instantly shows trusted vendors near you, ranked by distance, rating and availability. Tap any vendor to chat on WhatsApp.',
    },
    {
        question: 'How does the WhatsApp bot work?',
        answer: 'Tap the WhatsApp button and you land straight in a chat with the Jiidaa bot. Just tell it what you need — a product or type of vendor — and the bot finds matching vendors near you and connects you with them.',
    },
    {
        question: 'Is it free to shop on Jiidaa?',
        answer: 'Yes. Shopping and discovering vendors on Jiidaa is completely free for customers. You only pay vendors directly for what you buy.',
    },
    {
        question: 'How do I become a vendor?',
        answer: 'Tap "Join Jiidaa as a vendor", complete a short profile with your location and category, and get verified. Your store then appears on the local map.',
    },
    {
        question: 'What payment methods are accepted?',
        answer: 'Payments are arranged directly between you and the vendor — bank transfer, cash, or any method they accept. Jiidaa connects you; you stay in control.',
    },
    {
        question: 'How do I contact customer support?',
        answer: 'Reach our team any time through the in-app help chat or by messaging our support at hello@jiidaa.com. We typically reply within minutes.',
    },
    {
        question: 'Is Jiidaa available in my city?',
        answer: "Jiidaa is rolling out across cities now. Enable location to see if vendors are active near you — and if we're not there yet, you can request your city.",
    },
];

const FAQSection: React.FC = () => {
    const [openIndex, setOpenIndex] = useState<number | null>(null);

    const toggle = (idx: number) => {
        setOpenIndex((prev) => (prev === idx ? null : idx));
    };

    return (
        <section id="faq" className="scroll-mt-24 bg-brand-surface py-20 font-display text-brand-ink">
                <div className="mx-auto max-w-[1000px] px-6 lg:px-10">
                    {/* Header */}
                    <div className="text-center">
                        <p className="text-sm font-bold uppercase tracking-[0.18em] text-brand-ink">FAQ</p>
                        <h2 className="mt-3 font-serif text-[48px] font-medium leading-tight text-brand-ink">
                            Questions, answered.
                        </h2>
                        <p className="mt-4 text-base text-brand-muted">
                            Everything you need to know before getting started.
                        </p>
                    </div>

                    {/* List */}
                    <div className="mt-12 border-t border-brand-line">
                        {faqs.map((faq, idx) => (
                            <div key={idx} className="border-b border-brand-line">
                                <button
                                    className="flex w-full cursor-pointer items-center justify-between gap-4 py-6 text-left"
                                    onClick={() => toggle(idx)}
                                    aria-expanded={openIndex === idx}
                                >
                                    <span className="text-lg font-bold text-brand-ink">{faq.question}</span>
                                    <svg
                                        className={`h-5 w-5 shrink-0 text-brand-ink transition-transform duration-300 ${openIndex === idx ? 'rotate-180' : ''}`}
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth={2}
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>

                                <div
                                    className={`overflow-hidden transition-all duration-300 ease-in-out ${
                                        openIndex === idx ? 'max-h-96 pb-6' : 'max-h-0'
                                    }`}
                                >
                                    <p className="pr-10 text-[15px] leading-relaxed text-brand-muted">{faq.answer}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
        </section>
    );
};

export default FAQSection;
