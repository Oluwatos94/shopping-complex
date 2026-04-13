import { useState } from 'react';
import { VendorApplication } from '@/types/vendor';

export default function RejectModal({
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
