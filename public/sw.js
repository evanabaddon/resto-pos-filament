const CACHE_NAME = 'resto-pos-v1';
const urlsToCache = [
    '/kiosk',
    '/offline.html',
    '/js/face-api.min.js'
];

self.addEventListener('install', event => {
    self.skipWaiting();
    // Optional: Cache core assets 
});

self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', event => {
    // Simple network-first strategy
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request)
            .catch(() => {
                return caches.match(event.request);
            })
    );
});
