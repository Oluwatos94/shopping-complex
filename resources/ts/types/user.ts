/**
 * Base User type that all user types extend from
 */
export interface BaseUser {
    id: number;
    slug?: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

/**
 * User type for authenticated users
 */
export interface User extends BaseUser {
    role: UserRole;
    profile_image?: string;
    phone?: string;
}

/**
 * Customer-specific type
 */
export interface Customer extends BaseUser {
    role: 'customer';
    address?: Address;
    orders_count?: number;
    total_spent?: number;
    loyalty_points?: number;
}

/**
 * Vendor-specific type
 */
export interface Vendor extends BaseUser {
    role: 'vendor';
    business_name: string;
    business_description?: string;
    business_logo?: string;
    rating: number;
    reviews_count: number;
    products_count: number;
    is_verified: boolean;
    is_online: boolean;
    available_hours?: string | null;
    location?: VendorLocation;
}

/**
 * Admin-specific type
 */
export interface Admin extends BaseUser {
    role: 'admin';
    permissions: AdminPermission[];
    last_login_at?: string;
}

/**
 * User roles enum
 */
export type UserRole = 'customer' | 'vendor' | 'admin';

/**
 * Admin permissions
 */
export type AdminPermission =
    | 'manage_users'
    | 'manage_vendors'
    | 'manage_products'
    | 'manage_orders'
    | 'manage_categories'
    | 'view_analytics'
    | 'manage_settings';

/**
 * Address type for customer addresses
 */
export interface Address {
    id: number;
    user_id: number;
    street: string;
    city: string;
    state: string;
    country: string;
    postal_code: string;
    is_default: boolean;
    created_at: string;
    updated_at: string;
}

/**
 * Vendor location for real-time tracking
 */
export interface VendorLocation {
    latitude: number;
    longitude: number;
    address: string;
    updated_at: string;
}

/**
 * Admin user listing row (id, name, email, role, created_at + optional business_name for vendors)
 */
export type AdminUser = Pick<User, 'id' | 'name' | 'email' | 'role' | 'created_at'> & {
    business_name?: string | null;
};

/**
 * Summary stats shown on the admin users page
 */
export interface UserSummary {
    users: { total: number; admins: number; vendors: number; customers: number };
    vendors: { approved: number; pending_review: number; rejected: number; draft: number };
    products: { total: number };
}

/**
 * User authentication state
 */
export interface AuthUser {
    user: User | Customer | Vendor | Admin | null;
    isAuthenticated: boolean;
    isLoading: boolean;
}

/**
 * Login credentials
 */
export interface LoginCredentials {
    email: string;
    password: string;
    remember?: boolean;
}

/**
 * Registration data
 */
export interface RegistrationData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role: 'customer' | 'vendor';
    business_name?: string; // Required for vendors
}
