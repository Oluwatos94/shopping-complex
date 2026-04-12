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
        { label: 'Become a Vendor', href: '/vendor/register' },
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

    return (
        <header className="bg-primary-dark shadow-md sticky top-0 z-50">
            <nav className="container mx-auto px-4 py-4">
                <div className="flex items-center justify-between">
                    {/* Logo */}
                    <Link href="/" className="flex items-center space-x-3">
                        <img
                            src="/logo/dark-mode-logo.svg"
                            alt="Shopping Complex Logo"
                            className="h-12 w-auto"
                        />
                        <span className="text-primary-light font-bold text-xl hidden sm:block">
                            Shopping Complex
                        </span>
                    </Link>

                    {/* Desktop nav */}
                    <div className="hidden md:flex items-center space-x-8">
                        {navLinks.map((link) => (
                            <Link
                                key={link.label}
                                href={link.href}
                                className="text-primary-light hover:text-primary-peach transition-colors duration-300 font-medium"
                            >
                                {link.label}
                            </Link>
                        ))}

                        {user ? (
                            <div className="flex items-center gap-3">
                                {/* Notification bell — logged-in only */}
                                <NotificationBell />

                                {/* Avatar + dropdown */}
                                <div className="relative" ref={dropdownRef}>
                                    <button
                                        onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                                        className="flex items-center focus:outline-none"
                                    >
                                        {user.avatar ? (
                                            <img
                                                src={user.avatar}
                                                alt={user.name}
                                                className="w-9 h-9 rounded-full object-cover border-2 border-primary-light/30 hover:border-primary-peach transition-colors"
                                            />
                                        ) : (
                                            <div className="w-9 h-9 rounded-full bg-primary-olive flex items-center justify-center border-2 border-primary-light/30 hover:border-primary-peach transition-colors">
                                                <span className="text-white text-sm font-bold">
                                                    {user.name.charAt(0).toUpperCase()}
                                                </span>
                                            </div>
                                        )}
                                    </button>

                                    {isDropdownOpen && (
                                        <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                            <div className="px-4 py-2 border-b border-gray-100">
                                                <p className="text-sm font-medium text-gray-800 truncate">{user.name}</p>
                                                <p className="text-xs text-gray-400 truncate">{user.email}</p>
                                            </div>
                                            {user.role === 'vendor' && (
                                                <Link
                                                    href={profileLink}
                                                    onClick={() => setIsDropdownOpen(false)}
                                                    className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                                >
                                                    View Profile
                                                </Link>
                                            )}
                                            <Link
                                                href="/profile"
                                                onClick={() => setIsDropdownOpen(false)}
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                            >
                                                Account Settings
                                            </Link>
                                            <div className="border-t border-gray-100">
                                                <button
                                                    onClick={handleSignOut}
                                                    className="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                                >
                                                    Sign Out
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ) : (
                            <Link
                                href="/login"
                                className="bg-primary-olive text-white px-6 py-2 rounded-lg hover:bg-primary-peach transition-colors duration-300 font-medium"
                            >
                                Login
                            </Link>
                        )}
                    </div>

                    {/* Mobile right-side controls */}
                    <div className="md:hidden flex items-center gap-3">
                        {user && (
                            <>
                                {/* Notification bell — logged-in only */}
                                <NotificationBell />

                                {/* Avatar button */}
                                <div ref={mobileDropdownRef} className="relative">
                                    <button
                                        onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                                        className="focus:outline-none"
                                    >
                                        {user.avatar ? (
                                            <img
                                                src={user.avatar}
                                                alt={user.name}
                                                className="w-8 h-8 rounded-full object-cover border-2 border-primary-light/30"
                                            />
                                        ) : (
                                            <div className="w-8 h-8 rounded-full bg-primary-olive flex items-center justify-center border-2 border-primary-light/30">
                                                <span className="text-white text-xs font-bold">
                                                    {user.name.charAt(0).toUpperCase()}
                                                </span>
                                            </div>
                                        )}
                                    </button>

                                    {/* Mobile dropdown panel — inside same ref wrapper */}
                                    {isDropdownOpen && (
                                        <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                            <div className="px-4 py-2 border-b border-gray-100">
                                                <p className="text-sm font-medium text-gray-800 truncate">{user.name}</p>
                                                <p className="text-xs text-gray-400 truncate">{user.email}</p>
                                            </div>
                                            {user.role === 'vendor' && (
                                                <Link
                                                    href={profileLink}
                                                    onClick={() => setIsDropdownOpen(false)}
                                                    className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                                >
                                                    View Profile
                                                </Link>
                                            )}
                                            <Link
                                                href="/profile"
                                                onClick={() => setIsDropdownOpen(false)}
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                            >
                                                Account Settings
                                            </Link>
                                            <div className="border-t border-gray-100">
                                                <button
                                                    onClick={handleSignOut}
                                                    className="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                                >
                                                    Sign Out
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </>
                        )}

                        {/* Hamburger */}
                        <button
                            className="text-primary-light"
                            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <div className="md:hidden mt-4 pb-4 space-y-3">
                        {navLinks.map((link) => (
                            <Link
                                key={link.label}
                                href={link.href}
                                className="block text-primary-light hover:text-primary-peach transition-colors duration-300 font-medium py-2"
                            >
                                {link.label}
                            </Link>
                        ))}
                        {!user && (
                            <Link
                                href="/login"
                                className="block bg-primary-olive text-white px-6 py-2 rounded-lg hover:bg-primary-peach transition-colors duration-300 font-medium text-center"
                            >
                                Login
                            </Link>
                        )}
                    </div>
                )}
            </nav>
        </header>
    );
};

export default Header;
