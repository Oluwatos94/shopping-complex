
export interface PlaceSuggestion {
    placeId: string;
    description: string;
}

export interface PlaceLocation {
    latitude: number;
    longitude: number;
    label: string;
}

interface AutocompleteResponse {
    suggestions?: { place_id?: unknown; description?: unknown }[];
}

interface PlaceDetailsResponse {
    lat?: unknown;
    lng?: unknown;
    formatted?: unknown;
    street?: unknown;
    city?: unknown;
}

export function newSessionToken(): string {
    return crypto.randomUUID();
}

export async function suggestPlaces(
    query: string,
    sessionToken: string,
    signal?: AbortSignal,
): Promise<PlaceSuggestion[]> {
    const trimmed = query.trim();
    if (trimmed.length < 3) return [];

    const response = await fetch(
        `/api/geo/autocomplete?q=${encodeURIComponent(trimmed)}&session=${encodeURIComponent(sessionToken)}`,
        { headers: { Accept: 'application/json' }, signal: signal ?? null },
    );

    if (!response.ok) throw new Error('Place search is unavailable.');

    const data: AutocompleteResponse = await response.json();

    return (data.suggestions ?? []).flatMap((suggestion) => {
        const placeId = suggestion.place_id;
        const description = suggestion.description;
        if (typeof placeId !== 'string' || placeId === '') return [];

        return [{ placeId, description: typeof description === 'string' ? description : placeId }];
    });
}

export async function resolvePlace(placeId: string, sessionToken: string): Promise<PlaceLocation | null> {
    try {
        const response = await fetch(
            `/api/geo/place?place_id=${encodeURIComponent(placeId)}&session=${encodeURIComponent(sessionToken)}`,
            { headers: { Accept: 'application/json' } },
        );

        if (!response.ok) return null;

        const data: PlaceDetailsResponse = await response.json();
        if (typeof data.lat !== 'number' || typeof data.lng !== 'number') return null;

        const label = [data.street, data.city].find((part) => typeof part === 'string' && part !== '')
            ?? data.formatted;

        return {
            latitude: data.lat,
            longitude: data.lng,
            label: typeof label === 'string' ? label : '',
        };
    } catch {
        return null;
    }
}

/** Names a dragged pin. Returns null when the lookup fails; coordinates then stand in. */
export async function describeCoordinates(latitude: number, longitude: number): Promise<string | null> {
    try {
        const response = await fetch(`/api/geo/reverse?lat=${latitude}&lng=${longitude}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) return null;

        const data: { label?: unknown } = await response.json();

        return typeof data.label === 'string' && data.label !== '' ? data.label : null;
    } catch {
        return null;
    }
}
