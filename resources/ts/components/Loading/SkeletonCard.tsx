import Skeleton from './Skeleton';

interface SkeletonCardProps {
    count?: number;
    variant?: 'product' | 'vendor';
}

function Card({ variant }: { variant: 'product' | 'vendor' }) {
    return (
        <div className="overflow-hidden rounded-[18px] border border-brand-line bg-white">
            <Skeleton className={`rounded-none ${variant === 'vendor' ? 'aspect-[16/11]' : 'aspect-square'}`} />
            <div className="space-y-2 p-4">
                <Skeleton className="h-3 w-1/2" />
                <Skeleton className="h-4 w-3/4" />
                <Skeleton className="h-4 w-1/3" />
            </div>
        </div>
    );
}

export default function SkeletonCard({ count = 1, variant = 'product' }: SkeletonCardProps) {
    if (count === 1) return <Card variant={variant} />;

    return (
        <>
            {Array.from({ length: count }).map((_, i) => (
                <Card key={i} variant={variant} />
            ))}
        </>
    );
}
