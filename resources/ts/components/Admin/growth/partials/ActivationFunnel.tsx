import { GrowthFunnelStep } from '@/types';
import Panel from './Panel';

interface Props {
    steps: GrowthFunnelStep[];
}

export default function ActivationFunnel({ steps }: Props) {
    return (
        <Panel title="Vendor activation" subtitle="Each step is a subset of the one above it">
            <div className="space-y-4">
                {steps.map((step) => (
                    <div key={step.label}>
                        <div className="flex items-center justify-between mb-1.5">
                            <span className="text-sm text-gray-600">{step.label}</span>
                            <span className="text-sm font-bold text-gray-900">
                                {step.value.toLocaleString()}
                                <span className="text-[10px] text-gray-400 ml-1.5">{step.pct_of_registered}%</span>
                            </span>
                        </div>
                        <div className="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div
                                className="h-full rounded-full bg-primary-olive"
                                style={{ width: `${Math.min(step.pct_of_registered, 100)}%` }}
                            />
                        </div>
                    </div>
                ))}
            </div>
        </Panel>
    );
}
