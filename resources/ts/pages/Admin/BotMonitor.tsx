import { Head, Link, router } from '@inertiajs/react';

interface Interaction {
    id: number;
    phone_number: string;
    event_type: string;
    search_query: string | null;
    vendor_id: number | null;
    vendor_name: string | null;
    buyer_latitude: number | null;
    buyer_longitude: number | null;
    created_at: string;
}

interface Props {
    interactions: {
        data: Interaction[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

const EVENT_BADGE: Record<string, { label: string; className: string }> = {
    search:                    { label: 'Search',           className: 'bg-blue-50 text-blue-700' },
    vendor_viewed:             { label: 'Vendor Viewed',    className: 'bg-green-50 text-green-700' },
    product_catalogue_viewed:  { label: 'Catalogue Viewed', className: 'bg-teal-50 text-teal-700' },
    contact_requested:         { label: 'Contact Request',  className: 'bg-purple-50 text-purple-700' },
    no_results:                { label: 'No Results',       className: 'bg-amber-50 text-amber-700' },
};

function maskPhone(phone: string): string {
    return '***' + phone.slice(-4);
}

function relativeTime(isoString: string): string {
    const diff = Math.floor((Date.now() - new Date(isoString).getTime()) / 1000);
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
}

function truncate(text: string, max: number): string {
    return text.length > max ? text.slice(0, max) + '…' : text;
}

function EventBadge({ eventType }: { eventType: string }) {
    const badge = EVENT_BADGE[eventType] ?? { label: eventType, className: 'bg-gray-100 text-gray-600' };
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap ${badge.className}`}>
            {badge.label}
        </span>
    );
}

export default function BotMonitor({ interactions }: Props) {
    const goToPage = (page: number) => {
        router.get('/admin/bot-monitor', { page }, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Bot Monitor" />

            <div className="min-h-screen bg-gray-50">
                <div className="max-w-6xl mx-auto px-6 py-8">
                    {/* Header */}
                    <div className="flex items-center justify-between mb-8">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Bot Monitor</h1>
                            <p className="text-sm text-gray-500 mt-1">
                                {interactions.total.toLocaleString()} total interactions
                            </p>
                        </div>
                        <Link
                            href="/admin/dashboard"
                            className="text-sm text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            ← Dashboard
                        </Link>
                    </div>

                    {/* Table */}
                    <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        {interactions.data.length === 0 ? (
                            <div className="py-16 text-center text-gray-400 text-sm">
                                No interactions yet.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-gray-100 bg-gray-50">
                                            <th className="text-left py-3 px-4 text-gray-500 font-medium">Phone</th>
                                            <th className="text-left py-3 px-4 text-gray-500 font-medium">Event</th>
                                            <th className="text-left py-3 px-4 text-gray-500 font-medium">Search Query</th>
                                            <th className="text-left py-3 px-4 text-gray-500 font-medium">Vendor</th>
                                            <th className="text-right py-3 px-4 text-gray-500 font-medium">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {interactions.data.map((item) => (
                                            <tr key={item.id} className="border-b border-gray-50 last:border-0 hover:bg-gray-50/50">
                                                <td className="py-3 px-4 font-mono text-gray-600 text-xs">
                                                    {maskPhone(item.phone_number)}
                                                </td>
                                                <td className="py-3 px-4">
                                                    <EventBadge eventType={item.event_type} />
                                                </td>
                                                <td
                                                    className="py-3 px-4 text-gray-700 max-w-[200px]"
                                                    title={item.search_query ?? undefined}
                                                >
                                                    {item.search_query ? truncate(item.search_query, 40) : <span className="text-gray-300">—</span>}
                                                </td>
                                                <td className="py-3 px-4 text-gray-700">
                                                    {item.vendor_name ?? <span className="text-gray-300">—</span>}
                                                </td>
                                                <td
                                                    className="py-3 px-4 text-right text-gray-400 whitespace-nowrap"
                                                    title={new Date(item.created_at).toISOString()}
                                                >
                                                    {relativeTime(item.created_at)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>

                    {/* Pagination */}
                    {interactions.last_page > 1 && (
                        <div className="flex items-center justify-between mt-4">
                            <p className="text-sm text-gray-500">
                                Page {interactions.current_page} of {interactions.last_page}
                            </p>
                            <div className="flex gap-2">
                                <button
                                    onClick={() => goToPage(interactions.current_page - 1)}
                                    disabled={interactions.current_page === 1}
                                    className="px-4 py-2 text-sm rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                >
                                    Previous
                                </button>
                                <button
                                    onClick={() => goToPage(interactions.current_page + 1)}
                                    disabled={interactions.current_page === interactions.last_page}
                                    className="px-4 py-2 text-sm rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
