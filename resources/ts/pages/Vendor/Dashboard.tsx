import { Head, Link, router } from '@inertiajs/react';
import VendorSidebar from '@/components/VendorSidebar';
import { AlertIcon, BoltIcon, EyeIcon, UsersIcon } from '@/components/icons';
import { Referral } from '@/types';
import ReferralCard from './partials/ReferralCard';

const naira = (n: number) => `₦${n.toLocaleString('en-US')}`;
const coins = (n: number) => n.toLocaleString('en-US');

interface Props {
    vendor: {
        name: string;
        business_name: string;
    };
    referral: Referral;
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
    coins: {
        daily_coin_cap: number | null;
        charged_today: number;
        coin_naira_value: number;
    };
}

export default function VendorDashboard({ vendor, referral, subscription, stats, coins: wallet }: Props) {
    const isExpiringSoon = subscription.days_remaining !== null && subscription.days_remaining <= 7 && !subscription.is_expired;
    const referralCount = referral.count;

    const cap = wallet.daily_coin_cap;
    const capHit = cap !== null && wallet.charged_today >= cap;
    const remaining = cap !== null ? Math.max(0, cap - wallet.charged_today) : null;

    const raiseCap = () => {
        if (cap === null) return;
        router.post('/vendor/coin-cap', { daily_coin_cap: cap * 2 }, { preserveScroll: true });
    };

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
                            <AlertIcon className="w-4 h-4 flex-shrink-0" />
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
                                <EyeIcon className="w-4 h-4 text-gray-400" />
                            </div>
                            <p className="text-xs text-gray-400">This week</p>
                        </div>

                        {/* Referrals */}
                        <div className="bg-white rounded-2xl p-5 shadow-sm">
                            <p className="text-xs text-gray-500 mb-3">Referrals</p>
                            <div className="flex items-center gap-2 mb-1">
                                <p className="text-3xl font-bold text-gray-900">{referralCount}</p>
                                <UsersIcon className="w-4 h-4 text-gray-400" />
                            </div>
                            <p className="text-xs text-gray-400">
                                {referralCount === 0
                                    ? 'Share your referral code to start'
                                    : `${referralCount === 1 ? 'Business' : 'Businesses'} joined`}
                            </p>
                        </div>

                    </div>

                    {/* Coin spend today */}
                    <div className="mb-8 bg-white rounded-2xl p-5 shadow-sm">
                        <div className="flex flex-wrap items-baseline justify-between gap-2">
                            <p className="text-xs text-gray-500">Coin spend today</p>
                            <p className="text-xs text-gray-400">
                                {cap !== null ? `Cap: ${coins(cap)} coins/day (${naira(cap * wallet.coin_naira_value)})` : 'No daily cap'}
                            </p>
                        </div>

                        <p className="mt-2 text-3xl font-bold text-gray-900">
                            {coins(wallet.charged_today)} <span className="text-base font-medium text-gray-400">coins</span>
                        </p>
                        <p className="text-xs text-gray-400">{naira(wallet.charged_today * wallet.coin_naira_value)} spent on leads today</p>

                        {cap !== null && (
                            <>
                                <div className="mt-4 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                    <div
                                        className={`h-full rounded-full ${capHit ? 'bg-red-500' : 'bg-brand-green'}`}
                                        style={{ width: `${Math.min(100, (wallet.charged_today / cap) * 100)}%` }}
                                    />
                                </div>
                                {!capHit && (
                                    <p className="mt-2 text-xs text-gray-500">{coins(remaining ?? 0)} coins left before you hit today&apos;s cap.</p>
                                )}
                            </>
                        )}

                        {capHit && (
                            <div className="mt-4 rounded-xl border border-red-200 bg-red-50 p-4">
                                <p className="text-sm font-medium text-red-900">
                                    You&apos;ve hit your daily cap. New leads are still delivered to buyers, but you&apos;re not being charged for them today — so you can&apos;t claim them as paid leads until you raise the cap.
                                </p>
                                <div className="mt-3 flex flex-wrap items-center gap-3">
                                    <button
                                        type="button"
                                        onClick={raiseCap}
                                        className="inline-flex h-9 items-center justify-center rounded-lg bg-red-600 px-4 text-sm font-bold text-white transition hover:bg-red-700"
                                    >
                                        Raise cap to {coins(cap * 2)} coins
                                    </button>
                                    <Link href="/vendor/settings" className="text-sm font-semibold text-red-800 hover:underline">
                                        Adjust in settings
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Refer & win */}
                    <ReferralCard referral={referral} businessName={vendor.business_name} />

                    {/* Recent Activity */}
                    <div className="bg-white rounded-2xl p-6 shadow-sm">
                        <h2 className="text-base font-semibold text-gray-900 mb-1">Recent Activity</h2>
                        <p className="text-xs text-gray-500 mb-6">Latest interactions with your business</p>
                        <div className="flex flex-col items-center justify-center py-10 text-center">
                            <BoltIcon className="w-12 h-12 text-gray-200 mb-3" strokeWidth={1.5} />
                            <p className="text-sm text-gray-500">No activity yet. Start adding products to get discovered!</p>
                        </div>
                    </div>

                </div>
            </main>
        </>
    );
}
