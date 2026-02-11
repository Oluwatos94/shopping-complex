interface Props {
    label: string;
    value: string | number;
    icon: React.ReactNode;
    trend?: { value: number; label: string };
}

export default function StatCard({ label, value, icon, trend }: Props) {
    return (
        <div className="bg-white rounded-xl border border-gray-200 p-5 flex items-start gap-4">
            <div className="w-12 h-12 rounded-lg bg-primary-olive/10 flex items-center justify-center shrink-0">
                {icon}
            </div>
            <div className="min-w-0">
                <p className="text-sm text-gray-500 truncate">{label}</p>
                <p className="text-2xl font-bold text-gray-900 mt-1">{value}</p>
                {trend && (
                    <p className={`text-xs mt-1 ${trend.value >= 0 ? 'text-green-600' : 'text-red-500'}`}>
                        {trend.value >= 0 ? '+' : ''}{trend.value}% {trend.label}
                    </p>
                )}
            </div>
        </div>
    );
}
