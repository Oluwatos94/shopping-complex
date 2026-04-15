export default function Toggle({ enabled, onChange }: { enabled: boolean; onChange: (v: boolean) => void }) {
    return (
        <button
            onClick={() => onChange(!enabled)}
            className={`relative inline-flex h-5 w-9 flex-shrink-0 items-center rounded-full transition-colors ${
                enabled ? 'bg-primary-olive' : 'bg-gray-300'
            }`}
        >
            <span className="sr-only">Toggle</span>
            <span
                className={`inline-block h-3 w-3 transform rounded-full bg-white transition-transform ${
                    enabled ? 'translate-x-5' : 'translate-x-1'
                }`}
            />
        </button>
    );
}
