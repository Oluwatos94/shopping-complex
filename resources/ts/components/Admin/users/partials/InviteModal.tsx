export default function InviteModal({ open, onClose }: { open: boolean; onClose: () => void }) {
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onClick={onClose} />
            <div className="relative bg-white w-full max-w-lg rounded-xl shadow-2xl p-8 animate-dropdown-in">
                <button
                    onClick={onClose}
                    className="absolute top-4 right-4 p-2 rounded-full hover:bg-gray-100 text-gray-400 transition-colors"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <h4 className="text-2xl font-extrabold tracking-tight text-gray-900 mb-1">
                    Invite New Administrator
                </h4>
                <p className="text-gray-500 text-sm mb-6">
                    Send a secure invitation link to grant dashboard access.
                </p>
                <form className="space-y-5" onSubmit={(e) => e.preventDefault()}>
                    <div>
                        <label className="text-[10px] uppercase tracking-widest font-bold text-gray-400 mb-1.5 block">
                            Full Name
                        </label>
                        <input
                            type="text"
                            placeholder="e.g. Robert Smith"
                            className="w-full bg-gray-50 border border-gray-200/60 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20 placeholder:text-gray-300"
                        />
                    </div>
                    <div>
                        <label className="text-[10px] uppercase tracking-widest font-bold text-gray-400 mb-1.5 block">
                            Work Email Address
                        </label>
                        <input
                            type="email"
                            placeholder="admin@shoppingcomplex.com"
                            className="w-full bg-gray-50 border border-gray-200/60 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20 placeholder:text-gray-300"
                        />
                    </div>
                    <div>
                        <label className="text-[10px] uppercase tracking-widest font-bold text-gray-400 mb-2 block">
                            Access Level
                        </label>
                        <div className="grid grid-cols-2 gap-3">
                            {[
                                { label: 'Standard Admin', desc: 'Can manage users & orders.' },
                                { label: 'Super Admin', desc: 'Full system control.' },
                            ].map((opt, i) => (
                                <label key={opt.label} className="relative cursor-pointer">
                                    <input
                                        defaultChecked={i === 0}
                                        name="invite_role"
                                        type="radio"
                                        className="peer sr-only"
                                    />
                                    <div className="peer-checked:bg-primary-olive/5 peer-checked:ring-2 peer-checked:ring-primary-olive bg-gray-50 p-4 rounded-lg transition-all border border-transparent peer-checked:border-primary-olive/20">
                                        <p className="text-sm font-bold text-gray-900">{opt.label}</p>
                                        <p className="text-[10px] text-gray-400 mt-1 leading-tight">{opt.desc}</p>
                                    </div>
                                </label>
                            ))}
                        </div>
                    </div>
                    <div className="pt-2">
                        <button
                            type="submit"
                            className="w-full bg-primary-olive text-white py-3.5 rounded-lg font-bold active:scale-95 transition-transform hover:brightness-110"
                        >
                            Send Invitation
                        </button>
                        <p className="text-[10px] text-center text-gray-400 mt-3 uppercase tracking-widest font-medium">
                            Link expires in 24 hours
                        </p>
                    </div>
                </form>
            </div>
        </div>
    );
}
