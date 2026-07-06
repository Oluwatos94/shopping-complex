interface Props {
    open: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    tone?: 'danger' | 'primary';
    processing?: boolean;
    onConfirm: () => void;
    onClose: () => void;
}

const TONE_CLASSES: Record<NonNullable<Props['tone']>, string> = {
    danger: 'bg-red-500 text-white hover:brightness-110',
    primary: 'bg-brand-green text-white hover:opacity-90',
};

export default function NotificationModal({
    open,
    title,
    message,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    tone = 'primary',
    processing = false,
    onConfirm,
    onClose,
}: Props) {
    if (!open) return null;

    return (
        <div className="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" onClick={onClose} />
            <div className="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-md p-8">
                <h3 className="text-lg font-bold text-gray-900 mb-1">{title}</h3>
                <p className="text-sm text-gray-500 mb-6">{message}</p>
                <div className="flex gap-3">
                    <button
                        onClick={onClose}
                        disabled={processing}
                        className="flex-1 py-3 rounded-lg border border-gray-200 text-gray-600 font-bold text-xs uppercase tracking-widest hover:bg-gray-50 transition-all disabled:opacity-40"
                    >
                        {cancelLabel}
                    </button>
                    <button
                        onClick={onConfirm}
                        disabled={processing}
                        className={`flex-1 py-3 rounded-lg font-bold text-xs uppercase tracking-widest transition-all disabled:opacity-40 disabled:cursor-not-allowed ${TONE_CLASSES[tone]}`}
                    >
                        {processing ? 'Working…' : confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}
