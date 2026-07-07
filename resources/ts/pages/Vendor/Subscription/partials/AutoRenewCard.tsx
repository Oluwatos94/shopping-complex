import { useCallback, useState } from 'react';
import { router } from '@inertiajs/react';
import NotificationModal from '@/components/NotificationModal';
import type { AutoRenewState, VendorSubscription } from '@/types/vendor';

const dateFormatter = new Intl.DateTimeFormat('en-NG', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

interface Props {
    autoRenew: AutoRenewState;
    subscription: VendorSubscription;
}

export default function AutoRenewCard({ autoRenew, subscription }: Props) {
    const [processing, setProcessing] = useState(false);
    const [confirmingDisable, setConfirmingDisable] = useState(false);
    const { plan } = subscription;

    const enable = useCallback(() => {
        setProcessing(true);
        router.post('/vendor/subscription/auto-renew', {}, {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
        });
    }, []);

    const disable = useCallback(() => {
        setProcessing(true);
        router.post('/vendor/subscription/auto-renew/revoke', {}, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setConfirmingDisable(false);
            },
        });
    }, []);

    return (
        <div className="bg-white rounded-2xl border border-gray-200 p-6 mb-8">
            <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div className="max-w-xl">
                    <div className="flex items-center gap-2 mb-1">
                        <h3 className="text-lg font-bold text-brand-ink">Auto-renew</h3>
                        {autoRenew.enabled && (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                On
                            </span>
                        )}
                    </div>

                    {autoRenew.enabled ? (
                        <p className="text-sm text-gray-500">
                            Your <span className="font-medium text-gray-700">{plan.name}</span> plan renews automatically
                            each month
                            {autoRenew.validUntil && (
                                <>, until {dateFormatter.format(new Date(autoRenew.validUntil))}</>
                            )}
                            . You can turn this off anytime.
                        </p>
                    ) : (
                        <p className="text-sm text-gray-500">
                            Automatically renew your <span className="font-medium text-gray-700">{plan.name}</span> plan
                            each month.
                        </p>
                    )}
                </div>

                <div className="shrink-0">
                    {autoRenew.enabled ? (
                        <button
                            type="button"
                            onClick={() => setConfirmingDisable(true)}
                            disabled={processing}
                            className="px-4 py-2 rounded-lg text-sm font-semibold border border-gray-200 text-gray-600 hover:text-red-600 hover:border-red-200 transition-colors disabled:opacity-50"
                        >
                            {processing ? 'Working…' : 'Turn off'}
                        </button>
                    ) : (
                        <button
                            type="button"
                            onClick={enable}
                            disabled={processing}
                            className="px-4 py-2 rounded-lg text-sm font-semibold bg-brand-green text-white hover:opacity-90 transition-opacity disabled:opacity-50"
                        >
                            {processing ? 'Enabling…' : 'Enable auto-renew'}
                        </button>
                    )}
                </div>
            </div>

            <NotificationModal
                open={confirmingDisable}
                title="Turn off auto-renew?"
                message="Your plan will run until it expires, then lapse."
                confirmLabel="Turn off"
                tone="danger"
                processing={processing}
                onConfirm={disable}
                onClose={() => setConfirmingDisable(false)}
            />
        </div>
    );
}
