import React, { useState, useRef, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { NavigationLink } from '@/types/landing';
import { NotificationBell } from '@/components/Notifications/NotificationBell';

interface PageProps {
    [key: string]: unknown;
    auth?: {
        user?: {
            id: number;
            name: string;
            email: string;
            role: string;
            slug: string;
            avatar?: string | null;
        } | null;
    };
}

const BrandLogo: React.FC = () => (
    <Link href="/" className="flex items-center" aria-label="Jiidaa home">
        <img src="/logo/Logo.svg" alt="Jiidaa" className="h-12 w-auto" />
    </Link>
);

const Header: React.FC = () => {
    const { auth } = usePage<PageProps>().props;
    const user = auth?.user;
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);
    const mobileDropdownRef = useRef<HTMLDivElement>(null);

    const navLinks: NavigationLink[] = [
        { label: 'Home', href: '/' },
        { label: 'Explore Products', href: '/products' },
        { label: 'Vendors', href: '/vendors' },
        ...(user?.role === 'vendor' ? [] : [{ label: 'Become a Vendor', href: '/vendor/register' }]),
    ];

    const handleSignOut = () => {
        setIsDropdownOpen(false);
        setIsMobileMenuOpen(false);
        router.post('/logout');
    };

    useEffect(() => {
        const handleClickOutside = (e: MouseEvent) => {
            const inDesktop = dropdownRef.current?.contains(e.target as Node);
            const inMobile = mobileDropdownRef.current?.contains(e.target as Node);
            if (!inDesktop && !inMobile) {
                setIsDropdownOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const profileLink = user?.role === 'vendor' ? `/vendors/${user.slug}` : '/profile';

    const accountDropdown = (
        <div className="absolute right-0 mt-2 w-48 rounded-xl border border-brand-line bg-white py-1 shadow-lg shadow-brand-ink/5 z-50 animate-dropdown-in">
            <div className="border-b border-brand-line px-4 py-2">
                <p className="truncate text-sm font-semibold text-brand-ink">{user?.name}</p>
                <p className="truncate text-xs text-brand-muted">{user?.email}</p>
            </div>
            {user?.role === 'vendor' && (
                <Link
                    href={profileLink}
                    onClick={() => setIsDropdownOpen(false)}
                    className="block px-4 py-2 text-sm text-brand-ink transition-colors hover:bg-brand-surface"
                >
                    View Profile
                </Link>
            )}
            <Link
                href="/profile"
                onClick={() => setIsDropdownOpen(false)}
                className="block px-4 py-2 text-sm text-brand-ink transition-colors hover:bg-brand-surface"
            >
                Account Settings
            </Link>
            <div className="border-t border-brand-line">
                <button
                    onClick={handleSignOut}
                    className="block w-full px-4 py-2 text-left text-sm text-brand-danger transition-colors hover:bg-red-50"
                >
                    Sign Out
                </button>
            </div>
        </div>
    );

    const avatar = (size: string, textSize: string) =>
        user?.avatar ? (
            <img
                src={user.avatar}
                alt={user.name}
                className={`${size} rounded-full border-2 border-brand-line object-cover transition-colors hover:border-brand-green`}
            />
        ) : (
            <div
                className={`${size} flex items-center justify-center rounded-full border-2 border-brand-line bg-brand-ink transition-colors hover:border-brand-green`}
            >
                <span className={`font-bold text-white ${textSize}`}>{user?.name.charAt(0).toUpperCase()}</span>
            </div>
        );

    return (
        <header className="sticky top-0 z-50 border-b border-brand-line/60 bg-brand-surface/80 backdrop-blur-md font-display">
            <nav className="mx-auto max-w-[1320px] px-6 lg:px-10">
                <div className="flex items-center justify-between py-5">
                    {/* Logo */}
                    <BrandLogo />

                    {/* Center nav — desktop */}
                    <div className="hidden items-center gap-9 lg:flex">
                        {navLinks.map((link) => (
                            <Link
                                key={link.label}
                                href={link.href}
                                className="text-[15px] font-medium text-brand-muted transition hover:text-brand-ink"
                            >
                                {link.label}
                            </Link>
                        ))}
                    </div>

                    {/* Right actions — desktop */}
                    <div className="hidden items-center gap-5 lg:flex">
                        {user ? (
                            <div className="flex items-center gap-4">
                                <NotificationBell />
                                <div className="relative" ref={dropdownRef}>
                                    <button
                                        onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                                        className="flex items-center focus:outline-none"
                                    >
                                        {avatar('h-9 w-9', 'text-sm')}
                                    </button>
                                    {isDropdownOpen && accountDropdown}
                                </div>
                            </div>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="text-[15px] font-semibold text-brand-ink transition hover:text-brand-muted"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-full bg-brand-ink px-5 py-2.5 text-[15px] font-semibold text-white shadow-sm transition hover:bg-brand-ink/90"
                                >
                                    Get started
                                </Link>
                            </>
                        )}
                    </div>

                    {/* Mobile right-side controls */}
                    <div className="flex items-center gap-3 lg:hidden">
                        {user && (
                            <>
                                <NotificationBell />
                                <div ref={mobileDropdownRef} className="relative">
                                    <button
                                        onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                                        className="focus:outline-none"
                                    >
                                        {avatar('h-8 w-8', 'text-xs')}
                                    </button>
                                    {isDropdownOpen && accountDropdown}
                                </div>
                            </>
                        )}

                        {/* Hamburger */}
                        <button
                            className="text-brand-ink"
                            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                            aria-label="Toggle menu"
                        >
                            <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                {isMobileMenuOpen ? (
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                ) : (
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                                )}
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Mobile nav links */}
                {isMobileMenuOpen && (
                    <div className="space-y-1 pb-5 lg:hidden">
                        {navLinks.map((link) => (
                            <Link
                                key={link.label}
                                href={link.href}
                                className="block rounded-lg px-2 py-2.5 text-[15px] font-medium text-brand-muted transition hover:bg-white hover:text-brand-ink"
                            >
                                {link.label}
                            </Link>
                        ))}
                        {!user && (
                            <div className="flex flex-col gap-3 pt-3">
                                <Link
                                    href="/login"
                                    className="rounded-full border border-brand-line bg-white px-5 py-2.5 text-center text-[15px] font-semibold text-brand-ink transition hover:bg-brand-surface"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-full bg-brand-ink px-5 py-2.5 text-center text-[15px] font-semibold text-white transition hover:bg-brand-ink/90"
                                >
                                    Get started
                                </Link>
                            </div>
                        )}
                    </div>
                )}
            </nav>
        </header>
    );
};

export default Header;
