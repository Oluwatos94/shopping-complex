import React from 'react';

interface Step {
    title: string;
    description: string;
    icon: React.ReactNode;
}

const steps: Step[] = [
    {
        title: 'Enable location',
        description: 'Allow Jiidaa to see your area, we never store it',
        icon: <path d="M21 3 3 10.5l7.5 2.5L13 21 21 3Z" />,
    },
    {
        title: 'Discover Vendors',
        description: 'Browse trusted businesses ranked by distance.',
        icon: (
            <>
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" />
            </>
        ),
    },
    {
        title: 'Chat on WhatsApp',
        description: 'Open a conversation in a single tap.',
        icon: <path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7A8.5 8.5 0 1 1 21 11.5Z" />,
    },
    {
        title: 'Buy faster',
        description: 'Negotiate, pay, and pick up — no friction.',
        icon: (
            <>
                <path d="m4 5 8 7-8 7V5Z" />
                <path d="m13 5 8 7-8 7V5Z" />
            </>
        ),
    },
];

const HowItWorksSection: React.FC = () => {
    return (
        <section className="bg-brand-surface py-20 font-display text-brand-ink">
            <div className="mx-auto max-w-[1320px] px-6 lg:px-10">
                {/* Header */}
                <div className="text-center">
                    <p className="text-sm font-bold uppercase tracking-[0.18em] text-brand-ink">How it works</p>
                    <h2 className="mt-4 font-serif text-[44px] font-medium leading-tight text-brand-ink">
                        Four taps to your next purchase
                    </h2>
                </div>

                {/* Step timeline */}
                <div className="relative mt-16 grid gap-12 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
                    {/* Connector line — desktop */}
                    <div className="absolute left-0 right-0 top-8 hidden h-px bg-brand-line lg:block" aria-hidden="true" />

                    {steps.map((step, i) => (
                        <div key={step.title} className="relative flex flex-col items-center text-center lg:items-start lg:text-left">
                            <div className="relative z-10 flex h-16 w-16 items-center justify-center rounded-full bg-brand-ink ring-8 ring-brand-surface">
                                <svg
                                    className="h-6 w-6 text-brand-green"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth={1.7}
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    {step.icon}
                                </svg>
                                <span className="absolute -right-1 -top-1 flex h-6 w-6 items-center justify-center rounded-full bg-brand-green text-xs font-bold text-white ring-4 ring-brand-surface">
                                    {i + 1}
                                </span>
                            </div>
                            <h3 className="mt-6 text-lg font-bold text-brand-ink">{step.title}</h3>
                            <p className="mt-2 max-w-[15rem] text-[15px] leading-relaxed text-brand-muted">
                                {step.description}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
};

export default HowItWorksSection;
