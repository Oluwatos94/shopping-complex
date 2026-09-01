import { CartesianGrid, Legend, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { GrowthSeriesRow } from '@/types';
import { axisProps, gridProps, legendStyle } from '../chartTheme';
import ChartTooltip from './ChartTooltip';
import Panel from './Panel';

export interface TrendLine {
    dataKey: keyof GrowthSeriesRow;
    name: string;
    color: string;
}

interface Props {
    title: string;
    subtitle?: string;
    data: GrowthSeriesRow[];
    lines: TrendLine[];
    height?: number;
}

export default function TrendChart({ title, subtitle, data, lines, height = 260 }: Props) {
    return (
        <Panel title={title} subtitle={subtitle}>
            <ResponsiveContainer width="100%" height={height}>
                <LineChart data={data}>
                    <CartesianGrid {...gridProps} />
                    <XAxis dataKey="label" {...axisProps} />
                    <YAxis {...axisProps} allowDecimals={false} />
                    <Tooltip content={<ChartTooltip />} />
                    <Legend wrapperStyle={legendStyle} />
                    {lines.map((line) => (
                        <Line
                            key={line.dataKey}
                            type="monotone"
                            dataKey={line.dataKey}
                            name={line.name}
                            stroke={line.color}
                            strokeWidth={2}
                            dot={false}
                        />
                    ))}
                </LineChart>
            </ResponsiveContainer>
        </Panel>
    );
}
