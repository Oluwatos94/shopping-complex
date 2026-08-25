import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { CheckIcon, ChevronDownIcon, ClipboardIcon, LinkIcon, ShareIcon, UsersIcon, WhatsAppIcon } from '@/components/icons';
import { Referral } from '@/types';
import { copyText } from '@/utils/clipboard';
import { formatDateOnly } from '@/utils/date';

interface Props {
    referral: Referral;
    businessName: string;
}

type CopyTarget = 'code' | 'link';

export default function ReferralCard({ referral, businessName }: Props) {
    const [copied, setCopied] = useState<CopyTarget | null>(null);
    const [copyFailed, setCopyFailed] = useState<CopyTarget | null>(null);
    const [canNativeShare, setCanNativeShare] = useState(false);
    const [showReferrals, setShowReferrals] = useState(false);
    const resetRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Checked after mount so the markup stays identical on first paint.
    useEffect(() => {
        setCanNativeShare(typeof navigator !== 'undefined' && typeof navigator.share === 'function');
    }, []);

    useEffect(() => {
        return () => {
            if (resetRef.current) clearTimeout(resetRef.current);
        };
    }, []);

    const { code, link, count, recent } = referral;
    const joinedLabel = count === 1 ? 'business has' : 'businesses have';
    const shareMessage = `${businessName} is on jiidaa. Join with my referral code ${code} and get discovered on WhatsApp: ${link}`;

    const handleCopy = useCallback(async (target: CopyTarget, value: string | null) => {
        if (!value) return;

        const ok = await copyText(value);

        if (resetRef.current) clearTimeout(resetRef.current);
        setCopied(ok ? target : null);
        setCopyFailed(ok ? null : target);
        resetRef.current = setTimeout(() => {
            setCopied(null);
            setCopyFailed(null);
        }, 2000);
    }, []);

    const handleNativeShare = useCallback(async () => {
        if (!link) return;

        try {
            await navigator.share({
                title: 'Join jiidaa',
                text: shareMessage,
                url: link,
            });
        } catch {
            // The user dismissed the share sheet — nothing to report.
        }
    }, [link, shareMessage]);

    return (
        <div className="bg-white rounded-2xl p-6 shadow-sm mb-6">

            {/* Header */}
            <div className="flex items-start gap-3 mb-5">
                <div className="w-9 h-9 rounded-full bg-primary-olive/10 flex items-center justify-center flex-shrink-0">
                    <UsersIcon className="w-5 h-5 text-primary-olive" />
                </div>
                <div className="min-w-0 flex-1">
                    <h2 className="text-base font-semibold text-gray-900">Refer &amp; win</h2>
                    <p className="text-xs text-gray-500 mt-0.5">
                        Invite other businesses to jiidaa with your code and grow together.
                    </p>
                </div>
                <Link
                    href="/vendor/referral/leaderboard"
                    className="flex-shrink-0 text-xs font-semibold text-primary-dark underline-offset-2 hover:text-primary-olive hover:underline transition-colors"
                >
                    Check leaderboard
                </Link>
            </div>

            {!code || !link ? (
                <div className="flex flex-col items-center justify-center py-8 text-center">
                    <LinkIcon className="w-10 h-10 text-gray-200 mb-3" strokeWidth={1.5} />
                    <p className="text-sm text-gray-500">
                        We&apos;re setting up your referral code. Check back shortly.
                    </p>
                </div>
            ) : (
                <div className="space-y-3">

                    {/* Code */}
                    <div className="flex flex-col sm:flex-row sm:items-center gap-3 bg-brand-surface border border-brand-line rounded-xl px-4 py-3">
                        <div className="min-w-0 flex-1">
                            <p className="text-xs text-gray-500 mb-1">Your referral code</p>
                            <p className="text-lg font-bold tracking-widest text-gray-900 break-all">{code}</p>
                        </div>
                        <button
                            type="button"
                            onClick={() => handleCopy('code', code)}
                            className="flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors flex-shrink-0"
                            aria-label="Copy referral code"
                        >
                            {copied === 'code' ? (
                                <>
                                    <CheckIcon className="w-4 h-4 text-primary-olive" />
                                    Copied
                                </>
                            ) : copyFailed === 'code' ? (
                                'Copy failed'
                            ) : (
                                <>
                                    <ClipboardIcon className="w-4 h-4" />
                                    Copy code
                                </>
                            )}
                        </button>
                    </div>

                    {/* Link */}
                    <div className="flex flex-col sm:flex-row sm:items-center gap-3 bg-brand-surface border border-brand-line rounded-xl px-4 py-3">
                        <div className="min-w-0 flex-1">
                            <p className="text-xs text-gray-500 mb-1">Invite link</p>
                            <p className="text-sm text-gray-700 truncate" title={link}>{link}</p>
                        </div>
                        <button
                            type="button"
                            onClick={() => handleCopy('link', link)}
                            className="flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors flex-shrink-0"
                            aria-label="Copy invite link"
                        >
                            {copied === 'link' ? (
                                <>
                                    <CheckIcon className="w-4 h-4 text-primary-olive" />
                                    Copied
                                </>
                            ) : copyFailed === 'link' ? (
                                'Copy failed'
                            ) : (
                                <>
                                    <LinkIcon className="w-4 h-4" />
                                    Copy link
                                </>
                            )}
                        </button>
                    </div>

                    {/* Share */}
                    <div className="flex flex-col sm:flex-row gap-3 pt-1">
                        <a
                            href={`https://wa.me/?text=${encodeURIComponent(shareMessage)}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-brand-green text-white text-sm font-semibold hover:bg-brand-green-dark transition-colors"
                        >
                            <WhatsAppIcon className="w-4 h-4" />
                            Share on WhatsApp
                        </a>

                        {canNativeShare && (
                            <button
                                type="button"
                                onClick={handleNativeShare}
                                className="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            >
                                <ShareIcon className="w-4 h-4" />
                                Share via&hellip;
                            </button>
                        )}
                    </div>

                    {/* Who joined */}
                    <div className="pt-4 border-t border-brand-line">
                        {count === 0 ? (
                            <div className="flex items-start gap-3">
                                <UsersIcon className="w-5 h-5 text-gray-300 flex-shrink-0" />
                                <p className="text-sm text-gray-500">No referrals yet &mdash; share your code to get started.</p>
                            </div>
                        ) : (
                            <>
                                <button
                                    type="button"
                                    onClick={() => setShowReferrals((open) => !open)}
                                    aria-expanded={showReferrals}
                                    aria-controls="referral-breakdown"
                                    className="flex w-full items-center justify-between gap-3 text-left"
                                >
                                    <span className="text-sm text-gray-700">
                                        <span className="font-semibold text-gray-900">{count}</span> {joinedLabel} joined with your code
                                    </span>
                                    <ChevronDownIcon className={`w-4 h-4 text-gray-400 flex-shrink-0 transition-transform ${showReferrals ? 'rotate-180' : ''}`} />
                                </button>

                                <div id="referral-breakdown" className="mt-3" hidden={!showReferrals}>
                                    <ul className="border border-brand-line rounded-xl divide-y divide-brand-line overflow-hidden">
                                        {recent.map((referred, index) => (
                                            <li key={index} className="flex items-center justify-between gap-3 bg-brand-surface px-4 py-2.5">
                                                <span className="text-sm text-gray-900 truncate">{referred.name}</span>
                                                <span className="flex items-center gap-3 flex-shrink-0">
                                                    <span
                                                        className={`text-[11px] font-semibold tabular-nums px-2 py-0.5 rounded-full ${
                                                            referred.products_count > 0
                                                                ? 'bg-primary-olive/10 text-primary-olive'
                                                                : 'bg-gray-100 text-gray-400'
                                                        }`}
                                                    >
                                                        {referred.products_count === 0
                                                            ? 'Nothing listed'
                                                            : `${referred.products_count} listed`}
                                                    </span>
                                                    <span className="text-xs text-gray-500">{formatDateOnly(referred.joined_at)}</span>
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                    {count > recent.length && (
                                        <p className="text-xs text-gray-400 mt-2">Showing your {recent.length} most recent referrals.</p>
                                    )}
                                </div>
                            </>
                        )}
                    </div>

                    <p aria-live="polite" className="sr-only">
                        {copied ? `${copied === 'code' ? 'Referral code' : 'Invite link'} copied to clipboard` : ''}
                    </p>

                </div>
            )}
        </div>
    );
}
