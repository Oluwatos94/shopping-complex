export function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric',
    });
}

export function formatTime(iso: string): string {
    return new Date(iso).toLocaleTimeString([], {
        hour: 'numeric',
        minute: '2-digit',
    });
}

/**
 * Formats a date-only string (YYYY-MM-DD). `new Date()` reads a bare date as UTC
 * midnight, which renders as the previous day west of GMT — so build the date
 * from its parts and let it stay local.
 */
export function formatDateOnly(value: string): string {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);

    if (!match) {
        return formatDate(value);
    }

    const [, year, month, day] = match;

    return new Date(Number(year), Number(month) - 1, Number(day)).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric',
    });
}
