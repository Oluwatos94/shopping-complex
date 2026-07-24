import { Head } from '@inertiajs/react';
import { KeyboardEvent as ReactKeyboardEvent, useMemo, useState } from 'react';
import AdminLayout from '@/components/Admin/AdminLayout';
import SupportThread from '@/components/Support/SupportThread';
import { useSupportInbox } from '@/hooks/useSupportInbox';
import type { SupportConversationStatus, SupportInboxConversation } from '@/types/support';

interface Props {
    // The backend list endpoint may hand over a plain array or a paginator page.
    conversations: SupportInboxConversation[] | { data: SupportInboxConversation[] };
}

const STATUS_BADGE: Record<SupportConversationStatus, { label: string; className: string }> = {
    bot:            { label: 'Bot',        className: 'bg-gray-100 text-gray-500' },
    awaiting_agent: { label: 'Awaiting',   className: 'bg-amber-50 text-amber-700' },
    with_agent:     { label: 'With agent', className: 'bg-emerald-50 text-emerald-700' },
    resolved:       { label: 'Resolved',   className: 'bg-gray-100 text-gray-400' },
};

function relativeTime(isoString: string | null): string {
    if (!isoString) return '';
    const diff = Math.floor((Date.now() - new Date(isoString).getTime()) / 1000);
    if (diff < 60) return `${diff}s`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
    return `${Math.floor(diff / 86400)}d`;
}

function StatusBadge({ status }: { status: SupportConversationStatus }) {
    const badge = STATUS_BADGE[status];
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide whitespace-nowrap ${badge.className}`}>
            {badge.label}
        </span>
    );
}

export default function Support({ conversations }: Props) {
    const initial = Array.isArray(conversations) ? conversations : conversations?.data ?? [];

    const {
        conversations: rows,
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
    } = useSupportInbox(initial);

    const [draft, setDraft] = useState('');

    // Unread first, then most-recent activity — mirrors the backend ordering so
    // the queue stays stable between poll refreshes.
    const ordered = useMemo(() => {
        return [...rows].sort((a, b) => {
            if (a.unread !== b.unread) return a.unread ? -1 : 1;
            const at = a.last_message_at ? new Date(a.last_message_at).getTime() : 0;
            const bt = b.last_message_at ? new Date(b.last_message_at).getTime() : 0;
            return bt - at;
        });
    }, [rows]);

    const unreadCount = rows.filter((c) => c.unread).length;

    const handleSend = () => {
        const content = draft.trim();
        if (content === '' || isSending) return;
        setDraft('');
        sendReply(content);
    };

    const handleKeyDown = (e: ReactKeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    };

    const isResolved = selected?.status === 'resolved';

    return (
        <>
            <Head title="Support Inbox" />
            <AdminLayout>
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h2 className="text-3xl font-bold tracking-tight text-gray-900">Support Inbox</h2>
                        <p className="text-gray-500 mt-1">
                            {unreadCount > 0
                                ? `${unreadCount} conversation${unreadCount === 1 ? '' : 's'} need a reply`
                                : 'No conversations waiting'}
                        </p>
                    </div>
                    <span className="text-[10px] font-bold text-gray-400 uppercase bg-white border border-gray-100 px-3 py-1.5 rounded-lg">
                        Live
                    </span>
                </div>

                <div className="flex h-[calc(100vh-13rem)] overflow-hidden rounded-xl border border-gray-100 bg-white">
                    {/* Conversation list */}
                    <aside className="w-80 flex-shrink-0 overflow-y-auto border-r border-gray-100">
                        {ordered.length === 0 ? (
                            <div className="flex h-full items-center justify-center px-6 text-center">
                                <p className="text-sm text-gray-400">No escalated conversations.</p>
                            </div>
                        ) : (
                            <ul>
                                {ordered.map((c) => {
                                    const isActive = c.id === selectedId;
                                    return (
                                        <li key={c.id}>
                                            <button
                                                type="button"
                                                onClick={() => select(c.id)}
                                                className={`w-full border-b border-gray-50 px-4 py-3 text-left transition-colors ${
                                                    isActive ? 'bg-primary-olive/5' : 'hover:bg-gray-50/60'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="flex items-center gap-2 min-w-0">
                                                        {c.unread && (
                                                            <span className="h-2 w-2 flex-shrink-0 rounded-full bg-primary-olive" />
                                                        )}
                                                        <span className={`truncate text-sm ${c.unread ? 'font-semibold text-gray-900' : 'text-gray-700'}`}>
                                                            {c.user?.name ?? 'Guest'}
                                                        </span>
                                                    </span>
                                                    <span className="flex-shrink-0 text-[11px] text-gray-400">
                                                        {relativeTime(c.last_message_at)}
                                                    </span>
                                                </div>
                                                <div className="mt-1.5 flex items-center justify-between gap-2">
                                                    <span className="truncate text-xs text-gray-400">
                                                        {c.last_message_preview ?? 'No messages yet'}
                                                    </span>
                                                    <StatusBadge status={c.status} />
                                                </div>
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </aside>

                    {/* Thread */}
                    <section className="flex flex-1 flex-col min-w-0">
                        {!selected ? (
                            <div className="flex flex-1 items-center justify-center px-6 text-center">
                                <p className="text-sm text-gray-400">Select a conversation to view the thread.</p>
                            </div>
                        ) : (
                            <>
                                {/* Thread header */}
                                <div className="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold text-gray-900">
                                            {selected.user?.name ?? 'Guest'}
                                        </p>
                                        <div className="mt-0.5 flex items-center gap-2">
                                            <StatusBadge status={selected.status} />
                                            {selected.agent && (
                                                <span className="text-[11px] text-gray-400">Agent: {selected.agent.name}</span>
                                            )}
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={resolve}
                                        disabled={isResolved || isResolving}
                                        className="flex-shrink-0 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        {isResolved ? 'Resolved' : isResolving ? 'Resolving…' : 'Resolve'}
                                    </button>
                                </div>

                                {/* Messages */}
                                {isThreadLoading && messages.length === 0 ? (
                                    <div className="flex flex-1 items-center justify-center bg-brand-surface">
                                        <span className="text-sm text-brand-muted">Loading…</span>
                                    </div>
                                ) : messages.length === 0 ? (
                                    <div className="flex flex-1 items-center justify-center bg-brand-surface px-6 text-center">
                                        <span className="text-sm text-brand-muted">No messages in this conversation yet.</span>
                                    </div>
                                ) : (
                                    <SupportThread messages={messages} isTyping={false} />
                                )}

                                {error && (
                                    <div className="border-t border-gray-100 bg-red-50 px-5 py-2 text-xs text-red-600">{error}</div>
                                )}

                                {/* Agent composer */}
                                <div className="border-t border-gray-100 bg-white px-4 py-3">
                                    {isResolved ? (
                                        <p className="py-2 text-center text-xs text-gray-400">
                                            This conversation is resolved.
                                        </p>
                                    ) : (
                                        <div className="flex items-end gap-2">
                                            <textarea
                                                value={draft}
                                                onChange={(e) => setDraft(e.target.value)}
                                                onKeyDown={handleKeyDown}
                                                rows={1}
                                                placeholder="Reply as agent…"
                                                aria-label="Reply as agent"
                                                className="max-h-32 flex-1 resize-none rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-olive focus:outline-none focus:ring-1 focus:ring-primary-olive"
                                            />
                                            <button
                                                type="button"
                                                onClick={handleSend}
                                                disabled={draft.trim() === '' || isSending}
                                                aria-label="Send reply"
                                                className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary-olive text-white transition-colors hover:bg-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-olive disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                                </svg>
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </>
                        )}
                    </section>
                </div>
            </AdminLayout>
        </>
    );
}
