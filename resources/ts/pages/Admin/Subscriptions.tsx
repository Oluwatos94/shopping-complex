import { Head, router } from '@inertiajs/react';
import { Fragment, useState } from 'react';
import AdminLayout from '@/components/Admin/AdminLayout';
import { Paginated } from '@/types/product';
import { AdminSubscription } from '@/types';
import { formatDate } from '@/utils/date';
import { initials } from '@/utils/string';
import { SkeletonTable } from '@/components/Loading';

interface Props {
    subscriptions: Paginated<AdminSubscription>;
    stellarNetwork: string;
}

const METHOD_BADGE: Record<string, string> = {
    stellar: 'bg-primary-olive/10 text-primary-olive',
    // paystack: 'bg-gray-100 text-gray-600',
};

const METHOD_LABEL: Record<string, string> = {
    stellar: 'Direct payment',
    // paystack: 'Paystack',
};

const STATUS_BADGE: Record<string, string> = {
    active: 'text-emerald-600 bg-emerald-500',
    expired: 'text-amber-600 bg-amber-500',
    cancelled: 'text-gray-500 bg-gray-400',
};

const KIND_LABEL: Record<string, string> = {
    deposit: 'Initial deposit',
    mpp_charge: 'Auto-renew charge',
};

const naira = (n: number | null) => `₦${(n ?? 0).toLocaleString()}`;

const shortHash = (hash: string) => `${hash.slice(0, 8)}…${hash.slice(-6)}`;

export default function Subscriptions({ subscriptions, stellarNetwork }: Props) {
    const [activeMethod, setActiveMethod] = useState('');
    const [tableLoading, setTableLoading] = useState(false);
    const [expanded, setExpanded] = useState<number | null>(null);

    const explorerUrl = (hash: string) =>
        `https://stellar.expert/explorer/${stellarNetwork}/tx/${hash}`;

    const applyFilters = (overrides: { method?: string; page?: number } = {}) => {
        const params: Record<string, string | number> = {};
        const m = overrides.method !== undefined ? overrides.method : activeMethod;
        if (m) params.method = m;
        if (overrides.page) params.page = overrides.page;
        router.get('/admin/subscriptions', params, {
            preserveScroll: true,
            onStart: () => setTableLoading(true),
            onFinish: () => setTableLoading(false),
        });
    };

    const handleMethodFilter = (method: string) => {
        setActiveMethod(method);
        applyFilters({ method, page: 1 });
    };

    const goToPage = (page: number) => applyFilters({ page });

    const toggleExpand = (id: number) => setExpanded(expanded === id ? null : id);

    const methodTabs = [
        { label: 'All Payments', value: '' },
        { label: 'Direct payment', value: 'stellar' },
        // { label: 'Paystack', value: 'paystack' },
    ];

    return (
        <>
            <Head title="Payments — Admin" />
            <AdminLayout>
                {/* Page Header */}
                <div className="mb-10">
                    <h2 className="text-4xl font-extrabold tracking-tight text-gray-900 mb-2">
                        Payments
                    </h2>
                    <p className="text-gray-500 text-base">
                        Paid vendor subscriptions. Direct payments link to their on-chain proof on stellar.expert.
                    </p>
                </div>

                {/* Filters Bar */}
                <div className="flex flex-wrap items-center gap-4 mb-5 bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <div className="flex items-center gap-1">
                        {methodTabs.map((tab) => (
                            <button
                                key={tab.value}
                                onClick={() => handleMethodFilter(tab.value)}
                                className={`px-4 py-2 rounded-lg font-medium text-sm transition-colors ${
                                    activeMethod === tab.value
                                        ? 'bg-white text-primary-olive border border-primary-olive/20 shadow-sm font-bold'
                                        : 'text-gray-500 hover:text-gray-800'
                                }`}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Table */}
                <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    {tableLoading ? (
                        <SkeletonTable rows={8} cols={6} />
                    ) : subscriptions.data.length === 0 ? (
                        <div className="py-20 text-center">
                            <svg className="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <p className="text-gray-400 text-sm">No paid subscriptions found.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left">
                                <thead>
                                    <tr className="bg-gray-50/60 border-b border-gray-100">
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Vendor</th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Plan</th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Method</th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Amount</th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Expires</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {subscriptions.data.map((sub) => {
                                        const isStellar = sub.payment_method === 'stellar';
                                        const txs = sub.stellar_transactions ?? [];
                                        const isOpen = expanded === sub.id;

                                        return (
                                            <Fragment key={sub.id}>
                                                <tr
                                                    onClick={() => isStellar && toggleExpand(sub.id)}
                                                    className={`transition-colors ${
                                                        isStellar ? 'cursor-pointer hover:bg-primary-olive/[0.03]' : 'hover:bg-gray-50/40'
                                                    } ${isOpen ? 'bg-primary-olive/[0.03]' : ''}`}
                                                >
                                                    {/* Vendor */}
                                                    <td className="px-6 py-4">
                                                        <div className="flex items-center gap-3">
                                                            {isStellar && (
                                                                <svg
                                                                    className={`w-3.5 h-3.5 text-gray-400 flex-shrink-0 transition-transform ${isOpen ? 'rotate-90' : ''}`}
                                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                                >
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                                                </svg>
                                                            )}
                                                            <div className="w-10 h-10 rounded-full bg-primary-olive/10 flex items-center justify-center flex-shrink-0">
                                                                <span className="text-primary-olive text-xs font-bold">
                                                                    {initials(sub.vendor?.business_name || sub.vendor?.name || '—')}
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <p className="text-sm font-bold text-gray-900">
                                                                    {sub.vendor?.business_name || sub.vendor?.name || 'Unknown'}
                                                                </p>
                                                                <p className="text-xs text-gray-400">{sub.vendor?.email}</p>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    {/* Plan */}
                                                    <td className="px-6 py-4 text-sm font-medium text-gray-700">
                                                        {sub.plan?.name ?? '—'}
                                                    </td>

                                                    {/* Method */}
                                                    <td className="px-6 py-4">
                                                        <span className={`text-xs font-semibold px-2.5 py-1 rounded-full ${METHOD_BADGE[sub.payment_method] ?? 'bg-gray-100 text-gray-600'}`}>
                                                            {METHOD_LABEL[sub.payment_method] ?? sub.payment_method}
                                                        </span>
                                                    </td>

                                                    {/* Amount */}
                                                    <td className="px-6 py-4 text-sm font-bold text-gray-900 whitespace-nowrap">
                                                        {naira(sub.amount_paid)}
                                                    </td>

                                                    {/* Status */}
                                                    <td className="px-6 py-4">
                                                        <div className={`flex items-center text-xs font-bold capitalize ${(STATUS_BADGE[sub.status] ?? 'text-gray-500 bg-gray-400').split(' ')[0]}`}>
                                                            <span className={`w-1.5 h-1.5 rounded-full mr-2 ${(STATUS_BADGE[sub.status] ?? 'text-gray-500 bg-gray-400').split(' ')[1]}`} />
                                                            {sub.status}
                                                        </div>
                                                    </td>

                                                    {/* Expires */}
                                                    <td className="px-6 py-4 text-xs text-gray-400 font-medium whitespace-nowrap">
                                                        {sub.expires_at ? formatDate(sub.expires_at) : '—'}
                                                    </td>
                                                </tr>

                                                {/* On-chain history */}
                                                {isStellar && isOpen && (
                                                    <tr className="bg-gray-50/60">
                                                        <td colSpan={6} className="px-6 py-4">
                                                            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">
                                                                On-chain transactions ({stellarNetwork})
                                                            </p>
                                                            {txs.length === 0 ? (
                                                                <p className="text-xs text-gray-400">
                                                                    No settled on-chain transactions recorded yet.
                                                                </p>
                                                            ) : (
                                                                <div className="space-y-2">
                                                                    {txs.map((tx) => (
                                                                        <div
                                                                            key={tx.hash}
                                                                            className="flex flex-wrap items-center gap-x-4 gap-y-1 bg-white border border-gray-100 rounded-lg px-4 py-2.5"
                                                                        >
                                                                            <span className={`text-[11px] font-bold px-2 py-0.5 rounded-full ${tx.kind === 'deposit' ? 'bg-blue-50 text-blue-600' : 'bg-primary-olive/10 text-primary-olive'}`}>
                                                                                {KIND_LABEL[tx.kind] ?? tx.kind}
                                                                            </span>
                                                                            <span className="text-xs font-bold text-gray-700">{naira(tx.amount)}</span>
                                                                            {tx.billing_period && (
                                                                                <span className="text-xs text-gray-400">{tx.billing_period}</span>
                                                                            )}
                                                                            {tx.completed_at && (
                                                                                <span className="text-xs text-gray-400">{formatDate(tx.completed_at)}</span>
                                                                            )}
                                                                            <a
                                                                                href={explorerUrl(tx.hash)}
                                                                                target="_blank"
                                                                                rel="noopener noreferrer"
                                                                                onClick={(e) => e.stopPropagation()}
                                                                                className="ml-auto inline-flex items-center gap-1.5 text-xs font-bold text-primary-olive hover:underline"
                                                                            >
                                                                                <span className="font-mono">{shortHash(tx.hash)}</span>
                                                                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                                                </svg>
                                                                            </a>
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                            )}
                                                        </td>
                                                    </tr>
                                                )}
                                            </Fragment>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* Table Footer */}
                    <div className="flex items-center justify-between px-6 py-4 bg-gray-50/40 border-t border-gray-100">
                        <p className="text-xs text-gray-400 font-medium">
                            Showing{' '}
                            <span className="font-bold text-gray-700">{subscriptions.data.length}</span>{' '}
                            of{' '}
                            <span className="font-bold text-gray-700">{subscriptions.total.toLocaleString()}</span>{' '}
                            payments
                        </p>
                        {subscriptions.last_page > 1 && (
                            <div className="flex items-center gap-1">
                                <button
                                    onClick={() => goToPage(subscriptions.current_page - 1)}
                                    disabled={subscriptions.current_page === 1}
                                    className="p-2 text-gray-400 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <span className="px-3 text-xs font-bold text-gray-500">
                                    {subscriptions.current_page} / {subscriptions.last_page}
                                </span>
                                <button
                                    onClick={() => goToPage(subscriptions.current_page + 1)}
                                    disabled={subscriptions.current_page === subscriptions.last_page}
                                    className="p-2 text-gray-400 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </AdminLayout>
        </>
    );
}
