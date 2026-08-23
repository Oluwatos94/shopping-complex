import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Referral } from '@/types';
import { formatDateOnly } from '@/utils/date';

interface Props {
    referral: Referral;
    businessName: string;
}

type CopyTarget = 'code' | 'link';

async function copyText(value: string): Promise<boolean> {
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(value);
            return true;
        }
    } catch {
        // fall through to the legacy path
    }

    try {
        const textarea = document.createElement('textarea');
        textarea.value = value;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(textarea);
        return ok;
    } catch {
        return false;
    }
}

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
                    <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
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
                    <svg className="w-10 h-10 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m4.5-4.5l1.5-1.5a4 4 0 015.656 5.656l-3 3" />
                    </svg>
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
                                    <svg className="w-4 h-4 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                    Copied
                                </>
                            ) : copyFailed === 'code' ? (
                                'Copy failed'
                            ) : (
                                <>
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m2 4h2a2 2 0 012 2v3" />
                                    </svg>
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
                                    <svg className="w-4 h-4 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                    Copied
                                </>
                            ) : copyFailed === 'link' ? (
                                'Copy failed'
                            ) : (
                                <>
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m4.5-4.5l1.5-1.5a4 4 0 015.656 5.656l-3 3" />
                                    </svg>
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
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                            Share on WhatsApp
                        </a>

                        {canNativeShare && (
                            <button
                                type="button"
                                onClick={handleNativeShare}
                                className="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M8.684 13.342a3 3 0 100-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684zm0-12.632a3 3 0 105.368-2.684 3 3 0 00-5.368 2.684z" />
                                </svg>
                                Share via&hellip;
                            </button>
                        )}
                    </div>

                    {/* Who joined */}
                    <div className="pt-4 border-t border-brand-line">
                        {count === 0 ? (
                            <div className="flex items-start gap-3">
                                <svg className="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
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
                                    <svg
                                        className={`w-4 h-4 text-gray-400 flex-shrink-0 transition-transform ${showReferrals ? 'rotate-180' : ''}`}
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    >
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div id="referral-breakdown" className="mt-3" hidden={!showReferrals}>
                                    <ul className="border border-brand-line rounded-xl divide-y divide-brand-line overflow-hidden">
                                        {recent.map((referred, index) => (
                                            <li key={index} className="flex items-center justify-between gap-3 bg-brand-surface px-4 py-2.5">
                                                <span className="text-sm text-gray-900 truncate">{referred.name}</span>
                                                <span className="text-xs text-gray-500 flex-shrink-0">{formatDateOnly(referred.joined_at)}</span>
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
