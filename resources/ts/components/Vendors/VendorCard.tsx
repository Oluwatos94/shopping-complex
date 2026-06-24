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
        <div className="group flex flex-col overflow-hidden rounded-[20px] border border-brand-line bg-white font-display transition duration-150 hover:-translate-y-1 hover:border-[#D7DCE3] hover:shadow-[0_20px_42px_rgba(11,31,58,0.12)]">
            {/* Vendor image/logo */}
            <Link href={`/vendors/${vendor.slug}`} className="relative aspect-[16/11] overflow-hidden bg-brand-surface">
                <img
                    src={profileImage}
                    alt={vendor.business_name}
                    className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                />
            </Link>

            {/* Vendor info */}
            <div className="flex flex-1 flex-col px-[18px] pb-5 pt-[18px]">
                {/* Business name + online dot + verified */}
                <Link href={`/vendors/${vendor.slug}`} className="mb-3 flex items-center gap-2.5">
                    <span className="line-clamp-1 text-[19px] font-bold leading-tight text-brand-ink transition-colors group-hover:text-brand-green-dark">
                        {vendor.business_name}
                    </span>
                    <span
                        className={`h-[9px] w-[9px] flex-none rounded-full ${
                            vendor.is_online ? 'bg-brand-green shadow-[0_0_0_3px_rgba(37,211,102,0.18)]' : 'bg-brand-line'
                        }`}
                    />
                    {vendor.is_verified && (
                        <svg className="h-4 w-4 flex-none text-brand-green" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                        </svg>
                    )}
                </Link>

                {/* Distance & location */}
                {(distance || vendor.location?.address) && (
                    <div className="mb-2.5 flex items-center gap-2 text-sm text-brand-muted">
                        <svg className="h-4 w-4 text-brand-green" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                        </svg>
                        {distance && <span className="font-semibold text-brand-green-dark">{distance}</span>}
                        {vendor.location?.address && (
                            <>
                                {distance && <span className="text-brand-muted/50">•</span>}
                                <span className="truncate">{vendor.location.address.split(',')[0]}</span>
                            </>
                        )}
                    </div>
                )}

                {/* Rating */}
                {vendor.rating > 0 && (
                    <div className="mb-3 flex items-center gap-2">
                        <div className="flex items-center">
                            {[...Array(5)].map((_, i) => (
                                <svg
                                    key={i}
                                    className={`h-4 w-4 ${i < Math.floor(vendor.rating) ? 'fill-current text-brand-star' : 'text-brand-line'}`}
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            ))}
                        </div>
                        <span className="text-sm font-semibold text-brand-ink">{vendor.rating.toFixed(1)}</span>
                        {vendor.reviews_count && vendor.reviews_count > 0 ? (
                            <span className="text-xs text-brand-muted">({vendor.reviews_count})</span>
                        ) : null}
                    </div>
                )}

                {/* Products count */}
                <div className="mb-[18px] flex items-center gap-2 text-sm font-medium text-brand-muted">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                        <path d="m3.3 7 8.7 5 8.7-5" />
                        <path d="M12 22V12" />
                    </svg>
                    {vendor.products_count} {vendor.products_count === 1 ? 'product' : 'products'}
                </div>

                {/* Contact button */}
                {whatsAppHref ? (
                    <a
                        href={whatsAppHref}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="mt-auto flex h-[52px] items-center justify-center gap-2.5 rounded-[13px] bg-brand-green text-[15px] font-bold text-white transition hover:bg-brand-green-dark"
                    >
                        <svg className="h-5 w-5 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm5.8 14.18c-.24.68-1.42 1.31-1.95 1.36-.5.05-1.13.07-1.83-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.79-4.17-4.94-4.36-.14-.19-1.18-1.57-1.18-2.99 0-1.42.74-2.12 1.01-2.41.26-.29.57-.36.76-.36.19 0 .38 0 .55.01.18.01.41-.07.65.49.24.58.82 2 .89 2.14.07.14.12.31.02.5-.09.19-.14.31-.28.48-.14.17-.29.37-.42.5-.14.14-.28.29-.12.57.16.28.71 1.17 1.53 1.9 1.05.93 1.94 1.23 2.22 1.37.28.14.44.12.6-.07.16-.19.69-.81.88-1.09.18-.28.37-.23.62-.14.25.09 1.6.76 1.87.9.28.14.46.21.53.32.07.12.07.65-.17 1.33Z" />
                        </svg>
                        WhatsApp Vendor
                    </a>
                ) : (
                    <Link
                        href={`/vendors/${vendor.slug}`}
                        className="mt-auto flex h-[52px] items-center justify-center gap-2.5 rounded-[13px] border border-brand-ink text-[15px] font-bold text-brand-ink transition hover:bg-brand-ink hover:text-white"
                    >
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        Contact Vendor
                    </Link>
                )}
            </div>
        </div>
    );
}
