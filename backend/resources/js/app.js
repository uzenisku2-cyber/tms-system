import Echo from 'laravel-echo';
import Pusher from 'pusher-js';


window.Pusher = Pusher;


// ==============================
// Laravel Reverb Echo
// ==============================

window.Echo = new Echo({

    broadcaster: 'reverb',

    key: import.meta.env.VITE_REVERB_APP_KEY,

    wsHost: import.meta.env.VITE_REVERB_HOST,

    wsPort: Number(
        import.meta.env.VITE_REVERB_PORT ?? 8080
    ),

    wssPort: Number(
        import.meta.env.VITE_REVERB_PORT ?? 8080
    ),

    forceTLS: false,

    enabledTransports: [
        'ws'
    ],

});


console.log(
    'Echo initialized',
    window.Echo
);



// ==============================
// WebSocket connection status
// ==============================

window.Echo.connector.pusher.connection.bind(
    'connected',
    () => {

        console.log(
            'Reverb connected'
        );

    }
);


window.Echo.connector.pusher.connection.bind(
    'error',
    (error) => {

        console.error(
            'Reverb error',
            error
        );

    }
);



// ==============================
// DEBUG - všechny příchozí eventy
// ==============================

window.Echo.connector.pusher.bind_global(
    (eventName, data) => {

        console.log(
            'GLOBAL EVENT',
            eventName,
            data
        );

    }
);



// ==============================
// Trip realtime tracking
// ==============================

const tripChannel = window.Echo.channel(
    'trip.6'
);


console.log(
    'Subscribed to trip.6'
);



// Laravel Event:
// broadcastAs()
// => trip.position.updated
//
// Echo listener:
// listen('.trip.position.updated')


tripChannel.listen(
    '.trip.position.updated',
    (event) => {

        console.log(
            'REALTIME EVENT',
            event
        );


        // zde později aktualizace mapy
        // marker.setLatLng([
        //     event.latitude,
        //     event.longitude
        // ]);

    }
);