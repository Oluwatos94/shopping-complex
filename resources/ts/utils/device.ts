interface NavigatorUAData {
    mobile?: boolean;
}

declare global {
    interface Navigator {
        userAgentData?: NavigatorUAData;
    }
}

/**
 * A phone answers getCurrentPosition from its GPS radio; a laptop answers from a WiFi
 * lookup that can land kilometres away while claiming metres of confidence. So the
 * device fix is offered on phones and withheld everywhere else.
 *
 * Guessing wrong is safe in both directions: a phone read as a laptop just types its
 * area, and a laptop read as a phone still meets the DistanceLabel accuracy guard.
 */
export function hasSatelliteFix(): boolean {
    if (typeof window === 'undefined') return false;

    const mobile = navigator.userAgentData?.mobile;
    if (typeof mobile === 'boolean') return mobile;

    return window.matchMedia('(pointer: coarse) and (hover: none)').matches;
}
