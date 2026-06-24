import React from 'react';
import { Link, usePage } from '@inertiajs/react';

interface PageProps {
    [key: string]: unknown;
    platformWhatsApp?: string;
}

const WhatsAppIcon: React.FC<{ className?: string }> = ({ className }) => (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.8 4.9-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1.1-1.4-1.1-2.6 0-1.3.7-1.9.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.6c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.2.1.4.1.6-.1l.7-.9c.2-.2.4-.2.6-.1l1.9.9c.2.1.4.2.4.3.1.2.1.7-.1 1.3Z" />
    </svg>
);

const Pin: React.FC<{ className?: string }> = ({ className }) => (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 22s7-5.686 7-11a7 7 0 1 0-14 0c0 5.314 7 11 7 11Z" />
        <circle cx="12" cy="11" r="2.4" fill="#EEF1F5" />
    </svg>
);

const vendors = [
    { name: 'Sole Mate Kicks', meta: '0.4 km · Sneakers', verified: true },
    { name: 'Naija Sneaker Hub', meta: '0.7 km · Sneakers', verified: false },
    { name: 'StepUp Sneakers', meta: '1.1 km · Sneakers', verified: true },
    { name: 'Kicks & Co', meta: '1.4 km · Sneakers', verified: false },
    { name: 'FreshSole Lagos', meta: '1.8 km · Sneakers', verified: false },
];

const HeroSection: React.FC = () => {
    const { platformWhatsApp } = usePage<PageProps>().props;
    const whatsAppHref = platformWhatsApp
        ? `https://wa.me/${platformWhatsApp.replace(/[^0-9]/g, '')}?text=${encodeURIComponent('Hi Jiidaa.')}`
        : 'https://wa.me/';

    return (
        <section className="relative overflow-hidden bg-brand-surface font-display text-brand-ink">
            {/* Soft green glow backdrop */}
            <div className="pointer-events-none absolute inset-x-0 top-0 h-[640px] bg-[radial-gradient(120%_80%_at_70%_-10%,rgba(37,211,102,0.18),rgba(248,250,252,0)_60%)]" />

            <div className="relative mx-auto max-w-[1320px] px-6 lg:px-10">
                <div className="grid items-center gap-12 pb-24 pt-10 lg:grid-cols-2 lg:gap-8 lg:pt-16">
                    {/* Left column */}
                    <div className="max-w-xl">

                        {/* Headline */}
                        <h1 className="mt-7 text-5xl font-extrabold leading-[1.02] tracking-tight sm:text-6xl lg:text-[64px]">
                            Find nearby
                            <br />
                            vendors
                            <br />
                            in <span className="text-brand-green">seconds.</span>
                        </h1>

                        {/* Subtext */}
                        <p className="mt-7 max-w-md text-lg leading-relaxed text-brand-muted">
                            Jiidaa is a GPS-powered, WhatsApp-native marketplace. Discover trusted local
                            businesses around you and chat instantly — no apps to download.
                        </p>

                        {/* CTAs */}
                        <div className="mt-9 flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center">
                            <Link
                                href="/products"
                                className="inline-flex w-full items-center justify-center gap-2 rounded-full bg-brand-ink px-7 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-brand-ink/90 sm:w-auto"
                            >
                                Find vendors
                            </Link>
                            <a
                                href={whatsAppHref}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex w-full items-center justify-center gap-2.5 rounded-full bg-brand-green px-7 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-brand-green-dark sm:w-auto"
                            >
                                <WhatsAppIcon className="h-5 w-5" />
                                Chat on WhatsApp
                            </a>
                        </div>

                        {/* Social proof */}
                        <div className="mt-10 flex items-center gap-4">
                            <div className="flex -space-x-3">
                                <span className="h-10 w-10 rounded-full border-2 border-white bg-gradient-to-br from-brand-green to-emerald-300" />
                                <span className="h-10 w-10 rounded-full border-2 border-white bg-gradient-to-br from-emerald-300 to-teal-400" />
                                <span className="h-10 w-10 rounded-full border-2 border-white bg-gradient-to-br from-sky-400 to-indigo-400" />
                                <span className="h-10 w-10 rounded-full border-2 border-white bg-gradient-to-br from-indigo-400 to-fuchsia-400" />
                            </div>
                            <p className="text-[15px] font-medium text-brand-muted">
                                Loved by 12,000+ shoppers &amp; vendors
                            </p>
                        </div>
                    </div>

                    {/* Right column — phone mockup */}
                    <div className="relative mx-auto flex w-full max-w-[460px] justify-center">
                        {/* Floating card: New chat */}
                        <div className="absolute right-0 top-[58%] z-20 flex items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow-[0_18px_40px_-12px_rgba(11,31,58,0.25)] ring-1 ring-brand-line/70">
                            <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-green/10 text-brand-green">
                                <svg
                                    className="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth={1.8}
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7A8.5 8.5 0 1 1 21 11.5Z" />
                                </svg>
                            </span>
                        </div>

                        {/* Phone */}
                        <div className="relative w-[340px] rounded-[3.2rem] border-[12px] border-brand-ink bg-brand-ink shadow-[0_40px_80px_-20px_rgba(11,31,58,0.4)]">
                            <div className="relative overflow-hidden rounded-[2.4rem] bg-white">
                                {/* Dynamic island */}
                                <div className="absolute left-1/2 top-3 z-30 h-7 w-28 -translate-x-1/2 rounded-full bg-brand-ink" />

                                {/* Map area */}
                                <div className="relative h-[360px] bg-[#EEF1F5]">
                                    <svg
                                        className="absolute inset-0 h-full w-full"
                                        viewBox="0 0 340 360"
                                        fill="none"
                                        preserveAspectRatio="xMidYMid slice"
                                    >
                                        <path d="M-20 90 H360" stroke="#E4E7EC" strokeWidth={10} />
                                        <path d="M200 -20 V380" stroke="#E4E7EC" strokeWidth={10} />
                                        <path d="M-20 250 C90 230 130 300 360 240" stroke="#E4E7EC" strokeWidth={14} />
                                        <path d="M60 -20 C70 120 30 200 120 380" stroke="#E4E7EC" strokeWidth={8} />
                                    </svg>

                                    {/* green location halo — dot blinks/pings in place */}
                                    <div className="absolute left-[40%] top-[52%] z-10 flex h-16 w-16 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-brand-green/15">
                                        <span className="absolute h-6 w-6 rounded-full bg-brand-green/40 animate-ping" />
                                        <span className="relative h-4 w-4 rounded-full bg-brand-green ring-4 ring-white animate-pulse" />
                                    </div>

                                    {/* pins — drift slowly */}
                                    <Pin className="absolute left-[42%] top-[24%] z-10 h-7 w-7 text-brand-ink animate-pin-float-1" />
                                    <Pin className="absolute left-[64%] top-[34%] z-10 h-6 w-6 text-brand-ink animate-pin-float-2" />
                                    <Pin className="absolute left-[52%] top-[52%] z-10 h-6 w-6 text-brand-ink animate-pin-float-3" />
                                </div>

                                {/* Bottom sheet */}
                                <div className="relative -mt-6 rounded-t-[1.8rem] bg-white px-5 pb-6 pt-5">
                                    {/* search */}
                                    <div className="flex items-center gap-2.5 rounded-full bg-brand-surface px-4 py-3 ring-1 ring-brand-line">
                                        <svg
                                            className="h-4 w-4 text-brand-muted"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth={2}
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                        >
                                            <circle cx="11" cy="11" r="7" />
                                            <path d="m20 20-3.5-3.5" />
                                        </svg>
                                        <span className="text-sm text-brand-muted">Search nearby...</span>
                                    </div>

                                    {/* vendor list — auto-scrolling loop */}
                                    <div className="mt-4 h-[156px] overflow-hidden [mask-image:linear-gradient(to_bottom,transparent,#000_14%,#000_86%,transparent)]">
                                        <ul className="animate-vendor-scroll">
                                            {[...vendors, ...vendors].map((v, i) => (
                                                <li key={`${v.name}-${i}`} className="mb-4 flex items-center gap-3">
                                                    <span className="h-11 w-11 shrink-0 rounded-full bg-gradient-to-br from-brand-green/40 to-emerald-200" />
                                                    <div className="min-w-0 flex-1">
                                                        <p className="flex items-center gap-1 text-sm font-bold text-brand-ink">
                                                            {v.name}
                                                            {v.verified && (
                                                                <svg
                                                                    className="h-3.5 w-3.5 text-brand-green"
                                                                    viewBox="0 0 24 24"
                                                                    fill="currentColor"
                                                                >
                                                                    <path d="m9 16.2-3.5-3.5-1.4 1.4L9 19 20 8l-1.4-1.4z" />
                                                                </svg>
                                                            )}
                                                        </p>
                                                        <p className="text-xs text-brand-muted">{v.meta}</p>
                                                    </div>
                                                    <button
                                                        aria-label={`Chat with ${v.name} on WhatsApp`}
                                                        className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-green text-white shadow-sm"
                                                    >
                                                        <WhatsAppIcon className="h-4 w-4" />
                                                    </button>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
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
