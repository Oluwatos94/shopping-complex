import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

interface DailyData {
    date: string;
    count: number;
}

interface Props {
    data: DailyData[];
    title: string;
    color?: string;
}

export default function ViewsChart({ data, title, color = '#6B7B3A' }: Props) {
    const formatted = data.map((d) => ({
        date: new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
        count: d.count,
    }));

    return (
        <div className="bg-white rounded-xl border border-gray-200 p-5">
            <h3 className="text-sm font-semibold text-gray-700 mb-4">{title}</h3>
            {data.length === 0 ? (
                <div className="h-[250px] flex items-center justify-center text-gray-400 text-sm">
                    No data for this period
                </div>
            ) : (
                <ResponsiveContainer width="100%" height={250}>
                    <AreaChart data={formatted}>
                        <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                        <XAxis
                            dataKey="date"
                            tick={{ fontSize: 11, fill: '#9ca3af' }}
                            tickLine={false}
                            axisLine={false}
                        />
                        <YAxis
                            tick={{ fontSize: 11, fill: '#9ca3af' }}
                            tickLine={false}
                            axisLine={false}
                            allowDecimals={false}
                        />
                        <Tooltip
                            contentStyle={{
                                borderRadius: '8px',
                                border: '1px solid #e5e7eb',
                                fontSize: '13px',
                            }}
                        />
                        <Area
                            type="monotone"
                            dataKey="count"
                            stroke={color}
                            fill={color}
                            fillOpacity={0.1}
                            strokeWidth={2}
                        />
                    </AreaChart>
                </ResponsiveContainer>
            )}
        </div>
    );
}
