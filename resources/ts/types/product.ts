import { Vendor } from './user';

/**
 * Admin product (used in admin product listing)
 */
export interface AdminProduct {
    id: number;
    name: string;
    slug: string;
    price: number;
    stock: number;
    is_active: boolean;
    created_at: string;
    vendor: (Pick<Vendor, 'id' | 'name'> & { business_name: string | null }) | null;
    category: Pick<Category, 'id' | 'name' | 'slug'> | null;
    media: Pick<ProductImage, 'url'>[];
}

/**
 * Admin product list view mode
 */
export type ViewMode = 'grid' | 'list';

/**
 * Admin product moderation status
 */
export type ProductStatus = 'pending' | 'active' | 'flagged';

/**
 * Filter overrides for admin product listing
 */
export type FilterOverrides = {
    status?: string;
    category?: string;
    search?: string;
    sortBy?: string;
    page?: number;
};

/**
 * Shared props for ProductCard and ProductRow components
 */
export type ProductActionProps = {
    product: AdminProduct;
    status: ProductStatus;
    selected: boolean;
    onToggleSelect: (id: number) => void;
    onApprove: (id: number) => void;
    onDeactivate: (id: number) => void;
    onFlag: (product: AdminProduct) => void;
    onUnflag: (id: number) => void;
    processing: number | null;
};

/**
 * Product type
 */
export interface Product {
    id: number;
    vendor_id: number;
    category_id: number;
    name: string;
    slug: string;
    description: string;
    price: number;
    sale_price?: number;
    stock: number;
    is_active: boolean;
    is_featured: boolean;
    pay_on_delivery: boolean;
    is_returnable: boolean;
    images?: ProductImage[];
    vendor?: Vendor;
    category?: Category;
    reviews_count?: number;
    average_rating: number;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

/**
 * Product image type
 */
export interface ProductImage {
    id: number;
    product_id: number;
    url: string;
    alt_text?: string;
    is_primary: boolean;
    order: number;
}

/**
 * Product attribute (e.g., color, size, material)
 */
export interface ProductAttribute {
    id: number;
    product_id: number;
    name: string;
    value: string;
}

/**
 * Product category
 */
export interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string;
    icon?: string;
    parent_id?: number;
    products_count: number;
    vendors_count: number;
    created_at: string;
    updated_at: string;
}

/**
 * Product review (legacy - kept for compatibility)
 */
export interface Review {
    id: number;
    product_id: number;
    user_id: number;
    rating: number; // 1-5
    title?: string;
    comment: string;
    is_verified_purchase: boolean;
    helpful_count: number;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
        profile_image?: string;
    };
}

/**
 * Vendor review - reviews for vendor services
 */
export interface VendorReview {
    id: number;
    customer_id: number;
    vendor_id: number;
    conversation_id?: number;
    rating: number; // 1-5
    title?: string;
    comment?: string;
    status: 'pending' | 'approved' | 'rejected';
    helpful_count: number;
    not_helpful_count: number;
    vendor_response?: string;
    vendor_responded_at?: string;
    created_at: string;
    updated_at: string;
    customer?: {
        id: number;
        name: string;
        profile_image?: string;
    };
}

/**
 * Vendor rating statistics
 */
export interface VendorRatingStats {
    average: number;
    count: number;
    distribution: {
        1: number;
        2: number;
        3: number;
        4: number;
        5: number;
    };
}

/**
 * Paginated vendor reviews
 */
export interface PaginatedVendorReviews {
    reviews: VendorReview[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

/**
 * Lean product shape returned by CategoryController::products()
 */
export interface CategoryProduct {
    id: number;
    name: string;
    slug: string;
    price: number;
    image: string | null;
    vendor_name: string | null;
    vendor_slug: string | null;
}

/**
 * Product filter options
 */
export interface ProductFilters {
    category_id?: number;
    vendor_id?: number;
    min_price?: number;
    max_price?: number;
    is_featured?: boolean;
    search?: string;
    sort_by?: ProductSortOption;
    in_stock_only?: boolean;
}

/**
 * Product sorting options
 */
export type ProductSortOption =
    | 'price_asc'
    | 'price_desc'
    | 'name_asc'
    | 'name_desc'
    | 'rating'
    | 'newest'
    | 'popular';

/**
 * Generic paginated response wrapper
 */
export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

/**
 * Paginated product response
 */
export interface PaginatedProducts extends Paginated<Product> {
    from: number;
    to: number;
}

/**
 * Cart item
 */
export interface CartItem {
    id: number;
    product_id: number;
    product: Product;
    quantity: number;
    price: number; // Price at the time of adding to cart
    subtotal: number;
}

/**
 * Wishlist item
 */
export interface WishlistItem {
    id: number;
    product_id: number;
    product: Product;
    added_at: string;
}
