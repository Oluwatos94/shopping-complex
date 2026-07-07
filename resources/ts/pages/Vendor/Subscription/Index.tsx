import { useCallback, useEffect, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import VendorSidebar from '@/components/VendorSidebar';
import FlashBanner from '@/components/FlashBanner';
import type { AutoRenewState, PaymentMethod, SubscriptionPlan, VendorSubscription } from '@/types/vendor';
import AutoRenewCard from './partials/AutoRenewCard';
import CurrentPlanCard from './partials/CurrentPlanCard';
import PaymentMethodSelector from './partials/PaymentMethodSelector';
import PlanCard from './partials/PlanCard';
import StellarDepositModal from './partials/StellarDepositModal';

interface Props {
    plans: SubscriptionPlan[];
    currentSubscription: VendorSubscription | null;
    productsCount: number;
    autoRenew: AutoRenewState;
}

interface StellarCheckout {
    url: string;
    reference: string;
}

interface SharedProps {
    flash: { success?: string; error?: string; stellarCheckout?: StellarCheckout };
    [key: string]: unknown;
}

export default function SubscriptionIndex({ plans, currentSubscription, productsCount, autoRenew }: Props) {
    const { flash } = usePage<SharedProps>().props;
    const [processingPlanId, setProcessingPlanId] = useState<number | null>(null);
    const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>('stellar');
    const [stellarCheckout, setStellarCheckout] = useState<StellarCheckout | null>(null);

    useEffect(() => {
        if (flash?.stellarCheckout?.url && flash.stellarCheckout.reference) {
            setStellarCheckout(flash.stellarCheckout);
        }
    }, [flash?.stellarCheckout]);

    const handleSubscribe = useCallback((planId: number) => {
        setProcessingPlanId(planId);
        router.post(`/vendor/subscription/${planId}`, { method: paymentMethod }, {
            onFinish: () => setProcessingPlanId(null),
        });
    }, [paymentMethod]);

    const handleStellarCompleted = useCallback(() => {
        setStellarCheckout(null);
        router.reload();
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

            {stellarCheckout && (
                <StellarDepositModal
                    url={stellarCheckout.url}
                    reference={stellarCheckout.reference}
                    onClose={() => setStellarCheckout(null)}
                    onCompleted={handleStellarCompleted}
                />
            )}

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

                    {currentSubscription &&
                        currentSubscription.status === 'active' &&
                        currentSubscription.plan.slug !== 'free' && (
                            <AutoRenewCard autoRenew={autoRenew} subscription={currentSubscription} />
                        )}

                    <PaymentMethodSelector value={paymentMethod} onChange={setPaymentMethod} />

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                        {plans.map((plan) => (
                            <PlanCard
                                key={plan.id}
                                plan={plan}
                                isCurrentPlan={currentSubscription?.plan_id === plan.id}
                                onSubscribe={handleSubscribe}
                                processing={processingPlanId === plan.id}
                                paymentMethod={paymentMethod}
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
