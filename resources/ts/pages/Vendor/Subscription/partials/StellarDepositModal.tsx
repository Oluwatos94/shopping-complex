import { useEffect, useRef, useState } from 'react';

interface Props {
    url: string;
    reference: string;
    onClose: () => void;
    onCompleted: () => void;
}

type PollState = 'waiting' | 'completed' | 'failed';

const POLL_INTERVAL_MS = 4000;

export default function StellarDepositModal({ url, reference, onClose, onCompleted }: Props) {
    const [state, setState] = useState<PollState>('waiting');
    const [message, setMessage] = useState<string | null>(null);
    const onCompletedRef = useRef(onCompleted);
    onCompletedRef.current = onCompleted;

    const inFlightRef = useRef(false);

    useEffect(() => {
        let active = true;

        const poll = async () => {
            if (inFlightRef.current) return;
            inFlightRef.current = true;
            try {
                const res = await fetch(
                    `/vendor/subscription/stellar/status?reference=${encodeURIComponent(reference)}`,
                    { headers: { Accept: 'application/json' } },
                );
                const data = (await res.json()) as { status: 'pending' | 'completed' | 'failed' | 'error'; message?: string };
                if (!active) return;

                if (data.status === 'completed') {
                    setState('completed');
                    onCompletedRef.current();
                } else if (data.status === 'failed' || data.status === 'error') {
                    setState('failed');
                    setMessage(data.message ?? 'The deposit could not be completed.');
                }
            } catch {
                // Transient poll error — keep trying until the deposit settles or the user closes.
            } finally {
                inFlightRef.current = false;
            }
        };

        const interval = setInterval(() => {
            if (active && state === 'waiting') void poll();
        }, POLL_INTERVAL_MS);

        return () => {
            active = false;
            clearInterval(interval);
        };
    }, [reference, state]);

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col overflow-hidden" style={{ maxHeight: '90vh' }}>
                <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <div>
                        <h2 className="text-base font-semibold text-gray-900">Complete your payment</h2>
                        <p className="text-xs text-gray-500 mt-0.5">
                            {state === 'waiting' && 'Complete the transfer below — this updates automatically.'}
                            {state === 'completed' && 'Payment confirmed — activating your plan…'}
                            {state === 'failed' && (message ?? 'The payment could not be completed.')}
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600 transition-colors"
                        aria-label="Close"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <iframe
                    src={url}
                    className="flex-1 w-full border-0"
                    style={{ minHeight: '500px' }}
                    title="Complete payment"
                />
            </div>
        </div>
    );
}
