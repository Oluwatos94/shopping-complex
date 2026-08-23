import { Head, Link } from '@inertiajs/react';
import VendorSidebar from '@/components/VendorSidebar';
import StatCard from '@/components/Vendor/StatCard';
import { ReferralLeaderboardProps } from '@/types';

const RANK_STYLES: Partial<Record<number, string>> = {
    1: 'bg-amber-100 text-amber-800',
    2: 'bg-gray-200 text-gray-700',
    3: 'bg-orange-100 text-orange-800',
};

function RankBadge({ rank, highlighted }: { rank: number; highlighted: boolean }) {
    const style = highlighted
        ? 'bg-primary-olive text-white'
        : RANK_STYLES[rank] ?? 'bg-brand-surface text-gray-500';

    return (
        <span className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0 ${style}`}>
            {rank}
        </span>
    );
}

function ReferralCount({ count }: { count: number }) {
    const unit = count === 1 ? 'referral' : 'referrals';

    return (
        <span className="flex-shrink-0 text-sm font-semibold text-gray-900 tabular-nums">
            {count}
            <span className="sr-only"> {unit}</span>
            <span aria-hidden="true" className="hidden sm:inline font-normal text-gray-500"> {unit}</span>
        </span>
    );
}

function YouBadge() {
    return (
        <span className="flex-shrink-0 px-2 py-0.5 rounded-full bg-primary-olive text-white text-xs font-semibold">
            You
        </span>
    );
}

export default function ReferralLeaderboard({
    total_participants,
    top,
    my_rank,
    my_referral_count,
}: ReferralLeaderboardProps) {
    const inTop = top.some((entry) => entry.is_you);
    const pinnedRank = inTop ? null : my_rank;

    return (
        <>
            <Head title="Referral Leaderboard" />
            <VendorSidebar />

            <main className="md:ml-[260px] min-h-screen bg-brand-surface pb-20 md:pb-0">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8">

                    {/* Page Header */}
                    <div className="mb-6">
                        <Link
                            href="/vendor"
                            className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors mb-3"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                            Back to dashboard
                        </Link>
                        <h1 className="text-2xl font-bold text-gray-900">Referral Leaderboard</h1>
                        <p className="text-sm text-gray-500 mt-1">
                            The businesses bringing the most vendors onto jiidaa.
                        </p>
                    </div>

                    {/* Standings summary */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <StatCard
                            label="Total participants"
                            value={total_participants}
                            icon={
                                <svg className="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            }
                        />
                        <StatCard
                            label="Your position"
                            value={my_rank === null ? 'Unranked' : `#${my_rank}`}
                            icon={
                                <svg className="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                            }
                        />
                        <StatCard
                            label="Your referrals"
                            value={my_referral_count}
                            icon={
                                <svg className="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m4.5-4.5l1.5-1.5a4 4 0 015.656 5.656l-3 3" />
                                </svg>
                            }
                        />
                    </div>

                    {/* Standings */}
                    <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
                        <div className="px-5 sm:px-6 py-4 border-b border-brand-line">
                            <h2 className="text-base font-semibold text-gray-900">Top referrers</h2>
                            <p className="text-xs text-gray-500 mt-0.5">Ranked by verified referrals.</p>
                        </div>

                        {top.length === 0 ? (
                            <div className="flex flex-col items-center justify-center px-6 py-12 text-center">
                                <svg className="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <p className="text-sm text-gray-500">Leaderboard opens once referrals start.</p>
                                <Link href="/vendor" className="mt-3 text-sm font-semibold text-primary-olive hover:underline">
                                    Share your referral code
                                </Link>
                            </div>
                        ) : (
                            <ul className="divide-y divide-brand-line">
                                {top.map((entry) => (
                                    <li
                                        key={entry.rank}
                                        className={`flex items-center gap-3 sm:gap-4 px-5 sm:px-6 py-3.5 ${entry.is_you ? 'bg-primary-olive/10' : ''}`}
                                    >
                                        <RankBadge rank={entry.rank} highlighted={entry.is_you} />
                                        <p className={`min-w-0 flex-1 text-sm text-gray-900 truncate ${entry.is_you ? 'font-semibold' : ''}`}>
                                            {entry.name}
                                        </p>
                                        {entry.is_you && <YouBadge />}
                                        <ReferralCount count={entry.referral_count} />
                                    </li>
                                ))}
                            </ul>
                        )}

                        {pinnedRank !== null && (
                            <div className="flex items-center gap-3 sm:gap-4 px-5 sm:px-6 py-3.5 bg-primary-olive/10 border-t-2 border-brand-line">
                                <RankBadge rank={pinnedRank} highlighted />
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-semibold text-gray-900 truncate">Your position</p>
                                    <p className="text-xs text-gray-500 mt-0.5">
                                        Outside the top {top.length} &mdash; keep sharing to climb.
                                    </p>
                                </div>
                                <YouBadge />
                                <ReferralCount count={my_referral_count} />
                            </div>
                        )}
                    </div>

                    {my_rank === null && top.length > 0 && (
                        <p className="text-xs text-gray-500 mt-4 text-center">
                            You&apos;re not on the leaderboard yet &mdash; one verified referral gets you on it.
                        </p>
                    )}

                </div>
            </main>
        </>
    );
}
