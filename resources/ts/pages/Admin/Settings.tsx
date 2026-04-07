import { Head } from '@inertiajs/react';
import { useState, useRef } from 'react';
import AdminLayout from '@/components/Admin/AdminLayout';

type Tab = 'general' | 'payments' | 'notifications' | 'security';

function Toggle({ enabled, onChange }: { enabled: boolean; onChange: (v: boolean) => void }) {
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

function Toast({ onClose }: { onClose: () => void }) {
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

function GeneralTab() {
    const [maintenance, setMaintenance] = useState(false);
    const fileRef = useRef<HTMLInputElement>(null);

    return (
        <div className="space-y-12">
            {/* General Configuration */}
            <section>
                <div className="flex items-center gap-3 mb-6">
                    <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    <h3 className="text-xl font-bold tracking-tight text-gray-900">General Configuration</h3>
                </div>
                <div className="bg-white rounded-xl p-8 border border-gray-100 space-y-8">
                    <div className="grid grid-cols-2 gap-8">
                        <div className="space-y-2">
                            <label className="text-[10px] uppercase font-bold tracking-widest text-gray-400 block">
                                Platform Name
                            </label>
                            <input
                                type="text"
                                defaultValue="Shopping Complex"
                                className="w-full bg-gray-50 border border-gray-200/60 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20"
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-[10px] uppercase font-bold tracking-widest text-gray-400 block">
                                Support Email
                            </label>
                            <input
                                type="email"
                                defaultValue="admin@shoppingcomplex.com"
                                className="w-full bg-gray-50 border border-gray-200/60 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20"
                            />
                        </div>
                    </div>

                    {/* Brand Assets */}
                    <div className="space-y-4">
                        <label className="text-[10px] uppercase font-bold tracking-widest text-gray-400 block">
                            Brand Assets
                        </label>
                        <div className="flex items-center gap-6 p-6 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50/50">
                            <div className="w-20 h-20 rounded-xl bg-primary-dark flex items-center justify-center flex-shrink-0">
                                <svg className="w-8 h-8 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div className="flex-1">
                                <h4 className="font-bold text-sm text-gray-900 mb-1">Company Logo</h4>
                                <p className="text-xs text-gray-400 mb-4">Recommended size: 512×512px. SVG or PNG preferred.</p>
                                <div className="flex gap-2">
                                    <input ref={fileRef} type="file" accept="image/*" className="hidden" />
                                    <button
                                        onClick={() => fileRef.current?.click()}
                                        className="text-xs font-bold px-4 py-2 bg-primary-olive/10 text-primary-olive rounded-lg hover:bg-primary-olive/20 transition-colors"
                                    >
                                        Replace Logo
                                    </button>
                                    <button className="text-xs font-bold px-4 py-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Maintenance Mode */}
                    <div className="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div className="flex gap-3 items-center">
                            <svg className="w-5 h-5 text-primary-brown" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <div>
                                <p className="font-bold text-sm text-gray-900">Maintenance Mode</p>
                                <p className="text-xs text-gray-400">Prevent public access while making updates.</p>
                            </div>
                        </div>
                        <Toggle enabled={maintenance} onChange={setMaintenance} />
                    </div>
                </div>
            </section>

            {/* Payments & Commissions */}
            <section>
                <div className="flex items-center gap-3 mb-6">
                    <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 className="text-xl font-bold tracking-tight text-gray-900">Payments &amp; Commissions</h3>
                </div>
                <PaymentsSection />
            </section>
        </div>
    );
}

function PaymentsSection() {
    const [commission, setCommission] = useState(12.5);
    const [currencies, setCurrencies] = useState({ NGN: true, USD: true, EUR: false, GBP: false });

    const toggleCurrency = (key: keyof typeof currencies) => {
        setCurrencies((prev) => ({ ...prev, [key]: !prev[key] }));
    };

    return (
        <div className="bg-white rounded-xl p-8 border border-gray-100 space-y-10">
            {/* Commission Slider */}
            <div className="space-y-6">
                <div className="flex justify-between items-end">
                    <label className="text-[10px] uppercase font-bold tracking-widest text-gray-400">
                        Default Commission Rate
                    </label>
                    <span className="text-2xl font-bold text-primary-olive">{commission.toFixed(1)}%</span>
                </div>
                <input
                    type="range"
                    min={0}
                    max={25}
                    step={0.5}
                    value={commission}
                    onChange={(e) => setCommission(Number(e.target.value))}
                    className="w-full h-1.5 rounded-lg appearance-none cursor-pointer accent-[#86885e]"
                    style={{ background: `linear-gradient(to right, #86885e ${(commission / 25) * 100}%, #e5e7eb ${(commission / 25) * 100}%)` }}
                />
                <div className="flex justify-between text-[10px] font-bold text-gray-400">
                    <span>0%</span>
                    <span>5%</span>
                    <span>10%</span>
                    <span>15%</span>
                    <span>20%</span>
                    <span>25%</span>
                </div>
            </div>

            <div className="grid grid-cols-2 gap-8">
                {/* Payout Schedule */}
                <div className="space-y-2">
                    <label className="text-[10px] uppercase font-bold tracking-widest text-gray-400 block">
                        Payout Schedule
                    </label>
                    <select className="w-full bg-gray-50 border border-gray-200/60 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20">
                        <option>Weekly (Mondays)</option>
                        <option>Bi-Weekly</option>
                        <option selected>Monthly (1st of month)</option>
                        <option>Instant (Threshold based)</option>
                    </select>
                </div>

                {/* Currencies */}
                <div className="space-y-3">
                    <label className="text-[10px] uppercase font-bold tracking-widest text-gray-400 block">
                        Accepted Currencies
                    </label>
                    <div className="grid grid-cols-2 gap-2">
                        {(Object.keys(currencies) as Array<keyof typeof currencies>).map((key) => (
                            <label
                                key={key}
                                className="flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    checked={currencies[key]}
                                    onChange={() => toggleCurrency(key)}
                                    className="w-4 h-4 rounded border-gray-300 text-primary-olive focus:ring-primary-olive/20 accent-[#86885e]"
                                />
                                <span className="text-xs font-bold text-gray-700">{key}</span>
                            </label>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}

function NotificationsTab() {
    const [email, setEmail] = useState(true);
    const [sms, setSms] = useState(false);
    const [push, setPush] = useState(true);
    const [vendorAlerts, setVendorAlerts] = useState(true);
    const [orderAlerts, setOrderAlerts] = useState(true);
    const [reviewAlerts, setReviewAlerts] = useState(false);

    const rows = [
        { label: 'Email Notifications', description: 'Send updates via email to admins', value: email, onChange: setEmail },
        { label: 'SMS Critical Alerts', description: 'Urgent issues sent to registered numbers', value: sms, onChange: setSms },
        { label: 'Browser Push', description: 'In-browser notifications while logged in', value: push, onChange: setPush },
        { label: 'Vendor Approval Alerts', description: 'Notify when a new vendor applies', value: vendorAlerts, onChange: setVendorAlerts },
        { label: 'Order Escalations', description: 'High-priority order disputes', value: orderAlerts, onChange: setOrderAlerts },
        { label: 'Review Flags', description: 'Flagged customer reviews queue', value: reviewAlerts, onChange: setReviewAlerts },
    ];

    return (
        <section>
            <div className="flex items-center gap-3 mb-6">
                <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <h3 className="text-xl font-bold tracking-tight text-gray-900">Notification Preferences</h3>
            </div>
            <div className="bg-white rounded-xl border border-gray-100 divide-y divide-gray-50">
                {rows.map((row) => (
                    <div key={row.label} className="flex items-center justify-between px-8 py-5">
                        <div>
                            <p className="text-sm font-bold text-gray-900">{row.label}</p>
                            <p className="text-xs text-gray-400 mt-0.5">{row.description}</p>
                        </div>
                        <Toggle enabled={row.value} onChange={row.onChange} />
                    </div>
                ))}
            </div>
        </section>
    );
}

function SecurityTab() {
    const [timeout, setTimeout_] = useState(30);
    const [ipWhitelist, setIpWhitelist] = useState('192.168.1.1, 10.0.0.15');
    const [twoFa, setTwoFa] = useState(true);
    const [loginAlerts, setLoginAlerts] = useState(true);

    return (
        <section>
            <div className="flex items-center gap-3 mb-6">
                <svg className="w-5 h-5 text-primary-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <h3 className="text-xl font-bold tracking-tight text-gray-900">Security Policy</h3>
            </div>
            <div className="bg-white rounded-xl p-8 border border-gray-100 space-y-8">
                <div className="grid grid-cols-2 gap-8">
                    <div className="space-y-2">
                        <label className="text-[10px] uppercase font-bold tracking-widest text-gray-400 block">
                            Session Timeout (minutes)
                        </label>
                        <input
                            type="number"
                            min={5}
                            max={480}
                            value={timeout}
                            onChange={(e) => setTimeout_(Number(e.target.value))}
                            className="w-full bg-gray-50 border border-gray-200/60 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20"
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-[10px] uppercase font-bold tracking-widest text-gray-400 block">
                            IP Whitelist
                        </label>
                        <input
                            type="text"
                            value={ipWhitelist}
                            onChange={(e) => setIpWhitelist(e.target.value)}
                            placeholder="Comma-separated IPs..."
                            className="w-full bg-gray-50 border border-gray-200/60 rounded-lg px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary-olive/20"
                        />
                    </div>
                </div>

                <div className="space-y-3">
                    <div className="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div>
                            <p className="text-sm font-bold text-gray-900">Two-Factor Authentication (2FA)</p>
                            <p className="text-xs text-gray-400 mt-0.5">Require 2FA for all admin accounts.</p>
                        </div>
                        <div className="flex items-center gap-3">
                            {twoFa && (
                                <span className="text-[10px] font-bold bg-primary-olive/10 text-primary-olive px-2 py-1 rounded">
                                    ENFORCED
                                </span>
                            )}
                            <Toggle enabled={twoFa} onChange={setTwoFa} />
                        </div>
                    </div>
                    <div className="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div>
                            <p className="text-sm font-bold text-gray-900">Login Anomaly Alerts</p>
                            <p className="text-xs text-gray-400 mt-0.5">Alert on suspicious login attempts.</p>
                        </div>
                        <Toggle enabled={loginAlerts} onChange={setLoginAlerts} />
                    </div>
                </div>
            </div>
        </section>
    );
}

export default function Settings() {
    const [activeTab, setActiveTab] = useState<Tab>('general');
    const [showToast, setShowToast] = useState(false);

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

    const [notifToggles, setNotifToggles] = useState({ email: true, sms: false, push: true });
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
                                Global configuration for the Shopping Complex ecosystem.
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
