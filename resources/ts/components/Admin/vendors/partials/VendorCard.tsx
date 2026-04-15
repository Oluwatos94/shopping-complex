import { VendorApplication } from '@/types/vendor';
import { formatDate } from '@/utils/date';
import DocumentPill from './DocumentPill';

export default function VendorCard({
    vendor,
    onSelect,
    onApprove,
    onReject,
    processing,
}: {
    vendor: VendorApplication;
    onSelect: (v: VendorApplication) => void;
    onApprove: (v: VendorApplication) => void;
    onReject: (v: VendorApplication) => void;
    processing: number | null;
}) {
    const docs: string[] = [
        vendor.certificate_of_incorporation ? 'Certificate of Incorp.' : '',
        vendor.government_issued_id ? 'Govt. ID' : '',
        vendor.proof_of_address ? 'Proof of Address' : '',
    ].filter(Boolean);

    const isProcessing = processing === vendor.user_id;

    return (
        <div
            className="group bg-white p-6 rounded-xl border border-gray-100 hover:border-primary-olive/20 hover:shadow-xl hover:shadow-primary-olive/5 transition-all cursor-pointer relative overflow-hidden"
            onClick={() => onSelect(vendor)}
        >
            {/* Header */}
            <div className="flex justify-between items-start mb-6">
                <div className="w-12 h-12 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center group-hover:bg-primary-olive/5 transition-colors">
                    <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <span
                    className={`px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                        vendor.current_step >= 4
                            ? 'bg-primary-brown/10 text-primary-brown'
                            : 'bg-gray-100 text-gray-500'
                    }`}
                >
                    {vendor.current_step >= 4 ? 'Complete' : `Step ${vendor.current_step}/4`}
                </span>
            </div>

            {/* Name & Category */}
            <div className="mb-6">
                <h3 className="text-lg font-bold text-gray-900">
                    {vendor.user.business_name || vendor.legal_entity_name || vendor.user.name}
                </h3>
                <p className="text-sm text-gray-400 mt-0.5">
                    {vendor.business_category || 'Category not specified'}
                </p>
            </div>

            {/* Meta Grid */}
            <div className="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <p className="text-[10px] uppercase tracking-widest text-gray-400 mb-1">Applied</p>
                    <p className="text-sm font-semibold text-gray-800">{formatDate(vendor.created_at)}</p>
                </div>
                <div>
                    <p className="text-[10px] uppercase tracking-widest text-gray-400 mb-1">Location</p>
                    <p className="text-sm font-semibold text-gray-800 truncate">
                        {vendor.physical_address ? vendor.physical_address.split(',').at(-1)?.trim() || '—' : '—'}
                    </p>
                </div>
            </div>

            {/* Documents */}
            {docs.length > 0 && (
                <div className="flex flex-wrap gap-2 mb-6">
                    {docs.map((d) => (
                        <DocumentPill key={d} label={d} />
                    ))}
                </div>
            )}
            {docs.length === 0 && (
                <div className="mb-6">
                    <span className="text-xs text-gray-300 italic">No documents uploaded</span>
                </div>
            )}

            {/* Actions */}
            <div
                className="flex gap-2"
                onClick={(e) => e.stopPropagation()}
            >
                <button
                    disabled={isProcessing}
                    onClick={() => onApprove(vendor)}
                    className="flex-1 py-3 rounded-lg bg-primary-olive text-white font-bold text-xs uppercase tracking-widest hover:brightness-110 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {isProcessing ? 'Processing…' : 'Approve'}
                </button>
                <button
                    disabled={isProcessing}
                    onClick={() => onReject(vendor)}
                    className="px-4 py-3 rounded-lg bg-gray-100 text-red-500 font-bold text-xs uppercase hover:bg-red-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    );
}
