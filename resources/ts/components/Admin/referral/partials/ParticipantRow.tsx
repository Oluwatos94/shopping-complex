import { CampaignParticipant } from '@/types';
import { formatDate } from '@/utils/date';

const RANK_STYLES: Partial<Record<number, string>> = {
    1: 'bg-amber-100 text-amber-800',
    2: 'bg-gray-200 text-gray-700',
    3: 'bg-orange-100 text-orange-800',
};

export default function ParticipantRow({
    participant,
    onSelect,
    loading,
}: {
    participant: CampaignParticipant;
    onSelect: (p: CampaignParticipant) => void;
    loading: boolean;
}) {
    const rankStyle = RANK_STYLES[participant.rank] ?? 'bg-gray-100 text-gray-500';
    const showAccountName = participant.account_name !== participant.name;

    return (
        <button
            type="button"
            onClick={() => onSelect(participant)}
            disabled={loading}
            aria-label={`View ${participant.name}`}
            className="group w-full flex items-center gap-4 px-6 py-4 text-left hover:bg-gray-50/80 focus-visible:bg-gray-50/80 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-primary-olive disabled:opacity-60 transition-colors"
        >
            <span
                className={`w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0 tabular-nums ${rankStyle}`}
            >
                {participant.rank}
            </span>

            <div className="min-w-0 flex-1">
                <p className="text-sm font-bold text-gray-900 group-hover:text-primary-olive transition-colors truncate">
                    {participant.name}
                </p>
                <p className="text-xs text-gray-400 truncate">
                    {showAccountName ? `${participant.account_name} · ` : ''}
                    {participant.email}
                </p>
            </div>

            <div className="hidden sm:block text-right flex-shrink-0 w-28">
                <p className="text-[10px] uppercase tracking-widest text-gray-400">Joined</p>
                <p className="text-xs font-medium text-gray-600">
                    {participant.joined_at ? formatDate(participant.joined_at) : '—'}
                </p>
            </div>

            <div className="text-right flex-shrink-0 w-24">
                <p className="text-lg font-bold text-gray-900 tabular-nums leading-tight">
                    {participant.referral_count}
                </p>
                <p className="text-[10px] uppercase tracking-widest text-gray-400 tabular-nums">
                    of {participant.referred_count} invited
                </p>
            </div>

            {loading && (
                <span className="w-4 h-4 rounded-full border-2 border-primary-olive/30 border-t-primary-olive animate-spin flex-shrink-0" />
            )}
        </button>
    );
}
