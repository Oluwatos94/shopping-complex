import { GrowthRevenueSummary } from '@/types';
import { formatCurrency } from '../chartTheme';
import MetricCard from './MetricCard';

interface Props {
    revenue: GrowthRevenueSummary;
}

export default function RevenueSummary({ revenue }: Props) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            <MetricCard label="Paying vendors" value={revenue.paying_vendors} />
            <MetricCard
                label="Monthly recurring"
                value={formatCurrency(revenue.monthly_recurring)}
                footnote="Normalised per plan length"
            />
            <MetricCard label="Average per vendor" value={formatCurrency(revenue.average_per_vendor)} />
            <MetricCard
                label="Collected to date"
                value={formatCurrency(revenue.lifetime_collected)}
                footnote="All subscriptions, all time"
            />
        </div>
    );
}
