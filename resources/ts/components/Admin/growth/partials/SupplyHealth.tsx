import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { GrowthSupplyHealth } from '@/types';
import { axisProps, gridProps, OLIVE } from '../chartTheme';
import ChartTooltip from './ChartTooltip';
import Panel from './Panel';

interface Props {
    supply: GrowthSupplyHealth;
}

export default function SupplyHealth({ supply }: Props) {
    const change =
        supply.active_prior_30_days > 0
            ? Math.round(
                  ((supply.active_last_30_days - supply.active_prior_30_days) / supply.active_prior_30_days) * 100
              )
            : null;

    const bars = [
        { name: 'Registered', value: supply.registered_vendors },
        { name: 'Ever contacted', value: supply.ever_contacted },
        { name: 'Active 30d', value: supply.active_last_30_days },
    ];

    return (
        <Panel title="Supply health" subtitle="Vendors receiving real demand, and vendors sitting idle">
            <div className="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <p className="text-2xl font-bold text-gray-900">{supply.active_last_30_days.toLocaleString()}</p>
                    <p className="text-xs text-gray-500 mt-0.5">Contacted in last 30 days</p>
                    {change !== null && (
                        <span
                            className={`inline-block mt-1.5 text-xs font-bold px-1.5 py-0.5 rounded ${
                                change >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-red-600 bg-red-50'
                            }`}
                        >
                            {change >= 0 ? '+' : ''}
                            {change}% vs prior 30
                        </span>
                    )}
                </div>
                <div>
                    <p className="text-2xl font-bold text-gray-900">{supply.never_contacted.toLocaleString()}</p>
                    <p className="text-xs text-gray-500 mt-0.5">Never contacted</p>
                </div>
            </div>
            <ResponsiveContainer width="100%" height={140}>
                <BarChart data={bars}>
                    <CartesianGrid {...gridProps} />
                    <XAxis dataKey="name" {...axisProps} />
                    <YAxis {...axisProps} allowDecimals={false} />
                    <Tooltip content={<ChartTooltip />} />
                    <Bar dataKey="value" name="Vendors" fill={OLIVE} radius={[4, 4, 0, 0]} />
                </BarChart>
            </ResponsiveContainer>
        </Panel>
    );
}
