import { useState, FormEvent } from 'react';
import { router } from '@inertiajs/react';

interface Props {
    errors?: Record<string, string>;
}

export default function ChangePassword({ errors: serverErrors }: Props) {
    const [currentPassword, setCurrentPassword] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [processing, setProcessing] = useState(false);
    const [success, setSuccess] = useState(false);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        setSuccess(false);

        router.put('/profile/password', {
            current_password: currentPassword,
            password,
            password_confirmation: passwordConfirmation,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setCurrentPassword('');
                setPassword('');
                setPasswordConfirmation('');
                setSuccess(true);
            },
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 className="text-lg font-serif font-bold text-brand-ink mb-6">Change Password</h2>

            {success && (
                <div className="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-lg">
                    <p className="text-sm text-green-700">Password changed successfully.</p>
                </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input
                        type="password"
                        value={currentPassword}
                        onChange={(e) => setCurrentPassword(e.target.value)}
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green"
                    />
                    {serverErrors?.current_password && <p className="text-sm text-red-600 mt-1">{serverErrors.current_password}</p>}
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input
                        type="password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green"
                    />
                    {serverErrors?.password && <p className="text-sm text-red-600 mt-1">{serverErrors.password}</p>}
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input
                        type="password"
                        value={passwordConfirmation}
                        onChange={(e) => setPasswordConfirmation(e.target.value)}
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green"
                    />
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="bg-brand-green text-white px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-brand-ink transition-colors disabled:opacity-50"
                >
                    {processing ? 'Updating...' : 'Update Password'}
                </button>
            </form>
        </div>
    );
}
