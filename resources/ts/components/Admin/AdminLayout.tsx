import { Link, usePage } from '@inertiajs/react';
import { ReactNode } from 'react';

interface SharedProps {
    auth?: {
        user?: { name: string; role: string } | null;
    };
    [key: string]: unknown;
}

interface NavItem {
    label: string;
    href: string;
    match: string;
    icon: ReactNode;
}

export default function AdminLayout({ children }: { children: ReactNode }) {
    const page = usePage<SharedProps>();
    const user = page.props.auth?.user;
    const pathname = page.url.split('?')[0];

    const navItems: NavItem[] = [
        {
            label: 'Dashboard',
            href: '/admin/dashboard',
            match: '/admin/dashboard',
            icon: (
                <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1v-6zM4 14a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z" />
                </svg>
            ),
        },
        {
            label: 'Users',
            href: '/admin/users',
            match: '/admin/users',
            icon: (
                <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            ),
        },
        {
            label: 'Vendors',
            href: '/admin/vendors/pending',
            match: '/admin/vendors',
            icon: (
                <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            ),
        },
        {
            label: 'Products',
            href: '/admin/products',
            match: '/admin/products',
            icon: (
                <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            ),
        },
        {
            label: 'Bot Monitor',
            href: '/admin/bot-monitor',
            match: '/admin/bot-monitor',
            icon: (
                <svg className="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.526 5.845L.057 23.882l6.198-1.625A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.68.965.982-3.59-.234-.369A9.818 9.818 0 1112 21.818z" />
                </svg>
            ),
        },
        {
            label: 'Settings',
            href: '/admin/settings',
            match: '/admin/settings',
            icon: (
                <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            ),
        },
    ];

    const initials = (user?.name || 'A').charAt(0).toUpperCase();

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Sidebar */}
            <aside className="fixed left-0 top-0 h-screen w-64 bg-primary-dark flex flex-col py-6 px-4 z-50 overflow-y-auto scrollbar-thin-dark">
                <div className="mb-10 px-2 flex items-center gap-3">
                    <div className="w-10 h-10 bg-primary-olive rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div>
                        <h1 className="text-base font-bold text-white leading-tight">Shopping Complex</h1>
                        <p className="text-[10px] tracking-wider uppercase text-primary-light/40">Admin Console</p>
                    </div>
                </div>

                <nav className="flex-1 space-y-1">
                    {navItems.map((item) => {
                        const isActive = item.match !== '' && pathname.startsWith(item.match);
                        return (
                            <Link
                                key={item.label}
                                href={item.href}
                                className={`relative flex items-center py-3 px-4 rounded-lg transition-colors ${
                                    isActive
                                        ? 'bg-primary-olive/10 text-primary-olive'
                                        : 'text-primary-light/50 hover:text-primary-light/80 hover:bg-white/5'
                                }`}
                            >
                                {isActive && (
                                    <span className="absolute left-0 w-1 h-6 bg-primary-olive rounded-r-full" />
                                )}
                                {item.icon}
                                <span className="ml-3 text-[11px] tracking-wider uppercase font-medium">
                                    {item.label}
                                </span>
                            </Link>
                        );
                    })}
                </nav>

                <div className="border-t border-white/5 pt-4">
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="w-full flex items-center text-primary-light/50 hover:text-primary-light/80 hover:bg-white/5 py-3 px-4 rounded-lg transition-colors"
                    >
                        <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span className="ml-3 text-[11px] tracking-wider uppercase font-medium">Logout</span>
                    </Link>
                </div>
            </aside>

            {/* Top Header */}
            <header className="fixed top-0 left-64 right-0 h-16 bg-white/80 backdrop-blur-xl z-40 flex justify-between items-center px-8 border-b border-gray-100">
                <div className="flex items-center flex-1 max-w-md">
                    <div className="relative w-full">
                        <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            placeholder="Search users, vendors, or reports..."
                            className="w-full bg-gray-50 border border-gray-200/60 rounded-full py-2 pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20 placeholder:text-gray-400"
                        />
                    </div>
                </div>
                <div className="flex items-center gap-5">
                    <button className="relative text-gray-500 hover:bg-gray-100 p-2 rounded-full transition-colors">
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-primary-olive rounded-full ring-2 ring-white" />
                    </button>
                    <div className="flex items-center gap-3 pl-4 border-l border-gray-200">
                        <div className="text-right">
                            <p className="text-sm font-semibold text-gray-900">{user?.name || 'Admin'}</p>
                            <p className="text-[10px] text-gray-400 uppercase tracking-widest">Platform Admin</p>
                        </div>
                        <div className="w-9 h-9 rounded-full bg-primary-olive flex items-center justify-center flex-shrink-0">
                            <span className="text-white text-sm font-bold">{initials}</span>
                        </div>
                    </div>
                </div>
            </header>

            {/* Main Content */}
            <main className="ml-64 pt-24 px-8 pb-12 min-h-screen">
                {children}
            </main>
        </div>
    );
}
