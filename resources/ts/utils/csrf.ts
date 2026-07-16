/**
 * Read the XSRF-TOKEN from cookies and return it decoded, ready for use
 * as the X-XSRF-TOKEN request header in fetch() calls.
 */
export function getCsrfToken(): string {
    const raw = document.cookie
        .split('; ')
        .find((c) => c.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    return raw ? decodeURIComponent(raw) : '';
}

export async function fetchWithCsrf(url: string, options: RequestInit = {}): Promise<Response> {
    const headers: Record<string, string> = {
        'X-XSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
        ...((options.headers as Record<string, string>) || {}),
    };

    if (!(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
    }

    return fetch(url, { ...options, headers });
}
