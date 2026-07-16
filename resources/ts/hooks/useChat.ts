import { useState, useEffect, useRef, useCallback } from 'react';
import { fetchWithCsrf } from '@/utils/csrf';
import { ChatMessage, PollResponse } from '@/types/chat';

const POLL_INTERVAL = 3000;

export function useChat(
    conversationId: number,
    initialMessages: ChatMessage[],
    authUserId: number
) {
    const [messages, setMessages] = useState<ChatMessage[]>(() =>
        [...initialMessages].reverse()
    );
    const [isSending, setIsSending] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const lastMessageIdRef = useRef<number>(
        initialMessages.length > 0 ? Math.max(...initialMessages.map(m => m.id)) : 0
    );
    const pollTimerRef = useRef<ReturnType<typeof setInterval> | null>(null);
    const isVisibleRef = useRef(true);

    // Update lastMessageIdRef when messages change
    useEffect(() => {
        if (messages.length > 0) {
            lastMessageIdRef.current = Math.max(...messages.map(m => m.id));
        }
    }, [messages]);

    // Poll for new messages
    const pollMessages = useCallback(async () => {
        if (!isVisibleRef.current) return;

        try {
            const res = await fetchWithCsrf(
                `/api/chat/conversations/${conversationId}/messages/poll?after_message_id=${lastMessageIdRef.current}`
            );
            if (!res.ok) return;

            const data: PollResponse = await res.json();
            if (data.has_new && data.messages.length > 0) {
                setMessages(prev => {
                    const existingIds = new Set(prev.map(m => m.id));
                    const newMessages = data.messages.filter(m => !existingIds.has(m.id));
                    if (newMessages.length === 0) return prev;
                    return [...prev, ...newMessages];
                });

                // Mark as read if new messages are from the other user
                const hasOtherMessages = data.messages.some(m => m.sender_id !== authUserId);
                if (hasOtherMessages) {
                    fetchWithCsrf(`/api/chat/conversations/${conversationId}/messages/read`, {
                        method: 'PATCH',
                    });
                }
            }
        } catch {
            // Silent fail on poll
        }
    }, [conversationId, authUserId]);

    // Start/stop polling
    useEffect(() => {
        pollTimerRef.current = setInterval(pollMessages, POLL_INTERVAL);

        const handleVisibility = () => {
            isVisibleRef.current = document.visibilityState === 'visible';
            if (isVisibleRef.current) {
                pollMessages();
            }
        };
        document.addEventListener('visibilitychange', handleVisibility);

        return () => {
            if (pollTimerRef.current) clearInterval(pollTimerRef.current);
            document.removeEventListener('visibilitychange', handleVisibility);
        };
    }, [pollMessages]);

    // Send message
    const sendMessage = useCallback(async (content: string, attachment?: File) => {
        setIsSending(true);
        setError(null);

        const formData = new FormData();
        if (content.trim()) formData.append('content', content.trim());
        if (attachment) formData.append('attachment', attachment);

        try {
            const res = await fetchWithCsrf(
                `/api/chat/conversations/${conversationId}/messages`,
                { method: 'POST', body: formData }
            );

            if (!res.ok) {
                const data = await res.json();
                setError(data.message || 'Failed to send message');
                return;
            }

            const newMessage: ChatMessage = await res.json();
            setMessages(prev => {
                const existingIds = new Set(prev.map(m => m.id));
                if (existingIds.has(newMessage.id)) return prev;
                return [...prev, newMessage];
            });
        } catch {
            setError('Failed to send message');
        } finally {
            setIsSending(false);
        }
    }, [conversationId]);

    // Mark all as read
    const markAsRead = useCallback(async () => {
        try {
            await fetchWithCsrf(`/api/chat/conversations/${conversationId}/messages/read`, {
                method: 'PATCH',
            });
        } catch {
            // Silent fail
        }
    }, [conversationId]);

    // Send typing indicator
    const sendTypingIndicator = useCallback(async () => {
        try {
            await fetchWithCsrf(`/api/chat/conversations/${conversationId}/typing`, {
                method: 'POST',
            });
        } catch {
            // Silent fail
        }
    }, [conversationId]);

    return {
        messages,
        sendMessage,
        markAsRead,
        sendTypingIndicator,
        isSending,
        error,
    };
}
