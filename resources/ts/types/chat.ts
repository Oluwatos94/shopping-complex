import { Product } from './product';

export interface ChatUser {
    id: number;
    name: string;
    email?: string;
    role?: string;
    business_name?: string;
    bio?: string;
}

export interface ChatMessage {
    id: number;
    conversation_id: number;
    sender_id: number;
    content: string;
    attachment_path: string | null;
    attachment_type: 'image' | 'video' | 'audio' | 'document' | null;
    attachment_name: string | null;
    delivered_at: string | null;
    read_at: string | null;
    created_at: string;
    updated_at: string;
    sender?: ChatUser;
}

export interface Conversation {
    id: number;
    customer_id: number;
    vendor_id: number;
    product_id: number | null;
    last_message_at: string | null;
    created_at: string;
    updated_at: string;
    customer: ChatUser;
    vendor: ChatUser;
    product?: Product | null;
    messages?: ChatMessage[];
    unread_count: number;
}

export interface ChatPagination {
    current_page: number;
    last_page: number;
    total: number;
}

export interface PollResponse {
    messages: ChatMessage[];
    has_new: boolean;
}
