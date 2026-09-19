import { useState } from 'react';
import { fetchWithCsrf } from '@/utils/csrf';
import type { CoinPack } from '@/types/billing';

interface Props {
    pack: CoinPack;
    vendor: { business_name: string; email: string };
    onClose: () => void;
    onDone: () => void;
}

type State = 'confirm' | 'processing' | 'done' | 'error';

const naira = (n: number) => `₦${n.toLocaleString('en-US')}`;
const coins = (n: number) => n.toLocaleString('en-US');

export default function CoinConfirmModal({ pack, vendor, onClose, onDone }: Props) {
    const [state, setState] = useState<State>('confirm');
    const [message, setMessage] = useState<string | null>(null);
    const [txHash, setTxHash] = useState<string | null>(null);

    const pay = async () => {
        setState('processing');
        try {
            const res = await fetchWithCsrf(`/vendor/coins/${pack.key}/stellar`, { method: 'POST' });
            const data = (await res.json()) as { status: string; tx_hash?: string; message?: string };

            if (res.ok && data.status === 'completed') {
                setTxHash(data.tx_hash ?? null);
                setState('done');
            } else {
                setMessage(data.message ?? 'The payment could not be completed.');
                setState('error');
            }
        } catch {
            setMessage('Network error — please try again.');
            setState('error');
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div className="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div className="flex items-center justify-between border-b border-brand-line px-5 py-4">
                    <h2 className="text-base font-bold text-brand-ink">
                        {state === 'done' ? 'Payment confirmed' : 'Confirm your purchase'}
                    </h2>
                    {state !== 'processing' && (
                        <button
                            type="button"
                            onClick={onClose}
                            aria-label="Close"
                            className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                        >
                            <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    )}
                </div>

                <div className="px-5 py-5">
                    {state === 'done' ? (
                        <div className="text-center">
                            <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-green/10">
                                <svg className="h-6 w-6 text-brand-green" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p className="text-sm text-brand-ink">
                                <span className="font-bold">{coins(pack.total_coins)} coins</span> added to your wallet.
                            </p>
                            {txHash && (
                                <a
                                    href={`https://stellar.expert/explorer/testnet/tx/${txHash}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="mt-2 inline-block break-all text-xs text-brand-green-dark underline underline-offset-2"
                                >
                                    View transaction on Stellar
                                </a>
                            )}
                            <button
                                type="button"
                                onClick={onDone}
                                className="mt-5 flex h-11 w-full items-center justify-center rounded-xl bg-brand-green text-sm font-bold text-white transition hover:bg-brand-green-dark"
                            >
                                Done
                            </button>
                        </div>
                    ) : (
                        <>
                            <dl className="space-y-3 text-sm">
                                <div className="flex justify-between gap-4">
                                    <dt className="text-brand-muted">Payer</dt>
                                    <dd className="text-right font-medium text-brand-ink">{vendor.business_name}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-brand-muted">Email</dt>
                                    <dd className="break-all text-right text-brand-ink">{vendor.email}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-brand-muted">Pack</dt>
                                    <dd className="text-right font-medium text-brand-ink">
                                        {pack.name} — {coins(pack.total_coins)} coins
                                        {pack.bonus_coins > 0 && (
                                            <span className="text-brand-muted"> ({coins(pack.coins)} + {coins(pack.bonus_coins)} bonus)</span>
                                        )}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-4 border-t border-brand-line pt-3">
                                    <dt className="font-semibold text-brand-ink">Amount</dt>
                                    <dd className="text-right text-lg font-extrabold text-brand-ink">{naira(pack.price)}</dd>
                                </div>
                            </dl>

                            {state === 'error' && (
                                <p className="mt-4 rounded-lg bg-red-50 px-3 py-2 text-xs text-brand-danger">{message}</p>
                            )}

                            <div className="mt-6 flex gap-3">
                                <button
                                    type="button"
                                    onClick={onClose}
                                    disabled={state === 'processing'}
                                    className="flex h-11 flex-1 items-center justify-center rounded-xl border border-brand-line text-sm font-semibold text-brand-ink transition hover:bg-gray-50 disabled:opacity-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    onClick={pay}
                                    disabled={state === 'processing'}
                                    className="flex h-11 flex-1 items-center justify-center rounded-xl bg-brand-green text-sm font-bold text-white transition hover:bg-brand-green-dark disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {state === 'processing' ? 'Processing…' : 'Confirm & Pay'}
                                </button>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
