import { Head, Link } from '@inertiajs/react';
import VendorSidebar from '@/components/VendorSidebar';

interface Props {
    vendor: {
        name: string;
        business_name: string;
    };
    subscription: {
        plan_name: string | null;
        plan_slug: string | null;
        expires_at: string | null;
        days_remaining: number | null;
        is_expired: boolean;
        product_limit: number | null;
    };
    stats: {
        active_products: number;
        catalogue_views_this_week: number;
    };
}

export default function VendorDashboard({ vendor, subscription, stats }: Props) {
    const isExpiringSoon = subscription.days_remaining !== null && subscription.days_remaining <= 7 && !subscription.is_expired;

    return (
        <>
            <Head title="Dashboard" />
            <VendorSidebar />

            <main className="md:ml-[260px] min-h-screen bg-brand-surface pb-20 md:pb-0">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 py-8">

                    {/* Page Header */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
                        <p className="text-sm text-gray-500 mt-1">Welcome back, {vendor.business_name}!</p>
                    </div>

                    {/* Expiry / expired alert */}
                    {(subscription.is_expired || isExpiringSoon) && (
                        <div className="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
                            <svg className="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>
                                {subscription.is_expired
                                    ? 'Your subscription has expired. '
                                    : `Your subscription expires in ${subscription.days_remaining} day${subscription.days_remaining === 1 ? '' : 's'}. `}
                                <Link href="/vendor/subscription" className="font-semibold underline hover:no-underline">
                                    Renew now
                                </Link>
                                {' '}to stay discoverable.
                            </span>
                        </div>
                    )}

                    {/* Stats Cards */}
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        {/* Subscription */}
                        <div className="bg-white rounded-2xl p-5 shadow-sm col-span-2 sm:col-span-1">
                            <p className="text-xs text-gray-500 mb-3">Subscription</p>
                            <p className="text-lg font-bold text-gray-900 mb-1">
                                {subscription.plan_name ?? 'No Plan'}
                            </p>
                            <p className="text-xs text-gray-400 mb-4">
                                Expires: {subscription.expires_at ?? 'N/A'}
                            </p>
                            <Link
                                href="/vendor/subscription"
                                className="block text-center text-sm font-medium text-gray-700 border border-gray-300 rounded-lg py-1.5 hover:bg-gray-50 transition-colors"
                            >
                                Upgrade
                            </Link>
                        </div>

                        {/* Products */}
                        <div className="bg-white rounded-2xl p-5 shadow-sm">
                            <p className="text-xs text-gray-500 mb-3">Products</p>
                            <p className="text-3xl font-bold text-gray-900 mb-1">{stats.active_products}</p>
                            <p className="text-xs text-gray-400">
                                {subscription.product_limit !== null
                                    ? `of ${subscription.product_limit} allowed`
                                    : 'Unlimited products'}
                            </p>
                        </div>

                        {/* Catalogue Views */}
                        <div className="bg-white rounded-2xl p-5 shadow-sm">
                            <p className="text-xs text-gray-500 mb-3">Catalogue Views</p>
                            <div className="flex items-center gap-2 mb-1">
                                <p className="text-3xl font-bold text-gray-900">{stats.catalogue_views_this_week}</p>
                                <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <p className="text-xs text-gray-400">This week</p>
                        </div>

                    </div>

                    {/* Recent Activity */}
                    <div className="bg-white rounded-2xl p-6 shadow-sm">
                        <h2 className="text-base font-semibold text-gray-900 mb-1">Recent Activity</h2>
                        <p className="text-xs text-gray-500 mb-6">Latest interactions with your business</p>
                        <div className="flex flex-col items-center justify-center py-10 text-center">
                            <svg className="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <p className="text-sm text-gray-500">No activity yet. Start adding products to get discovered!</p>
                        </div>
                    </div>

                </div>
            </main>
        </>
    );
}
