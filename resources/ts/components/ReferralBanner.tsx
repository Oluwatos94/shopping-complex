import React from 'react';
import { Link } from '@inertiajs/react';

const REPEATS = 3;

const Message: React.FC = () => (
    <span className="flex shrink-0 items-center gap-2.5 px-6 sm:gap-3 sm:px-10">
        <svg
            className="h-4 w-4 shrink-0 text-brand-green sm:h-[18px] sm:w-[18px]"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.8}
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <path d="M20 12v9H4v-9" />
            <path d="M2 7h20v5H2z" />
            <path d="M12 21V7" />
            <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7Z" />
            <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7Z" />
        </svg>
        <span className="whitespace-nowrap text-[13px] font-medium text-white sm:text-[14px]">
            Join the referral program —{' '}
            <span className="font-bold text-brand-green">₦310k</span> in cash prizes to be won.{' '}
            <span className="underline decoration-brand-green decoration-2 underline-offset-4">Click here</span> to view
            more details
        </span>
    </span>
);

const ReferralBanner: React.FC = () => {
    return (
        <Link
            href="/referral-program"
            aria-label="Join the referral program. ₦310,000 in cash prizes to be won. Click to view more details."
            className="group block overflow-hidden bg-brand-ink font-display"
        >
            <div className="flex py-2 sm:py-2.5" aria-hidden="true">
                {/* Duplicated track: the marquee shifts by -50%, so two identical
                    halves make the loop seamless. */}
                <div className="flex w-max animate-marquee-banner items-center group-hover:[animation-play-state:paused] motion-reduce:animate-none">
                    {Array.from({ length: REPEATS * 2 }, (_, i) => (
                        <Message key={i} />
                    ))}
                </div>
            </div>
        </Link>
    );
};

export default ReferralBanner;
