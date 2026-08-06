import { router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

interface Props {
    open: boolean;
    onClose: () => void;
    selectedIds: number[];
}

type RecipientValue =
    | 'all'
    | 'status:approved'
    | 'status:pending_review'
    | 'status:rejected'
    | 'status:draft'
    | 'status:subscription_expiring'
    | 'selection';

const RECIPIENT_OPTIONS: { value: RecipientValue; label: string }[] = [
    { value: 'all', label: 'All vendors' },
    { value: 'status:approved', label: 'Approved vendors' },
    { value: 'status:pending_review', label: 'Pending review' },
    { value: 'status:rejected', label: 'Rejected vendors' },
    { value: 'status:draft', label: 'Draft applications' },
    { value: 'status:subscription_expiring', label: 'Subscriptions expiring soon' },
];

export default function ReminderModal({ open, onClose, selectedIds }: Props) {
    const [recipient, setRecipient] = useState<RecipientValue>('all');
    const [subject, setSubject] = useState('');
    const [body, setBody] = useState('');
    const [ctaLabel, setCtaLabel] = useState('');
    const [ctaUrl, setCtaUrl] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    useEffect(() => {
        if (open) {
            setRecipient(selectedIds.length > 0 ? 'selection' : 'all');
        }
    }, [open, selectedIds.length]);

    const options = useMemo(() => {
        if (selectedIds.length === 0) return RECIPIENT_OPTIONS;
        return [
            { value: 'selection' as RecipientValue, label: `Selected vendors (${selectedIds.length})` },
            ...RECIPIENT_OPTIONS,
        ];
    }, [selectedIds.length]);

    if (!open) return null;

    const buildPayload = () => {
        const base: Record<string, unknown> = { subject, body };
        if (ctaLabel) base.cta_label = ctaLabel;
        if (ctaUrl) base.cta_url = ctaUrl;

        if (recipient === 'all') return { ...base, target: 'all' };
        if (recipient === 'selection') return { ...base, target: 'selection', vendor_ids: selectedIds };
        return { ...base, target: 'status', status: recipient.replace('status:', '') };
    };

    const submit = () => {
        if (recipient === 'all' && !confirm('Send this reminder to ALL vendors? This cannot be undone.')) {
            return;
        }
        if (recipient === 'selection' && selectedIds.length === 0) {
            setErrors({ vendor_ids: 'Select at least one vendor first.' });
            return;
        }

        setSubmitting(true);
        setErrors({});
        router.post('/admin/vendors/reminders', buildPayload(), {
            preserveScroll: true,
            onError: (e) => setErrors(e as Record<string, string>),
            onSuccess: () => {
                setSubject('');
                setBody('');
                setCtaLabel('');
                setCtaUrl('');
                onClose();
            },
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-primary-dark/50 backdrop-blur-sm" onClick={onClose} />

            <div className="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                {/* Header */}
                <div className="px-8 py-6 border-b border-gray-100 flex items-start justify-between">
                    <div>
                        <p className="text-primary-olive font-bold text-xs tracking-[0.2em] uppercase mb-1">
                            Outreach
                        </p>
                        <h3 className="text-2xl font-extrabold tracking-tight text-gray-900">Send a reminder</h3>
                        <p className="text-sm text-gray-400 mt-1">
                            Delivered to each vendor's WhatsApp, email, and in-app inbox.
                        </p>
                    </div>
                    <button onClick={onClose} className="text-gray-300 hover:text-gray-500 transition-colors" aria-label="Close">
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div className="grid md:grid-cols-2 gap-0 overflow-y-auto">
                    {/* Compose */}
                    <div className="p-8 space-y-5 border-r border-gray-100">
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">
                                Recipients
                            </label>
                            <select
                                value={recipient}
                                onChange={(e) => setRecipient(e.target.value as RecipientValue)}
                                className="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-primary-olive focus:ring-1 focus:ring-primary-olive outline-none"
                            >
                                {options.map((o) => (
                                    <option key={o.value} value={o.value}>{o.label}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">
                                Subject
                            </label>
                            <input
                                value={subject}
                                onChange={(e) => setSubject(e.target.value)}
                                maxLength={150}
                                placeholder="Renew your subscription"
                                className="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-primary-olive focus:ring-1 focus:ring-primary-olive outline-none"
                            />
                            {errors.subject && <p className="text-xs text-red-500 mt-1">{errors.subject}</p>}
                        </div>

                        <div>
                            <label className="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">
                                Message
                            </label>
                            <textarea
                                value={body}
                                onChange={(e) => setBody(e.target.value)}
                                maxLength={2000}
                                rows={5}
                                placeholder="Your plan expires soon — renew to stay listed in search."
                                className="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-primary-olive focus:ring-1 focus:ring-primary-olive outline-none resize-none"
                            />
                            {errors.body && <p className="text-xs text-red-500 mt-1">{errors.body}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">
                                    Button label
                                </label>
                                <input
                                    value={ctaLabel}
                                    onChange={(e) => setCtaLabel(e.target.value)}
                                    maxLength={40}
                                    placeholder="Renew now"
                                    className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-primary-olive focus:ring-1 focus:ring-primary-olive outline-none"
                                />
                                {errors.cta_label && <p className="text-xs text-red-500 mt-1">{errors.cta_label}</p>}
                            </div>
                            <div>
                                <label className="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">
                                    Button link
                                </label>
                                <input
                                    value={ctaUrl}
                                    onChange={(e) => setCtaUrl(e.target.value)}
                                    placeholder="https://…"
                                    className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-primary-olive focus:ring-1 focus:ring-primary-olive outline-none"
                                />
                                {errors.cta_url && <p className="text-xs text-red-500 mt-1">{errors.cta_url}</p>}
                            </div>
                        </div>
                    </div>

                    {/* Preview */}
                    <div className="p-8 bg-gray-50">
                        <p className="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">Preview</p>
                        <div className="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                            <p className="font-bold text-gray-900 mb-2">{subject || 'Your subject line'}</p>
                            <p className="text-sm text-gray-600 whitespace-pre-line min-h-[60px]">
                                {body || 'Your message to vendors will appear here.'}
                            </p>
                            {ctaLabel && (
                                <span className="inline-block mt-4 px-5 py-2 rounded-lg bg-[#25D366] text-white text-sm font-semibold">
                                    {ctaLabel}
                                </span>
                            )}
                        </div>
                        <p className="text-[11px] text-gray-400 mt-4 leading-relaxed">
                            Vendors without a WhatsApp number are skipped for WhatsApp; vendors who disabled email
                            updates won't be emailed. Everyone gets the in-app notification.
                        </p>
                    </div>
                </div>

                {/* Footer */}
                <div className="px-8 py-5 border-t border-gray-100 flex justify-end gap-3">
                    <button
                        onClick={onClose}
                        className="px-6 py-2.5 rounded-full border border-gray-200 text-gray-600 font-bold text-xs uppercase tracking-widest hover:bg-gray-50 transition-all"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={submit}
                        disabled={submitting || !subject || !body}
                        className="px-8 py-2.5 rounded-full bg-primary-olive text-white font-bold text-xs uppercase tracking-widest shadow-lg shadow-primary-olive/20 hover:bg-primary-olive/90 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                    >
                        {submitting ? 'Queuing…' : 'Send reminder'}
                    </button>
                </div>
            </div>
        </div>
    );
}
