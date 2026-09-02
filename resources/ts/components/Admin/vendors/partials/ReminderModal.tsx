import { router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import MarkdownPreview from './MarkdownPreview';

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

type Channel = 'whatsapp' | 'email' | 'in_app';

const CHANNEL_OPTIONS: { value: Channel; label: string; note: string }[] = [
    { value: 'whatsapp', label: 'WhatsApp', note: 'Subject line only — no formatting' },
    { value: 'email', label: 'Email', note: 'Banner + full formatting' },
    { value: 'in_app', label: 'In-app', note: 'Banner + plain text' },
];

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
    const [banner, setBanner] = useState<File | null>(null);
    const [channels, setChannels] = useState<Channel[]>(['whatsapp', 'email', 'in_app']);
    const [submitting, setSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    useEffect(() => {
        if (open) {
            setRecipient(selectedIds.length > 0 ? 'selection' : 'all');
        }
    }, [open, selectedIds.length]);

    const bannerPreview = useMemo(() => (banner ? URL.createObjectURL(banner) : ''), [banner]);

    useEffect(() => () => {
        if (bannerPreview) URL.revokeObjectURL(bannerPreview);
    }, [bannerPreview]);

    const options = useMemo(() => {
        if (selectedIds.length === 0) return RECIPIENT_OPTIONS;
        return [
            { value: 'selection' as RecipientValue, label: `Selected vendors (${selectedIds.length})` },
            ...RECIPIENT_OPTIONS,
        ];
    }, [selectedIds.length]);

    if (!open) return null;

    const buildPayload = () => {
        const base: Record<string, unknown> = { subject, body, channels };
        if (ctaLabel) base.cta_label = ctaLabel;
        if (ctaUrl) base.cta_url = ctaUrl;
        if (banner) base.banner = banner;

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
        if (channels.length === 0) {
            setErrors({ channels: 'Pick at least one channel to send on.' });
            return;
        }

        setSubmitting(true);
        setErrors({});
        router.post('/admin/vendors/reminders', buildPayload(), {
            preserveScroll: true,
            forceFormData: true,
            onError: (e) => setErrors(e as Record<string, string>),
            onSuccess: () => {
                setSubject('');
                setBody('');
                setCtaLabel('');
                setCtaUrl('');
                setBanner(null);
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
                                Send on
                            </label>
                            <div className="space-y-2">
                                {CHANNEL_OPTIONS.map((option) => {
                                    const checked = channels.includes(option.value);
                                    return (
                                        <label
                                            key={option.value}
                                            className={`flex items-start gap-3 rounded-xl border px-4 py-2.5 cursor-pointer transition-colors ${
                                                checked
                                                    ? 'border-primary-olive bg-primary-olive/5'
                                                    : 'border-gray-200 hover:border-gray-300'
                                            }`}
                                        >
                                            <input
                                                type="checkbox"
                                                checked={checked}
                                                onChange={() =>
                                                    setChannels((prev) =>
                                                        prev.includes(option.value)
                                                            ? prev.filter((c) => c !== option.value)
                                                            : [...prev, option.value],
                                                    )
                                                }
                                                className="mt-0.5 accent-primary-olive"
                                            />
                                            <span>
                                                <span className="block text-sm font-semibold text-gray-800">
                                                    {option.label}
                                                </span>
                                                <span className="block text-[11px] text-gray-400">{option.note}</span>
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                            {errors.channels && <p className="text-xs text-red-500 mt-1">{errors.channels}</p>}
                        </div>

                        <div>
                            <label className="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">
                                Banner image
                            </label>
                            {banner ? (
                                <div className="rounded-xl border border-gray-200 overflow-hidden">
                                    <img src={bannerPreview} alt="Banner preview" className="w-full h-32 object-cover" />
                                    <div className="flex items-center justify-between px-3 py-2 bg-gray-50">
                                        <span className="text-xs text-gray-500 truncate">
                                            {banner.name} · {(banner.size / 1024 / 1024).toFixed(2)} MB
                                        </span>
                                        <button
                                            onClick={() => setBanner(null)}
                                            className="text-xs font-bold uppercase tracking-widest text-red-500 hover:text-red-600 shrink-0 ml-3"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            ) : (
                                <label className="flex flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-gray-200 py-6 cursor-pointer hover:border-primary-olive hover:bg-primary-olive/5 transition-colors">
                                    <svg className="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span className="text-xs font-semibold text-gray-500">Click to add a banner</span>
                                    <span className="text-[10px] text-gray-400">JPG, PNG, GIF or WebP · max 2 MB</span>
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/gif,image/webp"
                                        className="hidden"
                                        onChange={(e) => setBanner(e.target.files?.[0] ?? null)}
                                    />
                                </label>
                            )}
                            {errors.banner && <p className="text-xs text-red-500 mt-1">{errors.banner}</p>}
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
                                maxLength={5000}
                                rows={10}
                                placeholder={'## 🏆 Cash prizes\n\n- **1st place** — ₦100,000\n- **2nd place** — ₦50,000\n\nRefer as many vendors as you can and climb the leaderboard.'}
                                className="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-mono focus:border-primary-olive focus:ring-1 focus:ring-primary-olive outline-none resize-y"
                            />
                            <p className="text-[11px] text-gray-400 mt-1.5 leading-relaxed">
                                Markdown supported — <code className="text-gray-600">## Heading</code>,{' '}
                                <code className="text-gray-600">**bold**</code>,{' '}
                                <code className="text-gray-600">- list item</code>,{' '}
                                <code className="text-gray-600">[link](https://…)</code>. Formatting shows in the
                                email; WhatsApp and the in-app inbox get plain text.
                            </p>
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
                        <p className="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">Email preview</p>
                        <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <div className="bg-primary-dark py-4 text-center">
                                <img src="/logo/whiteLogo.png" alt="jiidaa" className="h-7 mx-auto" />
                            </div>
                            <div className="p-5">
                                {banner && (
                                    <img src={bannerPreview} alt="" className="w-full rounded-lg mb-4" />
                                )}
                                <p className="font-bold text-gray-900 mb-3">{subject || 'Your subject line'}</p>
                                {body ? (
                                    <MarkdownPreview source={body} />
                                ) : (
                                    <p className="text-sm text-gray-400 min-h-[60px]">
                                        Your message to vendors will appear here.
                                    </p>
                                )}
                                {ctaLabel && (
                                    <span className="inline-block mt-4 px-5 py-2 rounded-lg bg-[#25D366] text-white text-sm font-semibold">
                                        {ctaLabel}
                                    </span>
                                )}
                            </div>
                        </div>
                        <p className="text-[11px] text-gray-400 mt-4 leading-relaxed">
                            Sending on {channels.length === 0 ? 'no channels' : channels.map((c) => CHANNEL_OPTIONS.find((o) => o.value === c)?.label).join(', ')}.
                            Vendors without a WhatsApp number are skipped for WhatsApp, and vendors who turned off
                            email or in-app updates won't get those either.
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
                        disabled={submitting || !subject || !body || channels.length === 0}
                        className="px-8 py-2.5 rounded-full bg-primary-olive text-white font-bold text-xs uppercase tracking-widest shadow-lg shadow-primary-olive/20 hover:bg-primary-olive/90 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                    >
                        {submitting ? 'Queuing…' : 'Send reminder'}
                    </button>
                </div>
            </div>
        </div>
    );
}
