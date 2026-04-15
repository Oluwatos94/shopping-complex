export default function Toast({ onClose }: { onClose: () => void }) {
    return (
        <div className="fixed bottom-8 right-8 z-50 flex items-center gap-4 bg-primary-dark text-white px-6 py-4 rounded-xl shadow-2xl animate-dropdown-in">
            <div className="w-8 h-8 rounded-full bg-primary-olive/20 flex items-center justify-center flex-shrink-0">
                <svg className="w-4 h-4 text-primary-olive" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
                </svg>
            </div>
            <div>
                <p className="text-sm font-bold">Changes saved successfully</p>
                <p className="text-[11px] text-white/50">Settings applied across all platform instances.</p>
            </div>
            <button onClick={onClose} className="ml-4 text-white/40 hover:text-white transition-colors">
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    );
}
