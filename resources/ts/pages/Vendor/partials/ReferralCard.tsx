import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { CheckIcon, ChevronDownIcon, LinkIcon, ShareIcon, UsersIcon, WhatsAppIcon } from '@/components/icons';
import { Referral } from '@/types';
import { copyText } from '@/utils/clipboard';
import { formatDateOnly } from '@/utils/date';

interface Props {
    referral: Referral;
    businessName: string;
}

export default function ReferralCard({ referral, businessName }: Props) {
    const [copied, setCopied] = useState(false);
    const [copyFailed, setCopyFailed] = useState(false);
    const [canNativeShare, setCanNativeShare] = useState(false);
    const [shareFailed, setShareFailed] = useState(false);
    const [showReferrals, setShowReferrals] = useState(false);
    const resetRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const { code, link, count, referred_count, min_products, recent } = referral;
    const shareMessage = `${businessName} is on jiidaa. Join through my invite link and get discovered on WhatsApp: ${link}`;
    const sharePayload = useMemo(
        () => ({ title: 'Join jiidaa', text: shareMessage, url: link ?? '' }),
        [shareMessage, link]
    );

    useEffect(() => {
        setCanNativeShare(
            typeof navigator !== 'undefined' &&
            typeof navigator.share === 'function' &&
            typeof navigator.canShare === 'function' &&
            link !== null &&
            navigator.canShare(sharePayload)
        );
    }, [link, sharePayload]);

    useEffect(() => {
        return () => {
            if (resetRef.current) clearTimeout(resetRef.current);
        };
    }, []);

    const handleCopy = useCallback(async (value: string | null) => {
        if (!value) return;

        const ok = await copyText(value);

        if (resetRef.current) clearTimeout(resetRef.current);
        setCopied(ok);
        setCopyFailed(!ok);
        resetRef.current = setTimeout(() => {
            setCopied(false);
            setCopyFailed(false);
        }, 2000);
    }, []);

    const handleNativeShare = useCallback(async () => {
        if (!link) return;

        setShareFailed(false);

        try {
            await navigator.share(sharePayload);
        } catch (error) {
            if (!(error instanceof DOMException && error.name === 'AbortError')) {
                setShareFailed(true);
            }
        }
    }, [link, sharePayload]);

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

                    {/* Link */}
                    <div className="flex flex-col sm:flex-row sm:items-center gap-3 bg-brand-surface border border-brand-line rounded-xl px-4 py-3">
                        <div className="min-w-0 flex-1">
                            <p className="text-xs text-gray-500 mb-1">Your invite link</p>
                            <p className="text-sm text-gray-700 truncate" title={link}>{link}</p>
                        </div>
                        <button
                            type="button"
                            onClick={() => handleCopy(link)}
                            className="flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors flex-shrink-0"
                            aria-label="Copy invite link"
                        >
                            {copied ? (
                                <>
                                    <CheckIcon className="w-4 h-4 text-primary-olive" />
                                    Copied
                                </>
                            ) : copyFailed ? (
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
                                {shareFailed ? 'Sharing unavailable' : 'Share via…'}
                            </button>
                        )}
                    </div>

                    {/* Who joined */}
                    <div className="pt-4 border-t border-brand-line">
                        {referred_count === 0 ? (
                            <div className="flex items-start gap-3">
                                <UsersIcon className="w-5 h-5 text-gray-300 flex-shrink-0" />
                                <p className="text-sm text-gray-500">No referrals yet &mdash; share your link to get started.</p>
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
                                        <span className="font-semibold text-gray-900">{count}</span> of{' '}
                                        <span className="font-semibold text-gray-900">{referred_count}</span>{' '}
                                        {referred_count === 1 ? 'business has' : 'businesses have'} listed {min_products}+ products
                                    </span>
                                    <ChevronDownIcon className={`w-4 h-4 text-gray-400 flex-shrink-0 transition-transform ${showReferrals ? 'rotate-180' : ''}`} />
                                </button>

                                <div id="referral-breakdown" className="mt-3" hidden={!showReferrals}>
                                    <ul className="border border-brand-line rounded-xl divide-y divide-brand-line overflow-hidden">
                                        {recent.map((referred) => (
                                            <li key={referred.email} className="flex items-center justify-between gap-3 bg-brand-surface px-4 py-2.5">
                                                <span className="min-w-0">
                                                    <span className="block text-sm text-gray-900 truncate">{referred.name}</span>
                                                    <span className="block text-xs text-gray-500 truncate" title={referred.email}>
                                                        {referred.email}
                                                    </span>
                                                </span>
                                                <span className="flex items-center gap-3 flex-shrink-0">
                                                    <span
                                                        className={`text-[11px] font-semibold tabular-nums px-2 py-0.5 rounded-full ${
                                                            referred.products_count >= min_products
                                                                ? 'bg-primary-olive/10 text-primary-olive'
                                                                : 'bg-amber-50 text-amber-700'
                                                        }`}
                                                    >
                                                        {referred.products_count >= min_products
                                                            ? 'Counted'
                                                            : `${referred.products_count} of ${min_products} listed`}
                                                    </span>
                                                    <span className="text-xs text-gray-500">{formatDateOnly(referred.joined_at)}</span>
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                    {referred_count > recent.length && (
                                        <p className="text-xs text-gray-400 mt-2">Showing your {recent.length} most recent referrals.</p>
                                    )}
                                </div>
                            </>
                        )}
                    </div>

                    <p aria-live="polite" className="sr-only">
                        {copied ? 'Invite link copied to clipboard' : copyFailed ? 'Could not copy the invite link' : ''}
                    </p>

                </div>
            )}
        </div>
    );
}
