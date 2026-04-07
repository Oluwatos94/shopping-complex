import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/components/Admin/AdminLayout';

interface VendorUser {
    id: number;
    name: string;
    email: string;
    business_name?: string | null;
}

interface VendorApplication {
    id: number;
    user_id: number;
    user: VendorUser;
    legal_entity_name: string | null;
    business_category: string | null;
    tax_identification_number: string | null;
    physical_address: string | null;
    bank_name: string | null;
    bank_branch: string | null;
    account_number: string | null;
    certificate_of_incorporation: string | null;
    government_issued_id: string | null;
    proof_of_address: string | null;
    status: string;
    current_step: number;
    agreed_to_terms: boolean;
    rejection_reason: string | null;
    created_at: string;
    reviewed_at: string | null;
}

interface Props {
    vendors: {
        data: VendorApplication[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function initials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((n) => n.charAt(0))
        .join('')
        .toUpperCase();
}

function DocumentPill({ label }: { label: string }) {
    return (
        <div className="flex items-center bg-primary-olive/10 px-3 py-2 rounded-lg text-xs font-medium text-primary-olive gap-1.5">
            <svg className="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            {label}
        </div>
    );
}

function VendorCard({
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

function DetailPanel({
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
                                        <div
                                            key={doc.label}
                                            className="flex items-center justify-between p-4 bg-gray-50 rounded-lg group hover:bg-primary-olive/5 transition-all"
                                        >
                                            <div className="flex items-center gap-3">
                                                <svg className="w-4 h-4 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span className="text-sm font-medium text-gray-700">{doc.label}</span>
                                            </div>
                                            <span className="text-[10px] font-bold uppercase text-primary-olive bg-primary-olive/10 px-2 py-0.5 rounded-full">
                                                Submitted
                                            </span>
                                        </div>
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

function RejectModal({
    vendor,
    onClose,
    onConfirm,
}: {
    vendor: VendorApplication | null;
    onClose: () => void;
    onConfirm: (reason: string) => void;
}) {
    const [reason, setReason] = useState('');

    if (!vendor) return null;

    const handleConfirm = () => {
        if (reason.trim().length < 5) return;
        onConfirm(reason.trim());
        setReason('');
    };

    return (
        <div className="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" onClick={onClose} />
            <div className="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-md p-8">
                <h3 className="text-lg font-bold text-gray-900 mb-1">Reject Application</h3>
                <p className="text-sm text-gray-500 mb-6">
                    Provide a reason for rejecting{' '}
                    <span className="font-semibold text-gray-700">
                        {vendor.user.business_name || vendor.user.name}
                    </span>
                    . This will be sent to the vendor.
                </p>
                <textarea
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                    rows={4}
                    placeholder="e.g. Missing required documents, incomplete application..."
                    className="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20 placeholder:text-gray-300 resize-none"
                />
                <p className="text-[10px] text-gray-400 mt-1 mb-6">Minimum 5 characters required.</p>
                <div className="flex gap-3">
                    <button
                        onClick={onClose}
                        className="flex-1 py-3 rounded-lg border border-gray-200 text-gray-600 font-bold text-xs uppercase tracking-widest hover:bg-gray-50 transition-all"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleConfirm}
                        disabled={reason.trim().length < 5}
                        className="flex-1 py-3 rounded-lg bg-red-500 text-white font-bold text-xs uppercase tracking-widest hover:brightness-110 transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        Confirm Reject
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function Vendors({ vendors }: Props) {
    const [selectedVendor, setSelectedVendor] = useState<VendorApplication | null>(null);
    const [rejectingVendor, setRejectingVendor] = useState<VendorApplication | null>(null);
    const [processing, setProcessing] = useState<number | null>(null);

    const handleApprove = (vendor: VendorApplication) => {
        setProcessing(vendor.user_id);
        router.post(
            `/admin/vendors/${vendor.user_id}/approve`,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(null);
                    setSelectedVendor(null);
                },
            },
        );
    };

    const handleRejectConfirm = (reason: string) => {
        if (!rejectingVendor) return;
        const vendor = rejectingVendor;
        setRejectingVendor(null);
        setProcessing(vendor.user_id);
        router.post(
            `/admin/vendors/${vendor.user_id}/reject`,
            { rejection_reason: reason },
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(null);
                    setSelectedVendor(null);
                },
            },
        );
    };

    const goToPage = (page: number) => {
        router.get('/admin/vendors/pending', { page }, { preserveScroll: true });
    };

    const pendingCount = vendors.total;
    const progressPct = Math.min(100, (pendingCount / Math.max(pendingCount, 50)) * 100);

    return (
        <>
            <Head title="Vendor Approval — Admin" />
            <AdminLayout>
                {/* Background decoration */}
                <div className="fixed top-0 left-64 right-0 h-full pointer-events-none -z-10 overflow-hidden">
                    <div className="absolute top-[-10%] right-[-5%] w-[40%] h-[60%] bg-primary-olive/5 rounded-full blur-[120px]" />
                    <div className="absolute bottom-[-5%] left-[20%] w-[30%] h-[50%] bg-primary-peach/5 rounded-full blur-[100px]" />
                </div>

                {/* Page Header */}
                <div className="flex justify-between items-end mb-12">
                    <div className="space-y-1">
                        <p className="text-primary-olive font-bold text-sm tracking-[0.2em] uppercase">
                            Operations
                        </p>
                        <h2 className="text-4xl font-extrabold tracking-tight text-gray-900">
                            Vendor Approval
                        </h2>
                    </div>
                    <div className="flex items-center gap-1 bg-gray-100 p-1 rounded-xl">
                        <button className="px-5 py-2 rounded-lg bg-primary-olive text-white font-bold text-sm transition-all shadow-md shadow-primary-olive/20">
                            Pending
                        </button>
                        <button className="px-5 py-2 rounded-lg text-gray-500 font-medium text-sm hover:bg-white transition-all">
                            Approved
                        </button>
                        <button className="px-5 py-2 rounded-lg text-gray-500 font-medium text-sm hover:bg-white transition-all">
                            Rejected
                        </button>
                    </div>
                </div>

                {/* Stats Overview */}
                <div className="grid grid-cols-12 gap-5 mb-12">
                    {/* Main stat — earthy gradient */}
                    <div className="col-span-12 lg:col-span-8 bg-gradient-to-br from-primary-dark to-primary-brown p-8 rounded-xl flex items-center justify-between text-white shadow-xl shadow-primary-dark/20">
                        <div>
                            <p className="text-xs uppercase tracking-widest opacity-70 mb-2">
                                Total Applications in Review
                            </p>
                            <p className="text-6xl font-bold tracking-tight">{vendors.total}</p>
                        </div>
                        <div className="flex flex-col items-end gap-4">
                            <span className="bg-white/15 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold">
                                Pending Review
                            </span>
                            <div className="flex -space-x-2">
                                {['A', 'B', 'C'].map((l) => (
                                    <div
                                        key={l}
                                        className="w-9 h-9 rounded-full bg-white/20 border-2 border-white/30 flex items-center justify-center text-[11px] font-bold"
                                    >
                                        {l}
                                    </div>
                                ))}
                                <div className="w-9 h-9 rounded-full bg-white/10 border-2 border-white/20 flex items-center justify-center text-[10px] font-bold">
                                    +{Math.max(0, pendingCount - 3)}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Progress card */}
                    <div className="col-span-12 lg:col-span-4 bg-white p-8 rounded-xl border border-gray-100 flex flex-col justify-between">
                        <div>
                            <p className="text-xs uppercase tracking-widest text-gray-400 mb-2">
                                Pending Review
                            </p>
                            <p className="text-4xl font-bold tracking-tight text-gray-900">{pendingCount}</p>
                        </div>
                        <div>
                            <div className="w-full bg-gray-100 rounded-full h-1.5">
                                <div
                                    className="bg-primary-brown h-1.5 rounded-full transition-all"
                                    style={{ width: `${progressPct}%` }}
                                />
                            </div>
                            <p className="text-[10px] text-gray-400 mt-1.5">
                                Showing page {vendors.current_page} of {vendors.last_page}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Application Cards Grid */}
                {vendors.data.length === 0 ? (
                    <div className="py-24 text-center">
                        <svg className="w-12 h-12 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p className="text-gray-400 font-medium">All caught up — no pending applications.</p>
                    </div>
                ) : (
                    <>
                        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                            {vendors.data.map((vendor) => (
                                <VendorCard
                                    key={vendor.id}
                                    vendor={vendor}
                                    onSelect={setSelectedVendor}
                                    onApprove={handleApprove}
                                    onReject={setRejectingVendor}
                                    processing={processing}
                                />
                            ))}

                            {/* Placeholder card */}
                            <div className="bg-gray-50 border-2 border-dashed border-gray-200 p-6 rounded-xl flex flex-col items-center justify-center text-center min-h-[280px] opacity-60">
                                <svg className="w-8 h-8 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                </svg>
                                <p className="text-sm font-medium text-gray-400">
                                    Select an application to<br />view full details
                                </p>
                            </div>
                        </div>

                        {/* Pagination */}
                        {vendors.last_page > 1 && (
                            <div className="mt-10 flex justify-center gap-3">
                                <button
                                    onClick={() => goToPage(vendors.current_page - 1)}
                                    disabled={vendors.current_page === 1}
                                    className="px-6 py-2.5 rounded-full bg-white border border-gray-200 text-gray-600 font-bold text-xs uppercase tracking-widest hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                                >
                                    Previous
                                </button>
                                <button
                                    onClick={() => goToPage(vendors.current_page + 1)}
                                    disabled={vendors.current_page === vendors.last_page}
                                    className="px-6 py-2.5 rounded-full bg-white border border-gray-200 text-gray-600 font-bold text-xs uppercase tracking-widest hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                                >
                                    Load More
                                </button>
                            </div>
                        )}
                    </>
                )}
            </AdminLayout>

            {/* Detail Panel */}
            <DetailPanel
                vendor={selectedVendor}
                onClose={() => setSelectedVendor(null)}
                onApprove={handleApprove}
                onReject={setRejectingVendor}
                processing={processing}
            />

            {/* Reject Modal */}
            <RejectModal
                vendor={rejectingVendor}
                onClose={() => setRejectingVendor(null)}
                onConfirm={handleRejectConfirm}
            />
        </>
    );
}
