import { useState, useRef } from 'react';
import Toggle from '@/components/Forms/Toggle';
import PaymentsSection from './PaymentsSection';

export default function GeneralTab() {
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
