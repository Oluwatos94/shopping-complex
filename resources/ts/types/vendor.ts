import { Vendor } from './user';

// ---------------------------------------------------------------------------
// Vendor Profile page
// ---------------------------------------------------------------------------

/**
 * Lean vendor shape returned by VendorController::show()
 */
export interface VendorProfile {
    id: number;
    slug: string;
    name: string;
    email: string;
    business_name: string;
    business_description?: string;
    business_logo?: string;
    is_verified: boolean;
    created_at: string;
}

/**
 * Stats aggregates returned alongside the vendor profile page
 */
export interface VendorStats {
    products_count: number;
    reviews_count: number;
    average_rating: number;
    followers_count: number;
    plan_product_limit: number | null;
}

// ---------------------------------------------------------------------------
// Subscription
// ---------------------------------------------------------------------------

export interface SubscriptionPlan {
    id: number;
    name: string;
    slug: string;
    price: number;
    product_limit: number;
    search_priority: number;
    features: string[] | null;
    is_active: boolean;
}

export interface VendorSubscription {
    id: number;
    plan_id: number;
    status: 'active' | 'expired' | 'cancelled';
    started_at: string;
    expires_at: string;
    amount_paid: number | null;
    plan: SubscriptionPlan;
}

/**
 * Nearby Vendor type - extends Vendor with distance info
 */
export interface NearbyVendor extends Vendor {
    distance_km: number;
    distance_formatted: string;
}

/**
 * Lean vendor shape returned by CategoryController::vendors()
 */
export interface CategoryVendor {
    id: number;
    name: string;
    slug: string;
    profileImage: string | null;
    products: { id: number; name: string; price: number }[];
}

/**
 * Vendor filters for discovery
 */
export interface VendorFilters {
    latitude?: number;
    longitude?: number;
    radius?: number; // in km
    category_id?: number;
    verified_only?: boolean;
    active_only?: boolean;
    min_rating?: number;
    search?: string;
    sort_by?: VendorSortOption;
}

/**
 * Vendor sorting options
 */
export type VendorSortOption =
    | 'distance'    // Closest first
    | 'rating'      // Highest rated first
    | 'response_time' // Fastest response first
    | 'newest'      // Recently joined
    | 'products_count'; // Most products

/**
 * Paginated vendors response
 */
export interface PaginatedVendors {
    data: NearbyVendor[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

/**
 * User's current location
 */
export interface UserLocation {
    latitude: number;
    longitude: number;
    accuracy?: number;
    timestamp?: number;
}

/**
 * Vendor category
 */
export interface VendorCategory {
    id: number;
    name: string;
    slug: string;
    icon?: string;
    vendors_count: number;
}
