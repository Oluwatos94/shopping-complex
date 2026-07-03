import type { VendorSubscription } from '@/types/vendor';

const dateFormatter = new Intl.DateTimeFormat('en-NG', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

const STATUS_COLORS: Record<VendorSubscription['status'], string> = {
    active: 'bg-green-100 text-green-700',
    expired: 'bg-red-100 text-red-700',
    cancelled: 'bg-gray-100 text-gray-600',
};

interface Props {
    subscription: VendorSubscription;
    productsCount: number;
}

export default function CurrentPlanCard({ subscription, productsCount }: Props) {
    const { plan } = subscription;
    const isFree = plan.slug === 'free';
    const usagePercent = Math.min(Math.round((productsCount / plan.product_limit) * 100), 100);

    return (
        <div className="bg-white rounded-2xl border-2 border-brand-green p-6 mb-8">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                <div>
                    <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Current Plan</p>
                    <h2 className="text-2xl font-bold text-brand-ink">{plan.name}</h2>
                </div>
                <div className="flex items-center gap-3">
                    <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold capitalize ${STATUS_COLORS[subscription.status]}`}>
                        {subscription.status}
                    </span>
                    {!isFree && (
                        <span className="text-sm text-gray-500">
                            Renews {dateFormatter.format(new Date(subscription.expires_at))}
                        </span>
                    )}
                </div>
            </div>

            <div>
                <div className="flex items-center justify-between mb-2">
                    <span className="text-sm font-medium text-gray-700">Products</span>
                    <span className="text-sm text-gray-500">
                        {productsCount} / {plan.product_limit}
                    </span>
                </div>
                <div className="w-full bg-gray-100 rounded-full h-2">
                    <div
                        className={`h-2 rounded-full transition-all ${usagePercent >= 90 ? 'bg-red-500' : 'bg-brand-green'}`}
                        style={{ width: `${usagePercent}%` }}
                    />
                </div>
                {usagePercent >= 90 && (
                    <p className="text-xs text-red-600 mt-1.5">
                        You're close to your product limit. Upgrade to add more.
                    </p>
                )}
            </div>
        </div>
    );
}
