import React from 'react';

const LOCATION_IMG = 'https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/location.jpg';

const CheckItem: React.FC<{ children: React.ReactNode }> = ({ children }) => (
    <li className="flex items-center gap-3">
        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-brand-green/15 ring-1 ring-brand-green/30">
            <svg
                className="h-3.5 w-3.5 text-brand-green"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth={2.5}
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <path d="m5 12 5 5 9-11" />
            </svg>
        </span>
        <span className="text-[15px] font-medium text-brand-ink/85">{children}</span>
    </li>
);

const WindowDots: React.FC = () => (
    <div className="flex gap-1.5">
        <span className="h-2.5 w-2.5 rounded-full bg-brand-danger" />
        <span className="h-2.5 w-2.5 rounded-full bg-[#F5B544]" />
        <span className="h-2.5 w-2.5 rounded-full bg-brand-green" />
    </div>
);

const CoreFeaturesSection: React.FC = () => {
    return (
        <section className="bg-brand-surface py-20 font-display text-brand-ink">
            <div className="mx-auto max-w-[1320px] px-6 lg:px-10">
                {/* Header */}
                <div className="text-center">
                    <p className="text-sm font-bold uppercase tracking-[0.18em] text-brand-ink">Core features</p>
                    <h2 className="mt-4 font-serif text-[44px] font-medium leading-tight text-brand-ink">
                        Built for Speed &amp; Simplicity
                    </h2>
                </div>

                {/* ===== Row 1 ===== */}
                <div className="mt-16 grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    {/* Text */}
                    <div className="max-w-lg">
                        <h3 className="font-serif text-[28px] font-medium leading-snug text-brand-ink">
                            See Every Vendor Around You, Instantly
                        </h3>
                        <p className="mt-4 text-base leading-relaxed text-brand-ink/75">
                            Our GPS engine continuously scans your surroundings and surfaces only the most relevant
                            vendors, sorted by distance, ratings and availability in real time
                        </p>

                        <ul className="mt-7 space-y-3.5">
                            <CheckItem>Auto refreshing vendor list every 30 seconds</CheckItem>
                            <CheckItem>Filter by category: food, fashion, beauty &amp; more</CheckItem>
                            <CheckItem>Live availability status on each listing</CheckItem>
                            <CheckItem>Distance shown in walking/driving time</CheckItem>
                        </ul>
                    </div>

                    {/* Mockup: Nearby vendors */}
                    <div className="overflow-hidden rounded-[28px] bg-white shadow-[0_30px_70px_-30px_rgba(11,31,58,0.30)] ring-1 ring-brand-line/60">
                        <div className="flex items-center justify-between px-5 py-4">
                            <div className="flex items-center gap-3">
                                <WindowDots />
                                <span className="text-[15px] font-bold text-brand-ink">
                                    Nearby Vendors
                                </span>
                            </div>
                            <span className="flex items-center gap-1.5 text-[13px] font-semibold text-brand-green">
                                <span className="h-1.5 w-1.5 rounded-full bg-brand-green" />
                                Live
                            </span>
                        </div>
                        <div className="px-4">
                            <img
                                src={LOCATION_IMG}
                                alt="City map with vendor pins"
                                className="h-44 w-full rounded-2xl object-cover"
                            />
                        </div>
                        <div className="space-y-2.5 p-4">
                            {[
                                { name: "Mama Temi's Grocery", meta: 'Fresh Produce - 0.2km away', status: 'Open' },
                                { name: 'Kingsley Fabrics', meta: 'Fashion & Tailoring - 0.5km away', status: 'Open' },
                                { name: 'TechFix Express', meta: 'Electronics - 0.8km away', status: 'Busy' },
                            ].map((v) => (
                                <div key={v.name} className="flex items-center gap-3 rounded-2xl bg-brand-surface px-4 py-3">
                                    <img src={LOCATION_IMG} alt="" className="h-9 w-9 rounded-lg object-cover" />
                                    <div className="flex-1">
                                        <p className="text-sm font-bold text-brand-ink">{v.name}</p>
                                        <p className="text-xs text-brand-muted">{v.meta}</p>
                                    </div>
                                    {v.status === 'Open' ? (
                                        <span className="rounded-full bg-brand-green/15 px-3 py-1 text-xs font-semibold text-brand-green-dark">
                                            Open
                                        </span>
                                    ) : (
                                        <span className="rounded-full bg-brand-danger/10 px-3 py-1 text-xs font-semibold text-brand-danger">
                                            Busy
                                        </span>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* ===== Row 2 ===== */}
                <div className="mt-20 grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    {/* Text */}
                    <div className="max-w-lg lg:order-1">
                        <h3 className="font-serif text-[28px] font-medium leading-snug text-brand-ink">
                            Chat, Negotiate &amp; Buy on WhatsApp
                        </h3>
                        <p className="mt-4 text-base leading-relaxed text-brand-ink/75">
                            Every interaction happens in WhatsApp, the app your customers already love and trust. No new
                            interface to learn. Just fast, personal commerce
                        </p>

                        <ul className="mt-7 space-y-3.5">
                            <CheckItem>One tap to open WhatsApp with any vendor</CheckItem>
                            <CheckItem>Share product photos, voice notes, locations</CheckItem>
                            <CheckItem>Vendors get notified instantly on their phones</CheckItem>
                            <CheckItem>Works on any phone</CheckItem>
                        </ul>
                    </div>

                    {/* Mockup: WhatsApp chat */}
                    <div className="overflow-hidden rounded-[28px] bg-white shadow-[0_30px_70px_-30px_rgba(11,31,58,0.30)] ring-1 ring-brand-line/60 lg:order-2">
                        <div className="flex items-center gap-3 px-5 py-4">
                            <WindowDots />
                            <span className="text-[15px] font-bold text-brand-ink">WhatsApp</span>
                        </div>
                        <div className="bg-[#F2FBF4] p-5">
                            <div className="flex items-center gap-3">
                                <img src={LOCATION_IMG} alt="" className="h-9 w-9 rounded-full object-cover" />
                                <div>
                                    <p className="text-sm font-bold text-brand-ink">Mama Temi's Grocery</p>
                                    <p className="flex items-center gap-1.5 text-xs text-brand-green-dark">
                                        <span className="h-1.5 w-1.5 rounded-full bg-brand-green" />
                                        Online - Typically replies in minutes
                                    </p>
                                </div>
                            </div>

                            <div className="mt-5 space-y-4">
                                <p className="max-w-[78%] text-[13px] leading-relaxed text-brand-ink/85">
                                    Hi! I saw you sell fresh tomatoes. How much for 1kg?
                                </p>
                                <div className="flex justify-end">
                                    <p className="max-w-[78%] rounded-2xl rounded-tr-md bg-brand-green/25 px-4 py-3 text-[13px] leading-relaxed text-brand-ink">
                                        Hello! 1kg is ₦1,200. Very fresh, just came in from the farm this morning
                                    </p>
                                </div>
                                <p className="max-w-[78%] text-[13px] leading-relaxed text-brand-ink/85">
                                    Perfect, can I get 3kg delivered today?
                                </p>
                                <div className="flex justify-end">
                                    <p className="max-w-[78%] rounded-2xl rounded-tr-md bg-brand-green/25 px-4 py-3 text-[13px] leading-relaxed text-brand-ink">
                                        Of course! That'll be ₦3,600. Share your location and I'll send it over.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default CoreFeaturesSection;
