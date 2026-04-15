import { useState } from 'react';
import Toggle from '@/components/Forms/Toggle';

export default function SecurityTab() {
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
