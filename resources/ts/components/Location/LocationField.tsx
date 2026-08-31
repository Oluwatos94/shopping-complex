import { KeyboardEvent as ReactKeyboardEvent, useCallback, useEffect, useId, useMemo, useRef, useState } from 'react';
import { PlaceSuggestion, describeCoordinates, newSessionToken, resolvePlace, suggestPlaces } from '@/utils/geocoding';
import { SearchOrigin, describeOrigin, isUsableAccuracy } from '@/utils/geolocation';
import { hasSatelliteFix } from '@/utils/device';

const DEBOUNCE_MS = 350;

const MIN_QUERY = 3;

interface LocationFieldProps {
    origin: SearchOrigin | null;
    onConfirm: (latitude: number, longitude: number, label?: string) => void;
    /** Offered on phones only; may reject, which this field reports. */
    onUseDevice?: () => Promise<unknown>;
    onClear?: () => void;
    size?: 'sm' | 'md';
    menuPlacement?: 'top' | 'bottom';
    className?: string;
}

export default function LocationField({
    origin,
    onConfirm,
    onUseDevice,
    onClear,
    size = 'md',
    menuPlacement = 'bottom',
    className = '',
}: LocationFieldProps) {
    const confirmedLabel = origin !== null && origin.source === 'confirmed' ? describeOrigin(origin) : '';

    const [deviceLabel, setDeviceLabel] = useState('');
    const [query, setQuery] = useState(confirmedLabel);
    const [suggestions, setSuggestions] = useState<PlaceSuggestion[]>([]);
    const [activeIndex, setActiveIndex] = useState(-1);
    const [isOpen, setIsOpen] = useState(false);
    const [isBusy, setIsBusy] = useState(false);
    const [isLocating, setIsLocating] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [foundNothing, setFoundNothing] = useState(false);
    const [canUseDevice, setCanUseDevice] = useState(false);

    const listboxId = `location-suggestions-${useId()}`;
    const optionId = (index: number) => `${listboxId}-option-${index}`;

    const field = useRef<HTMLInputElement>(null);
    const isEditingRef = useRef(false);
    const sessionTokenRef = useRef(newSessionToken());
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        setCanUseDevice(hasSatelliteFix());
    }, []);

    // Naming a device fix is what lets a buyer notice it landed in the wrong town.
    const deviceKey = origin === null || origin.source !== 'device'
        ? null
        : `${origin.latitude},${origin.longitude}`;

    useEffect(() => {
        if (deviceKey === null) {
            setDeviceLabel('');

            return;
        }

        const [latitude, longitude] = deviceKey.split(',').map(Number) as [number, number];
        let cancelled = false;

        describeCoordinates(latitude, longitude).then((label) => {
            if (!cancelled) setDeviceLabel(label ?? '');
        });

        return () => {
            cancelled = true;
        };
    }, [deviceKey]);

    const activeLabel = confirmedLabel !== '' ? confirmedLabel : deviceLabel;

    useEffect(() => {
        if (isEditingRef.current) return;

        setQuery(activeLabel);
    }, [activeLabel]);

    useEffect(() => () => {
        if (debounceRef.current !== null) clearTimeout(debounceRef.current);
        abortRef.current?.abort();
    }, []);

    const handleChange = useCallback((value: string) => {
        isEditingRef.current = true;
        setQuery(value);
        setError(null);
        setFoundNothing(false);
        setActiveIndex(-1);
        setIsOpen(true);

        if (debounceRef.current !== null) clearTimeout(debounceRef.current);

        if (value.trim().length < MIN_QUERY) {
            setSuggestions([]);

            return;
        }

        debounceRef.current = setTimeout(() => {
            abortRef.current?.abort();
            const controller = new AbortController();
            abortRef.current = controller;

            setIsBusy(true);

            suggestPlaces(value, sessionTokenRef.current, controller.signal)
                .then((found) => {
                    if (controller.signal.aborted) return;

                    setSuggestions(found);
                    setFoundNothing(found.length === 0);
                })
                .catch((problem: unknown) => {
                    if (controller.signal.aborted) return;
                    if (problem instanceof DOMException && problem.name === 'AbortError') return;

                    setSuggestions([]);
                    setError('Place search is unavailable right now.');
                })
                .finally(() => {
                    if (!controller.signal.aborted) setIsBusy(false);
                });
        }, DEBOUNCE_MS);
    }, []);

    const choose = useCallback(async (suggestion: PlaceSuggestion) => {
        setQuery(suggestion.description);
        setSuggestions([]);
        setIsOpen(false);
        setIsBusy(true);

        try {
            const place = await resolvePlace(suggestion.placeId, sessionTokenRef.current);

            if (place === null) {
                setError('Could not load that place. Try another.');
                setIsOpen(true);

                return;
            }

            const label = place.label === '' ? suggestion.description : place.label;
            isEditingRef.current = false;
            setQuery(label);
            field.current?.blur();
            onConfirm(place.latitude, place.longitude, label);
        } finally {
            // Selection closes the billing session.
            sessionTokenRef.current = newSessionToken();
            setIsBusy(false);
        }
    }, [onConfirm]);

    const useDevice = useCallback(async () => {
        if (onUseDevice === undefined) return;

        setError(null);
        setIsLocating(true);

        try {
            await onUseDevice();
            isEditingRef.current = false;
            setSuggestions([]);
            setFoundNothing(false);
            setIsOpen(false);
            field.current?.blur();
        } catch {
            setError('Could not get your location. Search for your area instead.');
            setIsOpen(true);
        } finally {
            setIsLocating(false);
        }
    }, [onUseDevice]);

    const showDeviceRow = canUseDevice && onUseDevice !== undefined;

    const items = useMemo(
        () => [
            ...(showDeviceRow ? [{ kind: 'device' as const }] : []),
            ...suggestions.map((suggestion) => ({ kind: 'place' as const, suggestion })),
        ],
        [showDeviceRow, suggestions],
    );

    const handleKeyDown = useCallback((event: ReactKeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Escape') {

            if (isOpen) event.nativeEvent.stopImmediatePropagation();

            isEditingRef.current = false;
            setIsOpen(false);
            setQuery(activeLabel);

            return;
        }

        if (items.length === 0) return;

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setIsOpen(true);
            setActiveIndex((current) => (current + 1) % items.length);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setIsOpen(true);
            setActiveIndex((current) => (current <= 0 ? items.length - 1 : current - 1));
        } else if (event.key === 'Enter') {
            const picked = activeIndex === -1
                ? items.find((item) => item.kind === 'place')
                : items[activeIndex];

            if (picked === undefined) return;

            event.preventDefault();
            void (picked.kind === 'device' ? useDevice() : choose(picked.suggestion));
        }
    }, [activeIndex, activeLabel, choose, isOpen, items, useDevice]);

    const handleClear = useCallback(() => {
        isEditingRef.current = false;
        setQuery('');
        setSuggestions([]);
        setError(null);
        setFoundNothing(false);
        setIsOpen(false);
        onClear?.();
        field.current?.focus();
    }, [onClear]);

    const isCoarse = origin !== null
        && origin.source === 'device'
        && origin.accuracy !== null
        && !isUsableAccuracy(origin.accuracy);

    const pinTone = confirmedLabel !== ''
        ? 'text-brand-green'
        : isCoarse
          ? 'text-amber-500'
          : 'text-brand-muted';

    const small = size === 'sm';
    const showStatusRow = suggestions.length === 0 && (isBusy || foundNothing || error !== null);
    const showMenu = isOpen && (items.length > 0 || showStatusRow);

    return (
        <div
            className={`relative min-w-0 ${className}`}
            onBlur={(event) => {
                if (!event.currentTarget.contains(event.relatedTarget)) setIsOpen(false);
            }}
        >
            <svg
                className={`pointer-events-none absolute top-1/2 -translate-y-1/2 ${small ? 'left-3 h-4 w-4' : 'left-4 h-[18px] w-[18px]'} ${pinTone}`}
                viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round"
            >
                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                <circle cx="12" cy="10" r="2.6" />
            </svg>

            <input
                ref={field}
                type="text"
                value={query}
                onChange={(e) => handleChange(e.target.value)}
                onFocus={(e) => { setIsOpen(true); e.target.select(); }}
                onKeyDown={handleKeyDown}
                placeholder={isCoarse ? 'Set your exact location' : 'Where are you?'}
                aria-label="Your location"
                autoComplete="off"
                role="combobox"
                aria-expanded={showMenu}
                aria-autocomplete="list"
                aria-controls={listboxId}
                aria-activedescendant={activeIndex === -1 ? undefined : optionId(activeIndex)}
                className={`w-full rounded-xl bg-transparent outline-none placeholder:text-brand-muted focus-visible:ring-2 focus-visible:ring-brand-green/25 ${
                    small ? 'h-9 pl-9 pr-8 text-xs' : 'h-12 pl-11 pr-9 text-[15px]'
                } ${
                    // A guessed place reads muted; one the buyer picked reads solid.
                    confirmedLabel === '' && deviceLabel !== '' ? 'text-brand-muted' : 'text-brand-ink'
                }`}
            />

            {isBusy || isLocating ? (
                <svg className={`absolute top-1/2 -translate-y-1/2 animate-spin text-brand-muted ${small ? 'right-2.5 h-3.5 w-3.5' : 'right-3 h-4 w-4'}`} fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            ) : query !== '' && onClear !== undefined && (
                <button
                    type="button"
                    onMouseDown={(e) => e.preventDefault()}
                    onClick={handleClear}
                    aria-label="Clear your location"
                    className={`absolute top-1/2 -translate-y-1/2 rounded-full p-1 text-brand-muted transition hover:bg-brand-surface hover:text-brand-ink ${small ? 'right-1.5' : 'right-2'}`}
                >
                    <svg className={small ? 'h-3 w-3' : 'h-3.5 w-3.5'} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5} strokeLinecap="round">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            )}

            {showMenu && (
                <ul
                    id={listboxId}
                    role="listbox"
                    className={`absolute inset-x-0 z-30 max-h-64 overflow-y-auto rounded-xl border border-brand-line bg-white py-1 shadow-lg ${
                        menuPlacement === 'top' ? 'bottom-full mb-1' : 'top-full mt-1'
                    }`}
                >
                    {items.map((item, index) => (
                        <li
                            key={item.kind === 'device' ? 'device' : item.suggestion.placeId}
                            id={optionId(index)}
                            role="option"
                            aria-selected={index === activeIndex}
                        >
                            <button
                                type="button"
                                onMouseDown={(e) => e.preventDefault()}
                                onClick={() => (item.kind === 'device' ? useDevice() : choose(item.suggestion))}
                                onMouseEnter={() => setActiveIndex(index)}
                                disabled={item.kind === 'device' && isLocating}
                                className={`flex w-full items-center gap-2.5 px-4 py-2.5 text-left leading-snug transition disabled:opacity-60 ${
                                    small ? 'text-xs' : 'text-sm'
                                } ${index === activeIndex ? 'bg-brand-surface' : ''} ${
                                    item.kind === 'device' ? 'font-semibold text-brand-green-dark' : 'text-brand-ink'
                                }`}
                            >
                                {item.kind === 'device' ? (
                                    <>
                                        <svg className={small ? 'h-3.5 w-3.5' : 'h-4 w-4'} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                                            <circle cx="12" cy="12" r="7" />
                                            <path d="M12 2v3M12 19v3M2 12h3M19 12h3" />
                                        </svg>
                                        {isLocating ? 'Locating…' : 'Use my current location'}
                                    </>
                                ) : (
                                    item.suggestion.description
                                )}
                            </button>
                        </li>
                    ))}

                    {showStatusRow && (
                        <li className={`px-4 py-2.5 text-brand-muted ${small ? 'text-xs' : 'text-sm'}`}>
                            {error ?? (isBusy ? 'Searching…' : 'No places match that. Try a nearby street or landmark.')}
                        </li>
                    )}
                </ul>
            )}
        </div>
    );
}
