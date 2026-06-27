import { useCallback, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import VendorSidebar from '@/components/VendorSidebar';
import FlashBanner from '@/components/FlashBanner';
import type { SubscriptionPlan, VendorSubscription } from '@/types/vendor';

// ---------------------------------------------------------------------------
// Module-level constants — instantiated once, not on every render
// ---------------------------------------------------------------------------

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    maximumFractionDigits: 0,
});

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

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

interface Props {
    plans: SubscriptionPlan[];
    currentSubscription: VendorSubscription | null;
    productsCount: number;
}

interface SharedProps {
    flash: { success?: string; error?: string };
    [key: string]: unknown;
}

// ---------------------------------------------------------------------------
// Current plan card
// ---------------------------------------------------------------------------

function CurrentPlanCard({
    subscription,
    productsCount,
}: {
    subscription: VendorSubscription;
    productsCount: number;
}) {
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

// ---------------------------------------------------------------------------
// Plan card
// ---------------------------------------------------------------------------

function CheckIcon() {
    return (
        <svg className="w-4 h-4 text-brand-green flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 13l4 4L19 7" />
        </svg>
    );
}

function PlanCard({
    plan,
    isCurrentPlan,
    onSubscribe,
    processing,
}: {
    plan: SubscriptionPlan;
    isCurrentPlan: boolean;
    onSubscribe: (planId: number) => void;
    processing: boolean;
}) {
    const isFree = plan.slug === 'free';

    return (
        <div className={`bg-white rounded-2xl border-2 p-6 flex flex-col transition-shadow hover:shadow-md ${isCurrentPlan ? 'border-brand-green' : 'border-gray-200'}`}>
            <div className="mb-5">
                <div className="flex items-center justify-between mb-2">
                    <h3 className="text-lg font-bold text-brand-ink">{plan.name}</h3>
                    {isCurrentPlan && (
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-green/10 text-brand-green">
                            Current
                        </span>
                    )}
                </div>
                <div className="flex items-baseline gap-1">
                    {isFree ? (
                        <span className="text-3xl font-bold text-gray-900">Free</span>
                    ) : (
                        <>
                            <span className="text-3xl font-bold text-gray-900">
                                {currencyFormatter.format(plan.price)}
                            </span>
                            <span className="text-sm text-gray-500">/month</span>
                        </>
                    )}
                </div>
            </div>

            <ul className="flex-1 space-y-2.5 mb-6">
                <li className="flex items-center gap-2.5 text-sm text-gray-700">
                    <CheckIcon />
                    Up to {plan.product_limit} products
                </li>
                {(plan.features ?? []).map((feature) => (
                    <li key={feature} className="flex items-center gap-2.5 text-sm text-gray-700">
                        <CheckIcon />
                        {feature}
                    </li>
                ))}
            </ul>

            {isCurrentPlan ? (
                <button disabled className="w-full py-2.5 rounded-xl text-sm font-semibold bg-brand-green/10 text-brand-green cursor-default">
                    {isFree ? 'Default Plan' : 'Active Plan'}
                </button>
            ) : isFree ? (
                <button disabled className="w-full py-2.5 rounded-xl text-sm font-semibold bg-gray-100 text-gray-400 cursor-default">
                    Assigned on approval
                </button>
            ) : (
                <button
                    onClick={() => onSubscribe(plan.id)}
                    disabled={processing}
                    className="w-full py-2.5 rounded-xl text-sm font-semibold bg-brand-green text-white hover:bg-brand-ink transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    {processing ? 'Redirecting…' : 'Subscribe'}
                </button>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Page
// ---------------------------------------------------------------------------

export default function SubscriptionIndex({ plans, currentSubscription, productsCount }: Props) {
    const { flash } = usePage<SharedProps>().props;
    const [processingPlanId, setProcessingPlanId] = useState<number | null>(null);

    const handleSubscribe = useCallback((planId: number) => {
        setProcessingPlanId(planId);
        router.post(`/vendor/subscription/${planId}`, {}, {
            onFinish: () => setProcessingPlanId(null),
        });
    }, []);

    const handleCancel = useCallback(() => {
        if (confirm('Are you sure you want to cancel your subscription?')) {
            router.post('/vendor/subscription/cancel');
        }
    }, []);

    const showCancelLink =
        currentSubscription?.plan.slug !== 'free' &&
        currentSubscription?.status === 'active';

    return (
        <>
            <Head title="Subscription Plans" />
            <VendorSidebar />

            <main className="md:ml-[260px] min-h-screen bg-gray-50">
                <div className="max-w-5xl mx-auto px-6 py-8">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold text-gray-900">Subscription Plans</h1>
                        <p className="text-sm text-gray-500 mt-1">Choose the plan that fits your business.</p>
                    </div>

                    {flash?.success && <FlashBanner message={flash.success} type="success" />}
                    {flash?.error && <FlashBanner message={flash.error} type="error" />}

                    {currentSubscription && (
                        <CurrentPlanCard subscription={currentSubscription} productsCount={productsCount} />
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                        {plans.map((plan) => (
                            <PlanCard
                                key={plan.id}
                                plan={plan}
                                isCurrentPlan={currentSubscription?.plan_id === plan.id}
                                onSubscribe={handleSubscribe}
                                processing={processingPlanId === plan.id}
                            />
                        ))}
                    </div>

                    {showCancelLink && (
                        <div className="mt-8 text-center">
                            <button
                                onClick={handleCancel}
                                className="text-sm text-gray-400 hover:text-red-600 transition-colors underline underline-offset-2"
                            >
                                Cancel subscription
                            </button>
                        </div>
                    )}
                </div>
            </main>
        </>
    );
}
