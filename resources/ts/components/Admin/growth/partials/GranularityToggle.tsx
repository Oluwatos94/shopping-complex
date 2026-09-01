import { GrowthGranularity } from '@/types';

const OPTIONS: { value: GrowthGranularity; label: string }[] = [
    { value: 'week', label: 'Weekly' },
    { value: 'month', label: 'Monthly' },
];

interface Props {
    value: GrowthGranularity;
    onChange: (granularity: GrowthGranularity) => void;
}

export default function GranularityToggle({ value, onChange }: Props) {
    return (
        <div className="flex rounded-lg border border-gray-200 bg-white p-1">
            {OPTIONS.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    aria-pressed={value === option.value}
                    onClick={() => onChange(option.value)}
                    className={`px-4 py-1.5 text-sm font-medium rounded-md transition-colors ${
                        value === option.value ? 'bg-primary-olive text-white' : 'text-gray-600 hover:bg-gray-50'
                    }`}
                >
                    {option.label}
                </button>
            ))}
        </div>
    );
}
