import { useState } from 'react';
import { AdminProduct } from '@/types/product';

const FLAG_REASONS = [
    'Misleading Description',
    'Counterfeit / Unauthorized Item',
    'Inappropriate Image Content',
    'Pricing Irregularity',
];

export default function FlagModal({
    product,
    onClose,
    onSubmit,
}: {
    product: AdminProduct | null;
    onClose: () => void;
    onSubmit: (id: number, reason: string, notes: string) => void;
}) {
    const [selectedReason, setSelectedReason] = useState<string>(FLAG_REASONS[0]);
    const [notes, setNotes] = useState('');

    if (!product) return null;

    const handleSubmit = () => {
        onSubmit(product.id, selectedReason, notes);
        setNotes('');
        setSelectedReason(FLAG_REASONS[0]);
        onClose();
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onClick={onClose} />
            <div className="relative bg-white w-full max-w-lg rounded-xl shadow-2xl overflow-hidden animate-dropdown-in">
                <div className="p-8">
                    <div className="flex justify-between items-center mb-6">
                        <h3 className="text-2xl font-bold text-gray-900">Flag for Review</h3>
                        <button
                            onClick={onClose}
                            className="p-2 rounded-full hover:bg-gray-100 text-gray-400 transition-colors"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <p className="text-sm text-gray-500 mb-6">
                        Flagging{' '}
                        <span className="font-semibold text-gray-700">{product.name}</span>
                    </p>

                    <div className="space-y-6">
                        <div>
                            <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">
                                Reason for Flagging
                            </label>
                            <div className="space-y-2">
                                {FLAG_REASONS.map((reason) => (
                                    <button
                                        key={reason}
                                        onClick={() => setSelectedReason(reason)}
                                        className={`w-full flex items-center justify-between p-4 rounded-lg border-2 text-sm font-semibold text-left transition-colors ${
                                            selectedReason === reason
                                                ? 'border-primary-olive bg-primary-olive/5 text-primary-olive'
                                                : 'border-gray-100 text-gray-500 hover:border-primary-olive/40'
                                        }`}
                                    >
                                        {reason}
                                        {selectedReason === reason && (
                                            <svg className="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
                                            </svg>
                                        )}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div>
                            <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">
                                Internal Notes
                            </label>
                            <textarea
                                value={notes}
                                onChange={(e) => setNotes(e.target.value)}
                                rows={3}
                                placeholder="Provide more context for the vendor or legal team..."
                                className="w-full bg-gray-50 border border-gray-200/60 rounded-lg p-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20 placeholder:text-gray-300 resize-none"
                            />
                        </div>
                    </div>
                </div>

                <div className="bg-gray-50 px-8 py-5 flex justify-end gap-3 border-t border-gray-100">
                    <button
                        onClick={onClose}
                        className="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleSubmit}
                        className="bg-red-500 text-white px-8 py-2.5 rounded-lg text-sm font-bold shadow-lg shadow-red-500/20 hover:brightness-110 transition-all"
                    >
                        Flag Product
                    </button>
                </div>
            </div>
        </div>
    );
}
