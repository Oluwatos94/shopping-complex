import { useState } from 'react';
import Toggle from '@/components/Forms/Toggle';

export default function PaymentsSection() {
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
