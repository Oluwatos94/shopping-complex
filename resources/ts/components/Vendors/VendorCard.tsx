import { Link } from '@inertiajs/react';
import { NearbyVendor } from '@/types';

interface VendorCardProps {
    vendor: NearbyVendor;
}

export default function VendorCard({ vendor }: VendorCardProps) {
    const profileImage = vendor.business_logo || '/images/default-vendor.png';

    const whatsAppHref = vendor.whatsapp_number
        ? `https://wa.me/${vendor.whatsapp_number.replace(/[^0-9]/g, '')}?text=Hi, I found you on jiidaa`
        : null;

    const distance = vendor.distance_km !== null && vendor.distance_km !== undefined
        ? vendor.distance_km < 1
            ? `${Math.round(vendor.distance_km * 1000)}m away`
            : `${vendor.distance_km.toFixed(1)} km away`
        : null;

    return (
        <div className="group block bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-[#D49F89] hover:scale-[1.02]">
            {/* Vendor Image/Logo */}
            <div className="relative aspect-video overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200">
                <img
                    src={profileImage}
                    alt={vendor.business_name}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />


            </div>

            {/* Vendor Info */}
            <div className="p-4 pt-5">
                {/* Business Name */}
                <Link href={`/vendors/${vendor.slug}`}>
                    <h3 className="font-semibold text-[#272518] mb-1.5 group-hover:text-[#D49F89] transition-colors text-lg flex items-center gap-2">
                        <span className="flex items-center gap-2 flex-1 min-w-0">
                            <span className="line-clamp-1">{vendor.business_name}</span>
                            <span className={`flex-shrink-0 w-2 h-2 rounded-full ${vendor.is_online ? 'bg-green-500 animate-pulse' : 'bg-gray-400'}`} />
                        </span>
                        {vendor.is_verified && (
                            <svg className="flex-shrink-0 w-4 h-4 text-[#D49F89]" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                            </svg>
                        )}
                    </h3>
                </Link>

                {/* Distance & Location */}
                {(distance || vendor.location?.address) && (
                    <div className="flex items-center gap-2 text-sm text-gray-600 mb-2.5">
                        <svg className="w-4 h-4 text-[#D49F89]" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                        </svg>
                        {distance && <span className="font-medium text-[#D49F89]">{distance}</span>}
                        {vendor.location?.address && (
                            <>
                                {distance && <span className="text-gray-400">•</span>}
                                <span className="truncate">{vendor.location.address.split(',')[0]}</span>
                            </>
                        )}
                    </div>
                )}

                {/* Rating & Reviews */}
                {vendor.rating > 0 && (
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

                {/* Products Count */}
                <div className="flex items-center text-xs text-gray-600 mb-4">
                    <span className="flex items-center gap-1">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        {vendor.products_count} products
                    </span>
                </div>

                {/* Contact Button */}
                {whatsAppHref ? (
                    <a
                        href={whatsAppHref}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="w-full bg-transparent border-2 border-primary-dark text-primary-dark font-semibold py-2.5 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 hover:bg-primary-dark hover:text-white"
                    >
                        <svg className="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.526 5.845L.057 23.882l6.198-1.625A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.68.965.982-3.59-.234-.369A9.818 9.818 0 1112 21.818z"/>
                        </svg>
                        <span className="hidden sm:inline">WhatsApp Vendor</span>
                    </a>
                ) : (
                    <Link
                        href={`/vendors/${vendor.slug}`}
                        className="w-full bg-transparent border-2 border-primary-dark text-primary-dark font-semibold py-2.5 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 hover:bg-primary-dark hover:text-white"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        Contact Vendor
                    </Link>
                )}
            </div>
        </div>
    );
}
