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
