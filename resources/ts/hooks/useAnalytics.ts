import { useState, useCallback } from 'react';

interface AnalyticsData {
    overview: {
        chat_contacts: number;
        profile_views: number;
        product_views: number;
        average_view_value: number;
        followers_count: number;
        active_products: number;
        period: { start_date: string; end_date: string };
    };
    chatContacts: {
        total: number;
        daily: Array<{ date: string; count: number }>;
        period: { start_date: string; end_date: string };
    };
    profileViews: {
        total: number;
        daily: Array<{ date: string; count: number }>;
        period: { start_date: string; end_date: string };
    };
    topProducts: {
        products: Array<{
            product_id: number;
            name: string;
            price: number;
            views_count: number;
        }>;
        average_view_value: number;
        period: { start_date: string; end_date: string };
    };
}

export type { AnalyticsData };

export function useAnalytics(initialData: AnalyticsData) {
    const [data, setData] = useState<AnalyticsData>(initialData);
    const [loading, setLoading] = useState(false);
    const [period, setPeriod] = useState<string>('monthly');

    const fetchAnalytics = useCallback(async (newPeriod?: string, startDate?: string, endDate?: string) => {
        setLoading(true);

        const params = new URLSearchParams();
        if (startDate && endDate) {
            params.set('start_date', startDate);
            params.set('end_date', endDate);
        } else {
            params.set('period', newPeriod || period);
        }

        try {
            const csrfToken = document.cookie
                .split('; ')
                .find((c) => c.startsWith('XSRF-TOKEN='))
                ?.split('=')[1];

            const res = await fetch(`/vendor/analytics?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': csrfToken ? decodeURIComponent(csrfToken) : '',
                },
                credentials: 'same-origin',
            });

            if (res.ok) {
                const json = await res.json();
                setData(json);
            }
        } catch {
            // Silently fail — data stays at previous state
        } finally {
            setLoading(false);
        }
    }, [period]);

    const changePeriod = useCallback((newPeriod: string) => {
        setPeriod(newPeriod);
        fetchAnalytics(newPeriod);
    }, [fetchAnalytics]);

    const changeDateRange = useCallback((startDate: string, endDate: string) => {
        fetchAnalytics(undefined, startDate, endDate);
    }, [fetchAnalytics]);

    return { data, loading, period, changePeriod, changeDateRange };
}
