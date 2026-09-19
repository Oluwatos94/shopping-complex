import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/components/Admin/AdminLayout';
import { Paginated } from '@/types/product';
import { formatDate } from '@/utils/date';
import { initials } from '@/utils/string';
import { SkeletonTable } from '@/components/Loading';

interface AdminPayment {
    id: number;
    vendor: { name: string; email: string | null };
    pack: string;
    coins: number;
    amount: number;
    method: 'stellar' | 'paystack';
    tx_hash: string | null;
    paid_at: string | null;
}

interface Props {
    payments: Paginated<AdminPayment>;
    stellarNetwork: string;
}

const METHOD_BADGE: Record<string, string> = {
    stellar: 'bg-primary-olive/10 text-primary-olive',
    paystack: 'bg-gray-100 text-gray-600',
};

const METHOD_LABEL: Record<string, string> = {
    stellar: 'Direct payment',
    paystack: 'Paystack',
};

const naira = (n: number | null) => `₦${(n ?? 0).toLocaleString()}`;

const shortHash = (hash: string) => `${hash.slice(0, 8)}…${hash.slice(-6)}`;

export default function Subscriptions({ payments, stellarNetwork }: Props) {
    const [activeMethod, setActiveMethod] = useState('');
    const [tableLoading, setTableLoading] = useState(false);

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

    const methodTabs = [
        { label: 'All Payments', value: '' },
        { label: 'Direct payment', value: 'stellar' },
        { label: 'Paystack', value: 'paystack' },
    ];

    return (
        <>
            <Head title="Payments — Admin" />
            <AdminLayout>
                <div className="mb-10">
                    <h2 className="text-4xl font-extrabold tracking-tight text-gray-900 mb-2">Payments</h2>
                    <p className="text-gray-500 text-base">
                        Vendor coin-pack purchases. Direct payments link to their on-chain proof on stellar.expert.
                    </p>
                </div>

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

                <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    {tableLoading ? (
                        <SkeletonTable rows={8} cols={6} />
                    ) : payments.data.length === 0 ? (
                        <div className="py-20 text-center">
                            <svg className="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <p className="text-gray-400 text-sm">No coin-pack payments found.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left">
                                <thead>
                                    <tr className="bg-gray-50/60 border-b border-gray-100">
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Vendor</th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pack</th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Coins</th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Method</th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Amount</th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Date</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {payments.data.map((payment) => (
                                        <tr key={payment.id} className="transition-colors hover:bg-gray-50/40">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-full bg-primary-olive/10 flex items-center justify-center flex-shrink-0">
                                                        <span className="text-primary-olive text-xs font-bold">
                                                            {initials(payment.vendor.name)}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-bold text-gray-900">{payment.vendor.name}</p>
                                                        <p className="text-xs text-gray-400">{payment.vendor.email}</p>
                                                    </div>
                                                </div>
                                            </td>

                                            <td className="px-6 py-4 text-sm font-medium text-gray-700 capitalize">{payment.pack}</td>

                                            <td className="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                                {payment.coins.toLocaleString()}
                                            </td>

                                            <td className="px-6 py-4">
                                                <div className="flex flex-col gap-1">
                                                    <span className={`w-fit text-xs font-semibold px-2.5 py-1 rounded-full ${METHOD_BADGE[payment.method]}`}>
                                                        {METHOD_LABEL[payment.method]}
                                                    </span>
                                                    {payment.tx_hash && (
                                                        <a
                                                            href={explorerUrl(payment.tx_hash)}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="inline-flex items-center gap-1 text-[11px] font-bold text-primary-olive hover:underline"
                                                        >
                                                            <span className="font-mono">{shortHash(payment.tx_hash)}</span>
                                                            <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                            </svg>
                                                        </a>
                                                    )}
                                                </div>
                                            </td>

                                            <td className="px-6 py-4 text-sm font-bold text-gray-900 whitespace-nowrap">
                                                {naira(payment.amount)}
                                            </td>

                                            <td className="px-6 py-4 text-xs text-gray-400 font-medium whitespace-nowrap">
                                                {payment.paid_at ? formatDate(payment.paid_at) : '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <div className="flex items-center justify-between px-6 py-4 bg-gray-50/40 border-t border-gray-100">
                        <p className="text-xs text-gray-400 font-medium">
                            Showing <span className="font-bold text-gray-700">{payments.data.length}</span> of{' '}
                            <span className="font-bold text-gray-700">{payments.total.toLocaleString()}</span> payments
                        </p>
                        {payments.last_page > 1 && (
                            <div className="flex items-center gap-1">
                                <button
                                    onClick={() => goToPage(payments.current_page - 1)}
                                    disabled={payments.current_page === 1}
                                    className="p-2 text-gray-400 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <span className="px-3 text-xs font-bold text-gray-500">
                                    {payments.current_page} / {payments.last_page}
                                </span>
                                <button
                                    onClick={() => goToPage(payments.current_page + 1)}
                                    disabled={payments.current_page === payments.last_page}
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
