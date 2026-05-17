import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import VendorSidebar from '@/components/VendorSidebar';

interface NotificationPreference {
    label: string;
    description: string;
    email_enabled: boolean;
    push_enabled: boolean;
    in_app_enabled: boolean;
}

interface SettingsProps {
    preferences: Record<string, NotificationPreference>;
    availableTypes: Record<string, { label: string; description: string }>;
}

interface Toggle {
    type: string;
    channel: 'email_enabled' | 'push_enabled' | 'in_app_enabled';
    value: boolean;
}

function ToggleSwitch({ checked, onChange, disabled }: { checked: boolean; onChange: () => void; disabled?: boolean }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            onClick={onChange}
            disabled={disabled}
            className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-olive focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed ${
                checked ? 'bg-primary-olive' : 'bg-gray-200'
            }`}
        >
            <span
                className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ${
                    checked ? 'translate-x-5' : 'translate-x-0'
                }`}
            />
        </button>
    );
}

const CHANNEL_LABELS: Record<string, string> = {
    email_enabled: 'Email',
    push_enabled: 'Push',
    in_app_enabled: 'In-App',
};

export default function VendorSettings({ preferences, availableTypes }: SettingsProps) {
    const { auth } = usePage<{ auth: { user: any } | null }>().props;
    const [localPrefs, setLocalPrefs] = useState(preferences);
    const [saving, setSaving] = useState<Toggle | null>(null);
    const [flash, setFlash] = useState<string | null>(null);

    const update = (type: string, channel: Toggle['channel'], value: boolean) => {
        const toggle: Toggle = { type, channel, value };
        setSaving(toggle);

        // Optimistic update
        setLocalPrefs((prev) => ({
            ...prev,
            [type]: { ...prev[type], [channel]: value },
        }));

        router.post(
            `/notifications/preferences/${type}`,
            { [channel]: value },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setFlash('Saved');
                    setSaving(null);
                    setTimeout(() => setFlash(null), 2000);
                },
                onError: () => {
                    // Revert on error
                    setLocalPrefs((prev) => ({
                        ...prev,
                        [type]: { ...prev[type], [channel]: !value },
                    }));
                    setSaving(null);
                },
            }
        );
    };

    const isSaving = (type: string, channel: string) =>
        saving?.type === type && saving?.channel === channel;

    return (
        <>
            <Head title="Settings" />
            <VendorSidebar />

            <main className="md:ml-[260px] min-h-screen bg-gray-50 pb-20 md:pb-0">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
                        <p className="text-sm text-gray-500 mt-1">Manage your account preferences</p>
                    </div>

                    {/* Flash */}
                    {flash && (
                        <div className="mb-6 flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-2.5 w-fit">
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                            {flash}
                        </div>
                    )}

                    {/* Notification Preferences */}
                    <section className="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
                        <div className="px-6 py-5 border-b border-gray-100">
                            <div className="flex items-center gap-3">
                                <div className="w-9 h-9 rounded-lg bg-primary-olive/10 flex items-center justify-center">
                                    <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 className="text-base font-semibold text-gray-900">Notification Preferences</h2>
                                    <p className="text-xs text-gray-500 mt-0.5">Choose how you want to be notified</p>
                                </div>
                            </div>
                        </div>

                        {/* Channel headers */}
                        <div className="px-6 py-3 grid grid-cols-[1fr_auto_auto_auto] gap-4 border-b border-gray-50">
                            <span className="text-xs font-medium text-gray-400 uppercase tracking-wide">Notification</span>
                            {(['email_enabled', 'push_enabled', 'in_app_enabled'] as const).map((ch) => (
                                <span key={ch} className="text-xs font-medium text-gray-400 uppercase tracking-wide w-12 text-center">
                                    {CHANNEL_LABELS[ch]}
                                </span>
                            ))}
                        </div>

                        {Object.entries(localPrefs).map(([type, pref], idx, arr) => (
                            <div
                                key={type}
                                className={`px-6 py-4 grid grid-cols-[1fr_auto_auto_auto] gap-4 items-center ${
                                    idx < arr.length - 1 ? 'border-b border-gray-50' : ''
                                }`}
                            >
                                <div>
                                    <p className="text-sm font-medium text-gray-800">{pref.label}</p>
                                    <p className="text-xs text-gray-500 mt-0.5">{pref.description}</p>
                                </div>
                                {(['email_enabled', 'push_enabled', 'in_app_enabled'] as const).map((channel) => (
                                    <div key={channel} className="w-12 flex justify-center">
                                        <ToggleSwitch
                                            checked={pref[channel]}
                                            onChange={() => update(type, channel, !pref[channel])}
                                            disabled={isSaving(type, channel)}
                                        />
                                    </div>
                                ))}
                            </div>
                        ))}
                    </section>

                    {/* Account section placeholder */}
                    <section className="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
                        <div className="px-6 py-5 border-b border-gray-100">
                            <div className="flex items-center gap-3">
                                <div className="w-9 h-9 rounded-lg bg-primary-peach/20 flex items-center justify-center">
                                    <svg className="w-5 h-5 text-primary-brown" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 className="text-base font-semibold text-gray-900">Account</h2>
                                    <p className="text-xs text-gray-500 mt-0.5">Profile and security settings</p>
                                </div>
                            </div>
                        </div>
                        <div className="px-6 py-4 flex flex-col gap-3">
                            <a
                                href={auth?.user?.slug ? `/vendors/${auth.user.slug}?edit=1` : '#'}
                                className="flex items-center justify-between py-2 hover:text-primary-olive transition-colors"
                            >
                                <span className="text-sm text-gray-700">Edit Business Profile</span>
                                <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                            <div className="border-t border-gray-50" />
                            <a
                                href="/profile"
                                className="flex items-center justify-between py-2 hover:text-primary-olive transition-colors"
                            >
                                <span className="text-sm text-gray-700">Change Password</span>
                                <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </section>

                    {/* Danger zone placeholder */}
                    <section className="bg-white rounded-xl shadow-sm border border-red-100">
                        <div className="px-6 py-5 border-b border-red-50">
                            <div className="flex items-center gap-3">
                                <div className="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center">
                                    <svg className="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 className="text-base font-semibold text-gray-900">Danger Zone</h2>
                                    <p className="text-xs text-gray-500 mt-0.5">Irreversible account actions</p>
                                </div>
                            </div>
                        </div>
                        <div className="px-6 py-4">
                            <p className="text-sm text-gray-500 mb-4">
                                Deleting your account will permanently remove all your products, profile data, and vendor history. This cannot be undone.
                            </p>
                            <button
                                disabled
                                className="px-4 py-2 border border-red-300 text-red-600 text-sm font-medium rounded-lg opacity-50 cursor-not-allowed"
                            >
                                Delete Account
                            </button>
                        </div>
                    </section>
                </div>
            </main>
        </>
    );
}
