
export interface BaseUser {
    id: number;
    slug?: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface User extends BaseUser {
    role: UserRole;
    profile_image?: string;
    phone?: string;
}

export interface Customer extends BaseUser {
    role: 'customer';
    address?: Address;
    orders_count?: number;
    total_spent?: number;
    loyalty_points?: number;
}

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
    whatsapp_number?: string | null;
}

export interface Admin extends BaseUser {
    role: 'admin';
    permissions: AdminPermission[];
    last_login_at?: string;
}

export type UserRole = 'customer' | 'vendor' | 'admin';

export type AdminPermission =
    | 'manage_users'
    | 'manage_vendors'
    | 'manage_products'
    | 'manage_orders'
    | 'manage_categories'
    | 'view_analytics'
    | 'manage_settings';

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

export interface VendorLocation {
    latitude: number;
    longitude: number;
    address: string;
    updated_at: string;
}

export type AdminUser = Pick<User, 'id' | 'name' | 'email' | 'role' | 'created_at'> & {
    business_name?: string | null;
};

export interface StellarTransaction {
    kind: 'deposit' | 'mpp_charge';
    amount: number;
    billing_period: string | null;
    hash: string;
    completed_at: string | null;
}

export interface AdminSubscription {
    id: number;
    vendor: { id: number; name: string; business_name: string | null; email: string } | null;
    plan: { id: number; name: string; price: number } | null;
    payment_method: 'stellar'; // | 'paystack'
    amount_paid: number | null;
    status: string;
    expires_at: string | null;
    created_at: string;
    payment_reference: string | null;
    stellar_transactions: StellarTransaction[];
}

export interface UserSummary {
    users: { total: number; admins: number; vendors: number; customers: number };
    vendors: { approved: number; pending_review: number; rejected: number; draft: number };
    products: { total: number };
}

export interface AuthUser {
    user: User | Customer | Vendor | Admin | null;
    isAuthenticated: boolean;
    isLoading: boolean;
}

export interface LoginCredentials {
    email: string;
    password: string;
    remember?: boolean;
}

export interface RegistrationData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role: 'customer' | 'vendor';
    business_name?: string; // Required for vendors
}
