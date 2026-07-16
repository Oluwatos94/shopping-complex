import type { PaymentMethod, SubscriptionPlan } from '@/types/vendor';

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    maximumFractionDigits: 0,
});

function CheckIcon() {
    return (
        <svg className="w-4 h-4 text-brand-green flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 13l4 4L19 7" />
        </svg>
    );
}

interface Props {
    plan: SubscriptionPlan;
    isCurrentPlan: boolean;
    onSubscribe: (planId: number) => void;
    processing: boolean;
    paymentMethod: PaymentMethod;
}

export default function PlanCard({ plan, isCurrentPlan, onSubscribe, processing, paymentMethod }: Props) {
    const isFree = plan.slug === 'free';
    const buttonLabel = processing
        ? 'Redirecting…'
        : paymentMethod === 'paystack'
        ? 'Subscribe with Card / Bank'
        : 'Subscribe via transfer';

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
                    {buttonLabel}
                </button>
            )}
        </div>
    );
}
