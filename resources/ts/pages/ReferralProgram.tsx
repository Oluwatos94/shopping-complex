import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import Header from '@/components/Header';
import Footer from '@/components/Footer';

interface Prize {
    position: string;
    amount: string;
    note: string;
    featured?: boolean;
}

const prizes: Prize[] = [
    { position: '1st place', amount: '₦100,000', note: 'Top referrer on the leaderboard', featured: true },
    { position: '2nd place', amount: '₦50,000', note: 'Runner-up' },
    { position: '3rd – 10th', amount: '₦20,000', note: 'Each of the next eight positions' },
];

interface Step {
    title: string;
    body: string;
}

const steps: Step[] = [
    {
        title: 'Register as a vendor',
        body: 'Create your Jiidaa vendor account and upload at least 10 of the products or services you render.',
    },
    {
        title: 'Grab your referral link',
        body: 'Go to your vendor dashboard and copy your unique referral link. It never expires — share it anywhere.',
    },
    {
        title: 'Refer and climb',
        body: 'Refer as many vendors as you can to climb the leaderboard. The top 10 referrers all win cash.',
    },
];

const terms: string[] = [
    'Each vendor you refer must upload at least 5 of their products or services. Until they do, the referral will not reflect on the leaderboard or in your count.',
    'You can refer any vendor you know — the programme is not limited to Unilag vendors or businesses.',
    'Refer as many vendors as you can. There is no cap on the number of referrals per participant.',
    'A referral only counts when the vendor signs up through your referral link.',
    'Prizes are awarded to the top 10 positions on the final leaderboard: ₦100,000 for 1st, ₦50,000 for 2nd, and ₦20,000 each for 3rd through 10th — ₦310,000 in total.',
    'Referring yourself, or creating duplicate or fake vendor accounts, disqualifies those entries and may remove you from the programme entirely.',
    'Referred vendors must be genuine, reachable businesses. We may contact them to verify before a referral is counted.',
    'Leaderboard positions update as referrals qualify, so your count can rise as the vendors you referred finish their uploads.',
    'If two participants finish on the same number of qualified referrals, the one who reached that number first takes the higher position.',
    'Jiidaa reserves the right to disqualify any entry that shows fraudulent or abusive activity.',
    'Prizes are paid to each winner after we verify their referred accounts.',
    'Programme start and end dates are announced on this page and through Jiidaa’s official channels.',
    'Follow us on all our social handles — @jiidaa_ng on X and Instagram.',
];

interface FAQItem {
    question: string;
    answer: string;
}

const faqs: FAQItem[] = [
    {
        question: 'How much can I win?',
        answer: 'There is ₦310,000 in total prizes across 10 winners. First place takes ₦100,000, second takes ₦50,000, and positions 3 through 10 each take ₦20,000.',
    },
    {
        question: 'Who can join the referral program?',
        answer: 'Any Jiidaa vendor. Register as a vendor and upload at least 10 of your products or services, and your referral link becomes active on your dashboard.',
    },
    {
        question: "Does my referral count if the vendor doesn't upload anything?",
        answer: 'No. A referred vendor must upload at least 5 of their products or services before the referral counts towards your leaderboard position. Once they do, your count updates automatically.',
    },
    {
        question: 'Can I refer vendors outside Unilag?',
        answer: 'Yes. You can refer any vendor or business you know, anywhere. The programme is not limited to Unilag.',
    },
    {
        question: 'How many vendors can I refer?',
        answer: 'As many as you like — there is no limit. The more qualified referrals you bring in, the higher you climb.',
    },
    {
        question: 'How do I track my referrals?',
        answer: 'Your vendor dashboard shows your referral link and how many vendors you have referred. The leaderboard shows where you currently stand against everyone else.',
    },
    {
        question: 'How are winners decided?',
        answer: 'By final leaderboard position — the 10 participants with the highest number of qualified referrals win. If there is a tie, the participant who reached that number first takes the higher position.',
    },
];

const ReferralProgram: React.FC = () => {
    const [openIndex, setOpenIndex] = useState<number | null>(null);

    const toggle = (idx: number) => {
        setOpenIndex((prev) => (prev === idx ? null : idx));
    };

    return (
        <>
            <Head title="Referral Program - jiidaa" />
            <div className="min-h-screen bg-brand-surface font-display">
                <Header />

                {/* Hero */}
                <section className="bg-brand-ink px-5 py-14 text-center sm:px-6 sm:py-20 lg:px-10">
                    <div className="mx-auto max-w-3xl">
                        <p className="text-[12px] font-bold uppercase tracking-[0.18em] text-brand-green sm:text-sm">
                            Referral Program
                        </p>
                        <h1 className="mt-4 font-serif text-[30px] font-medium leading-[1.15] text-white sm:text-[42px] lg:text-[52px]">
                            Refer vendors. Climb the leaderboard.
                            <br className="hidden sm:block" /> Win a share of{' '}
                            <span className="text-brand-green">₦310,000</span>.
                        </h1>
                        <p className="mx-auto mt-5 max-w-xl text-[15px] leading-relaxed text-white/70 sm:mt-6 sm:text-base">
                            Bring more businesses onto Jiidaa and get rewarded for it. Every vendor you refer moves you
                            up the leaderboard — and the top 10 referrers all take home cash.
                        </p>
                        <div className="mt-8 sm:mt-9">
                            <Link
                                href="/register"
                                className="inline-block rounded-full bg-brand-green px-8 py-3 text-[15px] font-semibold text-white transition-colors hover:bg-brand-green-dark"
                            >
                                Get started
                            </Link>
                        </div>
                    </div>
                </section>

                {/* Prizes */}
                <section className="px-5 py-14 sm:px-6 sm:py-20 lg:px-10">
                    <div className="mx-auto max-w-[1000px]">
                        <div className="text-center">
                            <p className="text-[12px] font-bold uppercase tracking-[0.18em] text-brand-ink sm:text-sm">
                                Prizes
                            </p>
                            <h2 className="mt-3 font-serif text-[26px] font-medium leading-tight text-brand-ink sm:text-[34px] lg:text-[40px]">
                                ₦310,000 to be won, across 10 winners.
                            </h2>
                        </div>

                        <div className="mt-10 grid gap-5 sm:mt-12 sm:grid-cols-3">
                            {prizes.map((prize) => (
                                <div
                                    key={prize.position}
                                    className={`rounded-xl border p-6 text-center sm:p-7 ${
                                        prize.featured
                                            ? 'border-brand-green bg-brand-green/5 shadow-sm'
                                            : 'border-brand-line bg-white shadow-sm'
                                    }`}
                                >
                                    <p className="text-[13px] font-bold uppercase tracking-[0.12em] text-brand-muted">
                                        {prize.position}
                                    </p>
                                    <p className="mt-3 font-serif text-[32px] font-medium text-brand-ink sm:text-[36px]">
                                        {prize.amount}
                                    </p>
                                    <p className="mt-2 text-[14px] leading-relaxed text-brand-muted">{prize.note}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* How it works */}
                <section className="border-t border-brand-line bg-white px-5 py-14 sm:px-6 sm:py-20 lg:px-10">
                    <div className="mx-auto max-w-[1000px]">
                        <div className="text-center">
                            <p className="text-[12px] font-bold uppercase tracking-[0.18em] text-brand-ink sm:text-sm">
                                How it works
                            </p>
                            <h2 className="mt-3 font-serif text-[26px] font-medium leading-tight text-brand-ink sm:text-[34px] lg:text-[40px]">
                                Three steps to the leaderboard.
                            </h2>
                        </div>

                        <ol className="mt-10 grid gap-5 sm:mt-12 sm:grid-cols-3">
                            {steps.map((step, idx) => (
                                <li
                                    key={step.title}
                                    className="rounded-xl border border-brand-line bg-brand-surface p-6 shadow-sm sm:p-7"
                                >
                                    <span className="flex h-10 w-10 items-center justify-center rounded-full bg-brand-green/10 text-[17px] font-bold text-brand-green">
                                        {idx + 1}
                                    </span>
                                    <h3 className="mt-5 text-lg font-bold text-brand-ink">{step.title}</h3>
                                    <p className="mt-2 text-[15px] leading-relaxed text-brand-muted">{step.body}</p>
                                </li>
                            ))}
                        </ol>
                    </div>
                </section>

                {/* Terms & conditions */}
                <section className="px-5 py-14 sm:px-6 sm:py-20 lg:px-10">
                    <div className="mx-auto max-w-[820px]">
                        <div className="text-center">
                            <p className="text-[12px] font-bold uppercase tracking-[0.18em] text-brand-ink sm:text-sm">
                                Terms &amp; Conditions
                            </p>
                            <h2 className="mt-3 font-serif text-[26px] font-medium leading-tight text-brand-ink sm:text-[34px] lg:text-[40px]">
                                The rules, in plain words.
                            </h2>
                        </div>

                        <ol className="mt-9 space-y-4 sm:mt-11">
                            {terms.map((term, idx) => (
                                <li key={idx} className="flex gap-3 sm:gap-4">
                                    <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-ink text-[12px] font-bold text-white">
                                        {idx + 1}
                                    </span>
                                    <p className="text-[15px] leading-relaxed text-brand-muted">{term}</p>
                                </li>
                            ))}
                        </ol>
                    </div>
                </section>

                {/* FAQ */}
                <section className="border-t border-brand-line bg-white px-5 py-14 sm:px-6 sm:py-20 lg:px-10">
                    <div className="mx-auto max-w-[820px]">
                        <div className="text-center">
                            <p className="text-[12px] font-bold uppercase tracking-[0.18em] text-brand-ink sm:text-sm">
                                FAQ
                            </p>
                            <h2 className="mt-3 font-serif text-[26px] font-medium leading-tight text-brand-ink sm:text-[34px] lg:text-[40px]">
                                Questions, answered.
                            </h2>
                        </div>

                        <div className="mt-10 border-t border-brand-line sm:mt-12">
                            {faqs.map((faq, idx) => (
                                <div key={idx} className="border-b border-brand-line">
                                    <button
                                        className="flex w-full cursor-pointer items-center justify-between gap-4 py-5 text-left sm:py-6"
                                        onClick={() => toggle(idx)}
                                        aria-expanded={openIndex === idx}
                                    >
                                        <span className="text-[16px] font-bold text-brand-ink sm:text-lg">
                                            {faq.question}
                                        </span>
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
                                            openIndex === idx ? 'max-h-96 pb-5 sm:pb-6' : 'max-h-0'
                                        }`}
                                    >
                                        <p className="pr-4 text-[15px] leading-relaxed text-brand-muted sm:pr-10">
                                            {faq.answer}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Closing CTA */}
                <section className="bg-brand-ink px-5 py-14 text-center sm:px-6 sm:py-16 lg:px-10">
                    <h2 className="font-serif text-[24px] font-medium leading-tight text-white sm:text-[32px]">
                        Ready to start referring?
                    </h2>
                    <p className="mx-auto mt-3 max-w-lg text-[15px] text-white/70">
                        Register as a vendor, upload your products, and your referral link is ready.
                    </p>
                    <Link
                        href="/register"
                        className="mt-7 inline-block rounded-full bg-brand-green px-8 py-3 text-[15px] font-semibold text-white transition-colors hover:bg-brand-green-dark sm:mt-8"
                    >
                        Get started
                    </Link>
                </section>

                <Footer />
            </div>
        </>
    );
};

export default ReferralProgram;
