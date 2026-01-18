import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to real-time build robust real-time web applications.
 */

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

try {
    console.log('🔧 Initializing Echo with Pusher...');
    console.log('Pusher Key:', import.meta.env.VITE_PUSHER_APP_KEY);
    console.log('Pusher Cluster:', import.meta.env.VITE_PUSHER_APP_CLUSTER);

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'ap1',
        forceTLS: true,
        encrypted: true,
        // Enable for debugging
        // enabledTransports: ['ws', 'wss'],
        // logToConsole: true,
    });

    console.log('✅ Echo initialized successfully!', window.Echo);
} catch (error) {
    console.error('❌ Error initializing Echo:', error);
}
