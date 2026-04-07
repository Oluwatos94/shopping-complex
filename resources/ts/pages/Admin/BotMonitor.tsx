import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/components/Admin/AdminLayout';

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
    search:                   { label: 'Search',           className: 'bg-primary-olive/10 text-primary-olive' },
    vendor_viewed:            { label: 'Vendor Viewed',    className: 'bg-emerald-50 text-emerald-700' },
    product_catalogue_viewed: { label: 'Catalogue Viewed', className: 'bg-primary-peach/20 text-primary-brown' },
    contact_requested:        { label: 'Contact Request',  className: 'bg-primary-brown/10 text-primary-brown' },
    no_results:               { label: 'No Results',       className: 'bg-amber-50 text-amber-700' },
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
            <AdminLayout>
                {/* Header */}
                <div className="flex items-center justify-between mb-10">
                    <div>
                        <h2 className="text-3xl font-bold tracking-tight text-gray-900">Bot Monitor</h2>
                        <p className="text-gray-500 mt-1">
                            {interactions.total.toLocaleString()} total WhatsApp interactions
                        </p>
                    </div>
                    <span className="text-[10px] font-bold text-gray-400 uppercase bg-white border border-gray-100 px-3 py-1.5 rounded-lg">
                        Live Feed
                    </span>
                </div>

                {/* Table */}
                <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    {interactions.data.length === 0 ? (
                        <div className="py-20 text-center">
                            <svg className="w-10 h-10 text-gray-300 mx-auto mb-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
                                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.526 5.845L.057 23.882l6.198-1.625A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.68.965.982-3.59-.234-.369A9.818 9.818 0 1112 21.818z" />
                            </svg>
                            <p className="text-gray-400 text-sm">No interactions yet.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-gray-100 bg-gray-50/60">
                                        <th className="text-left py-3.5 px-5 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                            Phone
                                        </th>
                                        <th className="text-left py-3.5 px-5 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                            Event
                                        </th>
                                        <th className="text-left py-3.5 px-5 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                            Search Query
                                        </th>
                                        <th className="text-left py-3.5 px-5 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                            Vendor
                                        </th>
                                        <th className="text-right py-3.5 px-5 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                            Time
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {interactions.data.map((item) => (
                                        <tr
                                            key={item.id}
                                            className="border-b border-gray-50 last:border-0 hover:bg-gray-50/40 transition-colors"
                                        >
                                            <td className="py-4 px-5 font-mono text-gray-500 text-xs">
                                                {maskPhone(item.phone_number)}
                                            </td>
                                            <td className="py-4 px-5">
                                                <EventBadge eventType={item.event_type} />
                                            </td>
                                            <td
                                                className="py-4 px-5 text-gray-700 max-w-[200px]"
                                                title={item.search_query ?? undefined}
                                            >
                                                {item.search_query
                                                    ? truncate(item.search_query, 40)
                                                    : <span className="text-gray-300">—</span>}
                                            </td>
                                            <td className="py-4 px-5 text-gray-700">
                                                {item.vendor_name ?? <span className="text-gray-300">—</span>}
                                            </td>
                                            <td
                                                className="py-4 px-5 text-right text-gray-400 whitespace-nowrap text-xs"
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
                    <div className="flex items-center justify-between mt-5">
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
            </AdminLayout>
        </>
    );
}
