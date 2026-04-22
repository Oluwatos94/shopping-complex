import { useState, useEffect, useCallback } from 'react';
import { Head, router } from '@inertiajs/react';
import { PaginatedVendors, VendorFilters, UserLocation, VendorSortOption } from '@/types';
import { VendorGrid } from '@/components/Vendors';
import AuthenticatedLayout from '@/components/Layout/AuthenticatedLayout';

interface VendorListingProps {
    vendors: PaginatedVendors;
    filters: VendorFilters;
    auth?: {
        user: any;
    };
}

export default function VendorListing({ vendors, filters, auth }: VendorListingProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [radius, setRadius] = useState(filters.radius || 5);
    const [sortBy, setSortBy] = useState<VendorSortOption>(filters.sort_by || 'distance');
    const [userLocation, setUserLocation] = useState<UserLocation | null>(null);
    const [isLoadingLocation, setIsLoadingLocation] = useState(false);

    // Get user's current location
    const getCurrentLocation = () => {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
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

                // Automatically search with new location
                handleSearch({
                    latitude: location.latitude,
                    longitude: location.longitude,
                });
            },
            (error) => {
                console.error('Error getting location:', error);
                alert('Unable to retrieve your location. Please enable location services.');
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
                latitude: userLocation?.latitude,
                longitude: userLocation?.longitude,
                ...additionalFilters,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }, [searchQuery, radius, sortBy, userLocation]);

    // Auto-request location on mount
    useEffect(() => {
        if (!filters.latitude && !filters.longitude) {
            getCurrentLocation();
        } else if (filters.latitude && filters.longitude) {
            setUserLocation({
                latitude: filters.latitude,
                longitude: filters.longitude,
            });
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [filters.latitude, filters.longitude]);

    return (
        <AuthenticatedLayout user={auth?.user} className="!p-0 !max-w-none">
            <Head title="Find Nearby Vendors" />

            <div className="bg-gray-50">
                {/* Sticky Search Bar */}
                <div className="sticky top-[57px] z-10 bg-white border-b border-gray-200 shadow-sm">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                        {/* Top row: title + location status */}
                        <div className="flex items-center justify-between mb-3">
                            <div className="flex items-center gap-3">
                                <a href="/" className="text-gray-400 hover:text-gray-700 transition-colors">
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                    </svg>
                                </a>
                                <h1 className="text-base font-semibold text-gray-900">Nearby Vendors</h1>
                                <span className="text-sm text-gray-400 hidden sm:inline">
                                    {vendors.total} {vendors.total === 1 ? 'vendor' : 'vendors'} found
                                </span>
                            </div>
                            <button
                                onClick={getCurrentLocation}
                                disabled={isLoadingLocation}
                                className="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border transition-all disabled:opacity-50 border-gray-200 text-gray-600 hover:border-[#D49F89] hover:text-[#D49F89]"
                            >
                                {isLoadingLocation ? (
                                    <svg className="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                ) : (
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                )}
                                <span className="hidden sm:inline">{isLoadingLocation ? 'Locating...' : userLocation ? 'Location on' : 'Enable location'}</span>
                                {userLocation && !isLoadingLocation && (
                                    <span className="w-1.5 h-1.5 bg-green-500 rounded-full" />
                                )}
                            </button>
                        </div>

                        {/* Controls row */}
                        <div className="flex gap-2">
                            {/* Search */}
                            <div className="flex-1 max-w-xs relative">
                                <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    onKeyUp={(e) => e.key === 'Enter' && handleSearch()}
                                    placeholder="Search vendors or products..."
                                    className="w-full h-10 pl-9 pr-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#D49F89]/30 focus:border-[#D49F89] transition-all"
                                />
                            </div>

                            {/* Radius */}
                            <select
                                value={radius}
                                onChange={(e) => setRadius(Number(e.target.value))}
                                className="h-10 px-3 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#D49F89]/30 focus:border-[#D49F89] bg-white hidden sm:block"
                            >
                                <option value={1}>1 km</option>
                                <option value={3}>3 km</option>
                                <option value={5}>5 km</option>
                                <option value={10}>10 km</option>
                                <option value={20}>20 km</option>
                            </select>

                            {/* Sort */}
                            <select
                                value={sortBy}
                                onChange={(e) => setSortBy(e.target.value as VendorSortOption)}
                                className="h-10 px-3 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#D49F89]/30 focus:border-[#D49F89] bg-white hidden sm:block"
                            >
                                <option value="distance">Nearest</option>
                                <option value="rating">Top Rated</option>
                                <option value="newest">Newest</option>
                            </select>

                            {/* Search button */}
                            <button
                                onClick={() => handleSearch()}
                                className="h-10 px-5 bg-[#D49F89] hover:bg-[#c48f79] text-[#272518] font-semibold text-sm rounded-lg transition-colors flex items-center gap-1.5 flex-shrink-0"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span className="hidden sm:inline">Search</span>
                            </button>
                        </div>

                        {/* Mobile: radius + sort on second row */}
                        <div className="flex gap-2 mt-2 sm:hidden">
                            <select
                                value={radius}
                                onChange={(e) => setRadius(Number(e.target.value))}
                                className="flex-1 h-9 px-3 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:border-[#D49F89] bg-white"
                            >
                                <option value={1}>1 km</option>
                                <option value={3}>3 km</option>
                                <option value={5}>5 km</option>
                                <option value={10}>10 km</option>
                                <option value={20}>20 km</option>
                            </select>
                            <select
                                value={sortBy}
                                onChange={(e) => setSortBy(e.target.value as VendorSortOption)}
                                className="flex-1 h-9 px-3 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:border-[#D49F89] bg-white"
                            >
                                <option value="distance">Nearest</option>
                                <option value="rating">Top Rated</option>
                                <option value="newest">Newest</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    {/* Mobile results count */}
                    <p className="sm:hidden text-sm text-gray-500 mb-4">
                        {vendors.total} {vendors.total === 1 ? 'vendor' : 'vendors'} found
                    </p>

                    {/* Vendor Grid */}
                    <VendorGrid vendors={vendors.data} isLoading={false} />

                    {/* Pagination */}
                    {vendors.last_page > 1 && (
                        <div className="mt-8 flex justify-center gap-2">
                            {[...Array(vendors.last_page)].map((_, i) => (
                                <button
                                    key={i}
                                    onClick={() => handleSearch({ page: i + 1 })}
                                    className={`w-10 h-10 rounded-lg font-semibold text-sm transition-all ${
                                        vendors.current_page === i + 1
                                            ? 'bg-[#D49F89] text-[#272518]'
                                            : 'bg-white text-gray-700 border border-gray-200 hover:border-[#D49F89]'
                                    }`}
                                >
                                    {i + 1}
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
