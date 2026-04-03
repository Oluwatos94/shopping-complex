import { Head, Link } from '@inertiajs/react';
import VendorSidebar from '@/components/VendorSidebar';
import StatCard from '@/components/Vendor/StatCard';
import ViewsChart from '@/components/Charts/ViewsChart';
import { useAnalytics, type AnalyticsData, type SubscriptionInfo } from '@/hooks/useAnalytics';

const periods = [
    { value: 'daily', label: 'Today' },
    { value: 'weekly', label: '7 Days' },
    { value: 'monthly', label: '30 Days' },
    { value: 'yearly', label: '1 Year' },
];

const PLAN_BADGE_STYLES: Record<string, string> = {
    free: 'bg-gray-100 text-gray-600',
    basic: 'bg-blue-100 text-blue-700',
    premium: 'bg-amber-100 text-amber-800',
};

const dateFormatter = new Intl.DateTimeFormat('en-NG', { day: 'numeric', month: 'short', year: 'numeric' });

function formatCurrency(value: number) {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 0 }).format(value);
}

function SubscriptionStatusCard({ subscription, activeProducts }: { subscription: SubscriptionInfo; activeProducts: number }) {
    if (!subscription.plan_slug) {
        return null;
    }

    const isFree = subscription.plan_slug === 'free';
    const isExpiringSoon = subscription.days_remaining !== null && subscription.days_remaining <= 7;
    const atProductLimit = subscription.product_limit !== null && activeProducts >= subscription.product_limit;
    const badgeStyle = PLAN_BADGE_STYLES[subscription.plan_slug] ?? 'bg-gray-100 text-gray-600';

    const expiryLabel = subscription.expires_at
        ? dateFormatter.format(new Date(subscription.expires_at))
        : null;

    return (
        <div className={`rounded-xl border p-5 mb-8 ${isExpiringSoon ? 'border-amber-300 bg-amber-50' : 'border-gray-200 bg-white'}`}>
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                {/* Plan info */}
                <div className="flex items-center gap-3">
                    <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide ${badgeStyle}`}>
                        {subscription.plan_name ?? 'No Plan'}
                    </span>
                    <div>
                        <p className="text-sm font-medium text-gray-900">Current Plan</p>
                        {expiryLabel && (
                            <p className={`text-xs mt-0.5 ${isExpiringSoon ? 'text-amber-700 font-semibold' : 'text-gray-500'}`}>
                                {isExpiringSoon
                                    ? `Expires in ${subscription.days_remaining} day${subscription.days_remaining === 1 ? '' : 's'} — ${expiryLabel}`
                                    : `Valid until ${expiryLabel}`}
                            </p>
                        )}
                        {isFree && (
                            <p className="text-xs text-gray-500 mt-0.5">Free plan — no expiry</p>
                        )}
                    </div>
                </div>

                {/* Product usage */}
                <div className="flex items-center gap-6">
                    {subscription.product_limit !== null && (
                        <div className="text-center">
                            <p className="text-xs text-gray-500 mb-1">Products used</p>
                            <p className={`text-sm font-semibold ${atProductLimit ? 'text-red-600' : 'text-gray-900'}`}>
                                {activeProducts} / {subscription.product_limit}
                            </p>
                            <div className="w-24 h-1.5 bg-gray-200 rounded-full mt-1 overflow-hidden">
                                <div
                                    className={`h-full rounded-full transition-all ${atProductLimit ? 'bg-red-500' : 'bg-primary-olive'}`}
                                    style={{ width: `${Math.min(100, (activeProducts / subscription.product_limit) * 100)}%` }}
                                />
                            </div>
                        </div>
                    )}

                    {/* CTA */}
                    <Link
                        href="/vendor/subscription"
                        className={`inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap ${
                            isFree || atProductLimit
                                ? 'bg-primary-olive text-white hover:bg-primary-olive/90'
                                : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                        }`}
                    >
                        {isFree || atProductLimit ? 'Upgrade Plan' : 'Manage Plan'}
                    </Link>
                </div>
            </div>

            {/* Alerts */}
            {isExpiringSoon && (
                <p className="mt-3 text-xs text-amber-700 border-t border-amber-200 pt-3">
                    Your plan expires soon. Renew now to avoid service interruption.
                </p>
            )}
            {!isExpiringSoon && isFree && (
                <p className="mt-3 text-xs text-gray-500 border-t border-gray-100 pt-3">
                    Upgrade to a paid plan for more products, higher search priority, and premium features.
                </p>
            )}
            {atProductLimit && !isFree && (
                <p className="mt-3 text-xs text-red-600 border-t border-red-100 pt-3">
                    You have reached your product limit. Upgrade your plan to add more products.
                </p>
            )}
        </div>
    );
}

export default function Analytics(props: AnalyticsData) {
    const { data, loading, period, changePeriod } = useAnalytics(props);
    const { overview, chatContacts, profileViews, topProducts, subscription } = data;

    return (
        <>
            <Head title="Analytics" />
            <VendorSidebar />

            <main className="ml-[100px] min-h-screen bg-gray-50">
                <div className="max-w-6xl mx-auto px-6 py-8">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Analytics</h1>
                            <p className="text-sm text-gray-500 mt-1">
                                {overview.period.start_date} &mdash; {overview.period.end_date}
                            </p>
                        </div>

                        {/* Period Selector */}
                        <div className="flex bg-white rounded-lg border border-gray-200 p-1">
                            {periods.map((p) => (
                                <button
                                    key={p.value}
                                    onClick={() => changePeriod(p.value)}
                                    disabled={loading}
                                    className={`px-4 py-2 text-sm rounded-md transition-colors ${
                                        period === p.value
                                            ? 'bg-primary-olive text-white'
                                            : 'text-gray-600 hover:bg-gray-100'
                                    } ${loading ? 'opacity-50 cursor-not-allowed' : ''}`}
                                >
                                    {p.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Subscription Status Card */}
                    <SubscriptionStatusCard subscription={subscription} activeProducts={overview.active_products} />

                    {/* Loading overlay */}
                    <div className={`transition-opacity ${loading ? 'opacity-50' : 'opacity-100'}`}>
                        {/* Stat Cards */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                            <StatCard
                                label="Profile Views"
                                value={overview.profile_views}
                                icon={
                                    <svg className="w-6 h-6 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                }
                            />
                            <StatCard
                                label="Product Views"
                                value={overview.product_views}
                                icon={
                                    <svg className="w-6 h-6 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                }
                            />
                            <StatCard
                                label="Chat Contacts"
                                value={overview.chat_contacts}
                                icon={
                                    <svg className="w-6 h-6 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                }
                            />
                            <StatCard
                                label="Avg. View Value"
                                value={formatCurrency(overview.average_view_value)}
                                icon={
                                    <svg className="w-6 h-6 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                }
                            />
                            <StatCard
                                label="Active Products"
                                value={overview.active_products}
                                icon={
                                    <svg className="w-6 h-6 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    </svg>
                                }
                            />
                            <StatCard
                                label="Followers"
                                value={overview.followers_count}
                                icon={
                                    <svg className="w-6 h-6 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                }
                            />
                        </div>

                        {/* Charts */}
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                            <ViewsChart
                                data={profileViews.daily}
                                title="Profile Views"
                                color="#6B7B3A"
                            />
                            <ViewsChart
                                data={chatContacts.daily}
                                title="Chat Contacts"
                                color="#D4956A"
                            />
                        </div>

                        {/* Top Products Table */}
                        <div className="bg-white rounded-xl border border-gray-200 p-5">
                            <h3 className="text-sm font-semibold text-gray-700 mb-4">Top Viewed Products</h3>
                            {topProducts.products.length === 0 ? (
                                <p className="text-gray-400 text-sm py-8 text-center">No product views yet</p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-gray-100">
                                                <th className="text-left py-3 px-2 text-gray-500 font-medium">#</th>
                                                <th className="text-left py-3 px-2 text-gray-500 font-medium">Product</th>
                                                <th className="text-right py-3 px-2 text-gray-500 font-medium">Price</th>
                                                <th className="text-right py-3 px-2 text-gray-500 font-medium">Views</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {topProducts.products.map((product, idx) => (
                                                <tr key={product.product_id} className="border-b border-gray-50 last:border-0">
                                                    <td className="py-3 px-2 text-gray-400">{idx + 1}</td>
                                                    <td className="py-3 px-2 font-medium text-gray-900">{product.name}</td>
                                                    <td className="py-3 px-2 text-right text-gray-600">
                                                        {formatCurrency(product.price)}
                                                    </td>
                                                    <td className="py-3 px-2 text-right">
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-olive/10 text-primary-olive">
                                                            {product.views_count}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </main>
        </>
    );
}
