import React from 'react';
import { Link } from '@inertiajs/react';

interface FooterLink {
    label: string;
    href: string;
}

const quickLinks: FooterLink[] = [
    { label: 'Home', href: '/' },
    { label: 'Explore Products', href: '/products' },
    { label: 'Categories', href: '/categories' },
    { label: 'Become a Vendor', href: '/vendor/register' },
];

const supportLinks: FooterLink[] = [
    { label: 'Help Center', href: '/help' },
    { label: 'Contact Us', href: '/contact' },
    { label: 'FAQ', href: '/#faq' },
    { label: 'Terms of Service', href: '/terms' },
    { label: 'Privacy Policy', href: '/privacy' },
];

const socials: { label: string; href: string; icon: React.ReactNode }[] = [
    {
        label: 'Instagram',
        href: 'https://www.instagram.com/jiidaa_app?igsh=MXU1NHk2anFvd2FvdA==',
        icon: (
            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8}>
                <rect x="3" y="3" width="18" height="18" rx="5" />
                <circle cx="12" cy="12" r="4" />
                <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
            </svg>
        ),
    },
    {
        label: 'LinkedIn',
        href: 'https://www.linkedin.com/company/jiidaa/',
        icon: (
            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M4.98 3.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4ZM3 9h4v12H3V9Zm6 0h3.8v1.7h.05c.53-1 1.83-2.05 3.77-2.05 4.03 0 4.78 2.65 4.78 6.1V21h-4v-5.4c0-1.3-.02-3-1.82-3-1.83 0-2.1 1.43-2.1 2.9V21H9V9Z" />
            </svg>
        ),
    },
    {
        label: 'X',
        href: 'https://x.com/JiidaaSupport',
        icon: (
            <svg className="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.2 2H21l-6.6 7.5L22 22h-6.8l-4.8-6.3L4.8 22H2l7.1-8.1L2 2h6.9l4.3 5.8L18.2 2Zm-1.2 18h1.6L7.1 3.8H5.4L17 20Z" />
            </svg>
        ),
    },
    {
        label: 'Facebook',
        href: 'https://www.facebook.com/profile.php?id=61590646383043',
        icon: (
            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07c0 6.02 4.39 11.01 10.13 11.93v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.69.24 2.69.24v2.97h-1.52c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.08 24 18.09 24 12.07Z" />
            </svg>
        ),
    },
    {
        label: 'TikTok',
        href: 'https://www.tiktok.com/@jiidaa700?_r=1&_t=ZS-97Rc5Pknt7F',
        icon: (
            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M16 3c.3 2.2 1.7 3.7 3.8 4v2.4c-1.3 0-2.6-.4-3.8-1.1v5.9a5.6 5.6 0 1 1-5.6-5.6c.3 0 .6 0 .9.1v2.5a3.1 3.1 0 1 0 2.2 3V3H16Z" />
            </svg>
        ),
    },
];

const Footer: React.FC = () => {
    const currentYear = new Date().getFullYear();

    return (
        <footer className="bg-brand-ink font-display text-white">
            <div className="mx-auto max-w-[1320px] px-6 py-16 lg:px-10">
                <div className="grid gap-12 lg:grid-cols-[1.4fr_1fr_1fr_1.2fr]">
                    {/* Brand */}
                    <div className="max-w-xs">
                        <img src="/logo/whiteLogo.png" alt="Jiidaa" className="h-12 w-auto" />

                        <p className="mt-6 text-[15px] leading-relaxed text-white/55">
                            Shop smarter, shop anywhere. A GPS-powered, WhatsApp-native marketplace connecting you to
                            nearby vendors in seconds.
                        </p>

                        {/* socials */}
                        <div className="mt-7 flex gap-2.5">
                            {socials.map((s) => (
                                <a
                                    key={s.label}
                                    href={s.href}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label={s.label}
                                    className="flex h-9 w-9 items-center justify-center rounded-lg text-white/70 ring-1 ring-white/15 transition hover:bg-white/10 hover:text-white"
                                >
                                    {s.icon}
                                </a>
                            ))}
                        </div>
                    </div>

                    {/* Quick Links */}
                    <div>
                        <h4 className="text-[15px] font-bold text-white">Quick Links</h4>
                        <ul className="mt-5 space-y-3.5 text-[15px] text-white/55">
                            {quickLinks.map((link) => (
                                <li key={link.label}>
                                    <Link href={link.href} className="transition hover:text-white">
                                        {link.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Support */}
                    <div>
                        <h4 className="text-[15px] font-bold text-white">Support</h4>
                        <ul className="mt-5 space-y-3.5 text-[15px] text-white/55">
                            {supportLinks.map((link) => (
                                <li key={link.label}>
                                    <Link href={link.href} className="transition hover:text-white">
                                        {link.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Contact Info */}
                    <div>
                        <h4 className="text-[15px] font-bold text-white">Contact Info</h4>
                        <ul className="mt-5 space-y-4 text-[15px] text-white/55">
                            {/* Physical address hidden for now
                            <li className="flex items-center gap-3">
                                <svg
                                    className="h-4 w-4 shrink-0 text-brand-green"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth={1.8}
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M12 21s7-5.686 7-11a7 7 0 1 0-14 0c0 5.314 7 11 7 11Z" />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                                Johnkay crescent, Lagos, Nigeria
                            </li>
                            */}
                            <li className="flex items-center gap-3">
                                <svg
                                    className="h-4 w-4 shrink-0 text-brand-green"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth={1.8}
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M3 12h18M12 3c2.5 2.7 2.5 15.3 0 18M12 3c-2.5 2.7-2.5 15.3 0 18" />
                                </svg>
                                <a href="mailto:hello@jiidaa.com" className="transition hover:text-white">
                                    hello@jiidaa.com
                                </a>
                            </li>
                            <li className="flex items-center gap-3">
                                <svg className="h-4 w-4 shrink-0 text-brand-green" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.8 4.9-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1.1-1.4-1.1-2.6 0-1.3.7-1.9.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.6c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.2.1.4.1.6-.1l.7-.9c.2-.2.4-.2.6-.1l1.9.9c.2.1.4.2.4.3.1.2.1.7-.1 1.3Z" />
                                </svg>
                                <a href="https://wa.me/2340000000000" className="transition hover:text-white">
                                    +234 701 115 5694
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                {/* Bottom bar */}
                <div className="mt-14 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-7 text-[13px] text-white/55 md:flex-row">
                    <p>&copy; {currentYear} jiidaa. All rights reserved.</p>
                    <div className="flex gap-6">
                        <Link href="/terms" className="transition hover:text-white">
                            Terms
                        </Link>
                        <Link href="/privacy" className="transition hover:text-white">
                            Privacy
                        </Link>
                        <Link href="/cookies" className="transition hover:text-white">
                            Cookies
                        </Link>
                    </div>
                </div>
            </div>
        </footer>
    );
};

export default Footer;
