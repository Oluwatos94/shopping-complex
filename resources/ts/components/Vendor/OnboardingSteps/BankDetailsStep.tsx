import { BankDetails } from '@/hooks/useOnboarding';

interface BankDetailsStepProps {
    data: BankDetails;
    errors: Partial<Record<keyof BankDetails, string>>;
    onUpdate: (field: keyof BankDetails, value: string) => void;
}

export default function BankDetailsStep({ data, errors, onUpdate }: BankDetailsStepProps) {
    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-2xl font-serif font-bold text-gray-900">Bank & Payment Details</h2>
                <p className="mt-1 text-gray-500">Enter your banking information for payments</p>
            </div>

            <div className="space-y-5">
                {/* Bank Name */}
                <div>
                    <label htmlFor="bank_name" className="block text-sm font-medium text-gray-900 mb-2">
                        Bank Name
                    </label>
                    <input
                        type="text"
                        id="bank_name"
                        value={data.bank_name}
                        onChange={(e) => onUpdate('bank_name', e.target.value)}
                        placeholder="Bank Name"
                        className={`
                            w-full px-4 py-3 border rounded-lg text-gray-900 placeholder-gray-400
                            focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent
                            transition-colors
                            ${errors.bank_name ? 'border-red-500' : 'border-gray-300'}
                        `}
                    />
                    {errors.bank_name && (
                        <p className="mt-1.5 text-sm text-red-600">{errors.bank_name}</p>
                    )}
                </div>

                {/* Bank Branch */}
                <div>
                    <label htmlFor="bank_branch" className="block text-sm font-medium text-gray-900 mb-2">
                        Bank Branch
                    </label>
                    <input
                        type="text"
                        id="bank_branch"
                        value={data.bank_branch}
                        onChange={(e) => onUpdate('bank_branch', e.target.value)}
                        placeholder="Airport Road, Lagos"
                        className={`
                            w-full px-4 py-3 border rounded-lg text-gray-900 placeholder-gray-400
                            focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent
                            transition-colors
                            ${errors.bank_branch ? 'border-red-500' : 'border-gray-300'}
                        `}
                    />
                    {errors.bank_branch && (
                        <p className="mt-1.5 text-sm text-red-600">{errors.bank_branch}</p>
                    )}
                </div>

                {/* Account Number */}
                <div>
                    <label htmlFor="account_number" className="block text-sm font-medium text-gray-900 mb-2">
                        Account Number / IBAN
                    </label>
                    <input
                        type="text"
                        id="account_number"
                        value={data.account_number}
                        onChange={(e) => onUpdate('account_number', e.target.value)}
                        placeholder="0012345678"
                        className={`
                            w-full px-4 py-3 border rounded-lg text-gray-900 placeholder-gray-400
                            focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent
                            transition-colors
                            ${errors.account_number ? 'border-red-500' : 'border-gray-300'}
                        `}
                    />
                    {errors.account_number && (
                        <p className="mt-1.5 text-sm text-red-600">{errors.account_number}</p>
                    )}
                </div>

                {/* SWIFT/BIC Code */}
                <div>
                    <label htmlFor="swift_bic_code" className="block text-sm font-medium text-gray-900 mb-2">
                        SWIFT/BIC Code <span className="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input
                        type="text"
                        id="swift_bic_code"
                        value={data.swift_bic_code}
                        onChange={(e) => onUpdate('swift_bic_code', e.target.value)}
                        placeholder="NANBLG0123"
                        className={`
                            w-full px-4 py-3 border rounded-lg text-gray-900 placeholder-gray-400
                            focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent
                            transition-colors
                            ${errors.swift_bic_code ? 'border-red-500' : 'border-gray-300'}
                        `}
                    />
                    {errors.swift_bic_code && (
                        <p className="mt-1.5 text-sm text-red-600">{errors.swift_bic_code}</p>
                    )}
                </div>
            </div>

            {/* Security Notice */}
            <div className="flex items-center gap-3 p-4 bg-primary-peach/20 rounded-lg">
                <div className="flex-shrink-0">
                    <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <p className="text-sm text-gray-700">
                    Your banking details are securely encrypted and protected
                </p>
            </div>
        </div>
    );
}
