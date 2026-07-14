import Echo from 'laravel-echo';
import Pusher from 'pusher-js';


window.Pusher = Pusher;


window.Echo = new Echo({

    broadcaster: 'reverb',

    key: import.meta.env.VITE_REVERB_APP_KEY,

    wsHost: import.meta.env.VITE_REVERB_HOST,

    wsPort: Number(
        import.meta.env.VITE_REVERB_PORT
    ),

    wssPort: Number(
        import.meta.env.VITE_REVERB_PORT
    ),

    forceTLS: false,

    enabledTransports: [
        'ws'
    ],

});


export function subscribeTripRealtime(
    tripId,
    callback
) {

    return window.Echo

        .channel(
            `trip.${tripId}`
        )

        .listen(
            '.trip.position.updated',
            callback
        );

}