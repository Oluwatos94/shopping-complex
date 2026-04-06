import { Head, Link } from '@inertiajs/react';

interface Props {
    users: { total: number; admins: number; vendors: number; customers: number };
    products: { total: number };
    vendors: { approved: number; pending_review: number; rejected: number; draft: number };
    botStats: {
        total_searches: number;
        total_contacts_made: number;
        total_no_results: number;
        searches_this_month: number;
        contacts_this_month: number;
        active_subscribed_vendors: number;
        monthly_revenue: number;
    };
}

function StatCard({ label, value, subtitle }: { label: string; value: string | number; subtitle?: string }) {
    return (
        <div className="bg-white rounded-xl border border-gray-200 p-5">
            <p className="text-sm text-gray-500">{label}</p>
            <p className="text-2xl font-bold text-gray-900 mt-1">{value}</p>
            {subtitle && <p className="text-xs text-gray-400 mt-1">{subtitle}</p>}
        </div>
    );
}

function Badge({ count }: { count: number }) {
    if (count === 0) return null;
    return (
        <span className="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-xs font-bold ml-2">
            {count > 99 ? '99+' : count}
        </span>
    );
}

export default function Dashboard({ users, products, vendors, botStats }: Props) {
    const formatCurrency = (value: number) =>
        new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 0 }).format(value);

    return (
        <>
            <Head title="Admin Dashboard" />

            <div className="min-h-screen bg-gray-50">
                <div className="max-w-6xl mx-auto px-6 py-8">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
                        <p className="text-sm text-gray-500 mt-1">Platform overview and bot health</p>
                    </div>

                    {/* Users & Vendors */}
                    <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Platform</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <StatCard label="Total Users" value={users.total} />
                        <StatCard label="Approved Vendors" value={vendors.approved} />
                        <StatCard
                            label="Pending Applications"
                            value={vendors.pending_review}
                            subtitle="Awaiting review"
                        />
                        <StatCard label="Total Products" value={products.total} />
                    </div>

                    {/* WhatsApp Bot Stats */}
                    <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">WhatsApp Bot</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                        <StatCard
                            label="Total Searches"
                            value={botStats.total_searches.toLocaleString()}
                            subtitle={`${botStats.searches_this_month.toLocaleString()} this month`}
                        />
                        <StatCard
                            label="Connections Made"
                            value={botStats.total_contacts_made.toLocaleString()}
                            subtitle={`${botStats.contacts_this_month.toLocaleString()} this month`}
                        />
                        <StatCard
                            label="No Results"
                            value={botStats.total_no_results.toLocaleString()}
                            subtitle="Searches with no vendor found"
                        />
                        <StatCard
                            label="Active Subscribed Vendors"
                            value={botStats.active_subscribed_vendors.toLocaleString()}
                        />
                        <StatCard
                            label="Monthly Revenue"
                            value={formatCurrency(botStats.monthly_revenue)}
                            subtitle="Active subscriptions"
                        />
                    </div>

                    {/* Quick Links */}
                    <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Quick Links</h2>
                    <div className="flex flex-wrap gap-3">
                        <Link
                            href="/admin/vendors/pending"
                            className="inline-flex items-center px-4 py-2.5 rounded-lg bg-white border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            Pending Applications
                            <Badge count={vendors.pending_review} />
                        </Link>
                        <Link
                            href="/admin/bot-monitor"
                            className="inline-flex items-center px-4 py-2.5 rounded-lg bg-white border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            <svg className="w-4 h-4 mr-2 text-green-600" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.526 5.845L.057 23.882l6.198-1.625A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.68.965.982-3.59-.234-.369A9.818 9.818 0 1112 21.818z"/>
                            </svg>
                            Bot Monitor
                        </Link>
                        <Link
                            href="/admin/users"
                            className="inline-flex items-center px-4 py-2.5 rounded-lg bg-white border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            All Users
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}
