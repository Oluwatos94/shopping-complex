import { VendorApplication } from '@/types/vendor';
import { formatDate } from '@/utils/date';
import { initials } from '@/utils/string';
import DocumentPill from './DocumentPill';

export default function DetailPanel({
    vendor,
    onClose,
    onApprove,
    onReject,
    processing,
}: {
    vendor: VendorApplication | null;
    onClose: () => void;
    onApprove: (v: VendorApplication) => void;
    onReject: (v: VendorApplication) => void;
    processing: number | null;
}) {
    const isOpen = vendor !== null;
    const isProcessing = vendor ? processing === vendor.user_id : false;

    const docItems = vendor
        ? [
              vendor.certificate_of_incorporation && { label: 'Certificate of Incorporation', key: 'certificate_of_incorporation' },
              vendor.government_issued_id && { label: 'Government Issued ID', key: 'government_issued_id' },
              vendor.proof_of_address && { label: 'Proof of Address', key: 'proof_of_address' },
          ].filter(Boolean as unknown as <T>(x: T | false) => x is T)
        : [];

    const docUrl = (field: string) =>
        vendor ? `/admin/vendors/${vendor.user_id}/document/${field}` : '#';

    return (
        <>
            {/* Backdrop */}
            {isOpen && (
                <div
                    className="fixed inset-0 bg-black/20 z-50 backdrop-blur-sm"
                    onClick={onClose}
                />
            )}

            {/* Panel */}
            <div
                className={`fixed top-0 right-0 h-screen w-[480px] bg-white z-[60] shadow-2xl border-l border-gray-100 flex flex-col transition-transform duration-500 ease-in-out ${
                    isOpen ? 'translate-x-0' : 'translate-x-full'
                }`}
            >
                {vendor && (
                    <>
                        {/* Header */}
                        <div className="p-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/60">
                            <div>
                                <p className="text-[10px] uppercase tracking-[0.2em] text-primary-olive font-bold mb-1">
                                    Details &amp; Verification
                                </p>
                                <h3 className="text-2xl font-bold text-gray-900">
                                    {vendor.user.business_name || vendor.legal_entity_name || vendor.user.name}
                                </h3>
                            </div>
                            <button
                                onClick={onClose}
                                className="p-2 rounded-full hover:bg-gray-100 transition-all text-gray-500"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {/* Scrollable Body */}
                        <div className="flex-1 overflow-y-auto p-8 space-y-8 scrollbar-thin-dark">
                            {/* Identity */}
                            <div className="flex items-center gap-4">
                                <div className="w-14 h-14 rounded-full bg-primary-olive/10 flex items-center justify-center flex-shrink-0">
                                    <span className="text-primary-olive font-bold text-xl">
                                        {initials(vendor.user.business_name || vendor.user.name)}
                                    </span>
                                </div>
                                <div>
                                    <h4 className="font-bold text-gray-900">
                                        {vendor.user.business_name || vendor.user.name}
                                    </h4>
                                    {vendor.legal_entity_name && (
                                        <p className="text-sm text-gray-500">
                                            Registered as: {vendor.legal_entity_name}
                                        </p>
                                    )}
                                    <p className="text-sm text-gray-400">{vendor.user.email}</p>
                                </div>
                            </div>

                            {/* Info Grid */}
                            <div className="grid grid-cols-2 gap-6">
                                <div>
                                    <p className="text-[10px] uppercase tracking-widest text-gray-400 mb-1">
                                        Tax ID
                                    </p>
                                    <p className="font-mono text-sm font-bold text-gray-800">
                                        {vendor.tax_identification_number || '—'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-[10px] uppercase tracking-widest text-gray-400 mb-1">
                                        Category
                                    </p>
                                    <p className="text-sm font-bold text-gray-800">
                                        {vendor.business_category || '—'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-[10px] uppercase tracking-widest text-gray-400 mb-1">
                                        Bank
                                    </p>
                                    <p className="text-sm font-bold text-gray-800">
                                        {vendor.bank_name || '—'}
                                        {vendor.bank_branch ? ` · ${vendor.bank_branch}` : ''}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-[10px] uppercase tracking-widest text-gray-400 mb-1">
                                        Address
                                    </p>
                                    <p className="text-sm font-bold text-gray-800 leading-snug">
                                        {vendor.physical_address || '—'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-[10px] uppercase tracking-widest text-gray-400 mb-1">
                                        Applied
                                    </p>
                                    <p className="text-sm font-bold text-gray-800">
                                        {formatDate(vendor.created_at)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-[10px] uppercase tracking-widest text-gray-400 mb-1">
                                        Terms Agreed
                                    </p>
                                    <p className="text-sm font-bold text-gray-800">
                                        {vendor.agreed_to_terms ? 'Yes' : 'No'}
                                    </p>
                                </div>
                            </div>

                            {/* Documents */}
                            <div className="space-y-3">
                                <h4 className="text-xs uppercase tracking-widest font-bold text-gray-400 border-b border-gray-100 pb-2">
                                    Verification Documents
                                </h4>
                                {docItems.length > 0 ? (
                                    docItems.map((doc) => (
                                        <a
                                            key={doc.label}
                                            href={docUrl(doc.key)}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="flex items-center justify-between p-4 bg-gray-50 rounded-lg group hover:bg-primary-olive/5 transition-all cursor-pointer"
                                            onClick={(e) => e.stopPropagation()}
                                        >
                                            <div className="flex items-center gap-3">
                                                <svg className="w-4 h-4 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span className="text-sm font-medium text-gray-700 group-hover:text-primary-olive transition-colors">{doc.label}</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className="text-[10px] font-bold uppercase text-primary-olive bg-primary-olive/10 px-2 py-0.5 rounded-full">
                                                    View
                                                </span>
                                                <svg className="w-3.5 h-3.5 text-primary-olive opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </div>
                                        </a>
                                    ))
                                ) : (
                                    <p className="text-sm text-gray-300 italic">No documents uploaded yet.</p>
                                )}
                            </div>

                            {/* Onboarding Progress */}
                            <div className="p-5 bg-gray-50 rounded-xl border border-gray-100">
                                <div className="flex items-center justify-between mb-3">
                                    <h4 className="text-xs uppercase tracking-widest font-bold text-gray-600">
                                        Onboarding Progress
                                    </h4>
                                    <span className="text-xs font-bold text-primary-olive">
                                        Step {vendor.current_step} / 4
                                    </span>
                                </div>
                                <div className="w-full bg-gray-200 rounded-full h-1.5">
                                    <div
                                        className="bg-primary-olive h-1.5 rounded-full transition-all"
                                        style={{ width: `${Math.min(100, (vendor.current_step / 4) * 100)}%` }}
                                    />
                                </div>
                                <p className="text-[10px] text-gray-400 mt-2">
                                    {vendor.current_step >= 4
                                        ? 'Application complete — ready for review'
                                        : 'Application partially submitted'}
                                </p>
                            </div>
                        </div>

                        {/* Footer Actions */}
                        <div className="p-6 border-t border-gray-100 bg-gray-50/60 grid grid-cols-2 gap-3">
                            <button
                                disabled={isProcessing}
                                onClick={() => onReject(vendor)}
                                className="py-3.5 rounded-lg border border-red-200 text-red-500 font-bold text-xs uppercase tracking-widest hover:bg-red-50 transition-all disabled:opacity-50"
                            >
                                Reject
                            </button>
                            <button
                                disabled={isProcessing}
                                onClick={() => onApprove(vendor)}
                                className="py-3.5 rounded-lg bg-primary-olive text-white font-bold text-xs uppercase tracking-widest shadow-lg shadow-primary-olive/20 hover:brightness-110 active:scale-[0.98] transition-all disabled:opacity-50"
                            >
                                {isProcessing ? 'Processing…' : 'Approve'}
                            </button>
                        </div>
                    </>
                )}
            </div>
        </>
    );
}
