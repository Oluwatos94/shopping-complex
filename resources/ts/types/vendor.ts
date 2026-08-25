import { Paginated } from './product';
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
    banner_image?: string | null;
    is_verified: boolean;
    created_at: string;
    whatsapp_number?: string | null;
    address?: string | null;
    city?: string | null;
    state?: string | null;
    latitude?: number | null;
    longitude?: number | null;
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
// Admin — Vendor Applications
// ---------------------------------------------------------------------------

/**
 * Minimal user shape embedded in a vendor KYC application.
 */
export type VendorApplicationUser = Pick<Vendor, 'id' | 'name' | 'email'> & {
    business_name?: string | null;
};

/**
 * Full KYC / onboarding application submitted by a prospective vendor.
 */
export interface VendorApplication {
    id: number;
    user_id: number;
    user: VendorApplicationUser;
    legal_entity_name: string | null;
    business_category: string | null;
    tax_identification_number: string | null;
    physical_address: string | null;
    bank_name: string | null;
    bank_branch: string | null;
    account_number: string | null;
    certificate_of_incorporation: string | null;
    government_issued_id: string | null;
    proof_of_address: string | null;
    status: string;
    current_step: number;
    agreed_to_terms: boolean;
    rejection_reason: string | null;
    products_count?: number;
    created_at: string;
    reviewed_at: string | null;
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

/** Mirrors the backend PaymentMethodEnum (`paystack` | `stellar`). */
export type PaymentMethod = 'paystack' | 'stellar';

export interface VendorSubscription {
    id: number;
    plan_id: number;
    status: 'active' | 'expired' | 'cancelled';
    started_at: string;
    expires_at: string;
    amount_paid: number | null;
    plan: SubscriptionPlan;
}

export interface AutoRenewState {
    enabled: boolean;
    monthlyCap: number | null;
    validUntil: string | null;
}

/**
 * Nearby Vendor type - extends Vendor with distance info
 */
export interface NearbyVendor extends Vendor {
    distance_km: number | null;
    distance_formatted: string | null;
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
    accuracy?: number;
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

export interface ReferredUser {
    name: string;
    joined_at: string;
    products_count: number;
}
export interface Referral {
    code: string | null;
    link: string | null;
    count: number;
    referred_count: number;
    min_products: number;
    recent: ReferredUser[];
}

export interface LeaderboardEntry {
    rank: number;
    name: string;
    referral_count: number;
    is_you: boolean;
}

export interface ReferralLeaderboardProps {
    total_participants: number;
    top: LeaderboardEntry[];
    my_rank: number | null;
    my_referral_count: number;
}

// ---------------------------------------------------------------------------
// Vendor sidebar
// ---------------------------------------------------------------------------

/** One nav entry in the vendor sidebar. `exact` opts out of prefix matching. */
export interface SidebarItem {
    label: string;
    href: string;
    icon: React.ReactNode;
    exact?: boolean;
}

/** Inertia shared props the sidebar reads when the page passes no overrides. */
export interface SidebarPageProps {
    [key: string]: unknown;
    auth?: {
        user?: {
            id: number;
            slug?: string;
            name: string;
            email: string;
            role: string;
            business_name?: string;
            business_logo?: string | null;
        } | null;
    };
}

export interface VendorSidebarProps {
    businessName?: string;
    businessLogo?: string | null;
}

export interface SidebarContentProps {
    items: SidebarItem[];
    currentPath: string;
    name: string;
    email: string;
    logo: string | null;
    onSignOut: () => void;
    onNavigate?: () => void;
}

export interface CampaignParticipant {
    user_id: number;
    name: string;
    account_name: string;
    email: string;
    referral_count: number;
    referred_count: number;
    rank: number;
    joined_at: string | null;
}

export interface CampaignParticipantDetail extends VendorApplication {
    referral_count: number;
    referred_count: number;
    min_products: number;
    rank: number | null;
    referrals: ReferredUser[];
}

export interface ReferralParticipantsProps {
    total_participants: number;
    participants: Paginated<CampaignParticipant>;
    search: string;
}
