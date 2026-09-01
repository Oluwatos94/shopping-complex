interface TooltipEntry {
    name: string;
    value: number;
    color: string;
}

interface Props {
    active?: boolean;
    payload?: TooltipEntry[];
    label?: string;
}

export default function ChartTooltip({ active, payload, label }: Props) {
    if (!active || !payload?.length) {
        return null;
    }

    return (
        <div className="bg-white border border-gray-200 rounded-lg px-3 py-2 shadow-lg text-sm">
            <p className="text-gray-500 text-xs mb-1">{label}</p>
            {payload.map((entry) => (
                <p key={entry.name} className="font-medium text-gray-900 flex items-center gap-2">
                    <span className="w-2 h-2 rounded-full flex-shrink-0" style={{ backgroundColor: entry.color }} />
                    {entry.name}: {entry.value.toLocaleString()}
                </p>
            ))}
        </div>
    );
}
