import { useRef, useState, useCallback } from 'react';
import { VerificationDocuments } from '@/hooks/useOnboarding';

interface VerificationStepProps {
    data: VerificationDocuments;
    errors: Partial<Record<keyof VerificationDocuments, string>>;
    onUpdate: (field: keyof VerificationDocuments, file: File | null) => void;
}

interface FileUploadProps {
    id: string;
    label: string;
    description: string;
    acceptedTypes: string;
    maxSize: string;
    file: File | null;
    preview?: string;
    error?: string;
    onFileChange: (file: File | null) => void;
}

function FileUpload({
    id,
    label,
    description,
    acceptedTypes,
    maxSize,
    file,
    preview,
    error,
    onFileChange,
}: FileUploadProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [isDragging, setIsDragging] = useState(false);

    const handleDragOver = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(true);
    }, []);

    const handleDragLeave = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
    }, []);

    const handleDrop = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
        const droppedFile = e.dataTransfer.files[0];
        if (droppedFile) {
            onFileChange(droppedFile);
        }
    }, [onFileChange]);

    const handleChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const selectedFile = e.target.files?.[0];
        if (selectedFile) {
            onFileChange(selectedFile);
        }
    }, [onFileChange]);

    const handleRemove = useCallback(() => {
        onFileChange(null);
        if (inputRef.current) {
            inputRef.current.value = '';
        }
    }, [onFileChange]);

    const fileName = file?.name || (preview ? preview.split('/').pop() : null);

    return (
        <div className="space-y-2">
            <label className="block text-sm font-medium text-gray-900">
                {label}
            </label>

            {fileName ? (
                <div className="flex items-center justify-between p-4 border border-gray-300 rounded-lg bg-gray-50">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-primary-olive/10 rounded-lg flex items-center justify-center">
                            <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p className="text-sm font-medium text-gray-900 truncate max-w-xs">
                                {fileName}
                            </p>
                            {file && (
                                <p className="text-xs text-gray-500">
                                    {(file.size / 1024 / 1024).toFixed(2)} MB
                                </p>
                            )}
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={handleRemove}
                        className="p-2 text-gray-400 hover:text-red-500 transition-colors"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            ) : (
                <div
                    onDragOver={handleDragOver}
                    onDragLeave={handleDragLeave}
                    onDrop={handleDrop}
                    onClick={() => inputRef.current?.click()}
                    className={`
                        relative border-2 border-dashed rounded-lg p-8 text-center cursor-pointer
                        transition-colors
                        ${isDragging
                            ? 'border-primary-olive bg-primary-olive/5'
                            : error
                                ? 'border-red-300 hover:border-red-400'
                                : 'border-gray-300 hover:border-primary-olive'
                        }
                    `}
                >
                    <input
                        ref={inputRef}
                        type="file"
                        id={id}
                        accept={acceptedTypes}
                        onChange={handleChange}
                        className="sr-only"
                    />
                    <div className="flex flex-col items-center">
                        <div className="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                            <svg className="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <p className="text-sm text-gray-600">
                            Drag & drop files or{' '}
                            <span className="text-primary-olive font-medium">Browse files</span>
                        </p>
                        <p className="text-xs text-gray-400 mt-1">
                            {description}
                        </p>
                        <p className="text-xs text-gray-400">
                            Max file size: {maxSize}
                        </p>
                    </div>
                </div>
            )}

            {error && (
                <p className="text-sm text-red-600">{error}</p>
            )}
        </div>
    );
}

export default function VerificationStep({ data, errors, onUpdate }: VerificationStepProps) {
    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-2xl font-serif font-bold text-gray-900">Verification</h2>
                <p className="mt-1 text-gray-500">Upload documents to verify your business</p>
            </div>

            <div className="space-y-6">
                <FileUpload
                    id="certificate_of_incorporation"
                    label="Upload Certificate of Incorporation (PDF, JPG, PNG)"
                    description="Business registration document"
                    acceptedTypes=".pdf,.jpg,.jpeg,.png"
                    maxSize="5MB"
                    file={data.certificate_of_incorporation}
                    preview={data.certificate_of_incorporation_preview}
                    error={errors.certificate_of_incorporation}
                    onFileChange={(file) => onUpdate('certificate_of_incorporation', file)}
                />

                <FileUpload
                    id="government_issued_id"
                    label="Upload Government-Issued ID for Primary Account Holder (JPG, PNG)"
                    description="Ensure photo is clear, no glare, and all corners are visible"
                    acceptedTypes=".jpg,.jpeg,.png"
                    maxSize="5MB"
                    file={data.government_issued_id}
                    preview={data.government_issued_id_preview}
                    error={errors.government_issued_id}
                    onFileChange={(file) => onUpdate('government_issued_id', file)}
                />

                <FileUpload
                    id="proof_of_address"
                    label="Upload Proof of Address (Utility Bill or Bank Statement, PDF, JPG, PNG)"
                    description="Issued within the last 3 months"
                    acceptedTypes=".pdf,.jpg,.jpeg,.png"
                    maxSize="5MB"
                    file={data.proof_of_address}
                    preview={data.proof_of_address_preview}
                    error={errors.proof_of_address}
                    onFileChange={(file) => onUpdate('proof_of_address', file)}
                />
            </div>
        </div>
    );
}
