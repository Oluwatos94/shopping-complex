import { Head, Link } from '@inertiajs/react';
import { BarChart, Bar, XAxis, YAxis, ResponsiveContainer, Tooltip, Cell } from 'recharts';
import AdminLayout from '@/components/Admin/AdminLayout';

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

const OLIVE = '#86885e';
const PEACH = '#d49f89';
const LIGHT = '#cacfca';
const BROWN = '#523026';

function KpiCard({
    label,
    value,
    subtitle,
    trend,
    trendLabel,
    icon,
}: {
    label: string;
    value: string | number;
    subtitle?: string;
    trend?: 'up' | 'down' | 'neutral';
    trendLabel?: string;
    icon: React.ReactNode;
}) {
    return (
        <div className="bg-white p-6 rounded-xl border border-gray-100 flex flex-col justify-between h-44 hover:bg-gray-50/50 transition-colors">
            <div className="flex justify-between items-start">
                <span className="text-[10px] font-bold uppercase tracking-[0.1em] text-gray-500">{label}</span>
                <span className="text-primary-olive">{icon}</span>
            </div>
            <div>
                <h3 className="text-3xl font-bold text-gray-900 tracking-tight">{value}</h3>
                {(trend || subtitle) && (
                    <div className="flex items-center gap-2 mt-1.5">
                        {trend && trendLabel && (
                            <span
                                className={`text-xs font-bold px-1.5 py-0.5 rounded flex items-center gap-0.5 ${
                                    trend === 'up'
                                        ? 'text-emerald-600 bg-emerald-50'
                                        : trend === 'down'
                                        ? 'text-red-600 bg-red-50'
                                        : 'text-gray-500 bg-gray-100'
                                }`}
                            >
                                {trend === 'up' && (
                                    <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 15l7-7 7 7" />
                                    </svg>
                                )}
                                {trend === 'down' && (
                                    <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M19 9l-7 7-7-7" />
                                    </svg>
                                )}
                                {trendLabel}
                            </span>
                        )}
                        {subtitle && <span className="text-[10px] text-gray-400">{subtitle}</span>}
                    </div>
                )}
            </div>
        </div>
    );
}

function DonutChart({
    segments,
    total,
}: {
    segments: { label: string; value: number; pct: number; color: string }[];
    total: number;
}) {
    let offset = 0;
    return (
        <div>
            <div className="relative w-44 h-44 mx-auto mb-6">
                <svg className="w-full h-full -rotate-90" viewBox="0 0 36 36">
                    <path
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        fill="none"
                        stroke="#f3f4f6"
                        strokeWidth="3"
                    />
                    {segments.map((seg) => {
                        const dashOffset = -offset;
                        offset += seg.pct;
                        return (
                            <path
                                key={seg.label}
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke={seg.color}
                                strokeWidth="3"
                                strokeDasharray={`${seg.pct} 100`}
                                strokeDashoffset={dashOffset}
                                strokeLinecap="round"
                            />
                        );
                    })}
                </svg>
                <div className="absolute inset-0 flex flex-col items-center justify-center">
                    <span className="text-2xl font-bold text-gray-900">{total.toLocaleString()}</span>
                    <span className="text-[10px] uppercase tracking-widest text-gray-400">Total</span>
                </div>
            </div>
            <div className="space-y-2.5">
                {segments.map((seg) => (
                    <div key={seg.label} className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <span className="w-3 h-3 rounded-full flex-shrink-0" style={{ backgroundColor: seg.color }} />
                            <span className="text-sm text-gray-600">{seg.label}</span>
                        </div>
                        <div className="text-right">
                            <span className="text-sm font-bold text-gray-900">{seg.pct}%</span>
                            <span className="text-[10px] text-gray-400 ml-1">({seg.value.toLocaleString()})</span>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

const CustomTooltip = ({ active, payload, label }: { active?: boolean; payload?: { value: number }[]; label?: string }) => {
    if (active && payload?.length) {
        return (
            <div className="bg-white border border-gray-200 rounded-lg px-3 py-2 shadow-lg text-sm">
                <p className="text-gray-500 text-xs mb-0.5">{label}</p>
                <p className="font-semibold text-gray-900">{payload[0].value.toLocaleString()}</p>
            </div>
        );
    }
    return null;
};

export default function Dashboard({ users, products, vendors, botStats }: Props) {
    const formatCurrency = (value: number) =>
        new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 0 }).format(value);

    const safePercent = (part: number, total: number) =>
        total > 0 ? Math.round((part / total) * 100) : 0;

    const botChartData = [
        { name: 'Total Searches', value: botStats.total_searches },
        { name: 'This Month', value: botStats.searches_this_month },
        { name: 'Connections', value: botStats.total_contacts_made },
        { name: 'Monthly Contacts', value: botStats.contacts_this_month },
        { name: 'No Results', value: botStats.total_no_results },
        { name: 'Active Vendors', value: botStats.active_subscribed_vendors },
    ];

    const barColors = [OLIVE, OLIVE, PEACH, PEACH, BROWN, LIGHT];

    const userSegments = [
        { label: 'Customers', value: users.customers, pct: safePercent(users.customers, users.total), color: OLIVE },
        { label: 'Vendors', value: users.vendors, pct: safePercent(users.vendors, users.total), color: PEACH },
        { label: 'Admins', value: users.admins, pct: safePercent(users.admins, users.total), color: LIGHT },
    ];

    const vendorRows = [
        { label: 'Approved', value: vendors.approved, badge: 'bg-emerald-50 text-emerald-700', badgeText: 'Active' },
        { label: 'Pending Review', value: vendors.pending_review, badge: 'bg-amber-50 text-amber-700', badgeText: 'Pending' },
        { label: 'Rejected', value: vendors.rejected, badge: 'bg-red-50 text-red-700', badgeText: 'Rejected' },
        { label: 'Draft', value: vendors.draft, badge: 'bg-gray-100 text-gray-600', badgeText: 'Draft' },
    ];

    const botActivityItems = [
        {
            label: 'Total Bot Searches',
            value: botStats.total_searches.toLocaleString(),
            sub: `${botStats.searches_this_month.toLocaleString()} this month`,
            color: 'bg-primary-olive',
        },
        {
            label: 'Connections Made',
            value: botStats.total_contacts_made.toLocaleString(),
            sub: `${botStats.contacts_this_month.toLocaleString()} this month`,
            color: 'bg-primary-peach',
        },
        {
            label: 'Searches With No Results',
            value: botStats.total_no_results.toLocaleString(),
            sub: 'Vendors not found',
            color: 'bg-primary-brown',
        },
        {
            label: 'Active Subscribed Vendors',
            value: botStats.active_subscribed_vendors.toLocaleString(),
            sub: 'On WhatsApp Bot',
            color: 'bg-primary-light',
        },
    ];

    return (
        <>
            <Head title="Admin Dashboard" />
            <AdminLayout>
                {/* Page Header */}
                <div className="mb-10">
                    <h2 className="text-3xl font-bold tracking-tight text-gray-900">Analytics Intelligence</h2>
                    <p className="text-gray-500 mt-1">Platform performance overview for the current billing cycle.</p>
                </div>

                {/* KPI Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
                    <KpiCard
                        label="Monthly Revenue"
                        value={formatCurrency(botStats.monthly_revenue)}
                        trend="up"
                        trendLabel="Active subscriptions"
                        icon={
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        }
                    />
                    <KpiCard
                        label="Total Users"
                        value={users.total.toLocaleString()}
                        subtitle="registered accounts"
                        icon={
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        }
                    />
                    <KpiCard
                        label="Total Products"
                        value={products.total.toLocaleString()}
                        subtitle="across all vendors"
                        icon={
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        }
                    />
                    <KpiCard
                        label="Active Vendors"
                        value={vendors.approved.toLocaleString()}
                        trend={vendors.pending_review > 0 ? 'neutral' : 'up'}
                        trendLabel={vendors.pending_review > 0 ? `${vendors.pending_review} pending` : 'all clear'}
                        icon={
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                        }
                    />
                </div>

                {/* Charts Row */}
                <div className="grid grid-cols-12 gap-5 mb-8">
                    {/* Bot Activity Bar Chart */}
                    <div className="col-span-12 lg:col-span-8 bg-white p-8 rounded-xl border border-gray-100">
                        <div className="flex justify-between items-end mb-8">
                            <div>
                                <h4 className="text-lg font-bold text-gray-900">Bot Activity Overview</h4>
                                <p className="text-sm text-gray-500 mt-0.5">WhatsApp bot performance metrics</p>
                            </div>
                            <Link
                                href="/admin/bot-monitor"
                                className="text-xs font-bold uppercase tracking-widest text-primary-olive hover:underline"
                            >
                                View Monitor
                            </Link>
                        </div>
                        <ResponsiveContainer width="100%" height={240}>
                            <BarChart data={botChartData} barSize={32}>
                                <XAxis
                                    dataKey="name"
                                    tick={{ fontSize: 10, fill: '#9ca3af' }}
                                    tickLine={false}
                                    axisLine={false}
                                />
                                <YAxis
                                    tick={{ fontSize: 10, fill: '#9ca3af' }}
                                    tickLine={false}
                                    axisLine={false}
                                    allowDecimals={false}
                                />
                                <Tooltip content={<CustomTooltip />} cursor={{ fill: '#f9fafb' }} />
                                <Bar dataKey="value" radius={[4, 4, 0, 0]}>
                                    {botChartData.map((_, index) => (
                                        <Cell key={index} fill={barColors[index % barColors.length]} />
                                    ))}
                                </Bar>
                            </BarChart>
                        </ResponsiveContainer>
                    </div>

                    {/* User Breakdown Donut */}
                    <div className="col-span-12 lg:col-span-4 bg-white p-8 rounded-xl border border-gray-100">
                        <h4 className="text-lg font-bold text-gray-900 mb-1">User Breakdown</h4>
                        <p className="text-sm text-gray-500 mb-8">Role segmentation</p>
                        <DonutChart segments={userSegments} total={users.total} />
                    </div>
                </div>

                {/* Bottom Row */}
                <div className="grid grid-cols-12 gap-5">
                    {/* Vendor Status Table */}
                    <div className="col-span-12 lg:col-span-7 bg-white p-8 rounded-xl border border-gray-100">
                        <div className="flex justify-between items-center mb-8">
                            <h4 className="text-lg font-bold text-gray-900">Vendor Status Overview</h4>
                            <Link
                                href="/admin/vendors/pending"
                                className="text-xs font-bold uppercase tracking-widest text-primary-olive hover:underline"
                            >
                                Review Pending
                            </Link>
                        </div>
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-gray-100">
                                    <th className="text-left text-[10px] font-bold uppercase tracking-widest text-gray-400 pb-4">
                                        Status
                                    </th>
                                    <th className="text-right text-[10px] font-bold uppercase tracking-widest text-gray-400 pb-4">
                                        Count
                                    </th>
                                    <th className="text-right text-[10px] font-bold uppercase tracking-widest text-gray-400 pb-4">
                                        Share
                                    </th>
                                    <th className="text-right text-[10px] font-bold uppercase tracking-widest text-gray-400 pb-4">
                                        Label
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {vendorRows.map((row) => {
                                    const total = vendors.approved + vendors.pending_review + vendors.rejected + vendors.draft;
                                    const share = total > 0 ? Math.round((row.value / total) * 100) : 0;
                                    return (
                                        <tr key={row.label} className="hover:bg-gray-50/60 transition-colors">
                                            <td className="py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-9 h-9 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center">
                                                        <svg className="w-4 h-4 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                                        </svg>
                                                    </div>
                                                    <p className="text-sm font-semibold text-gray-800">{row.label}</p>
                                                </div>
                                            </td>
                                            <td className="text-right py-4 text-sm font-medium text-gray-700">
                                                {row.value.toLocaleString()}
                                            </td>
                                            <td className="text-right py-4 text-sm text-gray-400">{share}%</td>
                                            <td className="text-right py-4">
                                                <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${row.badge}`}>
                                                    {row.badgeText}
                                                </span>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>

                        {/* Quick Links */}
                        <div className="flex flex-wrap gap-2 mt-6 pt-6 border-t border-gray-100">
                            <Link
                                href="/admin/vendors/pending"
                                className="inline-flex items-center px-3 py-2 rounded-lg bg-gray-50 border border-gray-200 text-xs font-medium text-gray-700 hover:bg-gray-100 transition-colors"
                            >
                                Pending Applications
                                {vendors.pending_review > 0 && (
                                    <span className="ml-1.5 w-5 h-5 rounded-full bg-primary-olive text-white text-[10px] font-bold flex items-center justify-center">
                                        {vendors.pending_review > 99 ? '99+' : vendors.pending_review}
                                    </span>
                                )}
                            </Link>
                            <Link
                                href="/admin/users"
                                className="inline-flex items-center px-3 py-2 rounded-lg bg-gray-50 border border-gray-200 text-xs font-medium text-gray-700 hover:bg-gray-100 transition-colors"
                            >
                                All Users
                            </Link>
                            <Link
                                href="/admin/bot-monitor"
                                className="inline-flex items-center px-3 py-2 rounded-lg bg-gray-50 border border-gray-200 text-xs font-medium text-gray-700 hover:bg-gray-100 transition-colors"
                            >
                                Bot Monitor
                            </Link>
                        </div>
                    </div>

                    {/* Bot Activity Feed */}
                    <div className="col-span-12 lg:col-span-5 bg-white p-8 rounded-xl border border-gray-100">
                        <div className="flex justify-between items-center mb-8">
                            <h4 className="text-lg font-bold text-gray-900">Bot Highlights</h4>
                            <span className="text-[10px] font-bold text-gray-400 uppercase bg-gray-100 px-2 py-1 rounded-md">
                                WhatsApp
                            </span>
                        </div>
                        <div className="space-y-5 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-px before:bg-gray-100">
                            {botActivityItems.map((item) => (
                                <div key={item.label} className="relative flex gap-4 pl-8">
                                    <div className={`absolute left-0 top-1 w-6 h-6 rounded-full ${item.color} border-4 border-white`} />
                                    <div>
                                        <p className="text-sm font-semibold text-gray-900">{item.value}</p>
                                        <p className="text-xs text-gray-500">{item.label}</p>
                                        <p className="text-[10px] text-gray-400 mt-0.5 uppercase tracking-tight">{item.sub}</p>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="mt-8 pt-6 border-t border-gray-100">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-xs font-semibold text-gray-600 uppercase tracking-widest">
                                    Monthly Revenue
                                </span>
                                <span className="text-sm font-bold text-gray-900">
                                    {formatCurrency(botStats.monthly_revenue)}
                                </span>
                            </div>
                            <div className="w-full bg-gray-100 rounded-full h-2">
                                <div
                                    className="bg-primary-olive h-2 rounded-full transition-all"
                                    style={{
                                        width: `${Math.min(100, botStats.active_subscribed_vendors > 0 ? 70 : 10)}%`,
                                    }}
                                />
                            </div>
                            <p className="text-[10px] text-gray-400 mt-1">
                                From {botStats.active_subscribed_vendors.toLocaleString()} active subscriptions
                            </p>
                        </div>
                    </div>
                </div>
            </AdminLayout>
        </>
    );
}
