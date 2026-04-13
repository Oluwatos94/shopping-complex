import { Link } from '@inertiajs/react';
import { NearbyVendor } from '@/types';

interface VendorCardProps {
    vendor: NearbyVendor;
}

export default function VendorCard({ vendor }: VendorCardProps) {
    const profileImage = vendor.business_logo || '/images/default-vendor.png';

    // Format distance
    const distance = vendor.distance_km < 1
        ? `${Math.round(vendor.distance_km * 1000)}m away`
        : `${vendor.distance_km.toFixed(1)} km away`;

    // Determine status badge color
    const statusColor = vendor.is_online ? 'bg-green-500' : 'bg-gray-400';

    return (
        <div className="group block bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-[#D49F89]">
            {/* Vendor Image/Logo */}
            <div className="relative aspect-video overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200">
                <img
                    src={profileImage}
                    alt={vendor.business_name}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />

                {/* Badges */}
                <div className="absolute top-3 left-3 flex flex-col gap-2">
                    {vendor.is_verified && (
                        <span className="bg-[#D49F89] text-[#272518] text-xs font-semibold px-2.5 py-1 rounded-full flex items-center gap-1 shadow-md">
                            <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                            </svg>
                            Verified
                        </span>
                    )}
                </div>

                {/* Online Status */}
                <div className="absolute top-3 right-3">
                    <div className={`flex items-center gap-1.5 ${statusColor} text-white text-xs font-medium px-2.5 py-1 rounded-full shadow-md`}>
                        <span className={`w-2 h-2 ${statusColor} rounded-full ${vendor.is_online ? 'animate-pulse' : ''}`}></span>
                        {vendor.is_online ? 'Active' : 'Offline'}
                    </div>
                </div>
            </div>

            {/* Vendor Info */}
            <div className="p-4">
                {/* Business Name */}
                <Link href={`/vendors/${vendor.slug}`}>
                    <h3 className="font-semibold text-[#272518] mb-1.5 line-clamp-1 group-hover:text-[#D49F89] transition-colors text-lg">
                        {vendor.business_name}
                    </h3>
                </Link>

                {/* Distance & Location */}
                <div className="flex items-center gap-2 text-sm text-gray-600 mb-2.5">
                    <svg className="w-4 h-4 text-[#D49F89]" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                    </svg>
                    <span className="font-medium text-[#D49F89]">{distance}</span>
                    {vendor.location?.address && (
                        <>
                            <span className="text-gray-400">•</span>
                            <span className="truncate">{vendor.location.address.split(',')[0]}</span>
                        </>
                    )}
                </div>

                {/* Rating & Reviews */}
                {vendor.rating && vendor.rating > 0 && (
                    <div className="flex items-center gap-2 mb-3">
                        <div className="flex items-center">
                            {[...Array(5)].map((_, i) => (
                                <svg
                                    key={i}
                                    className={`w-4 h-4 ${
                                        i < Math.floor(vendor.rating)
                                            ? 'text-[#D49F89] fill-current'
                                            : 'text-gray-300'
                                    }`}
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            ))}
                        </div>
                        <span className="text-sm font-medium text-[#272518]">{vendor.rating.toFixed(1)}</span>
                        {vendor.reviews_count && vendor.reviews_count > 0 && (
                            <span className="text-xs text-gray-500">({vendor.reviews_count})</span>
                        )}
                    </div>
                )}

                {/* Products Count & Response Time */}
                <div className="flex items-center justify-between text-xs text-gray-600 mb-4">
                    <span className="flex items-center gap-1">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        {vendor.products_count} products
                    </span>
                    {vendor.avg_response_time && (
                        <span className="flex items-center gap-1">
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            ~{vendor.avg_response_time}min
                        </span>
                    )}
                </div>

                {/* Contact Button */}
                <Link
                    href={`/vendors/${vendor.slug}`}
                    className="w-full bg-[#D49F89] hover:bg-[#c48f79] text-[#272518] font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 shadow-sm"
                >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    Contact Vendor
                </Link>
            </div>
        </div>
    );
}
