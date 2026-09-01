import { Area, AreaChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { GrowthSeriesRow } from '@/types';
import { axisProps, gridProps, legendStyle, OLIVE, PEACH } from '../chartTheme';
import ChartTooltip from './ChartTooltip';
import Panel from './Panel';

const FILLS = [
    { id: 'growthVendorFill', color: OLIVE, dataKey: 'total_vendors' as const, name: 'Vendors' },
    { id: 'growthProductFill', color: PEACH, dataKey: 'total_products' as const, name: 'Live products' },
];

interface Props {
    data: GrowthSeriesRow[];
}

export default function CumulativeChart({ data }: Props) {
    return (
        <Panel title="Cumulative totals" subtitle="Vendors and live products on the platform">
            <ResponsiveContainer width="100%" height={240}>
                <AreaChart data={data}>
                    <defs>
                        {FILLS.map((fill) => (
                            <linearGradient key={fill.id} id={fill.id} x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor={fill.color} stopOpacity={0.3} />
                                <stop offset="95%" stopColor={fill.color} stopOpacity={0} />
                            </linearGradient>
                        ))}
                    </defs>
                    <CartesianGrid {...gridProps} />
                    <XAxis dataKey="label" {...axisProps} />
                    <YAxis {...axisProps} allowDecimals={false} />
                    <Tooltip content={<ChartTooltip />} />
                    <Legend wrapperStyle={legendStyle} />
                    {FILLS.map((fill) => (
                        <Area
                            key={fill.dataKey}
                            type="monotone"
                            dataKey={fill.dataKey}
                            name={fill.name}
                            stroke={fill.color}
                            fill={`url(#${fill.id})`}
                            strokeWidth={2}
                        />
                    ))}
                </AreaChart>
            </ResponsiveContainer>
        </Panel>
    );
}
