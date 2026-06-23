import React from 'react';
import { Link } from '@inertiajs/react';

interface VendorBenefit {
    title: string;
    description: string;
    icon: React.ReactNode;
}

const benefits: VendorBenefit[] = [
    {
        title: 'Appear on the Local Map',
        description:
            'Your store shows up automatically when customers nearby search your category. Zero advertising budget needed.',
        icon: <path d="M21 3 3 10.5l7.5 2.5L13 21 21 3Z" />,
    },
    {
        title: 'No App to Manage',
        description:
            "All orders and inquiries come directly to your WhatsApp. You're always in control using familiar tools.",
        icon: (
            <>
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" />
            </>
        ),
    },
    {
        title: 'Track Your Growth',
        description:
            'See how many people viewed your profile, messaged you, and converted. All in a simple dashboard.',
        icon: <path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7A8.5 8.5 0 1 1 21 11.5Z" />,
    },
    {
        title: 'Build Your Reputation',
        description:
            'Verified reviews and ratings help you stand out from competitors and build long-term customer trust.',
        icon: (
            <>
                <path d="m4 5 8 7-8 7V5Z" />
                <path d="m13 5 8 7-8 7V5Z" />
            </>
        ),
    },
];

const gridBg: React.CSSProperties = {
    backgroundImage:
        'linear-gradient(to right, rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.05) 1px, transparent 1px)',
    backgroundSize: '72px 72px',
};

const VendorsSection: React.FC = () => {
    return (
        <section style={gridBg} className="bg-brand-ink py-24 font-display text-white">
            <div className="mx-auto max-w-[1320px] px-6 lg:px-10">
                {/* Header */}
                <div className="mx-auto max-w-2xl text-center">
                    <p className="text-sm font-bold text-white/90">Vendors</p>
                    <h2 className="mt-3 font-serif text-[48px] font-medium leading-tight text-white">
                        Grow your visibility locally
                    </h2>
                    <p className="mx-auto mt-4 max-w-md text-base leading-relaxed text-white/65">
                        Join thousands of vendors using Jiidaa to reach nearby customers exactly when they're ready to
                        buy.
                    </p>
                </div>

                {/* Cards */}
                <div className="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {benefits.map((benefit) => (
                        <div key={benefit.title} className="rounded-2xl bg-white/[0.03] p-7 ring-1 ring-white/10">
                            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-white/[0.06]">
                                <svg
                                    className="h-5 w-5 text-brand-green"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth={1.7}
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    {benefit.icon}
                                </svg>
                            </span>
                            <h3 className="mt-12 text-lg font-bold text-white">{benefit.title}</h3>
                            <p className="mt-2.5 text-[15px] leading-relaxed text-white/60">{benefit.description}</p>
                        </div>
                    ))}
                </div>

                {/* CTA */}
                <div className="mt-16 flex justify-center">
                    <Link
                        href="/vendor/register"
                        className="inline-flex items-center gap-3 rounded-full bg-brand-green px-8 py-4 text-base font-semibold text-white shadow-[0_14px_30px_-10px_rgba(37,211,102,0.55)] transition hover:bg-brand-green-dark"
                    >
                        Join Jiidaa as a vendor
                    </Link>
                </div>
            </div>
        </section>
    );
};

export default VendorsSection;
