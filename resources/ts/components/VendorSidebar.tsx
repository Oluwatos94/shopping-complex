import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    BagIcon,
    BarsIcon,
    ChevronLeftIcon,
    CloseIcon,
    CogIcon,
    CubeIcon,
    GridIcon,
    MenuIcon,
    SignOutIcon,
    TrophyIcon,
    UserIcon,
    WalletIcon,
} from '@/components/icons';
import { SidebarContentProps, SidebarItem, SidebarPageProps, VendorSidebarProps } from '@/types';

const NAV_ICON = 'w-5 h-5 flex-shrink-0';

function isActive(item: SidebarItem, currentPath: string) {
    if (item.exact) return currentPath === item.href;
    return currentPath === item.href || currentPath.startsWith(item.href + '/');
}

function SidebarContent({ items, currentPath, name, email, logo, onSignOut, onNavigate }: SidebarContentProps) {
    return (
        <>
            {/* Top links */}
            <div className="px-5 pt-4 pb-2">
                <Link
                    href="/"
                    onClick={onNavigate}
                    className="inline-flex items-center gap-1.5 text-xs font-semibold text-white/60 hover:text-white transition-colors"
                >
                    <ChevronLeftIcon className="w-3.5 h-3.5" />
                    Back to home
                </Link>
            </div>

            {/* User profile section */}
            <div className="px-5 pt-2 pb-5">
                <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-full overflow-hidden border-2 border-white/15 flex-shrink-0">
                        {logo ? (
                            <img src={logo} alt={name} className="w-full h-full object-cover" />
                        ) : (
                            <div className="w-full h-full bg-gradient-to-br from-brand-green to-brand-green-dark flex items-center justify-center">
                                <span className="text-white text-base font-bold">
                                    {name.charAt(0).toUpperCase()}
                                </span>
                            </div>
                        )}
                    </div>
                    <div className="min-w-0">
                        <p className="font-semibold text-white text-sm truncate">{name}</p>
                        <p className="text-xs text-white/50 truncate">{email}</p>
                    </div>
                </div>
            </div>

            <div className="mx-5 border-t border-white/10" />

            {/* Nav Items */}
            <nav className="flex-1 px-3 py-4 flex flex-col gap-0.5">
                {items.map((item) => {
                    const active = isActive(item, currentPath);
                    return (
                        <Link
                            key={item.label}
                            href={item.href}
                            onClick={onNavigate}
                            className={`flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm transition-colors ${
                                active
                                    ? 'bg-brand-green text-white font-bold'
                                    : 'text-white/60 font-medium hover:bg-white/5 hover:text-white'
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
                <div className="border-t border-white/10 pt-3">
                    <button
                        onClick={() => { onNavigate?.(); onSignOut(); }}
                        className="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-white/70 hover:bg-white/5 hover:text-white transition-colors"
                    >
                        <SignOutIcon className={NAV_ICON} />
                        Sign Out
                    </button>
                </div>
            </div>
        </>
    );
}

export default function VendorSidebar({ businessName, businessLogo }: VendorSidebarProps) {
    const { auth } = usePage<SidebarPageProps>().props;
    const user = auth?.user;
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
    const [drawerOpen, setDrawerOpen] = useState(false);

    const slug = user?.slug || '';
    const name = businessName || user?.business_name || user?.name || '';
    const logo = businessLogo !== undefined ? businessLogo : (user?.business_logo ?? null);
    const email = user?.email || '';
    const storeHref = slug ? `/vendors/${slug}` : '/';

    const items: SidebarItem[] = [
        { label: 'Dashboard', href: '/vendor', exact: true, icon: <GridIcon className={NAV_ICON} /> },
        { label: 'Store', href: storeHref, exact: true, icon: <BagIcon className={NAV_ICON} /> },
        { label: 'My Products', href: '/vendor/products', icon: <CubeIcon className={NAV_ICON} /> },
        { label: 'Wallet', href: '/vendor/wallet', icon: <WalletIcon className={NAV_ICON} /> },
        { label: 'Analytics', href: '/vendor/analytics', icon: <BarsIcon className={NAV_ICON} /> },
        { label: 'Leaderboard', href: '/vendor/referral/leaderboard', icon: <TrophyIcon className={NAV_ICON} /> },
        { label: 'Settings', href: '/vendor/settings', icon: <CogIcon className={NAV_ICON} /> },
        { label: 'My Profile', href: '/profile', icon: <UserIcon className={NAV_ICON} /> },
    ];

    const handleSignOut = () => {
        router.post('/logout');
    };

    const content = { items, currentPath, name, email, logo, onSignOut: handleSignOut };

    return (
        <>
        {/* Desktop sidebar */}
        <aside className="hidden md:flex fixed left-0 top-0 bottom-0 w-[260px] bg-brand-ink font-display flex-col z-40">
            <SidebarContent {...content} />
        </aside>

        {/* Mobile — hamburger button */}
        <button
            onClick={() => setDrawerOpen(true)}
            className="md:hidden fixed top-4 left-4 z-50 w-10 h-10 flex items-center justify-center bg-brand-ink text-white border border-white/10 rounded-xl shadow-sm"
            aria-label="Open menu"
        >
            <MenuIcon className="w-5 h-5 text-white" />
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
            className={`md:hidden fixed left-0 top-0 bottom-0 w-[280px] bg-brand-ink font-display flex flex-col z-50 shadow-xl transition-transform duration-300 ${
                drawerOpen ? 'translate-x-0' : '-translate-x-full'
            }`}
        >
            {/* Close button */}
            <button
                onClick={() => setDrawerOpen(false)}
                className="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-lg text-white/60 hover:text-white hover:bg-white/10 transition-colors"
                aria-label="Close menu"
            >
                <CloseIcon className="w-5 h-5" />
            </button>

            <SidebarContent {...content} onNavigate={() => setDrawerOpen(false)} />
        </aside>
        </>
    );
}
