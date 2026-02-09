import { Conversation } from '@/types/chat';

interface Props {
    conversation: Conversation;
    authUserId: number;
    isActive: boolean;
    onClick: () => void;
}

function formatTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) {
        return date.toLocaleDateString([], { weekday: 'short' });
    }
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
}

function getInitials(name: string): string {
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
}

export default function ConversationItem({ conversation, authUserId, isActive, onClick }: Props) {
    const isCustomer = authUserId === conversation.customer_id;
    const other = isCustomer ? conversation.vendor : conversation.customer;
    const displayName = other.business_name || other.name;
    const lastMessage = conversation.messages?.[0];
    const unread = conversation.unread_count || 0;

    return (
        <button
            onClick={onClick}
            className={`w-full flex items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-gray-50 ${
                isActive ? 'bg-primary-olive/5 border-r-2 border-primary-olive' : ''
            }`}
        >
            {/* Avatar */}
            <div className="w-12 h-12 rounded-full flex-shrink-0 bg-primary-olive/20 flex items-center justify-center overflow-hidden">
                <span className="text-sm font-bold text-primary-olive">
                    {getInitials(displayName)}
                </span>
            </div>

            {/* Info */}
            <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between">
                    <h4 className={`text-sm truncate ${unread > 0 ? 'font-bold text-gray-900' : 'font-medium text-gray-800'}`}>
                        {displayName}
                    </h4>
                    {lastMessage && (
                        <span className="text-xs text-gray-400 flex-shrink-0 ml-2">
                            {formatTime(lastMessage.created_at)}
                        </span>
                    )}
                </div>
                <div className="flex items-center justify-between mt-0.5">
                    <p className={`text-xs truncate ${unread > 0 ? 'text-gray-700 font-medium' : 'text-gray-400'}`}>
                        {lastMessage
                            ? lastMessage.content || (lastMessage.attachment_name ? `Sent an attachment` : '')
                            : 'No messages yet'}
                    </p>
                    {unread > 0 && (
                        <span className="ml-2 flex-shrink-0 w-5 h-5 bg-primary-olive text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                            {unread > 9 ? '9+' : unread}
                        </span>
                    )}
                </div>
            </div>
        </button>
    );
}
