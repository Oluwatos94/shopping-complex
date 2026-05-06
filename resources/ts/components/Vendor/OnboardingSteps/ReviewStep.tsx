import { OnboardingData } from '@/hooks/useOnboarding';

interface ReviewStepProps {
    data: OnboardingData;
    categories: { id: number; name: string; slug: string }[];
    agreedToTermsError?: string;
    onEditStep: (step: number) => void;
    onToggleTerms: (agreed: boolean) => void;
}

interface ReviewSectionProps {
    title: string;
    onEdit: () => void;
    children: React.ReactNode;
}

function ReviewSection({ title, children }: ReviewSectionProps) {
    return (
        <div className="border border-gray-200 rounded-lg overflow-hidden">
            <div className="bg-gray-50 px-5 py-3 border-b border-gray-200">
                <h3 className="font-medium text-gray-900">{title}</h3>
            </div>
            <div className="divide-y divide-gray-100">
                {children}
            </div>
        </div>
    );
}

interface ReviewRowProps {
    label: string;
    value: string | React.ReactNode;
    onEdit: () => void;
}

function ReviewRow({ label, value, onEdit }: ReviewRowProps) {
    return (
        <div className="flex items-center justify-between px-5 py-3">
            <div className="flex-1">
                <dt className="text-sm text-gray-500">{label}</dt>
                <dd className="mt-0.5 text-sm font-medium text-gray-900">{value || '-'}</dd>
            </div>
            <button
                type="button"
                onClick={onEdit}
                className="text-sm text-primary-olive hover:text-primary-dark font-medium transition-colors"
            >
                Edit
            </button>
        </div>
    );
}

export default function ReviewStep({
    data,
    categories,
    agreedToTermsError,
    onEditStep,
    onToggleTerms,
}: ReviewStepProps) {
    const getCategoryName = (slug: string) => {
        const category = categories.find(c => c.slug === slug);
        return category?.name || slug;
    };

    const maskAccountNumber = (accountNumber: string) => {
        if (accountNumber.length <= 3) return accountNumber;
        return '*'.repeat(accountNumber.length - 3) + accountNumber.slice(-3);
    };

    const getFileName = (file: File | null, preview?: string) => {
        if (file) return file.name;
        if (preview) return preview.split('/').pop() || 'Uploaded';
        return null;
    };

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-2xl font-serif font-bold text-gray-900">Review Your Information</h2>
                <p className="mt-1 text-gray-500">Please review all information before submitting</p>
            </div>

            <div className="space-y-5">
                {/* Business Information */}
                <ReviewSection title="Business Information" onEdit={() => onEditStep(1)}>
                    <ReviewRow
                        label="Legal Entity Name:"
                        value={data.business_info.legal_entity_name}
                        onEdit={() => onEditStep(1)}
                    />
                    <ReviewRow
                        label="Business Category:"
                        value={getCategoryName(data.business_info.business_category)}
                        onEdit={() => onEditStep(1)}
                    />
                    <ReviewRow
                        label="TIN:"
                        value={data.business_info.tax_identification_number}
                        onEdit={() => onEditStep(1)}
                    />
                    <ReviewRow
                        label="Physical Address:"
                        value={data.business_info.physical_address}
                        onEdit={() => onEditStep(1)}
                    />
                </ReviewSection>

                {/* Business Verification */}
                <ReviewSection title="Business Verification" onEdit={() => onEditStep(2)}>
                    <ReviewRow
                        label="Certificate of Incorporation:"
                        value={
                            getFileName(data.verification.certificate_of_incorporation, data.verification.certificate_of_incorporation_preview) ? (
                                <span className="flex items-center gap-2">
                                    <span className="text-primary-olive">
                                        {getFileName(data.verification.certificate_of_incorporation, data.verification.certificate_of_incorporation_preview)}
                                    </span>
                                    <button
                                        type="button"
                                        className="text-xs text-gray-500 hover:text-primary-olive"
                                        onClick={() => {
                                            const preview = data.verification.certificate_of_incorporation_preview;
                                            if (preview) window.open(preview, '_blank');
                                        }}
                                    >
                                        (View)
                                    </button>
                                </span>
                            ) : (
                                <span className="text-red-500">Not uploaded</span>
                            )
                        }
                        onEdit={() => onEditStep(2)}
                    />
                    <ReviewRow
                        label="Government-Issued ID:"
                        value={
                            getFileName(data.verification.government_issued_id, data.verification.government_issued_id_preview) ? (
                                <span className="flex items-center gap-2">
                                    <span className="text-primary-olive">
                                        {getFileName(data.verification.government_issued_id, data.verification.government_issued_id_preview)}
                                    </span>
                                    <button
                                        type="button"
                                        className="text-xs text-gray-500 hover:text-primary-olive"
                                        onClick={() => {
                                            const preview = data.verification.government_issued_id_preview;
                                            if (preview) window.open(preview, '_blank');
                                        }}
                                    >
                                        (View)
                                    </button>
                                </span>
                            ) : (
                                <span className="text-red-500">Not uploaded</span>
                            )
                        }
                        onEdit={() => onEditStep(2)}
                    />
                    <ReviewRow
                        label="Proof of Address:"
                        value={
                            getFileName(data.verification.proof_of_address, data.verification.proof_of_address_preview) ? (
                                <span className="flex items-center gap-2">
                                    <span className="text-primary-olive">
                                        {getFileName(data.verification.proof_of_address, data.verification.proof_of_address_preview)}
                                    </span>
                                    <button
                                        type="button"
                                        className="text-xs text-gray-500 hover:text-primary-olive"
                                        onClick={() => {
                                            const preview = data.verification.proof_of_address_preview;
                                            if (preview) window.open(preview, '_blank');
                                        }}
                                    >
                                        (View)
                                    </button>
                                </span>
                            ) : (
                                <span className="text-red-500">Not uploaded</span>
                            )
                        }
                        onEdit={() => onEditStep(2)}
                    />
                </ReviewSection>

                {/* Bank & Payment Details */}
                <ReviewSection title="Bank & Payment Details" onEdit={() => onEditStep(3)}>
                    <ReviewRow
                        label="Bank Name & Branch:"
                        value={
                            data.bank_details.bank_name && data.bank_details.bank_branch
                                ? `${data.bank_details.bank_name}, ${data.bank_details.bank_branch}`
                                : data.bank_details.bank_name || '-'
                        }
                        onEdit={() => onEditStep(3)}
                    />
                    <ReviewRow
                        label="Account Number / IBAN:"
                        value={maskAccountNumber(data.bank_details.account_number)}
                        onEdit={() => onEditStep(3)}
                    />
                    <ReviewRow
                        label="SWIFT/BIC Code:"
                        value={data.bank_details.swift_bic_code || '-'}
                        onEdit={() => onEditStep(3)}
                    />
                </ReviewSection>
            </div>

            {/* Terms and Conditions */}
            <div className="pt-4">
                <label className="flex items-start gap-3 cursor-pointer">
                    <input
                        type="checkbox"
                        checked={data.agreed_to_terms}
                        onChange={(e) => onToggleTerms(e.target.checked)}
                        className="mt-0.5 w-5 h-5 rounded border-gray-300 text-primary-olive focus:ring-primary-olive"
                    />
                    <span className="text-sm text-gray-600">
                        I agree to the{' '}
                        <a
                            href="/terms/vendor"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-primary-olive hover:underline"
                        >
                            jiidaa Vendor Terms & Conditions
                        </a>
                        .
                    </span>
                </label>
                {agreedToTermsError && (
                    <p className="mt-2 text-sm text-red-600">{agreedToTermsError}</p>
                )}
            </div>
        </div>
    );
}
