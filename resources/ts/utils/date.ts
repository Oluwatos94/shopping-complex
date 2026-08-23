const DATE_FORMAT: Intl.DateTimeFormatOptions = {
    month: 'short', day: 'numeric', year: 'numeric',
};

export function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('en-US', DATE_FORMAT);
}

export function formatTime(iso: string): string {
    return new Date(iso).toLocaleTimeString([], {
        hour: 'numeric',
        minute: '2-digit',
    });
}

export function formatDateOnly(value: string): string {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);

    if (!match) {
        return formatDate(value);
    }

    const [, year, month, day] = match;
    const date = new Date(Number(year), Number(month) - 1, Number(day));

    if (date.getMonth() !== Number(month) - 1 || date.getDate() !== Number(day)) {
        return formatDate(value);
    }

    return date.toLocaleDateString('en-US', DATE_FORMAT);
}
