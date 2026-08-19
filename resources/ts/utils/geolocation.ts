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
