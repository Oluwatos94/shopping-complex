interface Props {
    label: string;
    value: string | number;
    changePct?: number | null;
    footnote?: string;
}

export default function MetricCard({ label, value, changePct, footnote }: Props) {
    const hasChange = changePct !== null && changePct !== undefined;
    const rising = hasChange && changePct >= 0;

    return (
        <div className="bg-white p-6 rounded-xl border border-gray-100 flex flex-col justify-between h-36">
            <span className="text-[10px] font-bold uppercase tracking-[0.1em] text-gray-500">{label}</span>
            <div>
                <h3 className="text-3xl font-bold text-gray-900 tracking-tight">
                    {typeof value === 'number' ? value.toLocaleString() : value}
                </h3>
                {(hasChange || footnote) && (
                    <div className="flex items-center gap-2 mt-1.5">
                        {hasChange && (
                            <span
                                className={`text-xs font-bold px-1.5 py-0.5 rounded ${
                                    rising ? 'text-emerald-600 bg-emerald-50' : 'text-red-600 bg-red-50'
                                }`}
                            >
                                {rising ? '+' : ''}
                                {changePct}%
                            </span>
                        )}
                        {footnote && <span className="text-[10px] text-gray-400">{footnote}</span>}
                    </div>
                )}
            </div>
        </div>
    );
}
