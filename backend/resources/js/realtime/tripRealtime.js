import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

let echo = null;
let activeTripId = null;

function normalizeTripId(tripId) {
    const normalizedTripId = Number.parseInt(
        String(tripId),
        10
    );

    if (
        !Number.isInteger(normalizedTripId) ||
        normalizedTripId <= 0
    ) {
        throw new Error(
            'Trip ID must be a positive integer.'
        );
    }

    return normalizedTripId;
}

export function connectRealtime(token) {
    if (
        typeof token !== 'string' ||
        token.trim() === ''
    ) {
        throw new Error(
            'Bearer token is required for realtime connection.'
        );
    }

    disconnectRealtime();

    const scheme =
        import.meta.env.VITE_REVERB_SCHEME ?? 'http';

    const port = Number(
        import.meta.env.VITE_REVERB_PORT ?? 8080
    );

    echo = new Echo({
        broadcaster: 'reverb',

        key: import.meta.env.VITE_REVERB_APP_KEY,

        wsHost:
            import.meta.env.VITE_REVERB_HOST ??
            window.location.hostname,

        wsPort: port,
        wssPort: port,

        forceTLS: scheme === 'https',

        enabledTransports: [
            'ws',
            'wss',
        ],

        authEndpoint: '/broadcasting/auth',

        auth: {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        },
    });

    window.Echo = echo;

    return echo;
}

export function subscribeTripRealtime(
    tripId,
    callback
) {
    if (!echo) {
        throw new Error(
            'Realtime client is not authenticated.'
        );
    }

    if (typeof callback !== 'function') {
        throw new Error(
            'Realtime callback must be a function.'
        );
    }

    const normalizedTripId = normalizeTripId(
        tripId
    );

    leaveTripRealtime();

    activeTripId = normalizedTripId;

    return echo
        .private(
            `trip.${normalizedTripId}`
        )
        .listen(
            '.trip.position.updated',
            callback
        );
}

export function leaveTripRealtime() {
    if (
        !echo ||
        activeTripId === null
    ) {
        return;
    }

    echo.leave(
        `trip.${activeTripId}`
    );

    activeTripId = null;
}

export function disconnectRealtime() {
    if (!echo) {
        activeTripId = null;
        return;
    }

    leaveTripRealtime();

    echo.disconnect();

    echo = null;

    if (window.Echo) {
        delete window.Echo;
    }
}
