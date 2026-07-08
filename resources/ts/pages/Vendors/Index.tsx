import { useState, useEffect, useCallback, useRef } from 'react';
import { Head, router } from '@inertiajs/react';
import { PaginatedVendors, VendorFilters, UserLocation, VendorSortOption } from '@/types';
import { Category } from '@/types/product';
import { VendorGrid } from '@/components/Vendors';
import Header from '@/components/Header';

interface VendorListingProps {
    vendors: PaginatedVendors;
    filters: VendorFilters;
    categories: Category[];
    auth?: {
        user: any;
    };
}

const VENDOR_BATCH_SIZE = 20;

const radiusOptions = [5, 10, 25, 50];

const sortOptions: { value: VendorSortOption; label: string }[] = [
    { value: 'distance', label: 'Nearest' },
    { value: 'rating', label: 'Top Rated' },
    { value: 'products_count', label: 'Most products' },
    { value: 'newest', label: 'Newest' },
];

export default function VendorListing({ vendors, filters, categories }: VendorListingProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [radius, setRadius] = useState(filters.radius || 5);
    const [sortBy, setSortBy] = useState<VendorSortOption>(filters.sort_by || 'distance');
    const [categoryId, setCategoryId] = useState<number | undefined>(filters.category_id);
    const [userLocation, setUserLocation] = useState<UserLocation | null>(null);
    const [isLoadingLocation, setIsLoadingLocation] = useState(false);
    const [isFiltering, setIsFiltering] = useState(false);
    const [notification, setNotification] = useState<{ type: 'success' | 'error' | 'info'; message: string } | null>(null);
    const [visibleCount, setVisibleCount] = useState(VENDOR_BATCH_SIZE);
    const sentinelRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        setVisibleCount(VENDOR_BATCH_SIZE);
    }, [vendors.current_page]);

    useEffect(() => {
        if (visibleCount >= vendors.data.length) return;
        if (!('IntersectionObserver' in window)) {
            setVisibleCount(vendors.data.length);
            return;
        }
        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0]?.isIntersecting) {
                    setVisibleCount((prev) => Math.min(prev + VENDOR_BATCH_SIZE, vendors.data.length));
                }
            },
            { threshold: 0.1 }
        );
        if (sentinelRef.current) observer.observe(sentinelRef.current);
        return () => observer.disconnect();
    }, [visibleCount, vendors.data.length]);

    const visibleVendors = vendors.data.slice(0, visibleCount);
    const allVendorsRevealed = visibleCount >= vendors.data.length;

    const showNotification = (type: 'success' | 'error' | 'info', message: string) => {
        setNotification({ type, message });
        setTimeout(() => setNotification(null), 5000);
    };

    // Get user's current location
    const getCurrentLocation = () => {
        if (!navigator.geolocation) {
            showNotification('error', 'Geolocation is not supported by your browser.');
            return;
        }

        setIsLoadingLocation(true);
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const location: UserLocation = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: position.timestamp,
                };
                setUserLocation(location);
                setIsLoadingLocation(false);
                showNotification('success', 'Location enabled! Showing vendors near you.');

                // Automatically search with new location
                handleSearch({
                    latitude: location.latitude,
                    longitude: location.longitude,
                });
            },
            (error) => {
                console.error('Error getting location:', error);
                showNotification('error', 'Unable to retrieve your location. Please enable location services in your browser settings.');
                setIsLoadingLocation(false);
            }
        );
    };

    // Handle search with filters
    const handleSearch = useCallback((additionalFilters: Partial<VendorFilters> & { page?: number } = {}) => {
        router.get(
            '/vendors',
            {
                search: searchQuery,
                radius: radius,
                sort_by: sortBy,
                category_id: categoryId,
                latitude: userLocation?.latitude,
                longitude: userLocation?.longitude,
                ...additionalFilters,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onStart: () => setIsFiltering(true),
                onFinish: () => setIsFiltering(false),
            }
        );
    }, [searchQuery, radius, sortBy, categoryId, userLocation]);

    // Restore location from URL filters only (no auto-prompt)
    useEffect(() => {
        if (filters.latitude && filters.longitude) {
            setUserLocation({
                latitude: filters.latitude,
                longitude: filters.longitude,
            });
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [filters.latitude, filters.longitude]);

    return (
        <div className="min-h-screen bg-brand-surface font-display text-brand-ink">
            <Head title="Find Nearby Vendors" />

            <Header />

            {/* Notification banner */}
            {notification && (
                <div
                    className={`fixed left-1/2 top-4 z-[60] mx-4 flex w-full max-w-sm -translate-x-1/2 items-center gap-3 rounded-xl px-4 py-3 shadow-lg ${
                        notification.type === 'success'
                            ? 'border border-brand-green/30 bg-brand-green/10 text-brand-green-dark'
                            : notification.type === 'error'
                              ? 'border border-brand-danger/30 bg-brand-danger/10 text-brand-danger'
                              : 'border border-brand-line bg-white text-brand-ink'
                    }`}
                >
                    {notification.type === 'success' ? (
                        <svg className="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    ) : (
                        <svg className="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    )}
                    <p className="flex-1 text-sm font-medium">{notification.message}</p>
                    <button onClick={() => setNotification(null)} className="flex-shrink-0 opacity-60 transition-opacity hover:opacity-100">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            )}

            <div className="mx-auto max-w-[1380px] px-5 pb-20 pt-8 lg:px-10">
                {/* Page head */}
                <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <button
                            onClick={() => window.history.back()}
                            className="flex h-11 w-11 flex-none items-center justify-center rounded-full border border-brand-line bg-white text-brand-ink transition hover:bg-brand-surface"
                            aria-label="Go back"
                        >
                            <svg className="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                <path d="M15 18l-6-6 6-6" />
                            </svg>
                        </button>
                        <h1 className="font-serif text-[26px] font-bold tracking-tight sm:text-[34px]">Nearby Vendors</h1>
                        <span className="hidden text-[15px] font-medium text-brand-muted sm:inline">
                            {vendors.total} {vendors.total === 1 ? 'vendor' : 'vendors'} found
                        </span>
                    </div>
                    <button
                        onClick={getCurrentLocation}
                        disabled={isLoadingLocation}
                        className={`inline-flex items-center gap-2.5 rounded-full border px-5 py-2.5 text-[15px] font-semibold transition disabled:opacity-50 ${
                            userLocation
                                ? 'border-brand-green bg-brand-green/10 text-brand-green-dark'
                                : 'border-brand-line bg-white text-brand-ink hover:border-brand-green'
                        }`}
                    >
                        {isLoadingLocation ? (
                            <svg className="h-[17px] w-[17px] animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        ) : (
                            <svg className="h-[17px] w-[17px] text-brand-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                                <circle cx="12" cy="10" r="2.6" />
                            </svg>
                        )}
                        {isLoadingLocation ? 'Locating...' : 'Near Me'}
                    </button>
                </div>

                {/* Search row */}
                <div className="mb-5 flex flex-wrap gap-3.5">
                    <div className="relative min-w-[260px] flex-1">
                        <svg className="absolute left-5 top-1/2 -translate-y-1/2" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#98A2B3" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                            <circle cx="11" cy="11" r="7" />
                            <path d="M21 21l-4.3-4.3" />
                        </svg>
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            onKeyUp={(e) => e.key === 'Enter' && handleSearch()}
                            placeholder="Search vendors or products..."
                            className="h-14 w-full rounded-[14px] border border-brand-line bg-white pl-[52px] pr-5 text-base text-brand-ink outline-none transition focus:border-brand-green focus:ring-2 focus:ring-brand-green/20"
                        />
                    </div>

                    {/* Radius */}
                    <div className="relative w-[calc(50%-7px)] sm:w-[150px]">
                        <select
                            value={radius}
                            onChange={(e) => {
                                const newRadius = Number(e.target.value);
                                setRadius(newRadius);
                                handleSearch({ radius: newRadius });
                            }}
                            className="h-14 w-full appearance-none rounded-[14px] border border-brand-line bg-white pl-[18px] pr-10 text-base font-medium text-brand-ink outline-none transition focus:border-brand-green focus:ring-2 focus:ring-brand-green/20"
                        >
                            {radiusOptions.map((km) => (
                                <option key={km} value={km}>{km} km</option>
                            ))}
                        </select>
                        <svg className="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#667085" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                            <path d="M6 9l6 6 6-6" />
                        </svg>
                    </div>

                    {/* Sort */}
                    <div className="relative w-[calc(50%-7px)] sm:w-[180px]">
                        <select
                            value={sortBy}
                            onChange={(e) => {
                                const newSort = e.target.value as VendorSortOption;
                                setSortBy(newSort);
                                handleSearch({ sort_by: newSort });
                            }}
                            className="h-14 w-full appearance-none rounded-[14px] border border-brand-line bg-white pl-[18px] pr-10 text-base font-medium text-brand-ink outline-none transition focus:border-brand-green focus:ring-2 focus:ring-brand-green/20"
                        >
                            {sortOptions.map((option) => (
                                <option key={option.value} value={option.value}>{option.label}</option>
                            ))}
                        </select>
                        <svg className="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#667085" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                            <path d="M6 9l6 6 6-6" />
                        </svg>
                    </div>

                    {/* Search button */}
                    <button
                        onClick={() => handleSearch()}
                        className="inline-flex h-14 w-full items-center justify-center gap-2.5 rounded-[14px] bg-brand-ink px-7 text-base font-bold text-white transition hover:bg-brand-ink/90 sm:w-auto"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                            <circle cx="11" cy="11" r="7" />
                            <path d="M21 21l-4.3-4.3" />
                        </svg>
                        Search
                    </button>
                </div>

                {/* Category chips */}
                {categories.length > 0 && (
                    <div className="mb-8 flex gap-2.5 overflow-x-auto pb-1.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <button
                            onClick={() => { setCategoryId(undefined); handleSearch({ category_id: undefined }); }}
                            className={`inline-flex flex-shrink-0 items-center whitespace-nowrap rounded-full border px-[18px] py-2.5 text-sm font-semibold transition ${
                                !categoryId
                                    ? 'border-brand-ink bg-brand-ink text-white'
                                    : 'border-brand-line bg-white text-brand-muted hover:border-brand-ink/30'
                            }`}
                        >
                            All
                        </button>
                        {categories.map((cat) => {
                            const active = categoryId === cat.id;
                            return (
                                <button
                                    key={cat.id}
                                    onClick={() => { setCategoryId(cat.id); handleSearch({ category_id: cat.id }); }}
                                    className={`inline-flex flex-shrink-0 items-center whitespace-nowrap rounded-full border px-[18px] py-2.5 text-sm font-semibold transition ${
                                        active
                                            ? 'border-brand-ink bg-brand-ink text-white'
                                            : 'border-brand-line bg-white text-brand-muted hover:border-brand-ink/30'
                                    }`}
                                >
                                    {cat.name}
                                    <span className={`ml-1.5 font-semibold ${active ? 'text-white/65' : 'text-brand-muted/60'}`}>
                                        ({cat.vendors_count})
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                )}

                {/* Vendor grid */}
                <VendorGrid vendors={visibleVendors} isLoading={isFiltering} />

                {/* Sentinel for progressive reveal */}
                {!allVendorsRevealed && (
                    <div ref={sentinelRef} className="mt-8 flex justify-center">
                        <div className="flex items-center gap-2 text-sm text-brand-muted">
                            <svg className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Loading more vendors…
                        </div>
                    </div>
                )}

                {/* Pagination */}
                {allVendorsRevealed && vendors.last_page > 1 && (
                    <div className="mt-10 flex items-center justify-center gap-2">
                        <button
                            onClick={() => handleSearch({ page: vendors.current_page - 1 })}
                            disabled={vendors.current_page === 1}
                            className="rounded-full border border-brand-line bg-white px-4 py-2 text-sm font-semibold text-brand-ink transition hover:bg-brand-surface disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Previous
                        </button>

                        {[...Array(vendors.last_page)].map((_, i) => {
                            const page = i + 1;
                            const isCurrentPage = vendors.current_page === page;
                            const showPage =
                                page === 1 ||
                                page === vendors.last_page ||
                                (page >= vendors.current_page - 1 && page <= vendors.current_page + 1);

                            if (!showPage) {
                                if (page === vendors.current_page - 2 || page === vendors.current_page + 2) {
                                    return <span key={page} className="px-1 text-brand-muted">…</span>;
                                }
                                return null;
                            }

                            return (
                                <button
                                    key={page}
                                    onClick={() => handleSearch({ page })}
                                    className={`h-10 w-10 rounded-full text-sm font-semibold transition ${
                                        isCurrentPage
                                            ? 'border border-brand-ink bg-brand-ink text-white'
                                            : 'border border-brand-line bg-white text-brand-ink hover:bg-brand-surface'
                                    }`}
                                >
                                    {page}
                                </button>
                            );
                        })}

                        <button
                            onClick={() => handleSearch({ page: vendors.current_page + 1 })}
                            disabled={vendors.current_page === vendors.last_page}
                            className="rounded-full border border-brand-line bg-white px-4 py-2 text-sm font-semibold text-brand-ink transition hover:bg-brand-surface disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Next
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}
