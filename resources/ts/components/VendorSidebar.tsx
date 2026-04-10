import { Link, usePage } from '@inertiajs/react';

interface SidebarItem {
    label: string;
    href: string;
    icon: React.ReactNode;
}

interface PageProps {
    [key: string]: unknown;
    auth?: {
        user?: {
            id: number;
            slug?: string;
            name: string;
            role: string;
            business_name?: string;
            business_logo?: string | null;
        } | null;
    };
}

interface Props {
    vendorSlug?: string;
    businessName?: string;
    businessLogo?: string;
}

export default function VendorSidebar({ vendorSlug, businessName, businessLogo }: Props) {
    const { auth } = usePage<PageProps>().props;
    const user = auth?.user;

    const slug = vendorSlug || user?.slug;
    const name = businessName || user?.business_name || user?.name || '';
    const logo = businessLogo || user?.business_logo || null;

    const items: SidebarItem[] = [
        {
            label: 'Home',
            href: '/',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            ),
        },
        {
            label: 'Catalogue',
            href: '/products',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
            ),
        },
        {
            label: 'Chat',
            href: '/chat',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            ),
        },
        {
            label: 'Analytics',
            href: '/vendor/analytics',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            ),
        },
        {
            label: 'Settings',
            href: '#',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            ),
        },
        {
            label: 'Subscription',
            href: '/vendor/subscription',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                </svg>
            ),
        },
    ];

    return (
        <>
        {/* Desktop sidebar — hidden on mobile */}
        <aside className="hidden md:flex fixed left-0 top-0 bottom-0 w-[100px] bg-gray-100 border-r border-gray-200 flex-col items-center py-6 z-40">
            {/* Logo */}
            <Link href="/" className="mb-8">
                <img
                    src="/logo/dark-mode-2.svg"
                    alt="Shopping Complex"
                    className="h-10 w-auto"
                />
            </Link>

            {/* Nav Items */}
            <nav className="flex-1 flex flex-col items-center gap-2">
                {items.map((item) => (
                    <Link
                        key={item.label}
                        href={item.href}
                        className="flex flex-col items-center gap-1 px-3 py-3 rounded-xl text-primary-brown hover:bg-primary-olive/10 hover:text-primary-olive transition-colors w-[80px]"
                    >
                        {item.icon}
                        <span className="text-[10px] font-medium">{item.label}</span>
                    </Link>
                ))}
            </nav>

            {/* Profile Avatar at Bottom */}
            <Link
                href={slug ? `/vendors/${slug}` : '#'}
                className="mt-auto flex flex-col items-center gap-1 px-3 py-3 rounded-xl hover:bg-primary-olive/10 transition-colors"
            >
                <div className="w-10 h-10 rounded-full overflow-hidden border-2 border-primary-olive">
                    {logo ? (
                        <img
                            src={logo}
                            alt={name}
                            className="w-full h-full object-cover"
                        />
                    ) : (
                        <div className="w-full h-full bg-primary-olive flex items-center justify-center">
                            <span className="text-white text-sm font-bold">
                                {name.charAt(0).toUpperCase()}
                            </span>
                        </div>
                    )}
                </div>
                <span className="text-[10px] font-medium text-primary-brown">Profile</span>
            </Link>
        </aside>

        {/* Mobile bottom nav bar */}
        <nav className="md:hidden fixed bottom-0 left-0 right-0 bg-gray-100 border-t border-gray-200 flex items-center justify-around py-2 z-40">
            {items.slice(0, 5).map((item) => (
                <Link
                    key={item.label}
                    href={item.href}
                    className="flex flex-col items-center gap-1 px-2 py-1 rounded-xl text-primary-brown hover:text-primary-olive transition-colors"
                >
                    <span className="w-5 h-5">{item.icon}</span>
                    <span className="text-[9px] font-medium">{item.label}</span>
                </Link>
            ))}
            {/* Profile */}
            <Link
                href={slug ? `/vendors/${slug}` : '#'}
                className="flex flex-col items-center gap-1 px-2 py-1 rounded-xl text-primary-brown hover:text-primary-olive transition-colors"
            >
                <div className="w-5 h-5 rounded-full overflow-hidden border border-primary-olive">
                    {logo ? (
                        <img src={logo} alt={name} className="w-full h-full object-cover" />
                    ) : (
                        <div className="w-full h-full bg-primary-olive flex items-center justify-center">
                            <span className="text-white text-[8px] font-bold">{name.charAt(0).toUpperCase()}</span>
                        </div>
                    )}
                </div>
                <span className="text-[9px] font-medium">Profile</span>
            </Link>
        </nav>
        </>
    );
}
