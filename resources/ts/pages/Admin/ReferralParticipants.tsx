import { Head, router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import AdminLayout from '@/components/Admin/AdminLayout';
import ParticipantRow from '@/components/Admin/referral/partials/ParticipantRow';
import ParticipantStanding from '@/components/Admin/referral/partials/ParticipantStanding';
import DetailPanel from '@/components/Admin/vendors/partials/DetailPanel';
import { SearchIcon, TrophyIcon } from '@/components/icons';
import { Skeleton } from '@/components/Loading';
import { CampaignParticipant, CampaignParticipantDetail, ReferralParticipantsProps } from '@/types';

const PARTICIPANTS_URL = '/admin/referral/participants';

function detailErrorMessage(name: string, error: unknown): string {
    const status = error instanceof Error ? error.message : '';

    if (status === '401' || status === '419') {
        return 'Your session has expired. Reload the page to sign in again.';
    }

    if (status === '429') {
        return 'Too many requests — wait a moment before opening another participant.';
    }

    return `Could not load ${name}. Please try again.`;
}

function RowSkeleton() {
    return (
        <div className="flex items-center gap-4 px-6 py-4">
            <Skeleton className="w-9 h-9 rounded-full flex-shrink-0" />
            <div className="min-w-0 flex-1 space-y-2">
                <Skeleton className="h-4 w-40" />
                <Skeleton className="h-3 w-56" />
            </div>
            <Skeleton className="h-8 w-16 flex-shrink-0" />
        </div>
    );
}

export default function ReferralParticipants({
    total_participants,
    participants,
    search,
}: ReferralParticipantsProps) {
    const [searchTerm, setSearchTerm] = useState(search);
    const [listLoading, setListLoading] = useState(false);
    const [selected, setSelected] = useState<CampaignParticipantDetail | null>(null);
    const [loadingId, setLoadingId] = useState<number | null>(null);
    const [detailError, setDetailError] = useState<string | null>(null);
    const requestRef = useRef(0);

    // preserveState keeps this component mounted across visits, so the input
    // has to follow the term the server actually applied (history nav included).
    useEffect(() => setSearchTerm(search), [search]);

    const navigate = (params: Record<string, string | number>) => {
        router.get(PARTICIPANTS_URL, params, {
            preserveState: true,
            preserveScroll: true,
            onStart: () => setListLoading(true),
            onFinish: () => setListLoading(false),
        });
    };

    // The detail endpoint returns JSON, so it is fetched rather than routed to.
    // Clicks can overtake each other — only the newest one may write state.
    const openParticipant = useCallback(async (participant: CampaignParticipant) => {
        const requestId = ++requestRef.current;
        setLoadingId(participant.user_id);
        setDetailError(null);

        try {
            const response = await fetch(`${PARTICIPANTS_URL}/${participant.user_id}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error(String(response.status));

            const detail: CampaignParticipantDetail = await response.json();
            if (requestId === requestRef.current) setSelected(detail);
        } catch (error) {
            if (requestId === requestRef.current) {
                setDetailError(detailErrorMessage(participant.name, error));
            }
        } finally {
            if (requestId === requestRef.current) setLoadingId(null);
        }
    }, []);

    const isSearching = search !== '';
    const { data, current_page, last_page, total } = participants;

    return (
        <>
            <Head title="Campaign Participants — Admin" />
            <AdminLayout>
                {/* Background decoration */}
                <div className="fixed top-0 left-64 right-0 h-full pointer-events-none -z-10 overflow-hidden">
                    <div className="absolute top-[-10%] right-[-5%] w-[40%] h-[60%] bg-primary-olive/5 rounded-full blur-[120px]" />
                    <div className="absolute bottom-[-5%] left-[20%] w-[30%] h-[50%] bg-primary-peach/5 rounded-full blur-[100px]" />
                </div>

                {/* Page Header */}
                <div className="flex justify-between items-end mb-12">
                    <div className="space-y-1">
                        <p className="text-primary-olive font-bold text-sm tracking-[0.2em] uppercase">
                            Campaign
                        </p>
                        <h2 className="text-4xl font-extrabold tracking-tight text-gray-900">
                            Referral Participants
                        </h2>
                    </div>

                    <form
                        role="search"
                        onSubmit={(e) => {
                            e.preventDefault();
                            navigate({ search: searchTerm });
                        }}
                        className="flex items-center gap-2"
                    >
                        <div className="relative">
                            <SearchIcon className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input
                                type="search"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="Search name, business or email…"
                                aria-label="Search participants"
                                className="pl-9 pr-4 py-2.5 rounded-xl bg-gray-100 text-sm text-gray-700 placeholder:text-gray-400 focus:bg-white focus:ring-1 focus:ring-primary-olive outline-none transition-all w-72"
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={listLoading}
                            className="px-4 py-2.5 rounded-xl bg-primary-olive text-white font-bold text-xs uppercase tracking-widest hover:brightness-110 disabled:opacity-40 transition-all"
                        >
                            Search
                        </button>
                    </form>
                </div>

                {/* Stats Overview */}
                <div className="grid grid-cols-12 gap-5 mb-12">
                    <div className="col-span-12 lg:col-span-8 bg-gradient-to-br from-primary-dark to-primary-brown p-8 rounded-xl flex items-center justify-between text-white shadow-xl shadow-primary-dark/20">
                        <div>
                            <p className="text-xs uppercase tracking-widest opacity-70 mb-2">
                                Total Campaign Participants
                            </p>
                            <p className="text-6xl font-bold tracking-tight tabular-nums">{total_participants}</p>
                        </div>
                        <TrophyIcon className="w-16 h-16 opacity-20" strokeWidth={1.25} />
                    </div>

                    <div className="col-span-12 lg:col-span-4 bg-white p-8 rounded-xl border border-gray-100 flex flex-col justify-between">
                        <div>
                            <p className="text-xs uppercase tracking-widest text-gray-400 mb-2">
                                {isSearching ? 'Matching Search' : 'Ranked List'}
                            </p>
                            <p className="text-4xl font-bold tracking-tight text-gray-900 tabular-nums">{total}</p>
                        </div>
                        <p className="text-[10px] text-gray-400 mt-1.5">
                            Showing page {current_page} of {last_page}
                        </p>
                    </div>
                </div>

                {detailError && (
                    <div className="mb-6 flex items-center justify-between gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
                        <span>{detailError}</span>
                        <button
                            type="button"
                            onClick={() => setDetailError(null)}
                            className="text-xs font-bold uppercase tracking-widest hover:underline"
                        >
                            Dismiss
                        </button>
                    </div>
                )}

                {/* Ranked participants */}
                <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/60">
                        <h3 className="text-xs uppercase tracking-widest font-bold text-gray-500">
                            Ranked by counted referrals
                        </h3>
                        <span className="text-[10px] uppercase tracking-widest text-gray-400">
                            Click a row for details
                        </span>
                    </div>

                    {listLoading ? (
                        <div className="divide-y divide-gray-100">
                            {Array.from({ length: 6 }, (_, i) => (
                                <RowSkeleton key={i} />
                            ))}
                        </div>
                    ) : data.length === 0 ? (
                        <div className="py-24 text-center">
                            <TrophyIcon className="w-12 h-12 text-gray-200 mx-auto mb-4" strokeWidth={1.5} />
                            <p className="text-gray-400 font-medium">
                                {isSearching
                                    ? 'No participants match your search.'
                                    : 'No one has joined the campaign yet.'}
                            </p>
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {data.map((participant) => (
                                <ParticipantRow
                                    key={participant.user_id}
                                    participant={participant}
                                    onSelect={openParticipant}
                                    loading={loadingId === participant.user_id}
                                />
                            ))}
                        </div>
                    )}
                </div>

                {/* Pagination */}
                {last_page > 1 && (
                    <div className="mt-10 flex justify-center gap-3">
                        <button
                            onClick={() => navigate({ page: current_page - 1, search })}
                            disabled={current_page === 1 || listLoading}
                            className="px-6 py-2.5 rounded-full bg-white border border-gray-200 text-gray-600 font-bold text-xs uppercase tracking-widest hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                        >
                            Previous
                        </button>
                        <button
                            onClick={() => navigate({ page: current_page + 1, search })}
                            disabled={current_page === last_page || listLoading}
                            className="px-6 py-2.5 rounded-full bg-white border border-gray-200 text-gray-600 font-bold text-xs uppercase tracking-widest hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                        >
                            Next
                        </button>
                    </div>
                )}
            </AdminLayout>

            <DetailPanel
                vendor={selected}
                onClose={() => setSelected(null)}
                eyebrow="Campaign Participant"
                showOnboarding={false}
            >
                {selected && <ParticipantStanding participant={selected} />}
            </DetailPanel>
        </>
    );
}
