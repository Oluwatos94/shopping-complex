import { useForm } from '@inertiajs/react';

export default function InviteModal({ open, onClose }: { open: boolean; onClose: () => void }) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        name: '',
        email: '',
    });

    if (!open) return null;

    const close = () => {
        reset();
        clearErrors();
        onClose();
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/users/invite', {
            preserveScroll: true,
            onSuccess: () => close(),
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onClick={close} />
            <div className="relative bg-white w-full max-w-lg rounded-xl shadow-2xl p-8 animate-dropdown-in">
                <button
                    onClick={close}
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
                <form className="space-y-5" onSubmit={handleSubmit}>
                    <div>
                        <label className="text-[10px] uppercase tracking-widest font-bold text-gray-400 mb-1.5 block">
                            Full Name
                        </label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            disabled={processing}
                            placeholder="e.g. Robert Smith"
                            className={`w-full bg-gray-50 border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20 placeholder:text-gray-300 disabled:opacity-50 ${
                                errors.name ? 'border-red-400' : 'border-gray-200/60'
                            }`}
                        />
                        {errors.name && <p className="text-red-500 text-xs mt-1.5">{errors.name}</p>}
                    </div>
                    <div>
                        <label className="text-[10px] uppercase tracking-widest font-bold text-gray-400 mb-1.5 block">
                            Work Email Address
                        </label>
                        <input
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            disabled={processing}
                            placeholder="admin@jiidaa.com"
                            className={`w-full bg-gray-50 border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20 placeholder:text-gray-300 disabled:opacity-50 ${
                                errors.email ? 'border-red-400' : 'border-gray-200/60'
                            }`}
                        />
                        {errors.email && <p className="text-red-500 text-xs mt-1.5">{errors.email}</p>}
                    </div>
                    <div className="pt-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full bg-primary-olive text-white py-3.5 rounded-lg font-bold active:scale-95 transition-transform hover:brightness-110 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {processing ? 'Sending…' : 'Send Invitation'}
                        </button>
                        <p className="text-[10px] text-center text-gray-400 mt-3 uppercase tracking-widest font-medium">
                            Link expires in 60 minutes
                        </p>
                    </div>
                </form>
            </div>
        </div>
    );
}
