import { NearbyVendor } from '@/types';
import VendorCard from './VendorCard';

interface VendorGridProps {
    vendors: NearbyVendor[];
    isLoading?: boolean;
}

export default function VendorGrid({ vendors, isLoading = false }: VendorGridProps) {
    if (isLoading) {
        return (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                {[...Array(8)].map((_, i) => (
                    <div key={i} className="bg-white rounded-lg overflow-hidden shadow-sm animate-pulse">
                        <div className="aspect-video bg-gray-200"></div>
                        <div className="p-4 space-y-3">
                            <div className="h-6 bg-gray-200 rounded w-3/4"></div>
                            <div className="h-4 bg-gray-200 rounded w-full"></div>
                            <div className="h-4 bg-gray-200 rounded w-2/3"></div>
                            <div className="h-10 bg-gray-200 rounded w-full"></div>
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    if (vendors.length === 0) {
        return (
            <div className="col-span-full flex flex-col items-center justify-center py-16 px-4 text-center">
                <svg
                    className="w-24 h-24 text-gray-300 mb-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={1.5}
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                    />
                </svg>
                <h3 className="text-xl font-semibold text-gray-700 mb-2">No vendors found</h3>
                <p className="text-gray-500 max-w-md">
                    We couldn't find any vendors in your area. Try expanding your search radius or updating your location.
                </p>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {vendors.map((vendor) => (
                <VendorCard key={vendor.id} vendor={vendor} />
            ))}
        </div>
    );
}
