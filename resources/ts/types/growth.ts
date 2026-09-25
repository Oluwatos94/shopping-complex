export type GrowthGranularity = 'week' | 'month';

export interface GrowthSeriesRow {
    key: string;
    label: string;
    is_partial: boolean;
    new_vendors: number;
    new_customers: number;
    new_products: number;
    searches: number;
    contacts: number;
    no_results: number;
    new_paid_subscriptions: number;
    active_vendors: number;
    total_vendors: number;
    total_products: number;
}

export interface GrowthHeadlineMetric {
    value: number;
    previous: number;
    change_pct: number | null;
    last_complete: number;
}

export interface GrowthHeadline {
    current_range: string;
    comparison_range: string;
    metrics: Record<string, GrowthHeadlineMetric>;
}

export interface GrowthFunnelStep {
    label: string;
    value: number;
    pct_of_registered: number;
}

export interface GrowthSupplyHealth {
    registered_vendors: number;
    active_last_30_days: number;
    active_prior_30_days: number;
    ever_contacted: number;
    never_contacted: number;
}

export interface GrowthRevenueSummary {
    paying_vendors: number;
    collected_this_month: number;
    average_per_vendor: number;
    lifetime_collected: number;
}

export interface UnmetDemandRow {
    query: string;
    total: number;
}

export interface GrowthPageProps {
    granularity: GrowthGranularity;
    range: { start: string; end: string };
    series: GrowthSeriesRow[];
    headline: GrowthHeadline;
    funnel: GrowthFunnelStep[];
    supply: GrowthSupplyHealth;
    revenue: GrowthRevenueSummary;
    unmet_demand: UnmetDemandRow[];
}
