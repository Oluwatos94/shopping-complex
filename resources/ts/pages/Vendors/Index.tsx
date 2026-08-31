import { useState, useEffect, useCallback, useRef } from 'react';
import { Head, router } from '@inertiajs/react';
import { PaginatedVendors, VendorFilters, VendorSortOption } from '@/types';
import { Category } from '@/types/product';
import { SearchOrigin, isSameCoordinate, isUsableAccuracy, originQueryParams } from '@/utils/geolocation';
import { useSearchOrigin } from '@/hooks/useSearchOrigin';
import { LocationField } from '@/components/Location';
import { VendorGrid } from '@/components/Vendors';
import Header from '@/components/Header';
import Footer from '@/components/Footer';

interface VendorListingProps {
    vendors: PaginatedVendors;
    filters: VendorFilters;
    categories: Category[];
    auth?: {
        user: any;
    };
}

const VENDOR_BATCH_SIZE = 20;

const radiusOptions = [5, 10, 20, 30];

const sortOptions: { value: VendorSortOption; label: string }[] = [
    { value: 'distance', label: 'Nearest' },
    { value: 'rating', label: 'Top Rated' },
    { value: 'products_count', label: 'Most products' },
    { value: 'newest', label: 'Newest' },
];

function originFromFilters(filters: VendorFilters): SearchOrigin | null {
    if (filters.latitude == null || filters.longitude == null) return null;

    return {
        latitude: filters.latitude,
        longitude: filters.longitude,
        accuracy: filters.accuracy ?? null,
        source: 'device',
    };
}

export default function VendorListing({ vendors, filters, categories }: VendorListingProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [radius, setRadius] = useState(filters.radius || 5);
    const [sortBy, setSortBy] = useState<VendorSortOption>(filters.sort_by || 'distance');
    const [categoryId, setCategoryId] = useState<number | undefined>(filters.category_id);
    const { origin, originRef, locateDevice, confirm, clear, adoptDeviceOrigin } =
        useSearchOrigin(originFromFilters(filters));
    const [isFiltering, setIsFiltering] = useState(false);
    const [notification, setNotification] = useState<{ type: 'success' | 'error' | 'info'; message: string } | null>(null);
    const [visibleCount, setVisibleCount] = useState(VENDOR_BATCH_SIZE);
    const sentinelRef = useRef<HTMLDivElement>(null);
    const notificationTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const appliedConfirmedOrigin = useRef(false);

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

    const showNotification = useCallback((type: 'success' | 'error' | 'info', message: string) => {
        setNotification({ type, message });

        if (notificationTimer.current) clearTimeout(notificationTimer.current);
        notificationTimer.current = setTimeout(() => setNotification(null), 5000);
    }, []);

    useEffect(() => {
        return () => {
            if (notificationTimer.current) clearTimeout(notificationTimer.current);
        };
    }, []);

    // Handle search with filters
    const handleSearch = useCallback((additionalFilters: Partial<VendorFilters> & { page?: number } = {}) => {
        router.get(
            '/vendors',
            {
                search: searchQuery,
                radius: radius,
                sort_by: sortBy,
                category_id: categoryId,
                ...originQueryParams(originRef.current),
                ...additionalFilters,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onStart: () => setIsFiltering(true),
                onFinish: () => setIsFiltering(false),

                onCancel: () => { appliedConfirmedOrigin.current = false; },
                onError: (errors) => {
                    appliedConfirmedOrigin.current = false;

                    const first = Object.values(errors)[0];
                    showNotification('error', first || 'We could not apply that filter. Please try again.');
                },
            }
        );
    }, [searchQuery, radius, sortBy, categoryId, originRef, showNotification]);

    const handleConfirmOrigin = useCallback((latitude: number, longitude: number, label?: string) => {
        confirm(latitude, longitude, label);
        showNotification('success', `Distances are now measured from ${label ?? 'the place you picked'}.`);
        // Claim the one-shot below; it would otherwise repeat this search.
        appliedConfirmedOrigin.current = true;
        handleSearch({ page: 1 });
    }, [confirm, handleSearch, showNotification]);

    const handleUseDevice = useCallback(() => {
        return locateDevice().then((next) => {
            if (next === null) return;

            if (!isUsableAccuracy(next.accuracy)) {
                // A coarse fix quietly empties a tight radius; say so.
                showNotification('info', 'Your device gave a rough position. Type your area for exact distances.');
            }

            handleSearch({ page: 1 });
        });
    }, [locateDevice, handleSearch, showNotification]);

    const handleClearOrigin = useCallback(() => {
        clear();
        handleSearch({ page: 1 });
    }, [clear, handleSearch]);

    const didMountSearch = useRef(false);
    useEffect(() => {
        if (!didMountSearch.current) {
            didMountSearch.current = true;
            return;
        }

        const timer = setTimeout(() => {
            if (searchQuery !== (filters.search || '')) {
                handleSearch({ search: searchQuery, page: 1 });
            }
        }, 350);

        return () => clearTimeout(timer);
    }, [searchQuery]);

    // Restore location from URL filters only (no auto-prompt)
    useEffect(() => {
        adoptDeviceOrigin(originFromFilters(filters));
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [filters.latitude, filters.longitude, filters.accuracy]);

    // A pin confirmed on another page lands with the list already built from the
    // browser fix. Re-run once.
    useEffect(() => {
        if (appliedConfirmedOrigin.current || origin === null || origin.source !== 'confirmed') return;

        appliedConfirmedOrigin.current = true;

        const alreadyApplied = isSameCoordinate(originFromFilters(filters), origin) && filters.accuracy == null;
        if (alreadyApplied) return;

        handleSearch({ page: 1 });
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [origin]);

    return (
        <div className="flex min-h-screen flex-col bg-brand-surface font-display text-brand-ink">
            <Head title="Vendors" />

            <Header />

            {notification && (
                <div className="pointer-events-none fixed inset-x-0 top-20 z-[60] flex justify-center px-4 sm:top-24">
                    <div
                        role="status"
                        aria-live="polite"
                        className={`pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-xl border bg-white px-4 py-3 shadow-lg shadow-brand-ink/10 animate-dropdown-in ${
                            notification.type === 'success'
                                ? 'border-brand-green/40'
                                : notification.type === 'error'
                                  ? 'border-brand-danger/40'
                                  : 'border-brand-line'
                        }`}
                    >
                        {notification.type === 'success' ? (
                            <svg className="mt-0.5 h-5 w-5 flex-shrink-0 text-brand-green-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        ) : (
                            <svg
                                className={`mt-0.5 h-5 w-5 flex-shrink-0 ${notification.type === 'error' ? 'text-brand-danger' : 'text-brand-muted'}`}
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}
                            >
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        )}
                        <p className="flex-1 text-sm font-medium leading-snug text-brand-ink">{notification.message}</p>
                        <button
                            type="button"
                            onClick={() => setNotification(null)}
                            aria-label="Dismiss notification"
                            className="-m-1 flex-shrink-0 rounded-md p-1 text-brand-muted transition-colors hover:bg-brand-surface hover:text-brand-ink"
                        >
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            )}

            <main className="mx-auto w-full max-w-[1380px] flex-1 px-5 pb-20 pt-8 lg:px-10">
                {/* Page head */}
                <div className="mb-6 flex items-center gap-4">
                    <button
                        onClick={() => window.history.back()}
                        className="flex h-11 w-11 flex-none items-center justify-center rounded-full border border-brand-line bg-white text-brand-ink transition hover:bg-brand-surface"
                        aria-label="Go back"
                    >
                        <svg className="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>
                    <h1 className="font-serif text-[26px] font-bold tracking-tight sm:text-[34px]">Vendors</h1>
                    <span className="hidden text-[15px] font-medium text-brand-muted sm:inline">
                        {vendors.total} {vendors.total === 1 ? 'vendor' : 'vendors'} found
                    </span>
                </div>

                {/* Search row: what you are looking for, and where you are looking from. */}
                <div className="mb-6 flex flex-col gap-2 rounded-2xl border border-brand-line bg-white p-2 shadow-sm lg:flex-row lg:items-center">
                    <div className="flex flex-1 flex-col divide-y divide-brand-line lg:flex-row lg:items-center lg:divide-x lg:divide-y-0">
                        <div className="relative min-w-0 flex-1">
                            <svg className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#98A2B3" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                <circle cx="11" cy="11" r="7" />
                                <path d="M21 21l-4.3-4.3" />
                            </svg>
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                onKeyUp={(e) => e.key === 'Enter' && handleSearch()}
                                placeholder="Search vendors or products…"
                                className="h-12 w-full rounded-xl bg-transparent pl-11 pr-4 text-[15px] text-brand-ink outline-none placeholder:text-brand-muted focus-visible:ring-2 focus-visible:ring-brand-green/25"
                            />
                        </div>

                        <LocationField
                            origin={origin}
                            onConfirm={handleConfirmOrigin}
                            onUseDevice={handleUseDevice}
                            onClear={handleClearOrigin}
                            className="flex-1 lg:max-w-[300px]"
                        />

                        <div className="flex divide-x divide-brand-line">
                            <div className="relative flex-1 lg:w-[104px] lg:flex-none">
                                <select
                                    value={radius}
                                    onChange={(e) => {
                                        const newRadius = Number(e.target.value);
                                        setRadius(newRadius);
                                        handleSearch({ radius: newRadius });
                                    }}
                                    aria-label="Search radius"
                                    className="h-12 w-full appearance-none rounded-xl bg-transparent pl-4 pr-8 text-sm font-medium text-brand-ink outline-none focus-visible:ring-2 focus-visible:ring-brand-green/25"
                                >
                                    {radiusOptions.map((km) => (
                                        <option key={km} value={km}>{km} km</option>
                                    ))}
                                </select>
                                <svg className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#667085" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </div>

                            <div className="relative flex-1 lg:w-[148px] lg:flex-none">
                                <select
                                    value={sortBy}
                                    onChange={(e) => {
                                        const newSort = e.target.value as VendorSortOption;
                                        setSortBy(newSort);
                                        handleSearch({ sort_by: newSort });
                                    }}
                                    aria-label="Sort vendors"
                                    className="h-12 w-full appearance-none rounded-xl bg-transparent pl-4 pr-8 text-sm font-medium text-brand-ink outline-none focus-visible:ring-2 focus-visible:ring-brand-green/25"
                                >
                                    {sortOptions.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                                <svg className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#667085" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <button
                        onClick={() => handleSearch()}
                        className="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-ink px-6 text-[15px] font-bold text-white transition hover:bg-brand-ink/90"
                    >
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
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
            </main>

            <Footer />
        </div>
    );
}
