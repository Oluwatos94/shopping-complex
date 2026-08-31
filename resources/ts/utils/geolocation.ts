/**
 * Beyond this much uncertainty a fix cannot support a distance claim at all.
 * Mirrors DistanceLabel::USABLE_METERS, which does the actual phrasing server-side.
 */
export const USABLE_ACCURACY_METERS = 10000;

export function isUsableAccuracy(accuracyMeters?: number | null): boolean {
    return accuracyMeters == null || accuracyMeters <= USABLE_ACCURACY_METERS;
}

export interface DevicePosition {
    latitude: number;
    longitude: number;
    accuracy: number;
    timestamp: number;
}

export function requestPosition(): Promise<DevicePosition> {
    if (!navigator.geolocation) {
        return Promise.reject(new Error('Geolocation is not supported by this browser.'));
    }

    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(
            (position) =>
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: position.timestamp,
                }),
            reject,
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 },
        );
    });
}

export interface ConfirmedOrigin {
    latitude: number;
    longitude: number;
    label?: string;
    confirmedAt: number;
}

export type OriginSource = 'confirmed' | 'device';

export interface SearchOrigin {
    latitude: number;
    longitude: number;
    accuracy: number | null;
    label?: string;
    source: OriginSource;
}

const STORAGE_KEY = 'jiidaa.confirmed-origin';

type OriginListener = (origin: ConfirmedOrigin | null) => void;

const listeners = new Set<OriginListener>();

let cached: ConfirmedOrigin | null | undefined;

function parseOrigin(raw: string): ConfirmedOrigin | null {
    try {
        const parsed: unknown = JSON.parse(raw);
        if (typeof parsed !== 'object' || parsed === null) return null;

        const { latitude, longitude, label, confirmedAt } = parsed as Record<string, unknown>;
        if (typeof latitude !== 'number' || !Number.isFinite(latitude)) return null;
        if (typeof longitude !== 'number' || !Number.isFinite(longitude)) return null;

        return {
            latitude,
            longitude,
            ...(typeof label === 'string' && label !== '' ? { label } : {}),
            confirmedAt: typeof confirmedAt === 'number' ? confirmedAt : Date.now(),
        };
    } catch {
        return null;
    }
}

export function readConfirmedOrigin(): ConfirmedOrigin | null {
    if (cached !== undefined) return cached;
    if (typeof window === 'undefined') return null;

    try {
        const raw = window.sessionStorage.getItem(STORAGE_KEY);
        cached = raw === null ? null : parseOrigin(raw);
    } catch {
        cached = null;
    }

    return cached;
}

function publish(origin: ConfirmedOrigin | null): void {
    cached = origin;
    listeners.forEach((listener) => listener(origin));
}

export function saveConfirmedOrigin(latitude: number, longitude: number, label?: string): ConfirmedOrigin {
    const origin: ConfirmedOrigin = {
        latitude,
        longitude,
        ...(label !== undefined && label !== '' ? { label } : {}),
        confirmedAt: Date.now(),
    };

    try {
        window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(origin));
    } catch { /* storage unavailable; the cache still holds it */ }

    publish(origin);

    return origin;
}

export function clearConfirmedOrigin(): void {
    try {
        window.sessionStorage.removeItem(STORAGE_KEY);
    } catch { /* storage unavailable */ }

    publish(null);
}

export function subscribeToConfirmedOrigin(listener: OriginListener): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}

export function toSearchOrigin(origin: ConfirmedOrigin): SearchOrigin {
    return {
        latitude: origin.latitude,
        longitude: origin.longitude,
        accuracy: null,
        ...(origin.label !== undefined ? { label: origin.label } : {}),
        source: 'confirmed',
    };
}

/** Keeps its accuracy, so DistanceLabel can still hedge a rough fix. */
export function toDeviceOrigin(position: DevicePosition): SearchOrigin {
    return {
        latitude: position.latitude,
        longitude: position.longitude,
        accuracy: position.accuracy,
        source: 'device',
    };
}

/** A confirmed origin sends no accuracy, which the server reads as a trusted fix. */
export function originQueryParams(origin: SearchOrigin | null): {
    latitude?: number;
    longitude?: number;
    accuracy?: number;
} {
    if (origin === null) return {};

    return {
        latitude: origin.latitude,
        longitude: origin.longitude,
        ...(origin.accuracy === null ? {} : { accuracy: origin.accuracy }),
    };
}

const COORDINATE_EPSILON = 1e-6;

export function isSameCoordinate(
    a: { latitude: number; longitude: number } | null,
    b: { latitude: number; longitude: number } | null,
): boolean {
    if (a === null || b === null) return a === b;

    return (
        Math.abs(a.latitude - b.latitude) < COORDINATE_EPSILON
        && Math.abs(a.longitude - b.longitude) < COORDINATE_EPSILON
    );
}

export function formatCoordinates(point: { latitude: number; longitude: number }): string {
    return `${point.latitude.toFixed(4)}, ${point.longitude.toFixed(4)}`;
}

export function describeOrigin(origin: SearchOrigin): string {
    if (origin.label !== undefined && origin.label !== '') return origin.label;

    return formatCoordinates(origin);
}
