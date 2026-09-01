import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/components/Admin/AdminLayout';
import { BROWN, LIGHT, OLIVE, PEACH } from '@/components/Admin/growth/chartTheme';
import ActivationFunnel from '@/components/Admin/growth/partials/ActivationFunnel';
import CumulativeChart from '@/components/Admin/growth/partials/CumulativeChart';
import GranularityToggle from '@/components/Admin/growth/partials/GranularityToggle';
import HeadlineMetrics from '@/components/Admin/growth/partials/HeadlineMetrics';
import RevenueSummary from '@/components/Admin/growth/partials/RevenueSummary';
import SupplyHealth from '@/components/Admin/growth/partials/SupplyHealth';
import TrendChart, { TrendLine } from '@/components/Admin/growth/partials/TrendChart';
import UnmetDemand from '@/components/Admin/growth/partials/UnmetDemand';
import { GrowthGranularity, GrowthPageProps } from '@/types';

const SIGNUP_LINES: TrendLine[] = [
    { dataKey: 'new_vendors', name: 'Vendors', color: OLIVE },
    { dataKey: 'new_customers', name: 'Customers', color: PEACH },
    { dataKey: 'new_products', name: 'Products', color: BROWN },
];

const ACTIVITY_LINES: TrendLine[] = [
    { dataKey: 'searches', name: 'Searches', color: OLIVE },
    { dataKey: 'contacts', name: 'Contacts', color: PEACH },
    { dataKey: 'active_vendors', name: 'Vendors contacted', color: BROWN },
    { dataKey: 'no_results', name: 'No results', color: LIGHT },
];

export default function Growth({
    granularity,
    range,
    series,
    headline,
    funnel,
    supply,
    revenue,
    unmet_demand,
}: GrowthPageProps) {
    const chartData = series.filter((row) => !row.is_partial);
    const periodWord = granularity === 'week' ? 'week' : 'month';

    const setGranularity = (next: GrowthGranularity) => {
        router.get('/admin/growth', { granularity: next }, { preserveScroll: true, preserveState: true });
    };

    return (
        <>
            <Head title="Growth" />
            <AdminLayout>
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 className="text-3xl font-bold tracking-tight text-gray-900">Growth</h2>
                        <p className="text-gray-500 mt-1">
                            How supply, demand and revenue have moved since {range.start}.
                            {headline.period_label &&
                                ` Comparisons use the last complete ${periodWord} (${headline.period_label}).`}
                        </p>
                    </div>
                    <GranularityToggle value={granularity} onChange={setGranularity} />
                </div>

                <div className="mb-8">
                    <HeadlineMetrics
                        headline={headline}
                        granularity={granularity}
                        registeredVendors={supply.registered_vendors}
                    />
                </div>

                <div className="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-5">
                    <TrendChart
                        title="Sign-ups and listings"
                        subtitle={`New per ${periodWord}`}
                        data={chartData}
                        lines={SIGNUP_LINES}
                    />
                    <TrendChart
                        title="Marketplace activity"
                        subtitle={`Bot demand per ${periodWord}`}
                        data={chartData}
                        lines={ACTIVITY_LINES}
                    />
                </div>

                <div className="mb-5">
                    <CumulativeChart data={chartData} />
                </div>

                <div className="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-5">
                    <ActivationFunnel steps={funnel} />
                    <SupplyHealth supply={supply} />
                </div>

                <div className="mb-5">
                    <RevenueSummary revenue={revenue} />
                </div>

                <UnmetDemand rows={unmet_demand} />
            </AdminLayout>
        </>
    );
}
