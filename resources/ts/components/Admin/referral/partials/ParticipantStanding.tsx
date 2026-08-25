import { CampaignParticipantDetail } from '@/types';
import { formatDateOnly } from '@/utils/date';

function listingStyle(count: number, target: number): string {
    if (count >= target) return 'bg-primary-olive/10 text-primary-olive border-primary-olive/20';
    if (count === 0) return 'bg-red-50 text-red-600 border-red-100';

    return 'bg-amber-50 text-amber-700 border-amber-100';
}

export default function ParticipantStanding({ participant }: { participant: CampaignParticipantDetail }) {
    const { referral_count, referred_count, min_products, rank, referrals } = participant;

    return (
        <div className="space-y-3">
            <h4 className="text-xs uppercase tracking-widest font-bold text-gray-400 border-b border-gray-100 pb-2">
                Campaign Standing
            </h4>

            <div className="grid grid-cols-2 gap-3">
                <div className="p-5 bg-primary-olive/5 rounded-xl border border-primary-olive/10">
                    <p className="text-[10px] uppercase tracking-widest text-gray-400 mb-1">Counted</p>
                    <p className="text-3xl font-bold text-gray-900 tabular-nums">{referral_count}</p>
                    <p className="text-[10px] text-gray-400 mt-1 tabular-nums">of {referred_count} invited</p>
                </div>
                <div className="p-5 bg-gray-50 rounded-xl border border-gray-100">
                    <p className="text-[10px] uppercase tracking-widest text-gray-400 mb-1">Rank</p>
                    <p className="text-3xl font-bold text-gray-900 tabular-nums">
                        {rank === null ? '—' : `#${rank}`}
                    </p>
                </div>
            </div>

            {referrals.length === 0 ? (
                <p className="text-sm text-gray-300 italic">Nobody has joined through this vendor yet.</p>
            ) : (
                <>
                    <div className="flex items-baseline justify-between gap-3 pt-2">
                        <p className="text-[10px] uppercase tracking-widest text-gray-400">
                            Vendors they brought in
                        </p>
                        <p className="text-[10px] text-gray-400 tabular-nums">
                            Counts at {min_products}+ products
                        </p>
                    </div>

                    <ul className="border border-gray-100 rounded-lg divide-y divide-gray-100 overflow-hidden">
                        {referrals.map((referred) => (
                            <li
                                key={referred.email}
                                className="flex items-center justify-between gap-3 bg-gray-50/60 px-4 py-2.5"
                            >
                                <span className="min-w-0">
                                    <span className="block text-sm text-gray-800 truncate">{referred.name}</span>
                                    <span className="block text-xs text-gray-400 truncate" title={referred.email}>
                                        {referred.email}
                                    </span>
                                </span>
                                <span className="flex items-center gap-3 flex-shrink-0">
                                    <span
                                        className={`text-[11px] font-bold tabular-nums px-2 py-0.5 rounded-full border ${listingStyle(referred.products_count, min_products)}`}
                                        title={
                                            referred.products_count >= min_products
                                                ? 'Counts towards the campaign'
                                                : `Needs ${min_products - referred.products_count} more to count`
                                        }
                                    >
                                        {referred.products_count} listed
                                    </span>
                                    <span className="text-xs text-gray-400">
                                        {formatDateOnly(referred.joined_at)}
                                    </span>
                                </span>
                            </li>
                        ))}
                    </ul>

                    {referred_count > referrals.length && (
                        <p className="text-[10px] text-gray-400">
                            Showing the {referrals.length} most recent of {referred_count}.
                        </p>
                    )}
                </>
            )}
        </div>
    );
}
