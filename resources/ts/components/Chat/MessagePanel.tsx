import { useEffect, useRef, useCallback } from 'react';
import { Conversation, ChatMessage } from '@/types/chat';
import { useChat } from '@/hooks/useChat';
import ChatHeader from './ChatHeader';
import MessageBubble from './MessageBubble';
import MessageInput from './MessageInput';
import DateSeparator from './DateSeparator';
import TypingIndicator from './TypingIndicator';

interface Props {
    conversation: Conversation;
    initialMessages: ChatMessage[];
    authUserId: number;
}

function formatDateLabel(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const msgDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const diffDays = Math.floor((today.getTime() - msgDate.getTime()) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Yesterday';
    return date.toLocaleDateString([], { month: 'long', day: 'numeric', year: 'numeric' });
}

function groupMessagesByDate(messages: ChatMessage[]): { date: string; messages: ChatMessage[] }[] {
    const groups: { date: string; messages: ChatMessage[] }[] = [];

    messages.forEach((message) => {
        const dateLabel = formatDateLabel(message.created_at);
        const lastGroup = groups[groups.length - 1];

        if (lastGroup && lastGroup.date === dateLabel) {
            lastGroup.messages.push(message);
        } else {
            groups.push({ date: dateLabel, messages: [message] });
        }
    });

    return groups;
}

export default function MessagePanel({ conversation, initialMessages, authUserId }: Props) {
    const {
        messages,
        sendMessage,
        sendTypingIndicator,
        isSending,
        error,
    } = useChat(conversation.id, initialMessages, authUserId);

    const messagesEndRef = useRef<HTMLDivElement>(null);
    const messagesContainerRef = useRef<HTMLDivElement>(null);
    const prevMessagesLengthRef = useRef(messages.length);

    // Auto-scroll to bottom on new messages
    const scrollToBottom = useCallback(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, []);

    useEffect(() => {
        // Only auto-scroll if new messages were added (not on initial render)
        if (messages.length > prevMessagesLengthRef.current) {
            const container = messagesContainerRef.current;
            if (container) {
                const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 150;
                if (isNearBottom) {
                    scrollToBottom();
                }
            }
        }
        prevMessagesLengthRef.current = messages.length;
    }, [messages.length, scrollToBottom]);

    // Scroll to bottom on initial load
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView();
    }, []);

    const dateGroups = groupMessagesByDate(messages);

    return (
        <div className="flex flex-col h-full bg-[#f9f6f0]">
            <ChatHeader conversation={conversation} authUserId={authUserId} />

            {/* Messages Area */}
            <div
                ref={messagesContainerRef}
                className="flex-1 overflow-y-auto px-4 py-4"
            >
                {dateGroups.map((group) => (
                    <div key={group.date}>
                        <DateSeparator date={group.date} />
                        {group.messages.map((message, index) => {
                            const isOwn = message.sender_id === authUserId;
                            const nextMessage = group.messages[index + 1];
                            const showTimestamp = !nextMessage
                                || nextMessage.sender_id !== message.sender_id
                                || (new Date(nextMessage.created_at).getTime() - new Date(message.created_at).getTime() > 120000);

                            return (
                                <MessageBubble
                                    key={message.id}
                                    message={message}
                                    isOwn={isOwn}
                                    showTimestamp={showTimestamp}
                                />
                            );
                        })}
                    </div>
                ))}

                {isSending && <TypingIndicator />}

                {error && (
                    <div className="text-center py-2">
                        <span className="text-xs text-red-500 bg-red-50 px-3 py-1 rounded-full">{error}</span>
                    </div>
                )}

                <div ref={messagesEndRef} />
            </div>

            <MessageInput
                onSend={sendMessage}
                onTyping={sendTypingIndicator}
                isSending={isSending}
            />
        </div>
    );
}
