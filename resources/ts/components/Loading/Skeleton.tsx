import { HTMLAttributes } from 'react';

type SkeletonProps = HTMLAttributes<HTMLDivElement>;

export default function Skeleton({ className = '', ...props }: SkeletonProps) {
    return (
        <div
            aria-hidden="true"
            className={`animate-pulse rounded bg-brand-line/60 ${className}`}
            {...props}
        />
    );
}
