import { UnmetDemandRow } from '@/types';
import Panel from './Panel';

interface Props {
    rows: UnmetDemandRow[];
}

export default function UnmetDemand({ rows }: Props) {
    return (
        <Panel title="Unmet demand" subtitle="Searches that returned nothing — what to go recruit">
            {rows.length === 0 ? (
                <p className="text-sm text-gray-500">No failed searches in this period.</p>
            ) : (
                <div className="divide-y divide-gray-100">
                    {rows.map((row) => (
                        <div key={row.query} className="flex items-center justify-between py-2.5">
                            <span className="text-sm text-gray-700">{row.query}</span>
                            <span className="text-sm font-bold text-gray-900">{row.total.toLocaleString()}</span>
                        </div>
                    ))}
                </div>
            )}
        </Panel>
    );
}
