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
 * Notification type identifiers — must match config/notifications.php keys
 * and the broadcastAs() suffix in BaseNotificationEvent.
 */
export type NotificationType =
    | 'message_received'
    | 'vendor_contact_request'
    | 'review_received'
    | 'system_alert'
    | string; // allow future types without breaking

/**
 * Raw notification shape returned by GET /api/notifications
 */
export interface RawNotification {
    id: string;
    type: NotificationType;
    message: string;
    data: Record<string, unknown> | null;
    read: boolean;
    created_at: string;
    group_count: number;
    is_grouped: boolean;
}

/**
 * Reverb broadcast event payload (broadcastWith() on BaseNotificationEvent)
 */
export interface BroadcastPayload {
    type: NotificationType;
    message: string;
    data?: Record<string, unknown>;
    created_at: string;
    id?: string;
}

/**
 * Notification shape used by the bell, dropdown, and item components
 */
export interface Notification {
    id: string;
    type: NotificationType;
    title: string;
    body: string;
    timestamp: Date;
    read: boolean;
    actionUrl?: string;
    groupCount?: number;
    isGrouped?: boolean;
}

/**
 * DB-level notification record (matches the Notification Eloquent model)
 */
export interface NotificationRecord {
    id: number;
    user_id: number;
    type: NotificationType;
    message: string;
    data?: Record<string, unknown>;
    read_at: string | null;
    group_key?: string | null;
    is_grouped: boolean;
    group_count: number;
    created_at: string;
    updated_at: string;
}

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
 * OpenStreetMap Nominatim address suggestion (filtered to Nigeria)
 */
export interface AddressSuggestion {
    display_name: string;
    lat: string;
    lon: string;
    address: {
        road?: string;
        neighbourhood?: string;
        suburb?: string;
        city?: string;
        town?: string;
        village?: string;
        state?: string;
        country?: string;
    };
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
