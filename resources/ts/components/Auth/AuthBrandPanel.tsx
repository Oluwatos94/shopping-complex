import React, { useMemo } from 'react';

interface AuthBrandPanelProps {
    title: React.ReactNode;
    subtitle: string;
}

// Representative avatars for the Nigerian launch (DiceBear, brown skin tones).
const seeds = [
    'Chidi', 'Amaka', 'Tunde', 'Ngozi', 'Emeka', 'Funke', 'Obinna', 'Zainab', 'Yusuf', 'Aisha',
    'Kunle', 'Bola', 'Ifeoma', 'Musa', 'Chioma', 'Segun', 'Hauwa', 'Femi', 'Adaeze', 'Tobi',
    'Halima', 'Uche', 'Damilola', 'Kelechi', 'Fatima',
];
const bgs = ['c0aede', 'b6e3f4', 'ffd5dc', 'd1f4d1', 'ffe9c8', 'ffdfbf', 'd9f2f4', 'e7e0fb', 'fce3f1', 'fbf0ce'];

// Evenly spaced concentric rings; count grows outward.
const rings = [
    { count: 3, r: 116, size: 50, start: -150 },
    { count: 5, r: 180, size: 50, start: -90 },
    { count: 7, r: 244, size: 50, start: -90 },
    { count: 10, r: 308, size: 50, start: -90 },
];

const buildUrl = (seed: string, bg: string) =>
    `https://api.dicebear.com/9.x/avataaars/svg?seed=${encodeURIComponent(seed)}&skinColor=614335,8d5524&backgroundColor=${bg}`;

const AuthBrandPanel: React.FC<AuthBrandPanelProps> = ({ title, subtitle }) => {
    const avatars = useMemo(() => {
        const out: { x: number; y: number; size: number; url: string; delay: number; key: string }[] = [];
        let p = 0;
        rings.forEach((ring) => {
            for (let i = 0; i < ring.count; i++) {
                const ang = ((ring.start + (360 / ring.count) * i) * Math.PI) / 180;
                const seed = seeds[p % seeds.length] ?? 'Jiidaa';
                const bg = bgs[p % bgs.length] ?? 'c0aede';
                out.push({
                    x: Math.cos(ang) * ring.r,
                    y: Math.sin(ang) * ring.r,
                    size: ring.size,
                    url: buildUrl(seed, bg),
                    delay: (p % 6) * 0.4,
                    key: `${seed}-${p}`,
                });
                p++;
            }
        });
        return out;
    }, []);

    return (
        <div className="relative hidden min-w-0 overflow-hidden bg-brand-ink lg:block">
            {/* Backdrop: green glow + dotted texture */}
            <div className="absolute inset-0 bg-[radial-gradient(120%_90%_at_75%_-5%,rgba(37,211,102,0.30),rgba(11,31,58,0)_55%)]" />
            <div className="absolute inset-0 bg-[radial-gradient(90%_70%_at_-10%_110%,rgba(37,211,102,0.18),rgba(11,31,58,0)_55%)]" />
            <div className="absolute inset-0 opacity-[0.18] [background-image:radial-gradient(rgba(255,255,255,0.6)_1px,transparent_1px)] [background-size:26px_26px]" />

            {/* Logo */}
            <a href="/" className="absolute left-12 top-10 z-30 flex items-center">
                <img src="/logo/whiteLogo.png" alt="Jiidaa" className="h-10 w-auto" />
            </a>

            {/* Sonar / radar stage */}
            <div className="absolute inset-0 flex items-center justify-center">
                <div className="relative h-[660px] w-[660px] -translate-y-8">
                    {/* expanding water ripples (origin = pin tip = center) */}
                    <span className="absolute left-1/2 top-1/2 h-[620px] w-[620px] rounded-full border border-brand-green/50 animate-auth-ripple" />
                    <span className="absolute left-1/2 top-1/2 h-[620px] w-[620px] rounded-full border border-brand-green/50 animate-auth-ripple" style={{ animationDelay: '1.5s' }} />
                    <span className="absolute left-1/2 top-1/2 h-[620px] w-[620px] rounded-full border border-brand-green/50 animate-auth-ripple" style={{ animationDelay: '3s' }} />

                    {/* static guide rings */}
                    <span className="absolute left-1/2 top-1/2 h-[224px] w-[224px] -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/10" />
                    <span className="absolute left-1/2 top-1/2 h-[356px] w-[356px] -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/[0.08]" />
                    <span className="absolute left-1/2 top-1/2 h-[488px] w-[488px] -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/[0.06]" />
                    <span className="absolute left-1/2 top-1/2 h-[620px] w-[620px] -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/[0.04]" />

                    {/* avatars */}
                    {avatars.map((a) => (
                        <div
                            key={a.key}
                            className="absolute left-1/2 top-1/2 z-10"
                            style={{ transform: `translate(calc(-50% + ${a.x.toFixed(1)}px), calc(-50% + ${a.y.toFixed(1)}px))` }}
                        >
                            <div style={{ width: a.size, height: a.size }}>
                                <div
                                    className="h-full w-full overflow-hidden rounded-full border-[3px] border-white/90 bg-[#1b3350] shadow-[0_10px_24px_-6px_rgba(0,0,0,0.55)] animate-auth-bob"
                                    style={{ animationDelay: `${a.delay}s` }}
                                >
                                    <img
                                        src={a.url}
                                        alt="Vendor"
                                        loading="lazy"
                                        referrerPolicy="no-referrer"
                                        className="block h-full w-full object-cover"
                                    />
                                </div>
                            </div>
                        </div>
                    ))}

                    {/* water-drop impact at tip */}
                    <span className="absolute left-1/2 top-1/2 h-16 w-16 rounded-full border-2 border-brand-green/70 animate-auth-drop" />
                    <span className="absolute left-1/2 top-1/2 h-16 w-16 rounded-full border-2 border-brand-green/70 animate-auth-drop" style={{ animationDelay: '1s' }} />

                    {/* center location pin (tip pinned to exact center) */}
                    <div className="absolute left-1/2 top-1/2 z-40 -translate-x-1/2 -translate-y-full">
                        <div className="relative flex h-[96px] w-[84px] items-start justify-center drop-shadow-[0_18px_28px_rgba(0,0,0,0.55)]">
                            <span className="absolute -bottom-6 left-1/2 h-16 w-16 -translate-x-1/2 rounded-full bg-brand-green/40 blur-2xl" />
                            <svg className="relative h-[96px] w-[84px] text-brand-green" viewBox="0 0 24 26" fill="currentColor">
                                <path d="M12 26s9-8.5 9-15A9 9 0 1 0 3 11c0 6.5 9 15 9 15Z" />
                            </svg>
                            <span className="absolute top-[9px] flex h-[42px] w-[42px] items-center justify-center rounded-full bg-brand-ink">
                                <svg className="h-5 w-5 text-brand-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                            </span>
                        </div>
                    </div>
                    {/* contact dot exactly at center */}
                    <span className="absolute left-1/2 top-1/2 z-30 h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand-green shadow-[0_0_0_6px_rgba(37,211,102,0.25)]" />
                </div>
            </div>

            {/* bottom scrim for headline legibility */}
            <div className="pointer-events-none absolute inset-x-0 bottom-0 z-20 h-72 bg-gradient-to-t from-brand-ink via-brand-ink/85 to-transparent" />

            {/* Headline */}
            <div className="absolute bottom-12 left-12 right-12 z-30">
                <h1 className="max-w-md font-serif text-[40px] font-semibold leading-[1.08] text-white">{title}</h1>
                <p className="mt-4 max-w-sm text-[15px] leading-relaxed text-white/60">{subtitle}</p>
            </div>
        </div>
    );
};

export default AuthBrandPanel;
