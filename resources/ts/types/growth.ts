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
    in_progress: number;
}

export interface GrowthHeadline {
    period_label: string | null;
    in_progress_label: string | null;
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
    monthly_recurring: number;
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
