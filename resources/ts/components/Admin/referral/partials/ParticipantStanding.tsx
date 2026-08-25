import { CampaignParticipantDetail } from '@/types';
import { formatDateOnly } from '@/utils/date';

/** A scanning aid only — nothing on the server enforces it. */
const LISTING_TARGET = 5;

function listingStyle(count: number): string {
    if (count === 0) return 'bg-red-50 text-red-600 border-red-100';
    if (count < LISTING_TARGET) return 'bg-amber-50 text-amber-700 border-amber-100';

    return 'bg-primary-olive/10 text-primary-olive border-primary-olive/20';
}

export default function ParticipantStanding({ participant }: { participant: CampaignParticipantDetail }) {
    const { referral_count, rank, referrals } = participant;
    const meetingTarget = referrals.filter((r) => r.products_count >= LISTING_TARGET).length;

    return (
        <div className="space-y-3">
            <h4 className="text-xs uppercase tracking-widest font-bold text-gray-400 border-b border-gray-100 pb-2">
                Campaign Standing
            </h4>

            <div className="grid grid-cols-2 gap-3">
                <div className="p-5 bg-primary-olive/5 rounded-xl border border-primary-olive/10">
                    <p className="text-[10px] uppercase tracking-widest text-gray-400 mb-1">Referrals</p>
                    <p className="text-3xl font-bold text-gray-900 tabular-nums">{referral_count}</p>
                </div>
                <div className="p-5 bg-gray-50 rounded-xl border border-gray-100">
                    <p className="text-[10px] uppercase tracking-widest text-gray-400 mb-1">Rank</p>
                    <p className="text-3xl font-bold text-gray-900 tabular-nums">
                        {rank === null ? '—' : `#${rank}`}
                    </p>
                </div>
            </div>

            {referrals.length === 0 ? (
                <p className="text-sm text-gray-300 italic">No verified referrals yet.</p>
            ) : (
                <>
                    <div className="flex items-baseline justify-between gap-3 pt-2">
                        <p className="text-[10px] uppercase tracking-widest text-gray-400">
                            Vendors they brought in
                        </p>
                        <p className="text-[10px] text-gray-400 tabular-nums">
                            {meetingTarget} of {referrals.length} listed {LISTING_TARGET}+
                        </p>
                    </div>

                    <ul className="border border-gray-100 rounded-lg divide-y divide-gray-100 overflow-hidden">
                        {referrals.map((referred, index) => (
                            <li
                                key={`${referred.name}-${index}`}
                                className="flex items-center justify-between gap-3 bg-gray-50/60 px-4 py-2.5"
                            >
                                <span className="text-sm text-gray-800 truncate">{referred.name}</span>
                                <span className="flex items-center gap-3 flex-shrink-0">
                                    <span
                                        className={`text-[11px] font-bold tabular-nums px-2 py-0.5 rounded-full border ${listingStyle(referred.products_count)}`}
                                        title={`${referred.products_count} product${referred.products_count === 1 ? '' : 's'} listed`}
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

                    {referral_count > referrals.length && (
                        <p className="text-[10px] text-gray-400">
                            Showing the {referrals.length} most recent of {referral_count}.
                        </p>
                    )}
                </>
            )}
        </div>
    );
}
