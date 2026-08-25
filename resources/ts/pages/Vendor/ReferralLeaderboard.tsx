import { Head, Link } from '@inertiajs/react';
import VendorSidebar from '@/components/VendorSidebar';
import StatCard from '@/components/Vendor/StatCard';
import { BagIcon, LinkIcon, SparkleIcon, UsersIcon } from '@/components/icons';
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
                        <h1 className="text-2xl font-bold text-gray-900">Referral Leaderboard</h1>
                    </div>

                    {/* Standings summary */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <StatCard
                            label="Total participants"
                            value={total_participants}
                            icon={<UsersIcon className="w-6 h-6 text-brand-green" />}
                        />
                        <StatCard
                            label="Your position"
                            value={my_rank === null ? 'Unranked' : `#${my_rank}`}
                            icon={<SparkleIcon className="w-6 h-6 text-brand-green" />}
                        />
                        <StatCard
                            label="Your referrals"
                            value={my_referral_count}
                            icon={<LinkIcon className="w-6 h-6 text-brand-green" />}
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
                                <BagIcon className="w-12 h-12 text-gray-200 mb-3" strokeWidth={1.5} />
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
