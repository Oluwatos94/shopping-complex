import { useEffect, useRef } from 'react';
import type { SupportMessage } from '@/types/support';
import EmptyState from './EmptyState';
import MessageRow from './MessageRow';
import TypingIndicator from '@/components/Chat/TypingIndicator';

interface Props {
    messages: SupportMessage[];
    isTyping: boolean;
    onQuickPrompt?: (text: string) => void;
    hasOlderMessages?: boolean;
    onLoadOlder?: () => void;
}

function showTimestamp(messages: SupportMessage[], index: number): boolean {
    const message = messages[index];
    const next = messages[index + 1];
    if (!message) return false;
    if (!next) return true;

    return (
        next.role !== message.role ||
        new Date(next.created_at).getTime() - new Date(message.created_at).getTime() > 120000
    );
}

export default function SupportThread({ messages, isTyping, onQuickPrompt, hasOlderMessages, onLoadOlder }: Props) {
    const containerRef = useRef<HTMLDivElement>(null);
    const bottomRef = useRef<HTMLDivElement>(null);
    const prevCountRef = useRef(messages.length);

    useEffect(() => {
        bottomRef.current?.scrollIntoView();
    }, []);

    // Auto-scroll on growth only when already near the bottom, so reading
    // older history is not interrupted.
    useEffect(() => {
        const container = containerRef.current;
        const grew = messages.length > prevCountRef.current;
        prevCountRef.current = messages.length;

        if (!container || (!grew && !isTyping)) return;

        const isNearBottom =
            container.scrollHeight - container.scrollTop - container.clientHeight < 150;
        if (isNearBottom) {
            bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
        }
    }, [messages.length, isTyping]);

    return (
        <div ref={containerRef} className="flex-1 overflow-y-auto bg-brand-surface px-4 py-4">
            {messages.length === 0 && !isTyping ? (
                <EmptyState onQuickPrompt={onQuickPrompt} />
            ) : (
                <div className="space-y-3">
                    {hasOlderMessages && onLoadOlder && (
                        <div className="flex justify-center">
                            <button
                                type="button"
                                onClick={onLoadOlder}
                                className="rounded-full border border-brand-line bg-white px-3 py-1 text-xs text-brand-muted transition-colors hover:border-brand-green hover:text-brand-green focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-green"
                            >
                                Load earlier messages
                            </button>
                        </div>
                    )}
                    {messages.map((message, index) => (
                        <MessageRow
                            key={message.id}
                            role={message.role}
                            content={message.content}
                            timestamp={showTimestamp(messages, index) ? message.created_at : undefined}
                        />
                    ))}
                    {isTyping && <TypingIndicator wrapperClassName="flex justify-start" bubbleClassName="bg-gray-100" />}
                </div>
            )}
            <div ref={bottomRef} />
        </div>
    );
}
