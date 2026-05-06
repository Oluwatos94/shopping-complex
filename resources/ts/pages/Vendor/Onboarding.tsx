import { Head } from '@inertiajs/react';
import { useOnboarding, OnboardingData } from '@/hooks/useOnboarding';
import {
    StepIndicator,
    BusinessInfoStep,
    VerificationStep,
    BankDetailsStep,
    ReviewStep,
} from '@/components/Vendor/OnboardingSteps';
import { Category } from '@/types/product';

interface OnboardingProps {
    categories: Pick<Category, 'id' | 'name' | 'slug'>[];
    savedData?: Partial<OnboardingData>;
}

const STEPS = [
    { number: 1, label: 'Business Info' },
    { number: 2, label: 'Verification' },
    { number: 3, label: 'Bank Details' },
    { number: 4, label: 'Review' },
];

export default function Onboarding({ categories, savedData }: OnboardingProps) {
    const {
        currentStep,
        data,
        errors,
        isSubmitting,
        isSaving,
        updateBusinessInfo,
        updateVerification,
        updateBankDetails,
        setAgreedToTerms,
        goToStep,
        nextStep,
        prevStep,
        saveAndExit,
        submit,
    } = useOnboarding(savedData);

    const getStepContent = () => {
        switch (currentStep) {
            case 1:
                return (
                    <BusinessInfoStep
                        data={data.business_info}
                        errors={errors.business_info}
                        categories={categories}
                        onUpdate={updateBusinessInfo}
                    />
                );
            case 2:
                return (
                    <VerificationStep
                        data={data.verification}
                        errors={errors.verification}
                        onUpdate={updateVerification}
                    />
                );
            case 3:
                return (
                    <BankDetailsStep
                        data={data.bank_details}
                        errors={errors.bank_details}
                        onUpdate={updateBankDetails}
                    />
                );
            case 4:
                return (
                    <ReviewStep
                        data={data}
                        categories={categories}
                        agreedToTermsError={errors.agreed_to_terms}
                        onEditStep={goToStep}
                        onToggleTerms={setAgreedToTerms}
                    />
                );
            default:
                return null;
        }
    };

    const getNextButtonText = () => {
        if (currentStep === 4) {
            return isSubmitting ? 'Submitting...' : 'Submit Application';
        }
        return 'Next';
    };

    const getPrevButtonText = () => {
        if (currentStep === 1) {
            return 'Back';
        }
        return 'Back';
    };

    return (
        <>
            <Head title="Vendor Onboarding - jiidaa" />

            <div className="min-h-screen bg-gray-50">
                {/* Header */}
                <header className="bg-white border-b border-gray-200">
                    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center justify-between h-16">
                            {/* Logo */}
                            <div className="flex items-center gap-3">
                                <img
                                    src="/logo/jiidaa.jpeg"
                                    alt="jiidaa"
                                    className="h-10 w-auto"
                                />
                                <h1 className="text-xl font-semibold text-gray-900">
                                    Vendor Onboarding
                                </h1>
                            </div>

                            {/* Save and Exit */}
                            <button
                                type="button"
                                onClick={saveAndExit}
                                disabled={isSaving}
                                className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50"
                            >
                                {isSaving ? 'Saving...' : 'Save and Exit'}
                            </button>
                        </div>
                    </div>
                </header>

                {/* Main Content */}
                <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        {/* Step Indicator */}
                        <div className="px-6 sm:px-10 pt-6 pb-2 border-b border-gray-100">
                            <StepIndicator
                                currentStep={currentStep}
                                steps={STEPS}
                                onStepClick={goToStep}
                            />
                        </div>

                        {/* Step Content */}
                        <div className="px-6 sm:px-10 py-8">
                            {getStepContent()}
                        </div>

                        {/* Navigation Buttons */}
                        <div className="px-6 sm:px-10 py-6 bg-gray-50 border-t border-gray-100">
                            <div className="flex items-center justify-between">
                                <button
                                    type="button"
                                    onClick={prevStep}
                                    disabled={currentStep === 1}
                                    className={`
                                        px-6 py-3 border rounded-lg text-sm font-medium transition-colors
                                        ${currentStep === 1
                                            ? 'border-gray-200 text-gray-400 cursor-not-allowed'
                                            : 'border-gray-300 text-gray-700 hover:bg-gray-100'
                                        }
                                    `}
                                >
                                    {getPrevButtonText()}
                                </button>

                                <button
                                    type="button"
                                    onClick={currentStep === 4 ? submit : nextStep}
                                    disabled={isSubmitting}
                                    className="px-6 py-3 bg-primary-olive text-white rounded-lg text-sm font-medium hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                >
                                    {isSubmitting && (
                                        <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                    )}
                                    {getNextButtonText()}
                                </button>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
