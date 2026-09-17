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
    daily_coin_cap: number | null;
    coin_naira_value: number;
}

const naira = (n: number) => `₦${n.toLocaleString('en-US')}`;
const DEFAULT_CAP = 40;

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
            className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-brand-green focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed ${
                checked ? 'bg-brand-green' : 'bg-gray-200'
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

export default function VendorSettings({ preferences, availableTypes, daily_coin_cap, coin_naira_value }: SettingsProps) {
    const { auth } = usePage<{ auth: { user: any } | null }>().props;
    const [localPrefs, setLocalPrefs] = useState(preferences);
    const [saving, setSaving] = useState<Toggle | null>(null);
    const [flash, setFlash] = useState<string | null>(null);

    const [capEnabled, setCapEnabled] = useState(daily_coin_cap !== null);
    const [capValue, setCapValue] = useState(daily_coin_cap ?? DEFAULT_CAP);
    const [savingCap, setSavingCap] = useState(false);

    const capIsValid = Number.isInteger(capValue) && capValue >= 1 && capValue <= 100000;

    const saveCap = () => {
        if (capEnabled && !capIsValid) return;
        setSavingCap(true);
        router.post(
            '/vendor/coin-cap',
            { daily_coin_cap: capEnabled ? capValue : null },
            {
                preserveScroll: true,
                onSuccess: () => setFlash(capEnabled ? 'Daily spend cap saved' : 'Daily spend cap removed'),
                onFinish: () => setSavingCap(false),
            },
        );
    };

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
                                <div className="w-9 h-9 rounded-lg bg-brand-green/10 flex items-center justify-center">
                                    <svg className="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

                    {/* Coin spending cap */}
                    <section className="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
                        <div className="px-6 py-5 border-b border-gray-100">
                            <div className="flex items-center gap-3">
                                <div className="w-9 h-9 rounded-lg bg-brand-green/10 flex items-center justify-center">
                                    <svg className="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 className="text-base font-semibold text-gray-900">Daily spend cap</h2>
                                    <p className="text-xs text-gray-500 mt-0.5">
                                        The most coins you can be charged for leads in a single day.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="px-6 py-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-800">Limit my daily spend</p>
                                    <p className="text-xs text-gray-500 mt-0.5">
                                        Turn off for no cap — every lead is charged at your rate.
                                    </p>
                                </div>
                                <ToggleSwitch checked={capEnabled} onChange={() => setCapEnabled((v) => !v)} disabled={savingCap} />
                            </div>

                            {capEnabled && (
                                <div className="mt-5 flex flex-wrap items-end gap-4">
                                    <label className="block">
                                        <span className="text-xs font-medium text-gray-500">Cap (coins per day)</span>
                                        <input
                                            type="number"
                                            min={1}
                                            max={100000}
                                            value={capValue}
                                            onChange={(e) => setCapValue(Number(e.target.value))}
                                            className="mt-1 block w-40 rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green"
                                        />
                                    </label>
                                    <p className="pb-2 text-sm text-gray-500">
                                        ≈ <span className="font-semibold text-gray-800">{naira(capValue * coin_naira_value)}</span> per day
                                    </p>
                                </div>
                            )}

                            <p className="mt-4 rounded-lg bg-gray-50 px-3 py-2.5 text-xs text-gray-500">
                                Leads beyond your cap are still delivered to buyers — you simply aren&apos;t charged for them.
                            </p>

                            <button
                                type="button"
                                onClick={saveCap}
                                disabled={savingCap || (capEnabled && !capIsValid)}
                                className="mt-4 inline-flex h-10 items-center justify-center rounded-lg bg-brand-green px-5 text-sm font-bold text-white transition hover:bg-brand-green-dark disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {savingCap ? 'Saving…' : 'Save cap'}
                            </button>
                        </div>
                    </section>

                    {/* Account section placeholder */}
                    <section className="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
                        <div className="px-6 py-5 border-b border-gray-100">
                            <div className="flex items-center gap-3">
                                <div className="w-9 h-9 rounded-lg bg-brand-green/20 flex items-center justify-center">
                                    <svg className="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                className="flex items-center justify-between py-2 hover:text-brand-green transition-colors"
                            >
                                <span className="text-sm text-gray-700">Edit Business Profile</span>
                                <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                            <div className="border-t border-gray-50" />
                            <a
                                href="/profile"
                                className="flex items-center justify-between py-2 hover:text-brand-green transition-colors"
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
