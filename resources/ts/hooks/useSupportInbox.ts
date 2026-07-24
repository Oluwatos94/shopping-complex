import { useCallback, useEffect, useRef, useState } from 'react';
import Echo from '@/echo';
import { fetchWithCsrf } from '@/utils/csrf';
import type { SupportInboxConversation, SupportMessage } from '@/types/support';

// The list-refresh cadence catches newly-escalated threads even when the
// websocket is unavailable. The thread poll is a fallback beside Echo so the
// open conversation stays live if Reverb is down.
const LIST_POLL_INTERVAL = 8000;
const THREAD_POLL_INTERVAL = 4000;

// Shape broadcast by SupportMessageSentEvent::broadcastWith().
interface BroadcastMessage {
    id: number;
    support_conversation_id: number;
    role: SupportMessage['role'];
    sender: { id: number; name: string } | null;
    content: string;
    created_at: string;
}

let tempId = -1;

export interface UseSupportInboxReturn {
    conversations: SupportInboxConversation[];
    selected: SupportInboxConversation | null;
    selectedId: number | null;
    messages: SupportMessage[];
    isThreadLoading: boolean;
    isSending: boolean;
    isResolving: boolean;
    error: string | null;
    select: (id: number) => void;
    sendReply: (content: string) => void;
    resolve: () => void;
}

export function useSupportInbox(initialConversations: SupportInboxConversation[]): UseSupportInboxReturn {
    const [conversations, setConversations] = useState<SupportInboxConversation[]>(initialConversations);
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [messages, setMessages] = useState<SupportMessage[]>([]);
    const [isThreadLoading, setIsThreadLoading] = useState(false);
    const [isSending, setIsSending] = useState(false);
    const [isResolving, setIsResolving] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const selectedIdRef = useRef<number | null>(null);
    selectedIdRef.current = selectedId;

    const selected = conversations.find((c) => c.id === selectedId) ?? null;

    // --- Conversation list ---------------------------------------------------

    const refreshList = useCallback(async () => {
        try {
            const res = await fetchWithCsrf('/admin/support/conversations');
            if (!res.ok) return; // endpoint not yet available — keep current list
            const data: { conversations: { data: SupportInboxConversation[] } } = await res.json();
            setConversations(data.conversations.data);
        } catch {
            // best-effort refresh; keep whatever we last had
        }
    }, []);

    useEffect(() => {
        const timer = setInterval(refreshList, LIST_POLL_INTERVAL);
        return () => clearInterval(timer);
    }, [refreshList]);

    // --- Thread messages -----------------------------------------------------

    const loadThread = useCallback(async (conversationId: number) => {
        setIsThreadLoading(true);
        setMessages([]);
        try {
            const res = await fetchWithCsrf(`/api/support/conversations/${conversationId}/messages`);
            if (!res.ok) throw new Error();
            const data: { messages: SupportMessage[] } = await res.json();
            // API returns newest-first; the thread renders chronologically.
            setMessages([...data.messages].reverse());
        } catch {
            setError('Could not load this conversation.');
        } finally {
            setIsThreadLoading(false);
        }
    }, []);

    // Merge-append server messages by id so it never drops in-flight optimistic
    // replies, and clears the optimistic twin once the server confirms it.
    const pollThread = useCallback(async (conversationId: number) => {
        try {
            const res = await fetchWithCsrf(`/api/support/conversations/${conversationId}/messages`);
            if (!res.ok) return;
            const data: { messages: SupportMessage[] } = await res.json();
            const server = [...data.messages].reverse();

            setMessages((prev) => {
                const existing = new Set(prev.map((m) => m.id));
                const fresh = server.filter((m) => !existing.has(m.id));
                if (fresh.length === 0) return prev;
                const withoutTwins = prev.filter(
                    (m) => !(m.id < 0 && fresh.some((s) => s.role === m.role && s.content === m.content)),
                );
                return [...withoutTwins, ...fresh];
            });
        } catch {
            // silent — Echo or the next poll will catch up
        }
    }, []);

    const appendLiveMessage = useCallback((msg: SupportMessage) => {
        setMessages((prev) => {
            if (prev.some((m) => m.id === msg.id)) return prev;
            const withoutTwin = prev.filter((m) => !(m.id < 0 && m.role === msg.role && m.content === msg.content));
            return [...withoutTwin, msg];
        });
    }, []);

    const markRead = useCallback(async (conversationId: number) => {
        setConversations((prev) => prev.map((c) => (c.id === conversationId ? { ...c, unread: false } : c)));
        try {
            await fetchWithCsrf(`/admin/support/conversations/${conversationId}/read`, { method: 'POST' });
        } catch {
            // best-effort — contract endpoint may not exist yet
        }
    }, []);

    const select = useCallback(
        (id: number) => {
            setSelectedId(id);
            setError(null);
            loadThread(id);
            markRead(id);
        },
        [loadThread, markRead],
    );

    // Live updates for the open thread: Echo first, thread poll as fallback.
    useEffect(() => {
        if (selectedId === null) return;

        const channelName = `support-conversation.${selectedId}`;
        const channel = Echo.private(channelName);

        channel.listen('.support.message.sent', (payload: BroadcastMessage) => {
            appendLiveMessage({
                id: payload.id,
                support_conversation_id: payload.support_conversation_id,
                role: payload.role,
                sender_id: payload.sender?.id ?? null,
                content: payload.content,
                created_at: payload.created_at,
            });
            setConversations((prev) =>
                prev.map((c) =>
                    c.id === selectedId
                        ? { ...c, last_message_preview: payload.content, last_message_at: payload.created_at, unread: false }
                        : c,
                ),
            );
        });

        const timer = setInterval(() => pollThread(selectedId), THREAD_POLL_INTERVAL);

        return () => {
            clearInterval(timer);
            Echo.leave(channelName);
        };
    }, [selectedId, appendLiveMessage, pollThread]);

    // --- Actions -------------------------------------------------------------

    const sendReply = useCallback(
        async (text: string) => {
            const id = selectedIdRef.current;
            const content = text.trim();
            if (id === null || content === '' || isSending) return;

            setError(null);

            const optimistic: SupportMessage = {
                id: tempId--,
                support_conversation_id: id,
                role: 'agent',
                sender_id: null,
                content,
                created_at: new Date().toISOString(),
            };
            setMessages((prev) => [...prev, optimistic]);
            setIsSending(true);

            try {
                const res = await fetchWithCsrf(`/api/support/conversations/${id}/messages`, {
                    method: 'POST',
                    body: JSON.stringify({ content }),
                });
                if (!res.ok) {
                    const data = await res.json().catch(() => null);
                    throw new Error(typeof data?.message === 'string' ? data.message : '');
                }

                const data: { message: SupportMessage } = await res.json();
                setMessages((prev) => prev.map((m) => (m.id === optimistic.id ? data.message : m)));
                setConversations((prev) =>
                    prev.map((c) =>
                        c.id === id
                            ? {
                                  ...c,
                                  status: 'with_agent',
                                  unread: false,
                                  last_message_preview: content,
                                  last_message_at: new Date().toISOString(),
                              }
                            : c,
                    ),
                );
            } catch (e) {
                setMessages((prev) => prev.filter((m) => m.id !== optimistic.id));
                setError(e instanceof Error && e.message !== '' ? e.message : 'Reply failed to send.');
            } finally {
                setIsSending(false);
            }
        },
        [isSending],
    );

    const resolve = useCallback(async () => {
        const id = selectedIdRef.current;
        if (id === null || isResolving) return;

        setIsResolving(true);
        setError(null);

        try {
            const res = await fetchWithCsrf(`/api/support/conversations/${id}/resolve`, { method: 'POST' });
            if (!res.ok) {
                const data = await res.json().catch(() => null);
                throw new Error(typeof data?.message === 'string' ? data.message : '');
            }
            setConversations((prev) => prev.map((c) => (c.id === id ? { ...c, status: 'resolved', unread: false } : c)));
        } catch (e) {
            setError(e instanceof Error && e.message !== '' ? e.message : 'Could not resolve the conversation.');
        } finally {
            setIsResolving(false);
        }
    }, [isResolving]);

    return {
        conversations,
        selected,
        selectedId,
        messages,
        isThreadLoading,
        isSending,
        isResolving,
        error,
        select,
        sendReply,
        resolve,
    };
}
