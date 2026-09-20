import { Head, Link } from '@inertiajs/react';
import VendorSidebar from '@/components/VendorSidebar';
import ViewsChart from '@/components/Charts/ViewsChart';
import { VendorWalletProps } from '@/types';

const TYPE_LABEL: Record<string, string> = {
    purchase: 'Coin purchase',
    bonus: 'Bonus coins',
    debit: 'Lead charge',
    credit: 'Lead credit',
    expiry: 'Coins expired',
    promo: 'Promo coins',
};

const coins = (n: number) => n.toLocaleString('en-US');

export default function Wallet({
    vendor,
    balance,
    lead_rate,
    leads_affordable,
    category,
    low_balance,
    unbilled_out_of_coins,
    spent_series,
    ledger,
    top_up_link,
}: VendorWalletProps) {
    return (
        <>
            <Head title="Wallet" />
            <VendorSidebar />

            <main className="md:ml-[260px] min-h-screen bg-brand-surface pb-20 md:pb-0">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 py-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-extrabold tracking-tight text-brand-ink">Wallet</h1>
                        <p className="mt-1 text-sm text-brand-muted">
                            What you have, what a lead costs you, and every coin in and out.
                        </p>
                    </div>

                    {low_balance && (
                        <div
                            className={`mb-6 flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between ${
                                unbilled_out_of_coins > 0 ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50'
                            }`}
                        >
                            <p className={`text-sm font-medium ${unbilled_out_of_coins > 0 ? 'text-red-900' : 'text-amber-900'}`}>
                                {unbilled_out_of_coins > 0
                                    ? `You have already missed ${coins(unbilled_out_of_coins)} lead${unbilled_out_of_coins === 1 ? '' : 's'} while out of coins — those buyers reached ${vendor.business_name} but you could not follow up as a paid lead. Top up to stop missing them.`
                                    : 'Your balance is running low. Top up so you never miss a paid lead.'}
                            </p>
                            <a
                                href={top_up_link}
                                className={`inline-flex h-10 shrink-0 items-center justify-center rounded-lg px-4 text-sm font-bold text-white transition ${
                                    unbilled_out_of_coins > 0 ? 'bg-red-600 hover:bg-red-700' : 'bg-brand-green hover:bg-brand-green-dark'
                                }`}
                            >
                                Top up coins
                            </a>
                        </div>
                    )}

                    <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div className="rounded-xl border border-brand-line bg-white p-5">
                            <p className="text-xs font-semibold uppercase tracking-wide text-brand-muted">Balance</p>
                            <p className="mt-2 text-3xl font-extrabold text-brand-ink">{coins(balance)}</p>
                            <p className="mt-1 text-sm text-brand-muted">coins</p>
                        </div>
                        <div className="rounded-xl border border-brand-line bg-white p-5">
                            <p className="text-xs font-semibold uppercase tracking-wide text-brand-muted">Buys you</p>
                            <p className="mt-2 text-3xl font-extrabold text-brand-ink">{coins(leads_affordable)}</p>
                            <p className="mt-1 text-sm text-brand-muted">leads at your rate</p>
                        </div>
                        <div className="rounded-xl border border-brand-line bg-white p-5">
                            <p className="text-xs font-semibold uppercase tracking-wide text-brand-muted">Cost per lead</p>
                            <p className="mt-2 text-3xl font-extrabold text-brand-ink">{coins(lead_rate)}</p>
                            <p className="mt-1 text-sm text-brand-muted">
                                {category ? `${category.name} rate` : 'default rate — no category set'}
                            </p>
                        </div>
                    </div>

                    <div className="mb-6">
                        <ViewsChart data={spent_series} title="Coins spent on leads (last 30 days)" color="#25D366" />
                    </div>

                    <div className="overflow-hidden rounded-xl border border-brand-line bg-white">
                        <div className="border-b border-brand-line px-5 py-4">
                            <h2 className="text-sm font-semibold text-brand-ink">Coin history</h2>
                        </div>

                        {ledger.data.length === 0 ? (
                            <p className="px-5 py-10 text-center text-sm text-brand-muted">No coin activity yet.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-brand-surface text-xs uppercase tracking-wide text-brand-muted">
                                        <tr>
                                            <th className="px-5 py-3 font-semibold">Date</th>
                                            <th className="px-5 py-3 font-semibold">Activity</th>
                                            <th className="px-5 py-3 text-right font-semibold">Coins</th>
                                            <th className="px-5 py-3 text-right font-semibold">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-brand-line">
                                        {ledger.data.map((entry) => (
                                            <tr key={entry.id}>
                                                <td className="whitespace-nowrap px-5 py-3 text-brand-muted">
                                                    {new Date(entry.date).toLocaleDateString('en-US', {
                                                        year: 'numeric',
                                                        month: 'short',
                                                        day: 'numeric',
                                                    })}
                                                </td>
                                                <td className="px-5 py-3 font-medium text-brand-ink">
                                                    {TYPE_LABEL[entry.type] ?? entry.type}
                                                </td>
                                                <td
                                                    className={`whitespace-nowrap px-5 py-3 text-right font-semibold ${
                                                        entry.amount >= 0 ? 'text-brand-green-dark' : 'text-red-600'
                                                    }`}
                                                >
                                                    {entry.amount > 0 ? '+' : ''}
                                                    {coins(entry.amount)}
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-3 text-right text-brand-ink">
                                                    {coins(entry.balance_after)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>

                    {ledger.last_page > 1 && (
                        <div className="mt-6 flex flex-wrap justify-center gap-2">
                            {Array.from({ length: ledger.last_page }, (_, i) => i + 1).map((page) => (
                                <Link
                                    key={page}
                                    href={`/vendor/wallet?page=${page}`}
                                    preserveScroll
                                    className={`flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium transition-colors ${
                                        page === ledger.current_page
                                            ? 'bg-brand-green text-white'
                                            : 'border border-brand-line bg-white text-brand-ink hover:bg-brand-surface'
                                    }`}
                                >
                                    {page}
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </main>
        </>
    );
}
