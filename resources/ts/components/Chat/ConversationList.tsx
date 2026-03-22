import { useState, useMemo } from 'react';
import { Conversation } from '@/types/chat';
import ConversationItem from './ConversationItem';

interface Props {
    conversations: Conversation[];
    activeConversationId: number | null;
    authUserId: number;
    onConversationSelect: (id: number) => void;
}

export default function ConversationList({
    conversations,
    activeConversationId,
    authUserId,
    onConversationSelect,
}: Props) {
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        if (!search.trim()) return conversations;
        const q = search.toLowerCase();
        return conversations.filter(c => {
            const isCustomer = authUserId === c.customer_id;
            const other = isCustomer ? c.vendor : c.customer;
            const name = (other.business_name || other.name || '').toLowerCase();
            return name.includes(q);
        });
    }, [conversations, search, authUserId]);

    return (
        <div className="flex flex-col h-full bg-white">
            {/* Header */}
            <div className="flex items-center justify-between px-4 py-4 border-b border-gray-100">
                <h2 className="text-xl font-serif font-bold text-primary-dark">Messages</h2>
                <button className="p-2 text-gray-400 hover:text-primary-olive rounded-lg hover:bg-gray-100 transition-colors">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
            </div>

            {/* Search */}
            <div className="px-4 py-3">
                <div className="relative">
                    <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        placeholder="Search conversations..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/30 focus:border-primary-olive"
                    />
                </div>
            </div>

            {/* Conversation List */}
            <div className="flex-1 overflow-y-auto">
                {filtered.length > 0 ? (
                    filtered.map((conversation) => (
                        <ConversationItem
                            key={conversation.id}
                            conversation={conversation}
                            authUserId={authUserId}
                            isActive={conversation.id === activeConversationId}
                            onClick={() => onConversationSelect(conversation.id)}
                        />
                    ))
                ) : (
                    <div className="px-4 py-8 text-center">
                        <p className="text-sm text-gray-400">
                            {search ? 'No conversations found' : 'No conversations yet'}
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}
