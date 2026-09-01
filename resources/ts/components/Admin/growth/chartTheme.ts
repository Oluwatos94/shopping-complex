export const OLIVE = '#86885e';
export const PEACH = '#d49f89';
export const LIGHT = '#cacfca';
export const BROWN = '#523026';

export const axisProps = {
    tick: { fontSize: 11, fill: '#9ca3af' },
    axisLine: false,
    tickLine: false,
} as const;

export const gridProps = {
    strokeDasharray: '3 3',
    stroke: '#f3f4f6',
    vertical: false,
} as const;

export const legendStyle = { fontSize: 12 } as const;

export const formatCurrency = (value: number): string =>
    new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 0 }).format(value);
