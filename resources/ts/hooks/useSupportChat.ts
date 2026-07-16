import { useCallback, useEffect, useRef, useState } from 'react';
import { fetchWithCsrf } from '@/utils/csrf';
import type { SupportConversation, SupportMessage } from '@/types/support';

const POLL_INTERVAL = 3000;

const ESCALATED_STATUSES: SupportConversation['status'][] = ['awaiting_agent', 'with_agent'];

let tempId = -1;

export function useSupportChat(isOpen: boolean) {
    const [conversation, setConversation] = useState<SupportConversation | null>(null);
    const [messages, setMessages] = useState<SupportMessage[]>([]);
    const [isTyping, setIsTyping] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [failedText, setFailedText] = useState<string | null>(null);
    const [hasLocation, setHasLocation] = useState(false);
    const [hasOlderMessages, setHasOlderMessages] = useState(false);
    const conversationRef = useRef<SupportConversation | null>(null);
    const startedRef = useRef(false);
    const coordsRef = useRef<{ lat: number; lng: number } | null>(null);
    const sendingRef = useRef(false);
    const oldestPageRef = useRef(1);
    const loadingOlderRef = useRef(false);

    conversationRef.current = conversation;

    const shareLocation = useCallback(() => {
        if (!navigator.geolocation) {
            setError('Location is not supported by this browser.');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                coordsRef.current = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                setHasLocation(true);
                setError(null);
            },
            () => setError('Could not get your location. Please allow location access.'),
        );
    }, []);

    const refresh = useCallback(async (conversationId: number, keepPending = true) => {
        const res = await fetchWithCsrf(`/api/support/conversations/${conversationId}/messages`);
        if (!res.ok) return;

        const data: { messages: SupportMessage[]; meta: { last_page: number } } = await res.json();
        setHasOlderMessages(oldestPageRef.current < data.meta.last_page);

        setMessages(prev => {
            const server = [...data.messages].reverse();
            const oldestServerId = server[0]?.id;
            const older = oldestServerId === undefined
                ? []
                : prev.filter(m => m.id > 0 && m.id < oldestServerId);
            const pending = keepPending ? prev.filter(m => m.id < 0) : [];
            return [...older, ...server, ...pending];
        });
    }, []);

    const loadOlderMessages = useCallback(async () => {
        const target = conversationRef.current;
        if (!target || loadingOlderRef.current) return;

        loadingOlderRef.current = true;
        try {
            const page = oldestPageRef.current + 1;
            const res = await fetchWithCsrf(`/api/support/conversations/${target.id}/messages?page=${page}`);
            if (!res.ok) return;

            const data: { messages: SupportMessage[]; meta: { current_page: number; last_page: number } } = await res.json();
            oldestPageRef.current = data.meta.current_page;
            setHasOlderMessages(data.meta.current_page < data.meta.last_page);
            setMessages(prev => {
                const ids = new Set(prev.map(m => m.id));
                const older = [...data.messages].reverse().filter(m => !ids.has(m.id));
                return older.length > 0 ? [...older, ...prev] : prev;
            });
        } finally {
            loadingOlderRef.current = false;
        }
    }, []);

    const syncConversation = useCallback(async (conversationId: number) => {
        const res = await fetchWithCsrf(`/api/support/conversations/${conversationId}`);
        if (!res.ok) return;

        const data: { conversation: SupportConversation } = await res.json();
        setConversation(data.conversation);
    }, []);

    const start = useCallback(async () => {
        setIsLoading(true);
        setError(null);

        try {
            const res = await fetchWithCsrf('/api/support/conversations', { method: 'POST' });
            if (!res.ok) throw new Error();

            const data: { conversation: SupportConversation } = await res.json();
            setConversation(data.conversation);
            oldestPageRef.current = 1;
            await refresh(data.conversation.id);
        } catch {
            startedRef.current = false;
            setError('Could not start the chat. Please try again.');
        } finally {
            setIsLoading(false);
        }
    }, [refresh]);

    useEffect(() => {
        if (isOpen && !startedRef.current) {
            startedRef.current = true;
            start();
        }
    }, [isOpen, start]);

    const sendMessage = useCallback(async (text: string) => {
        const target = conversationRef.current;
        const content = text.trim();
        if (!target || content === '' || isTyping) return;

        setError(null);
        setFailedText(null);

        const optimistic: SupportMessage = {
            id: tempId--,
            support_conversation_id: target.id,
            role: 'user',
            sender_id: target.user_id,
            content,
            created_at: new Date().toISOString(),
        };
        setMessages(prev => [...prev, optimistic]);
        setIsTyping(true);
        sendingRef.current = true;

        try {
            const res = await fetchWithCsrf(`/api/support/conversations/${target.id}/messages`, {
                method: 'POST',
                body: JSON.stringify({ content, ...(coordsRef.current ?? {}) }),
            });

            if (!res.ok) {
                const data = await res.json().catch(() => null);
                throw new Error(typeof data?.message === 'string' ? data.message : '');
            }

            await Promise.all([refresh(target.id, false), syncConversation(target.id)]);
        } catch (e) {
            setMessages(prev => prev.filter(m => m.id !== optimistic.id));
            setError(e instanceof Error && e.message !== '' ? e.message : 'Message failed to send.');
            setFailedText(content);
        } finally {
            setIsTyping(false);
            sendingRef.current = false;
        }
    }, [isTyping, refresh, syncConversation]);

    const retry = useCallback(() => {
        if (failedText !== null) {
            sendMessage(failedText);
        } else if (conversationRef.current === null) {
            startedRef.current = true;
            start();
        }
    }, [failedText, sendMessage, start]);

    const conversationId = conversation?.id;
    const conversationStatus = conversation?.status;

    useEffect(() => {
        if (!isOpen || conversationId === undefined || conversationStatus === undefined || !ESCALATED_STATUSES.includes(conversationStatus)) return;

        const timer = setInterval(async () => {

            if (sendingRef.current) return;

            try {
                await Promise.all([syncConversation(conversationId), refresh(conversationId)]);
            } catch {
                // Silent fail on poll
            }
        }, POLL_INTERVAL);

        return () => clearInterval(timer);
    }, [isOpen, conversationId, conversationStatus, refresh, syncConversation]);

    return {
        conversation,
        messages,
        isTyping,
        isLoading,
        error,
        failedText,
        hasLocation,
        hasOlderMessages,
        loadOlderMessages,
        shareLocation,
        sendMessage,
        retry,
    };
}
