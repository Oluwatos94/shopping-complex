import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
    ConfirmedOrigin,
    SearchOrigin,
    clearConfirmedOrigin,
    readConfirmedOrigin,
    requestPosition,
    saveConfirmedOrigin,
    subscribeToConfirmedOrigin,
    toDeviceOrigin,
    toSearchOrigin,
} from '@/utils/geolocation';

export function useSearchOrigin(initialDeviceOrigin: SearchOrigin | null = null) {
    const [confirmed, setConfirmed] = useState<ConfirmedOrigin | null>(() => readConfirmedOrigin());
    const [deviceOrigin, setDeviceOrigin] = useState<SearchOrigin | null>(initialDeviceOrigin);

    useEffect(() => subscribeToConfirmedOrigin(setConfirmed), []);

    const origin = useMemo<SearchOrigin | null>(
        () => (confirmed !== null ? toSearchOrigin(confirmed) : deviceOrigin),
        [confirmed, deviceOrigin],
    );

    const originRef = useRef<SearchOrigin | null>(origin);

    useEffect(() => {
        originRef.current = origin;
    }, [origin]);

    const selection = useRef(0);

    const locateDevice = useCallback(async (): Promise<SearchOrigin | null> => {
        const generation = ++selection.current;
        const next = toDeviceOrigin(await requestPosition());

        if (generation !== selection.current) return null;

        originRef.current = next;
        clearConfirmedOrigin();
        setDeviceOrigin(next);

        return next;
    }, []);

    const confirm = useCallback((latitude: number, longitude: number, label?: string): SearchOrigin => {
        selection.current += 1;

        const next = toSearchOrigin(saveConfirmedOrigin(latitude, longitude, label));
        originRef.current = next;

        return next;
    }, []);

    const clear = useCallback(() => {
        selection.current += 1;

        originRef.current = null;
        clearConfirmedOrigin();
        setDeviceOrigin(null);
    }, []);

    const adoptDeviceOrigin = useCallback((next: SearchOrigin | null) => {
        setDeviceOrigin(next);
    }, []);

    return {
        origin,
        originRef,
        locateDevice,
        confirm,
        clear,
        adoptDeviceOrigin,
    };
}
