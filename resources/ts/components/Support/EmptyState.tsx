interface Props {
    onQuickPrompt?: (text: string) => void;
}

const QUICK_PROMPTS = [
    'How do I register as a vendor?',
    'Help me find a product',
    'I have a subscription issue',
];

export default function EmptyState({ onQuickPrompt }: Props) {
    return (
        <div className="flex h-full flex-col items-center justify-center gap-4 px-4 text-center">
            <p className="text-base font-semibold text-gray-900">Hi 👋 how can we help?</p>
            <p className="text-sm text-brand-muted">
                Ask anything about Jiidaa — finding vendors, selling, or your account.
            </p>
            <div className="flex flex-col gap-2">
                {QUICK_PROMPTS.map((prompt) => (
                    <button
                        key={prompt}
                        type="button"
                        onClick={() => onQuickPrompt?.(prompt)}
                        className="rounded-full border border-brand-line bg-white px-4 py-2 text-sm text-gray-700 transition-colors hover:border-brand-green hover:text-brand-green focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-green"
                    >
                        {prompt}
                    </button>
                ))}
            </div>
        </div>
    );
}
