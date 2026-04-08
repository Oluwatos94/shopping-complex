import { Product } from './product';
import { Vendor, Customer } from './user';

/**
 * Payment status
 */
export type PaymentStatus =
    | 'pending'
    | 'paid'
    | 'failed'
    | 'refunded'
    | 'partially_refunded';

/**
 * Notification type
 */
export interface Notification {
    id: number;
    user_id: number;
    type: NotificationType;
    title: string;
    message: string;
    data?: Record<string, any>;
    read_at: string | null;
    created_at: string;
}

/**
 * Notification types
 */
export type NotificationType =
    | 'order_placed'
    | 'order_confirmed'
    | 'order_shipped'
    | 'order_delivered'
    | 'payment_success'
    | 'payment_failed'
    | 'vendor_message'
    | 'product_review'
    | 'system_alert';

/**
 * Laravel paginator shape (returned by ->paginate() via Inertia)
 */
export interface LaravelPaginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    next_page_url: string | null;
    prev_page_url: string | null;
}

/**
 * Pagination meta
 */
export interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
}

/**
 * Paginated response
 */
export interface PaginatedResponse<T> {
    data: T[];
    meta: PaginationMeta;
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
}

/**
 * API response wrapper
 */
export interface ApiResponse<T = any> {
    success: boolean;
    message?: string;
    data?: T;
    errors?: Record<string, string[]>;
}

/**
 * Form validation errors
 */
export interface ValidationErrors {
    [key: string]: string | string[];
}

/**
 * Chat message for vendor-customer communication
 */
export interface ChatMessage {
    id: number;
    conversation_id: number;
    sender_id: number;
    sender_type: 'customer' | 'vendor';
    message: string;
    is_read: boolean;
    created_at: string;
}

/**
 * Conversation between customer and vendor
 */
export interface Conversation {
    id: number;
    customer_id: number;
    vendor_id: number;
    customer: Customer;
    vendor: Vendor;
    last_message?: ChatMessage;
    unread_count: number;
    created_at: string;
    updated_at: string;
}

/**
 * Media/File upload type
 */
export interface Media {
    id: number;
    filename: string;
    original_filename: string;
    mime_type: string;
    size: number;
    url: string;
    thumbnail_url?: string;
    created_at: string;
}

/**
 * Search result
 */
export interface SearchResult {
    products: Product[];
    vendors: Vendor[];
    categories: any[];
    total_results: number;
}
