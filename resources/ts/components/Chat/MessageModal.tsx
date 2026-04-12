import { useState } from 'react';
import { router } from '@inertiajs/react';

interface MessageModalVendor {
    id: number;
    business_name: string;
    business_logo?: string | null;
    is_online?: boolean;
}

interface MessageModalProps {
    vendor: MessageModalVendor;
    initialMessage?: string;
    onClose: () => void;
}

export default function MessageModal({ vendor, initialMessage = '', onClose }: MessageModalProps) {
    const [message, setMessage] = useState(initialMessage);
    const [isSending, setIsSending] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSending(true);

        router.post('/chat/start', {
            vendor_id: vendor.id,
            message,
        }, {
            onSuccess: () => {
                onClose();
            },
            onError: () => {
                setIsSending(false);
            },
        });
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-black/50 transition-opacity"
                onClick={onClose}
            />

            {/* Modal */}
            <div className="flex min-h-full items-center justify-center p-4">
                <div className="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
                    {/* Header */}
                    <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <div className="flex items-center gap-3">
                            {vendor.business_logo ? (
                                <img
                                    src={vendor.business_logo}
                                    alt={vendor.business_name}
                                    className="w-10 h-10 rounded-full object-cover"
                                />
                            ) : (
                                <div className="w-10 h-10 rounded-full bg-primary-olive flex items-center justify-center">
                                    <span className="text-white font-semibold">
                                        {vendor.business_name.charAt(0).toUpperCase()}
                                    </span>
                                </div>
                            )}
                            <div>
                                <h3 className="font-semibold text-gray-900">
                                    Message {vendor.business_name}
                                </h3>
                                <p className="text-sm text-gray-500 flex items-center gap-1">
                                    <span className={`w-2 h-2 rounded-full ${vendor.is_online ? 'bg-green-500' : 'bg-gray-400'}`} />
                                    {vendor.is_online ? 'Online now' : 'Usually responds within 24 hours'}
                                </p>
                            </div>
                        </div>
                        <button
                            onClick={onClose}
                            className="p-2 hover:bg-gray-100 rounded-full transition-colors"
                            aria-label="Close"
                        >
                            <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {/* Body */}
                    <form onSubmit={handleSubmit} className="p-6">
                        <div className="mb-4">
                            <label htmlFor="message" className="block text-sm font-medium text-gray-700 mb-2">
                                Your Message
                            </label>
                            <textarea
                                id="message"
                                rows={4}
                                value={message}
                                onChange={(e) => setMessage(e.target.value)}
                                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent resize-none"
                                placeholder="Type your message..."
                                required
                                autoFocus
                            />
                        </div>

                        <p className="text-xs text-gray-500 mb-6">
                            By sending a message, you agree to our terms of service and privacy policy.
                        </p>

                        <div className="flex gap-3">
                            <button
                                type="button"
                                onClick={onClose}
                                className="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={isSending || !message.trim()}
                                className="flex-1 px-4 py-3 bg-primary-olive text-white rounded-lg hover:bg-primary-dark transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                {isSending ? (
                                    <>
                                        <svg className="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                        Sending...
                                    </>
                                ) : (
                                    <>
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        Send Message
                                    </>
                                )}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
