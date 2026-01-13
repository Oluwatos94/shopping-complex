import { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import { PaginatedVendors, VendorFilters, UserLocation } from '@/types';
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
    const [sortBy, setSortBy] = useState(filters.sort_by || 'distance');
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
    const handleSearch = (additionalFilters: Partial<VendorFilters> = {}) => {
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
    };

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
    }, []);

    return (
        <AuthenticatedLayout user={auth?.user}>
            <Head title="Find Nearby Vendors" />

            <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Header */}
                    <div className="mb-10">
                        <h1 className="text-4xl font-bold text-gray-900 mb-3 tracking-tight">
                            Discover Nearby Vendors
                        </h1>
                        <p className="text-lg text-gray-600">
                            Find vendors selling what you need, right in your area
                        </p>
                    </div>

                    {/* Search & Filters Bar */}
                    <div className="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-10">
                        <div className="grid grid-cols-1 md:grid-cols-12 gap-4">
                            {/* Search Input */}
                            <div className="md:col-span-5">
                                <label htmlFor="search" className="block text-sm font-semibold text-gray-700 mb-2">
                                    Search Vendors or Products
                                </label>
                                <div className="relative">
                                    <input
                                        type="text"
                                        id="search"
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        onKeyUp={(e) => e.key === 'Enter' && handleSearch()}
                                        placeholder="e.g., Electronics, Fashion, Food..."
                                        className="w-full pl-11 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#D49F89] focus:border-[#D49F89] transition-all duration-200 shadow-sm hover:border-gray-400"
                                    />
                                    <svg
                                        className="absolute left-3.5 top-3.5 w-5 h-5 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                </div>
                            </div>

                            {/* Radius Slider */}
                            <div className="md:col-span-3">
                                <label htmlFor="radius" className="block text-sm font-semibold text-gray-700 mb-2">
                                    Search Radius: <span className="text-[#D49F89]">{radius} km</span>
                                </label>
                                <input
                                    type="range"
                                    id="radius"
                                    min="1"
                                    max="20"
                                    step="1"
                                    value={radius}
                                    onChange={(e) => setRadius(Number(e.target.value))}
                                    className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-[#D49F89]"
                                />
                                <div className="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>1km</span>
                                    <span>20km</span>
                                </div>
                            </div>

                            {/* Sort By */}
                            <div className="md:col-span-2">
                                <label htmlFor="sort" className="block text-sm font-semibold text-gray-700 mb-2">
                                    Sort By
                                </label>
                                <select
                                    id="sort"
                                    value={sortBy}
                                    onChange={(e) => setSortBy(e.target.value as any)}
                                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#D49F89] focus:border-[#D49F89] transition-all duration-200 shadow-sm hover:border-gray-400 bg-white"
                                >
                                    <option value="distance">Distance</option>
                                    <option value="rating">Rating</option>
                                    <option value="response_time">Response Time</option>
                                    <option value="newest">Newest</option>
                                </select>
                            </div>

                            {/* Search Button */}
                            <div className="md:col-span-2 flex items-end">
                                <button
                                    onClick={() => handleSearch()}
                                    className="w-full bg-[#D49F89] hover:bg-[#c48f79] text-[#272518] font-semibold py-3 px-6 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                                >
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                    Search
                                </button>
                            </div>
                        </div>

                        {/* Location Status */}
                        <div className="mt-6 pt-4 border-t border-gray-200 flex items-center justify-between text-sm">
                            <div className="flex items-center gap-2">
                                {userLocation ? (
                                    <>
                                        <svg className="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fillRule="evenodd"
                                                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                        <span className="text-gray-700">Location enabled</span>
                                    </>
                                ) : (
                                    <>
                                        <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                            />
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                        </svg>
                                        <span className="text-gray-500">Location not set</span>
                                    </>
                                )}
                            </div>
                            <button
                                onClick={getCurrentLocation}
                                disabled={isLoadingLocation}
                                className="text-[#D49F89] hover:text-[#c48f79] font-semibold flex items-center gap-1.5 disabled:opacity-50 transition-all duration-200 hover:gap-2"
                            >
                                {isLoadingLocation ? (
                                    <>
                                        <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path
                                                className="opacity-75"
                                                fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                            ></path>
                                        </svg>
                                        Getting location...
                                    </>
                                ) : (
                                    <>
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                            />
                                        </svg>
                                        Update Location
                                    </>
                                )}
                            </button>
                        </div>
                    </div>

                    {/* Results Count */}
                    <div className="mb-8">
                        <p className="text-base text-gray-600">
                            Found <span className="font-bold text-gray-900 text-lg">{vendors.total}</span> vendor
                            {vendors.total !== 1 && 's'} near you
                        </p>
                    </div>

                    {/* Vendor Grid */}
                    <VendorGrid vendors={vendors.data} isLoading={false} />

                    {/* Pagination */}
                    {vendors.last_page > 1 && (
                        <div className="mt-10 flex justify-center gap-2">
                            {[...Array(vendors.last_page)].map((_, i) => (
                                <button
                                    key={i}
                                    onClick={() => handleSearch({ ...filters, page: i + 1 } as any)}
                                    className={`px-5 py-2.5 rounded-lg font-semibold transition-all duration-200 shadow-sm ${
                                        vendors.current_page === i + 1
                                            ? 'bg-[#D49F89] text-[#272518] shadow-md hover:bg-[#c48f79]'
                                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 hover:border-[#D49F89]'
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
