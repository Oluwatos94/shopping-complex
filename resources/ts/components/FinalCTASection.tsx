import React from 'react';
import { Link } from '@inertiajs/react';

const cardBg: React.CSSProperties = {
    background:
        'linear-gradient(115deg, rgba(37,211,102,0.16) 0%, rgba(248,250,252,0.4) 30%, rgba(248,250,252,0.4) 70%, rgba(37,211,102,0.16) 100%), #F8FAFC',
};

const gridBg: React.CSSProperties = {
    backgroundImage:
        'linear-gradient(to right, rgba(11,31,58,0.06) 1px, transparent 1px), linear-gradient(to bottom, rgba(11,31,58,0.06) 1px, transparent 1px)',
    backgroundSize: '132px 94px',
};

const FinalCTASection: React.FC = () => {
    return (
        <section className="bg-white py-16 font-display text-brand-ink">
            <div className="mx-auto max-w-[1320px] px-6 lg:px-10">
                <div style={cardBg} className="relative overflow-hidden rounded-[28px] ring-1 ring-brand-line/70">
                    <div style={gridBg} className="px-6 py-24 text-center">
                        <h2 className="font-serif text-[52px] font-bold leading-tight text-brand-ink">
                            Start finding vendors smarter.
                        </h2>
                        <p className="mx-auto mt-5 max-w-xl text-base text-brand-muted">
                            Join thousands shopping their neighborhood. No app download, no friction.
                        </p>

                        <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                            <Link
                                href="/register"
                                className="inline-flex items-center gap-2.5 rounded-full bg-brand-ink px-8 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-brand-ink/90"
                            >
                                Get Started
                                <svg
                                    className="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth={2.2}
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M5 12h14M13 6l6 6-6 6" />
                                </svg>
                            </Link>
                            <Link
                                href="/#how-it-works"
                                className="inline-flex items-center rounded-full border border-brand-line bg-white/60 px-8 py-4 text-base font-semibold text-brand-ink transition hover:bg-white"
                            >
                                See how it works
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default FinalCTASection;
