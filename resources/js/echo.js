import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const key = import.meta.env.VITE_PUSHER_APP_KEY;
const wsHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const wsPort = Number(import.meta.env.VITE_REVERB_PORT || 80);
const scheme = import.meta.env.VITE_REVERB_SCHEME || 'http';
const forceTLS = scheme === 'https';

if (key) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost,
        wsPort,
        wssPort: wsPort,
        forceTLS,
        enabledTransports: ['ws', 'wss'],
    });
}