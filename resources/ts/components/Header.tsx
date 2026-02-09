import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { NavigationLink } from '@/types/landing';

interface PageProps {
    [key: string]: unknown;
    auth?: {
        user?: {
            id: number;
            name: string;
            email: string;
        } | null;
    };
}

const Header: React.FC = () => {
    const { auth } = usePage<PageProps>().props;
    const user = auth?.user;
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

    const navLinks: NavigationLink[] = [
        { label: 'Home', href: '/' },
        { label: 'Explore Products', href: '/products' },
        { label: 'Become a Vendor', href: '/vendor/register' },
    ];

    const handleSignOut = () => {
        router.post('/logout');
    };

    return (
        <header className="bg-primary-dark shadow-md sticky top-0 z-50">
            <nav className="container mx-auto px-4 py-4">
                <div className="flex items-center justify-between">
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
                            <button
                                onClick={handleSignOut}
                                className="bg-primary-olive text-white px-6 py-2 rounded-lg hover:bg-primary-peach transition-colors duration-300 font-medium"
                            >
                                Sign Out
                            </button>
                        ) : (
                            <Link
                                href="/login"
                                className="bg-primary-olive text-white px-6 py-2 rounded-lg hover:bg-primary-peach transition-colors duration-300 font-medium"
                            >
                                Login
                            </Link>
                        )}
                    </div>

                    <button
                        className="md:hidden text-primary-light"
                        onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                    >
                        <svg
                            className="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            {isMobileMenuOpen ? (
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            ) : (
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                            )}
                        </svg>
                    </button>
                </div>

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
                        {user ? (
                            <button
                                onClick={handleSignOut}
                                className="block w-full bg-primary-olive text-white px-6 py-2 rounded-lg hover:bg-primary-peach transition-colors duration-300 font-medium text-center"
                            >
                                Sign Out
                            </button>
                        ) : (
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
