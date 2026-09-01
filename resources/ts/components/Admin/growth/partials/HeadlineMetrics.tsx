import { GrowthGranularity, GrowthHeadline, GrowthHeadlineMetric } from '@/types';
import MetricCard from './MetricCard';

const EMPTY_METRIC: GrowthHeadlineMetric = { value: 0, previous: 0, change_pct: null, in_progress: 0 };

const CARDS: { key: string; label: string }[] = [
    { key: 'new_vendors', label: 'New vendors' },
    { key: 'new_customers', label: 'New customers' },
    { key: 'new_products', label: 'Products listed' },
    { key: 'contacts', label: 'Contact requests' },
    { key: 'searches', label: 'Bot searches' },
    { key: 'active_vendors', label: 'Vendors contacted' },
];

interface Props {
    headline: GrowthHeadline;
    granularity: GrowthGranularity;
    registeredVendors: number;
}

export default function HeadlineMetrics({ headline, granularity, registeredVendors }: Props) {
    const periodWord = granularity === 'week' ? 'week' : 'month';

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            {CARDS.map((card) => {
                const metric = headline.metrics[card.key] ?? EMPTY_METRIC;
                const footnote =
                    card.key === 'active_vendors'
                        ? `of ${registeredVendors.toLocaleString()} registered`
                        : headline.in_progress_label
                        ? `${metric.in_progress.toLocaleString()} so far this ${periodWord}`
                        : undefined;

                return (
                    <MetricCard
                        key={card.key}
                        label={card.label}
                        value={metric.value}
                        changePct={metric.change_pct}
                        footnote={footnote}
                    />
                );
            })}
        </div>
    );
}
