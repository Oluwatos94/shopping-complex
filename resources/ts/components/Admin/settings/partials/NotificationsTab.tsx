import { useState } from 'react';
import Toggle from '@/components/Forms/Toggle';

export default function NotificationsTab() {
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
