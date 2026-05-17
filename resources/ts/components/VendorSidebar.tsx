import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';

interface SidebarItem {
    label: string;
    href: string;
    icon: React.ReactNode;
    exact?: boolean;
}

interface PageProps {
    [key: string]: unknown;
    auth?: {
        user?: {
            id: number;
            slug?: string;
            name: string;
            email: string;
            role: string;
            business_name?: string;
            business_logo?: string | null;
        } | null;
    };
}

interface Props {
    businessName?: string;
    businessLogo?: string | null;
}

export default function VendorSidebar({ businessName, businessLogo }: Props) {
    const { auth } = usePage<PageProps>().props;
    const user = auth?.user;
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
    const [drawerOpen, setDrawerOpen] = useState(false);

    const slug = user?.slug || '';
    const name = businessName || user?.business_name || user?.name || '';
    const logo = businessLogo !== undefined ? businessLogo : (user?.business_logo ?? null);
    const email = user?.email || '';
    const storeHref = slug ? `/vendors/${slug}` : '/';

    const items: SidebarItem[] = [
        {
            label: 'Dashboard',
            href: '/vendor',
            exact: true,
            icon: (
                <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 12a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z" />
                </svg>
            ),
        },
        {
            label: 'Store',
            href: storeHref,
            exact: true,
            icon: (
                <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            ),
        },
        {
            label: 'My Products',
            href: '/vendor/products',
            icon: (
                <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            ),
        },
        {
            label: 'Subscription',
            href: '/vendor/subscription',
            icon: (
                <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            ),
        },
        {
            label: 'Analytics',
            href: '/vendor/analytics',
            icon: (
                <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            ),
        },
        {
            label: 'Settings',
            href: '/vendor/settings',
            icon: (
                <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            ),
        },
        {
            label: 'My Profile',
            href: '/profile',
            icon: (
                <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            ),
        },
    ];

    const isActive = (item: SidebarItem) => {
        if (item.exact) return currentPath === item.href;
        return currentPath === item.href || currentPath.startsWith(item.href + '/');
    };

    const handleSignOut = () => {
        router.post('/logout');
    };

    const SidebarContent = ({ onNavigate }: { onNavigate?: () => void }) => (
        <>
            {/* Top links */}
            <div className="px-5 pt-4 pb-2">
                <Link
                    href="/"
                    onClick={onNavigate}
                    className="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-primary-olive transition-colors"
                >
                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to home
                </Link>
            </div>

            {/* User profile section */}
            <div className="px-5 pt-2 pb-5">
                <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-full overflow-hidden border-2 border-primary-olive flex-shrink-0">
                        {logo ? (
                            <img src={logo} alt={name} className="w-full h-full object-cover" />
                        ) : (
                            <div className="w-full h-full bg-primary-olive flex items-center justify-center">
                                <span className="text-white text-base font-bold">
                                    {name.charAt(0).toUpperCase()}
                                </span>
                            </div>
                        )}
                    </div>
                    <div className="min-w-0">
                        <p className="font-semibold text-gray-900 text-sm truncate">{name}</p>
                        <p className="text-xs text-gray-500 truncate">{email}</p>
                    </div>
                </div>
            </div>

            <div className="mx-5 border-t border-gray-200" />

            {/* Nav Items */}
            <nav className="flex-1 px-3 py-4 flex flex-col gap-0.5">
                {items.map((item) => {
                    const active = isActive(item);
                    return (
                        <Link
                            key={item.label}
                            href={item.href}
                            onClick={onNavigate}
                            className={`flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors ${
                                active
                                    ? 'bg-primary-brown text-white'
                                    : 'text-gray-700 hover:bg-primary-olive/10 hover:text-primary-olive'
                            }`}
                        >
                            {item.icon}
                            {item.label}
                        </Link>
                    );
                })}
            </nav>

            {/* Sign Out */}
            <div className="px-3 pb-5">
                <div className="border-t border-gray-200 pt-3">
                    <button
                        onClick={() => { onNavigate?.(); handleSignOut(); }}
                        className="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:bg-red-50 hover:text-red-600 transition-colors"
                    >
                        <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Sign Out
                    </button>
                </div>
            </div>
        </>
    );

    return (
        <>
        {/* Desktop sidebar */}
        <aside className="hidden md:flex fixed left-0 top-0 bottom-0 w-[260px] bg-[#f5f0ea] border-r border-gray-200 flex-col z-40">
            <SidebarContent />
        </aside>

        {/* Mobile — hamburger button */}
        <button
            onClick={() => setDrawerOpen(true)}
            className="md:hidden fixed top-4 left-4 z-50 w-10 h-10 flex items-center justify-center bg-[#f5f0ea] border border-gray-200 rounded-xl shadow-sm"
            aria-label="Open menu"
        >
            <svg className="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        {/* Mobile — backdrop */}
        {drawerOpen && (
            <div
                className="md:hidden fixed inset-0 bg-black/40 z-40"
                onClick={() => setDrawerOpen(false)}
            />
        )}

        {/* Mobile — slide-in drawer */}
        <aside
            className={`md:hidden fixed left-0 top-0 bottom-0 w-[280px] bg-[#f5f0ea] flex flex-col z-50 shadow-xl transition-transform duration-300 ${
                drawerOpen ? 'translate-x-0' : '-translate-x-full'
            }`}
        >
            {/* Close button */}
            <button
                onClick={() => setDrawerOpen(false)}
                className="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:text-gray-800 hover:bg-gray-200 transition-colors"
                aria-label="Close menu"
            >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <SidebarContent onNavigate={() => setDrawerOpen(false)} />
        </aside>
        </>
    );
}
