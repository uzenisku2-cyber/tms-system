import {
    connectRealtime,
    disconnectRealtime,
    leaveTripRealtime,
    subscribeTripRealtime,
} from './realtime/tripRealtime';

function setConnectionStatus(message) {
    const status = document.getElementById(
        'status'
    );

    if (status) {
        status.textContent = message;
    }
}

function bindConnectionStatus(echo) {
    const connection =
        echo.connector.pusher.connection;

    connection.bind(
        'connected',
        () => {
            setConnectionStatus(
                'CONNECTED - AUTHENTICATED'
            );
        }
    );

    connection.bind(
        'error',
        (error) => {

            setConnectionStatus(
                'DISCONNECTED'
            );
        }
    );

    connection.bind(
        'disconnected',
        () => {
            setConnectionStatus(
                'DISCONNECTED'
            );
        }
    );

    if (connection.state === 'connected') {
        setConnectionStatus(
            'CONNECTED - AUTHENTICATED'
        );
    }
}

window.TmsRealtime = Object.freeze({
    connect(token) {
        setConnectionStatus(
            'CONNECTING - AUTHENTICATING'
        );

        const echo = connectRealtime(
            token
        );

        bindConnectionStatus(
            echo
        );

        return echo;
    },

    subscribeTrip(tripId, callback) {
        return subscribeTripRealtime(
            tripId,
            callback
        );
    },

    leaveTrip() {
        leaveTripRealtime();
    },

    disconnect() {
        disconnectRealtime();

        setConnectionStatus(
            'AUTHENTICATION REQUIRED'
        );
    },
});
