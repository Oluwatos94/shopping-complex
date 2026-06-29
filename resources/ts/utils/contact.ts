import { getCsrfToken } from './csrf';

/**
 * Record that the current user contacted a vendor via an on-platform contact
 * button (e.g. the WhatsApp "Message" links). Fire-and-forget: it only succeeds
 * for authenticated users and never blocks opening the WhatsApp link.
 */
export function recordVendorContact(vendorSlug: string | undefined | null): void {
    if (!vendorSlug) {
        return;
    }

    void fetch(`/vendors/${vendorSlug}/contact`, {
        method: 'POST',
        headers: {
            'X-XSRF-TOKEN': getCsrfToken(),
            Accept: 'application/json',
        },
        keepalive: true,
    }).catch(() => {
        // ignore — contact tracking is best-effort
    });
}
