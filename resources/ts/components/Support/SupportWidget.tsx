import { KeyboardEvent as ReactKeyboardEvent, useCallback, useEffect, useRef, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { useSupportChat } from '@/hooks/useSupportChat';
import SupportThread from './SupportThread';

export default function SupportWidget() {
    const { auth } = usePage<{ auth?: { user?: { role: string } | null } }>().props;
    const [isOpen, setIsOpen] = useState(false);
    const [draft, setDraft] = useState('');
    const panelRef = useRef<HTMLDivElement>(null);
    const launcherRef = useRef<HTMLButtonElement>(null);
    const inputRef = useRef<HTMLTextAreaElement>(null);

    const { conversation, messages, isTyping, isLoading, isEscalating, error, hasLocation, hasOlderMessages, loadOlderMessages, shareLocation, sendMessage, escalate, retry } = useSupportChat(isOpen);

    // Escalation ("Talk to a human") is auth-only — guests are routed to sign in.
    const isAuthenticated = Boolean(auth?.user);

    const close = useCallback(() => {
        inputRef.current?.blur();
        setIsOpen(false);
        launcherRef.current?.focus();
    }, []);

    const handleSend = useCallback(() => {
        const content = draft.trim();
        if (content === '' || isTyping) return;

        setDraft('');
        sendMessage(content);
    }, [draft, isTyping, sendMessage]);

    const handleComposerKeyDown = (e: ReactKeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    };

    useEffect(() => {
        if (!isOpen) return;

        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                close();
                return;
            }

            if (e.key !== 'Tab' || !panelRef.current) return;

            const focusables = panelRef.current.querySelectorAll<HTMLElement>(
                'button, textarea, input, a[href], [tabindex]:not([tabindex="-1"])'
            );
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            if (!first || !last) return;

            const active = document.activeElement;

            if (e.shiftKey && (active === first || !panelRef.current.contains(active))) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && (active === last || !panelRef.current.contains(active))) {
                e.preventDefault();
                first.focus();
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [isOpen, close]);

    useEffect(() => {
        if (isOpen && window.matchMedia('(min-width: 640px)').matches) inputRef.current?.focus();
    }, [isOpen]);

    useEffect(() => {
        if (!isOpen || window.matchMedia('(min-width: 640px)').matches) return;

        const panel = panelRef.current;
        const scrollY = window.scrollY;
        const body = document.body.style;
        const prev = { position: body.position, top: body.top, left: body.left, right: body.right, overflow: body.overflow };
        body.position = 'fixed';
        body.top = `-${scrollY}px`;
        body.left = '0';
        body.right = '0';
        body.overflow = 'hidden';

        const vv = window.visualViewport;
        const update = () => {
            if (!panel || !vv) return;
            panel.style.height = `${vv.height}px`;
            panel.style.transform = `translateY(${vv.offsetTop}px)`;
        };

        const keepTop = () => {
            if (window.scrollY !== 0) window.scrollTo(0, 0);
        };

        update();
        vv?.addEventListener('resize', update);
        vv?.addEventListener('scroll', update);
        window.addEventListener('scroll', keepTop);

        return () => {
            window.removeEventListener('scroll', keepTop);
            vv?.removeEventListener('resize', update);
            vv?.removeEventListener('scroll', update);
            if (panel) {
                panel.style.height = '';
                panel.style.transform = '';
            }
            Object.assign(body, prev);
            window.scrollTo(0, scrollY);
        };
    }, [isOpen]);

    if (auth?.user?.role === 'admin') return null;

    return (
        <>
            {!isOpen && (
                <button
                    ref={launcherRef}
                    type="button"
                    onClick={() => setIsOpen(true)}
                    aria-label="Open support chat"
                    className="fixed bottom-5 right-5 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-brand-green text-white shadow-lg transition-transform hover:scale-105 hover:bg-brand-green-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-ink focus-visible:ring-offset-2"
                >
                    <svg className="h-7 w-7" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                        />
                    </svg>
                </button>
            )}

            {isOpen && (
                <div
                    ref={panelRef}
                    role="dialog"
                    aria-modal="true"
                    aria-label="Jiidaa support chat"
                    className="fixed inset-0 z-50 flex h-dvh flex-col bg-white sm:inset-auto sm:bottom-5 sm:right-5 sm:h-[560px] sm:max-h-[calc(100vh-2.5rem)] sm:w-[380px] sm:rounded-2xl sm:border sm:border-brand-line sm:shadow-2xl overflow-hidden"
                >
                    <div className="flex items-center justify-between bg-brand-ink px-4 py-3">
                        <div className="flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-green/20">
                                <svg className="h-5 w-5 text-brand-green" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h2 className="text-sm font-semibold text-white">Jiidaa Support</h2>
                                <p className="text-xs text-brand-green">We typically reply right away</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={close}
                            aria-label="Close support chat"
                            className="rounded-lg p-2 text-white/70 transition-colors hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-green"
                        >
                            <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {/* "Talk to a human" control — available while the bot is handling the chat. */}
                    {conversation?.status === 'bot' && (
                        <div className="flex items-center justify-between gap-3 border-b border-brand-line bg-brand-surface px-4 py-2">
                            <span className="text-xs text-brand-muted">Prefer a person?</span>
                            {isAuthenticated ? (
                                <button
                                    type="button"
                                    onClick={escalate}
                                    disabled={isEscalating}
                                    className="inline-flex items-center gap-1.5 rounded-full border border-brand-green px-3 py-1 text-xs font-semibold text-brand-green transition-colors hover:bg-brand-green hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-green disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {isEscalating ? 'Connecting…' : 'Talk to a human'}
                                </button>
                            ) : (
                                <Link
                                    href="/login"
                                    className="inline-flex items-center gap-1.5 rounded-full border border-brand-green px-3 py-1 text-xs font-semibold text-brand-green transition-colors hover:bg-brand-green hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-green"
                                >
                                    Sign in to talk to a human
                                </Link>
                            )}
                        </div>
                    )}
                    {conversation?.status === 'awaiting_agent' && (
                        <div className="flex items-center gap-2 border-b border-brand-line bg-amber-50 px-4 py-2">
                            <span className="h-2 w-2 flex-shrink-0 animate-pulse rounded-full bg-amber-500" />
                            <span className="text-xs text-amber-800">
                                Connecting you to an agent — the bot will keep helping until they join.
                            </span>
                        </div>
                    )}
                    {conversation?.status === 'with_agent' && (
                        <div className="flex items-center gap-2 border-b border-brand-line bg-brand-green/10 px-4 py-2">
                            <span className="h-2 w-2 flex-shrink-0 rounded-full bg-brand-green" />
                            <span className="text-xs text-brand-ink">
                                You're chatting with {conversation.agent?.name ?? 'a human agent'}.
                            </span>
                        </div>
                    )}
                    {conversation?.status === 'resolved' && (
                        <div className="flex items-center gap-2 border-b border-brand-line bg-gray-50 px-4 py-2">
                            <span className="h-2 w-2 flex-shrink-0 rounded-full bg-gray-400" />
                            <span className="text-xs text-brand-muted">This conversation has been resolved.</span>
                        </div>
                    )}

                    {isLoading ? (
                        <div className="flex flex-1 items-center justify-center bg-brand-surface">
                            <span className="text-sm text-brand-muted">Loading…</span>
                        </div>
                    ) : (
                        <SupportThread
                            messages={messages}
                            isTyping={isTyping}
                            onQuickPrompt={sendMessage}
                            hasOlderMessages={hasOlderMessages}
                            onLoadOlder={loadOlderMessages}
                        />
                    )}

                    {error && (
                        <div className="flex items-center justify-between gap-2 border-t border-brand-line bg-red-50 px-4 py-2">
                            <span className="text-xs text-brand-danger">{error}</span>
                            <button
                                type="button"
                                onClick={retry}
                                className="text-xs font-semibold text-brand-ink underline underline-offset-2 hover:text-brand-green focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-green"
                            >
                                Retry
                            </button>
                        </div>
                    )}

                    <div className="border-t border-brand-line bg-white px-3 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                        <div className="flex items-end gap-2">
                            <button
                                type="button"
                                onClick={shareLocation}
                                aria-label={hasLocation ? 'Location shared' : 'Share your location'}
                                aria-pressed={hasLocation}
                                title={hasLocation ? 'Location shared' : 'Share your location so we can find vendors near you'}
                                className={`flex h-10 w-10 items-center justify-center rounded-xl border transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-green ${
                                    hasLocation
                                        ? 'border-brand-green bg-brand-green/10 text-brand-green'
                                        : 'border-brand-line text-gray-400 hover:border-brand-green hover:text-brand-green'
                                }`}
                            >
                                <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                            <textarea
                                ref={inputRef}
                                value={draft}
                                onChange={(e) => setDraft(e.target.value)}
                                onKeyDown={handleComposerKeyDown}
                                rows={1}
                                placeholder="Type your message…"
                                aria-label="Type your message"
                                className="max-h-28 flex-1 resize-none rounded-xl border border-brand-line bg-gray-50 px-3.5 py-2.5 text-base text-gray-900 placeholder-brand-muted focus:border-brand-green focus:outline-none focus:ring-1 focus:ring-brand-green sm:text-sm"
                            />
                            <button
                                type="button"
                                onClick={handleSend}
                                disabled={draft.trim() === '' || isTyping}
                                aria-label="Send message"
                                className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-green text-white transition-colors hover:bg-brand-green-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-ink disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
