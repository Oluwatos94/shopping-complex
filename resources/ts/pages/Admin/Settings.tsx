import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/components/Admin/AdminLayout';
import Toggle from '@/components/Forms/Toggle';
import Toast from '@/components/Toast';
import GeneralTab from '@/components/Admin/settings/partials/GeneralTab';
import PaymentsSection from '@/components/Admin/settings/partials/PaymentsSection';
import NotificationsTab from '@/components/Admin/settings/partials/NotificationsTab';
import SecurityTab from '@/components/Admin/settings/partials/SecurityTab';

type Tab = 'general' | 'payments' | 'notifications' | 'security';

export default function Settings() {
    const [activeTab, setActiveTab] = useState<Tab>('general');
    const [showToast, setShowToast] = useState(false);
    const [notifToggles, setNotifToggles] = useState({ email: true, sms: false, push: true });

    const tabs: { key: Tab; label: string }[] = [
        { key: 'general', label: 'General' },
        { key: 'payments', label: 'Payments' },
        { key: 'notifications', label: 'Notifications' },
        { key: 'security', label: 'Security' },
    ];

    const handleSave = () => {
        setShowToast(true);
        setTimeout(() => setShowToast(false), 4000);
    };

    const toggleNotif = (key: keyof typeof notifToggles) =>
        setNotifToggles((prev) => ({ ...prev, [key]: !prev[key] }));

    return (
        <>
            <Head title="Platform Settings — Admin" />
            <AdminLayout>
                <div className="max-w-6xl mx-auto">
                    {/* Page Header */}
                    <div className="mb-12 flex items-end justify-between">
                        <div>
                            <h2 className="text-4xl font-extrabold tracking-tight text-gray-900 mb-2">
                                Platform Settings
                            </h2>
                            <p className="text-gray-400 text-sm">
                                Global configuration for the jiidaa ecosystem.
                            </p>
                        </div>
                        <div className="flex gap-3">
                            <button className="px-6 py-2.5 rounded-lg text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors border border-gray-200/60">
                                Discard
                            </button>
                            <button
                                onClick={handleSave}
                                className="px-8 py-2.5 rounded-lg text-sm font-semibold text-white bg-primary-olive hover:brightness-110 transition-all shadow-lg shadow-primary-olive/20 active:scale-[0.98]"
                            >
                                Save Changes
                            </button>
                        </div>
                    </div>

                    {/* Tabs */}
                    <div className="flex gap-8 mb-10 border-b border-gray-100">
                        {tabs.map((tab) => (
                            <button
                                key={tab.key}
                                onClick={() => setActiveTab(tab.key)}
                                className={`pb-4 text-sm font-semibold transition-colors border-b-2 -mb-px ${
                                    activeTab === tab.key
                                        ? 'text-primary-olive border-primary-olive'
                                        : 'text-gray-400 border-transparent hover:text-gray-700'
                                }`}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>

                    {/* Content Grid */}
                    <div className="grid grid-cols-12 gap-10">
                        {/* Left Column */}
                        <div className="col-span-12 lg:col-span-8">
                            {activeTab === 'general' && <GeneralTab />}
                            {activeTab === 'payments' && (
                                <section>
                                    <div className="flex items-center gap-3 mb-6">
                                        <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <h3 className="text-xl font-bold tracking-tight text-gray-900">Payments &amp; Commissions</h3>
                                    </div>
                                    <PaymentsSection />
                                </section>
                            )}
                            {activeTab === 'notifications' && <NotificationsTab />}
                            {activeTab === 'security' && <SecurityTab />}
                        </div>

                        {/* Right Column */}
                        <div className="col-span-12 lg:col-span-4 space-y-8">
                            {/* Alert Center */}
                            <div className="bg-gray-50 rounded-xl p-6 border border-gray-100">
                                <h4 className="text-[10px] font-bold uppercase tracking-widest mb-6 flex items-center gap-2 text-gray-500">
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    Alert Center
                                </h4>
                                <div className="space-y-5">
                                    {(
                                        [
                                            { key: 'email', label: 'Email Notifications' },
                                            { key: 'sms', label: 'SMS Critical Alerts' },
                                            { key: 'push', label: 'Browser Push' },
                                        ] as { key: keyof typeof notifToggles; label: string }[]
                                    ).map(({ key, label }) => (
                                        <div key={key} className="flex items-center justify-between">
                                            <span className="text-xs font-medium text-gray-700">{label}</span>
                                            <Toggle enabled={notifToggles[key]} onChange={() => toggleNotif(key)} />
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Security Insights — dark panel */}
                            <div className="bg-primary-dark rounded-xl p-6 text-white">
                                <div className="flex items-center gap-3 mb-6">
                                    <svg className="w-5 h-5 text-primary-olive" fill="currentColor" viewBox="0 0 24 24">
                                        <path fillRule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clipRule="evenodd" />
                                    </svg>
                                    <h4 className="text-[10px] font-bold uppercase tracking-widest">Security Policy</h4>
                                </div>
                                <div className="space-y-5">
                                    <div className="space-y-2">
                                        <label className="text-[10px] text-white/40 font-bold uppercase tracking-wider block">
                                            Session Timeout (Min)
                                        </label>
                                        <input
                                            type="number"
                                            defaultValue={30}
                                            className="w-full bg-white/10 border-none rounded-lg px-4 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-primary-olive/40 placeholder:text-white/30"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-[10px] text-white/40 font-bold uppercase tracking-wider block">
                                            IP Whitelist
                                        </label>
                                        <div className="bg-white/10 rounded-lg p-3 text-[11px] font-mono text-white/60">
                                            192.168.1.1, 10.0.0.15...
                                        </div>
                                    </div>
                                    <div className="pt-4 border-t border-white/10">
                                        <div className="flex items-center justify-between">
                                            <span className="text-xs font-bold">2FA Mandatory</span>
                                            <span className="text-[10px] font-bold bg-primary-olive/20 text-primary-olive px-2 py-1 rounded">
                                                ENFORCED
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Status Card */}
                            <div className="relative overflow-hidden rounded-xl h-48 group">
                                <div className="absolute inset-0 bg-gradient-to-br from-primary-dark via-primary-brown to-primary-olive" />
                                <div className="absolute inset-0 opacity-10">
                                    <svg viewBox="0 0 200 200" className="w-full h-full">
                                        <circle cx="150" cy="50" r="80" fill="white" />
                                        <circle cx="30" cy="150" r="60" fill="white" />
                                    </svg>
                                </div>
                                <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent group-hover:from-black/40 transition-all duration-500" />
                                <div className="absolute bottom-4 left-4 right-4">
                                    <p className="text-[10px] font-bold uppercase tracking-widest text-primary-peach mb-1">
                                        Status Report
                                    </p>
                                    <h5 className="text-white font-bold leading-tight text-sm">
                                        All systems operational across all regions.
                                    </h5>
                                </div>
                                <div className="absolute top-4 right-4 flex gap-1">
                                    {[...Array(3)].map((_, i) => (
                                        <span key={i} className="w-2 h-2 rounded-full bg-primary-olive opacity-80" />
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </AdminLayout>

            {showToast && <Toast onClose={() => setShowToast(false)} />}
        </>
    );
}
