const CACHE_NAME = 'resto-pos-v1';
const ASSETS_TO_CACHE = [
    '/css/filament/filament/app.css', // Example asset
    '/js/filament/filament/app.js',   // Example asset 
    '/manifest.json',
    '/favicon.ico'
];

// Install Event: Cache critical assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE).catch(err => {
                console.warn('Failed to cache strict assets', err);
            });
        })
    );
    self.skipWaiting();
});

// Activate Event: Clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch Event: Network First for Documents, Cache First for Assets
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Ignore non-http (e.g. extension request)
    if (!url.protocol.startsWith('http')) return;

    // Ignore non-GET requests (POST, PUT, DELETE, etc.)
    if (request.method !== 'GET') return;

    // 1. Navigation Requests (HTML pages) -> Network First
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Update cache with fresh version
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    return response;
                })
                .catch(() => {
                    // Fallback to cache if offline
                    return caches.match(request);
                })
        );
        return;
    }

    // 2. Static Assets (JS, CSS, Images, Fonts) -> Cache First
    if (
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.jpeg') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.ico')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) return cachedResponse;

                return fetch(request).then((response) => {
                    // Check if we received a valid response
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }

                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    return response;
                }).catch(() => {
                    // Fallback for images
                    if (request.destination === 'image') {
                        return new Response('<svg role="img" aria-labelledby="offline-title" viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg"><title id="offline-title">Offline</title><rect width="100%" height="100%" fill="#eee"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="20" fill="#aaa">Offline</text></svg>', { headers: { 'Content-Type': 'image/svg+xml' } });
                    }
                    // Return 404 for others to avoid SW error
                    return new Response('Not found', { status: 404, statusText: 'Not Found' });
                });
            })
        );
        return;
    }

    // 3. Default -> Network First
    event.respondWith(
        fetch(request).catch(() => {
            return caches.match(request).then((cachedResponse) => {
                if (cachedResponse) return cachedResponse;
                return new Response('Offline', { status: 503, statusText: 'Offline' });
            });
        })
    );
});
