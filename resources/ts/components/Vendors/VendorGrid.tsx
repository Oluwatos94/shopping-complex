import { NearbyVendor } from '@/types';
import VendorCard from './VendorCard';
import { SkeletonCard } from '@/components/Loading';

interface VendorGridProps {
    vendors: NearbyVendor[];
    isLoading?: boolean;
}

const GRID_CLASS = 'grid grid-cols-2 gap-4 sm:gap-6 sm:[grid-template-columns:repeat(auto-fill,minmax(280px,1fr))]';

export default function VendorGrid({ vendors, isLoading = false }: VendorGridProps) {
    if (isLoading) {
        return (
            <div className={GRID_CLASS}>
                <SkeletonCard count={8} variant="vendor" />
            </div>
        );
    }

    if (vendors.length === 0) {
        return (
            <div className="rounded-[18px] border border-brand-line bg-white px-5 py-20 text-center font-display">
                <h3 className="text-[19px] font-bold text-brand-ink">No vendors found</h3>
                <p className="mt-1.5 text-[15px] text-brand-muted">Try a different search or category.</p>
            </div>
        );
    }

    return (
        <div className={GRID_CLASS}>
            {vendors.map((vendor) => (
                <VendorCard key={vendor.id} vendor={vendor} />
            ))}
        </div>
    );
}
