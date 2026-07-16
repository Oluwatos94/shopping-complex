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

export interface SupportConversation {
    id: number;
    user_id: number | null;
    status: SupportConversationStatus;
    last_message_at: string | null;
    escalated_at: string | null;
    agent_id: number | null;
    created_at: string;
}
