/**
 * Copies text, returning whether it worked.
 *
 * `navigator.clipboard` is undefined outside a secure context (plain HTTP, a LAN
 * IP, some in-app WebViews), so the deprecated `execCommand` path is kept as the
 * fallback rather than letting the copy silently fail there.
 */
export async function copyText(value: string): Promise<boolean> {
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(value);
            return true;
        }
    } catch {
        // fall through to the legacy path
    }

    try {
        const textarea = document.createElement('textarea');
        textarea.value = value;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(textarea);
        return ok;
    } catch {
        return false;
    }
}
