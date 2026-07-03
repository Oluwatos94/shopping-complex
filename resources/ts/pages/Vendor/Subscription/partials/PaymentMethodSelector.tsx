import type { PaymentMethod } from '@/types/vendor';

interface Props {
    value: PaymentMethod;
    onChange: (method: PaymentMethod) => void;
}

export default function PaymentMethodSelector({ value, onChange }: Props) {
    return (
        <div className="mb-6">
            <p className="text-sm font-medium text-gray-700 mb-3">Pay with</p>
            <div className="inline-flex rounded-xl border border-gray-200 bg-gray-50 p-1 gap-1">
                {/* Paystack rail temporarily hidden — to be implemented later.
                <button
                    type="button"
                    onClick={() => onChange('paystack')}
                    className={`px-4 py-2 rounded-lg text-sm font-semibold transition-all ${
                        value === 'paystack'
                            ? 'bg-white text-brand-ink shadow-sm border border-gray-200'
                            : 'text-gray-500 hover:text-gray-700'
                    }`}
                >
                    Card / Bank (Paystack)
                </button>
                */}
                <button
                    type="button"
                    onClick={() => onChange('stellar')}
                    className={`px-4 py-2 rounded-lg text-sm font-semibold transition-all ${
                        value === 'stellar'
                            ? 'bg-white text-brand-ink shadow-sm border border-gray-200'
                            : 'text-gray-500 hover:text-gray-700'
                    }`}
                >
                    Direct transfer
                </button>
            </div>
        </div>
    );
}
