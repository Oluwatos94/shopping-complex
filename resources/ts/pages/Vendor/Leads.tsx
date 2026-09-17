import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import VendorSidebar from '@/components/VendorSidebar';
import { LeadHistoryProps } from '@/types';

const STATE_BADGE: Record<string, string> = {
    billed: 'bg-brand-green/10 text-brand-green-dark',
    credited: 'bg-blue-50 text-blue-700',
    unbilled: 'bg-amber-50 text-amber-700',
};

const STATE_LABEL: Record<string, string> = {
    billed: 'Billed',
    credited: 'Credited',
    unbilled: 'Unbilled',
};

const REASON_LABEL: Record<string, string> = {
    insufficient_balance: 'out of coins',
    daily_cap: 'daily cap',
    self_click: 'own click',
    velocity_buyer: 'flagged',
    velocity_ip: 'flagged',
};

export default function Leads({ leads, states, filters }: LeadHistoryProps) {
    const [state, setState] = useState(filters.state);
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    const query = () => {
        const params: Record<string, string> = {};
        if (state) params.state = state;
        if (from) params.from = from;
        if (to) params.to = to;
        return params;
    };

    const apply = () => router.get('/vendor/leads', query(), { preserveScroll: true });

    const exportHref = `/vendor/leads/export?${new URLSearchParams(query()).toString()}`;

    const hrefForPage = (page: number) => {
        const params = new URLSearchParams(query());
        params.set('page', String(page));
        return `/vendor/leads?${params.toString()}`;
    };

    return (
        <>
            <Head title="Leads" />
            <VendorSidebar />

            <main className="md:ml-[260px] min-h-screen bg-brand-surface pb-20 md:pb-0">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 py-8">
                    <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-extrabold tracking-tight text-brand-ink">Leads</h1>
                            <p className="mt-1 text-sm text-brand-muted">
                                Every buyer we introduced to you — what they wanted, and what it cost.
                            </p>
                        </div>
                        <a
                            href={exportHref}
                            className="inline-flex h-10 items-center justify-center rounded-lg border border-brand-line bg-white px-4 text-sm font-semibold text-brand-ink transition hover:bg-brand-surface"
                        >
                            Export CSV
                        </a>
                    </div>

                    <div className="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-brand-line bg-white p-4">
                        <label className="block">
                            <span className="text-xs font-medium text-brand-muted">State</span>
                            <select
                                value={state}
                                onChange={(e) => setState(e.target.value)}
                                className="mt-1 block rounded-lg border border-brand-line px-3 py-2 text-sm outline-none focus:border-brand-green"
                            >
                                <option value="">All</option>
                                {states.map((s) => (
                                    <option key={s} value={s}>
                                        {STATE_LABEL[s] ?? s.charAt(0).toUpperCase() + s.slice(1)}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="block">
                            <span className="text-xs font-medium text-brand-muted">From</span>
                            <input
                                type="date"
                                value={from}
                                onChange={(e) => setFrom(e.target.value)}
                                className="mt-1 block rounded-lg border border-brand-line px-3 py-2 text-sm outline-none focus:border-brand-green"
                            />
                        </label>
                        <label className="block">
                            <span className="text-xs font-medium text-brand-muted">To</span>
                            <input
                                type="date"
                                value={to}
                                onChange={(e) => setTo(e.target.value)}
                                className="mt-1 block rounded-lg border border-brand-line px-3 py-2 text-sm outline-none focus:border-brand-green"
                            />
                        </label>
                        <button
                            type="button"
                            onClick={apply}
                            className="h-10 rounded-lg bg-brand-green px-5 text-sm font-bold text-white transition hover:bg-brand-green-dark"
                        >
                            Apply
                        </button>
                    </div>

                    {leads.data.length === 0 ? (
                        <div className="rounded-xl border border-brand-line bg-white px-6 py-16 text-center">
                            <h2 className="text-base font-bold text-brand-ink">No leads yet</h2>
                            <p className="mx-auto mt-2 max-w-md text-sm text-brand-muted">
                                A lead is recorded whenever a buyer asks to contact you — through the WhatsApp bot or a
                                button on your storefront. Each new buyer is charged at your rate; repeat contacts from
                                the same buyer are free.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-hidden rounded-xl border border-brand-line bg-white">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-brand-surface text-xs uppercase tracking-wide text-brand-muted">
                                        <tr>
                                            <th className="px-5 py-3 font-semibold">Date</th>
                                            <th className="px-5 py-3 font-semibold">Searched for</th>
                                            <th className="px-5 py-3 font-semibold">Area</th>
                                            <th className="px-5 py-3 font-semibold">Channel</th>
                                            <th className="px-5 py-3 text-right font-semibold">Coins</th>
                                            <th className="px-5 py-3 font-semibold">State</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-brand-line">
                                        {leads.data.map((lead) => (
                                            <tr key={lead.id}>
                                                <td className="whitespace-nowrap px-5 py-3 text-brand-muted">
                                                    {new Date(lead.date).toLocaleDateString('en-US', {
                                                        year: 'numeric',
                                                        month: 'short',
                                                        day: 'numeric',
                                                    })}
                                                </td>
                                                <td className="px-5 py-3 text-brand-ink">{lead.search ?? '—'}</td>
                                                <td className="px-5 py-3 text-brand-muted">{lead.area ?? '—'}</td>
                                                <td className="px-5 py-3 text-brand-muted capitalize">{lead.channel}</td>
                                                <td className="whitespace-nowrap px-5 py-3 text-right font-semibold text-brand-ink">
                                                    {lead.coins_charged > 0 ? lead.coins_charged : '—'}
                                                </td>
                                                <td className="px-5 py-3">
                                                    <span className={`rounded-full px-2.5 py-1 text-xs font-bold ${STATE_BADGE[lead.state] ?? 'bg-gray-100 text-gray-600'}`}>
                                                        {STATE_LABEL[lead.state] ?? lead.state}
                                                        {lead.state === 'unbilled' && lead.unbilled_reason
                                                            ? ` · ${REASON_LABEL[lead.unbilled_reason] ?? ''}`
                                                            : ''}
                                                    </span>
                                                    {lead.repeat_count > 0 && (
                                                        <span className="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">
                                                            +{lead.repeat_count} repeat, free
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {leads.last_page > 1 && (
                        <div className="mt-6 flex flex-wrap justify-center gap-2">
                            {Array.from({ length: leads.last_page }, (_, i) => i + 1).map((page) => (
                                <Link
                                    key={page}
                                    href={hrefForPage(page)}
                                    preserveScroll
                                    className={`flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium transition-colors ${
                                        page === leads.current_page
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
