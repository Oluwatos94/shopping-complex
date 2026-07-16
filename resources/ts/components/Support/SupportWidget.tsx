import { useCallback, useEffect, useRef, useState } from 'react';
import type { SupportMessage } from '@/types/support';
import SupportThread from './SupportThread';

// Static scaffold content — replaced by real conversation data in the API wiring issue.
const PLACEHOLDER_MESSAGES: SupportMessage[] = [];

export default function SupportWidget() {
    const [isOpen, setIsOpen] = useState(false);
    const [draft, setDraft] = useState('');
    const panelRef = useRef<HTMLDivElement>(null);
    const launcherRef = useRef<HTMLButtonElement>(null);
    const inputRef = useRef<HTMLTextAreaElement>(null);

    const close = useCallback(() => {
        setIsOpen(false);
        launcherRef.current?.focus();
    }, []);

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
        if (isOpen) inputRef.current?.focus();
    }, [isOpen]);

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
                    className="fixed inset-0 z-50 flex flex-col bg-white sm:inset-auto sm:bottom-5 sm:right-5 sm:h-[560px] sm:max-h-[calc(100vh-2.5rem)] sm:w-[380px] sm:rounded-2xl sm:border sm:border-brand-line sm:shadow-2xl overflow-hidden"
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
                        {/* "Talk to a human" control lands here in the escalation issue. */}
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

                    <SupportThread
                        messages={PLACEHOLDER_MESSAGES}
                        isTyping={false}
                        onQuickPrompt={(text) => {
                            setDraft(text);
                            inputRef.current?.focus();
                        }}
                    />

                    <div className="border-t border-brand-line bg-white px-3 py-3">
                        <div className="flex items-end gap-2">
                            <textarea
                                ref={inputRef}
                                value={draft}
                                onChange={(e) => setDraft(e.target.value)}
                                rows={1}
                                placeholder="Type your message…"
                                aria-label="Type your message"
                                className="max-h-28 flex-1 resize-none rounded-xl border border-brand-line bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-brand-muted focus:border-brand-green focus:outline-none focus:ring-1 focus:ring-brand-green"
                            />
                            <button
                                type="button"
                                disabled
                                aria-label="Send message"
                                title="Coming soon"
                                className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-green text-white opacity-50 cursor-not-allowed"
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
