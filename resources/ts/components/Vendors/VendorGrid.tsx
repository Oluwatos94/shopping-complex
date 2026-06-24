import { NearbyVendor } from '@/types';
import VendorCard from './VendorCard';

interface VendorGridProps {
    vendors: NearbyVendor[];
    isLoading?: boolean;
}

const GRID_CLASS = 'grid gap-6 [grid-template-columns:repeat(auto-fill,minmax(240px,1fr))] sm:[grid-template-columns:repeat(auto-fill,minmax(280px,1fr))]';

export default function VendorGrid({ vendors, isLoading = false }: VendorGridProps) {
    if (isLoading) {
        return (
            <div className={GRID_CLASS}>
                {[...Array(8)].map((_, i) => (
                    <div key={i} className="animate-pulse overflow-hidden rounded-[20px] border border-brand-line bg-white">
                        <div className="aspect-[16/11] bg-brand-line/60" />
                        <div className="space-y-3 p-[18px]">
                            <div className="h-5 w-3/4 rounded bg-brand-line/60" />
                            <div className="h-4 w-1/2 rounded bg-brand-line/60" />
                            <div className="h-[52px] w-full rounded-[13px] bg-brand-line/60" />
                        </div>
                    </div>
                ))}
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
