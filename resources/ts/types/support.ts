export type SupportMessageRole = 'user' | 'assistant' | 'agent';

export type SupportConversationStatus = 'bot' | 'awaiting_agent' | 'with_agent' | 'resolved';

export interface SupportMessage {
    id: number;
    support_conversation_id: number;
    role: SupportMessageRole;
    sender_id: number | null;
    content: string;
    created_at: string;
}

export interface SupportParticipant {
    id: number;
    name: string;
}

export interface SupportConversation {
    id: number;
    user_id: number | null;
    status: SupportConversationStatus;
    last_message_at: string | null;
    escalated_at: string | null;
    agent_id: number | null;
    created_at: string;
    // Optional: only present when the backend eager-loads the assigned agent.
    // Lets the widget name the agent in the "with_agent" banner; falls back to
    // generic copy when absent.
    agent?: SupportParticipant | null;
}

/**
 * A row in the admin support inbox. Shapes the contract the backend list
 * endpoint (GET /admin/support/conversations) is expected to return.
 */
export interface SupportInboxConversation {
    id: number;
    user: SupportParticipant | null;
    agent: SupportParticipant | null;
    status: SupportConversationStatus;
    last_message_at: string | null;
    escalated_at: string | null;
    unread: boolean;
    last_message_preview: string | null;
}
