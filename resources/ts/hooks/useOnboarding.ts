import { useState, useCallback, useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';

export interface BusinessInfo {
    legal_entity_name: string;
    business_category: string;
    tax_identification_number: string;
    physical_address: string;
    whatsapp_number: string;
}

export interface VerificationDocuments {
    certificate_of_incorporation: File | null;
    government_issued_id: File | null;
    proof_of_address: File | null;
    certificate_of_incorporation_preview?: string;
    government_issued_id_preview?: string;
    proof_of_address_preview?: string;
}

export interface BankDetails {
    bank_name: string;
    bank_branch: string;
    account_number: string;
    swift_bic_code: string;
}

export interface OnboardingData {
    business_info: BusinessInfo;
    verification: VerificationDocuments;
    bank_details: BankDetails;
    agreed_to_terms: boolean;
}

export interface OnboardingErrors {
    business_info: Partial<Record<keyof BusinessInfo, string>>;
    verification: Partial<Record<keyof VerificationDocuments, string>>;
    bank_details: Partial<Record<keyof BankDetails, string>>;
    agreed_to_terms?: string;
}

const STORAGE_KEY = 'vendor_onboarding_draft';

const initialBusinessInfo: BusinessInfo = {
    legal_entity_name: '',
    business_category: '',
    tax_identification_number: '',
    physical_address: '',
    whatsapp_number: '',
};

const initialVerification: VerificationDocuments = {
    certificate_of_incorporation: null,
    government_issued_id: null,
    proof_of_address: null,
};

const initialBankDetails: BankDetails = {
    bank_name: '',
    bank_branch: '',
    account_number: '',
    swift_bic_code: '',
};

const initialData: OnboardingData = {
    business_info: initialBusinessInfo,
    verification: initialVerification,
    bank_details: initialBankDetails,
    agreed_to_terms: false,
};

const initialErrors: OnboardingErrors = {
    business_info: {},
    verification: {},
    bank_details: {},
};

type ErrorSection = 'business_info' | 'verification' | 'bank_details';

export function useOnboarding(savedData?: Partial<OnboardingData>) {
    const [currentStep, setCurrentStep] = useState(1);
    const [data, setData] = useState<OnboardingData>(() => {
        // Try to load from localStorage first
        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                try {
                    const parsed = JSON.parse(saved);
                    return {
                        ...initialData,
                        ...parsed,
                        verification: {
                            ...initialVerification,
                            ...parsed.verification,
                            // Files can't be stored in localStorage, only previews
                            certificate_of_incorporation: null,
                            government_issued_id: null,
                            proof_of_address: null,
                        },
                    };
                } catch {
                    // Invalid JSON, use initial data
                }
            }
        }
        return { ...initialData, ...savedData };
    });
    const [errors, setErrors] = useState<OnboardingErrors>(initialErrors);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    // Track object URLs for cleanup to prevent memory leaks
    const objectUrlsRef = useRef<Set<string>>(new Set());

    // Cleanup object URLs on unmount
    useEffect(() => {
        return () => {
            objectUrlsRef.current.forEach((url) => {
                URL.revokeObjectURL(url);
            });
            objectUrlsRef.current.clear();
        };
    }, []);

    // Auto-save to localStorage when data changes
    useEffect(() => {
        if (typeof window !== 'undefined') {
            const toSave = {
                business_info: data.business_info,
                bank_details: data.bank_details,
                verification: {
                    certificate_of_incorporation_preview: data.verification.certificate_of_incorporation_preview,
                    government_issued_id_preview: data.verification.government_issued_id_preview,
                    proof_of_address_preview: data.verification.proof_of_address_preview,
                },
                agreed_to_terms: data.agreed_to_terms,
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(toSave));
        }
    }, [data]);

    /**
     * Clear error for a specific field in a section (DRY helper)
     */
    const clearFieldError = useCallback(<T extends ErrorSection>(
        section: T,
        field: string
    ) => {
        setErrors(prev => ({
            ...prev,
            [section]: {
                ...prev[section],
                [field]: undefined,
            },
        }));
    }, []);

    const updateBusinessInfo = useCallback((field: keyof BusinessInfo, value: string) => {
        setData(prev => ({
            ...prev,
            business_info: {
                ...prev.business_info,
                [field]: value,
            },
        }));
        clearFieldError('business_info', field);
    }, [clearFieldError]);

    const updateVerification = useCallback((field: keyof VerificationDocuments, file: File | null) => {
        setData(prev => {
            const previewKey = `${field}_preview` as keyof VerificationDocuments;
            const oldPreviewUrl = prev.verification[previewKey] as string | undefined;

            // Revoke old object URL to prevent memory leak
            if (oldPreviewUrl && objectUrlsRef.current.has(oldPreviewUrl)) {
                URL.revokeObjectURL(oldPreviewUrl);
                objectUrlsRef.current.delete(oldPreviewUrl);
            }

            const newVerification = {
                ...prev.verification,
                [field]: file,
            };

            if (file) {
                // Create new object URL and track it
                const newUrl = URL.createObjectURL(file);
                objectUrlsRef.current.add(newUrl);
                newVerification[previewKey] = newUrl as any;
            } else {
                // Clear the preview when file is removed
                newVerification[previewKey] = undefined as any;
            }

            return {
                ...prev,
                verification: newVerification,
            };
        });
        clearFieldError('verification', field);
    }, [clearFieldError]);

    const updateBankDetails = useCallback((field: keyof BankDetails, value: string) => {
        setData(prev => ({
            ...prev,
            bank_details: {
                ...prev.bank_details,
                [field]: value,
            },
        }));
        clearFieldError('bank_details', field);
    }, [clearFieldError]);

    const setAgreedToTerms = useCallback((agreed: boolean) => {
        setData(prev => ({
            ...prev,
            agreed_to_terms: agreed,
        }));
        setErrors(prev => ({
            ...prev,
            agreed_to_terms: undefined,
        }));
    }, []);

    const validateBusinessInfo = useCallback((): boolean => {
        const newErrors: Partial<Record<keyof BusinessInfo, string>> = {};

        if (!data.business_info.legal_entity_name.trim()) {
            newErrors.legal_entity_name = 'Legal entity name is required';
        }
        if (!data.business_info.business_category.trim()) {
            newErrors.business_category = 'Business category is required';
        }
        if (!data.business_info.physical_address.trim()) {
            newErrors.physical_address = 'Physical address is required';
        }
        if (!data.business_info.whatsapp_number.trim()) {
            newErrors.whatsapp_number = 'Business WhatsApp number is required';
        }

        setErrors(prev => ({ ...prev, business_info: newErrors }));
        return Object.keys(newErrors).length === 0;
    }, [data.business_info]);

    const validateVerification = useCallback((): boolean => {
        const newErrors: Partial<Record<keyof VerificationDocuments, string>> = {};

        if (!data.verification.certificate_of_incorporation && !data.verification.certificate_of_incorporation_preview) {
            newErrors.certificate_of_incorporation = 'Certificate of incorporation is required';
        }
        if (!data.verification.government_issued_id && !data.verification.government_issued_id_preview) {
            newErrors.government_issued_id = 'Government-issued ID is required';
        }
        if (!data.verification.proof_of_address && !data.verification.proof_of_address_preview) {
            newErrors.proof_of_address = 'Proof of address is required';
        }

        setErrors(prev => ({ ...prev, verification: newErrors }));
        return Object.keys(newErrors).length === 0;
    }, [data.verification]);

    const validateBankDetails = useCallback((): boolean => {
        const newErrors: Partial<Record<keyof BankDetails, string>> = {};

        if (!data.bank_details.bank_name.trim()) {
            newErrors.bank_name = 'Bank name is required';
        }
        if (!data.bank_details.bank_branch.trim()) {
            newErrors.bank_branch = 'Bank branch is required';
        }
        if (!data.bank_details.account_number.trim()) {
            newErrors.account_number = 'Account number is required';
        }

        setErrors(prev => ({ ...prev, bank_details: newErrors }));
        return Object.keys(newErrors).length === 0;
    }, [data.bank_details]);

    const validateCurrentStep = useCallback((): boolean => {
        switch (currentStep) {
            case 1:
                return validateBusinessInfo();
            case 2:
                return validateVerification();
            case 3:
                return validateBankDetails();
            case 4:
                if (!data.agreed_to_terms) {
                    setErrors(prev => ({ ...prev, agreed_to_terms: 'You must agree to the terms and conditions' }));
                    return false;
                }
                return true;
            default:
                return true;
        }
    }, [currentStep, validateBusinessInfo, validateVerification, validateBankDetails, data.agreed_to_terms]);

    const goToStep = useCallback((step: number) => {
        if (step >= 1 && step <= 4) {
            setCurrentStep(step);
        }
    }, []);

    const nextStep = useCallback(() => {
        if (validateCurrentStep() && currentStep < 4) {
            setCurrentStep(prev => prev + 1);
        }
    }, [validateCurrentStep, currentStep]);

    const prevStep = useCallback(() => {
        if (currentStep > 1) {
            setCurrentStep(prev => prev - 1);
        }
    }, [currentStep]);

    const saveAndExit = useCallback(async () => {
        setIsSaving(true);
        try {
            const formData = new FormData();
            formData.append('business_info', JSON.stringify(data.business_info));
            formData.append('bank_details', JSON.stringify(data.bank_details));
            formData.append('current_step', currentStep.toString());
            formData.append('status', 'draft');

            if (data.verification.certificate_of_incorporation) {
                formData.append('certificate_of_incorporation', data.verification.certificate_of_incorporation);
            }
            if (data.verification.government_issued_id) {
                formData.append('government_issued_id', data.verification.government_issued_id);
            }
            if (data.verification.proof_of_address) {
                formData.append('proof_of_address', data.verification.proof_of_address);
            }

            router.post('/vendor/onboarding/save', formData, {
                forceFormData: true,
                onSuccess: () => {
                    localStorage.removeItem(STORAGE_KEY);
                    router.visit('/');
                },
                onError: () => {
                    setIsSaving(false);
                },
            });
        } catch {
            setIsSaving(false);
        }
    }, [data, currentStep]);

    const submit = useCallback(async () => {
        if (!validateCurrentStep()) {
            return;
        }

        setIsSubmitting(true);
        try {
            const formData = new FormData();
            formData.append('business_info', JSON.stringify(data.business_info));
            formData.append('bank_details', JSON.stringify(data.bank_details));
            formData.append('agreed_to_terms', data.agreed_to_terms ? '1' : '0');
            formData.append('status', 'pending_review');

            if (data.verification.certificate_of_incorporation) {
                formData.append('certificate_of_incorporation', data.verification.certificate_of_incorporation);
            }
            if (data.verification.government_issued_id) {
                formData.append('government_issued_id', data.verification.government_issued_id);
            }
            if (data.verification.proof_of_address) {
                formData.append('proof_of_address', data.verification.proof_of_address);
            }

            router.post('/vendor/onboarding/submit', formData, {
                forceFormData: true,
                onSuccess: () => {
                    localStorage.removeItem(STORAGE_KEY);
                },
                onError: () => {
                    setIsSubmitting(false);
                },
            });
        } catch {
            setIsSubmitting(false);
        }
    }, [data, validateCurrentStep]);

    const clearDraft = useCallback(() => {
        localStorage.removeItem(STORAGE_KEY);
        // Cleanup all object URLs
        objectUrlsRef.current.forEach((url) => {
            URL.revokeObjectURL(url);
        });
        objectUrlsRef.current.clear();
        setData(initialData);
        setErrors(initialErrors);
        setCurrentStep(1);
    }, []);

    return {
        currentStep,
        data,
        errors,
        isSubmitting,
        isSaving,
        updateBusinessInfo,
        updateVerification,
        updateBankDetails,
        setAgreedToTerms,
        validateCurrentStep,
        goToStep,
        nextStep,
        prevStep,
        saveAndExit,
        submit,
        clearDraft,
    };
}
