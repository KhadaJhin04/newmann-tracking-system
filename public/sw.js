const CACHE_NAME = 'newmann-tracking-cache-v2'; // Updated version
const urlsToCache = [
    '/newmann_tracking/public/',
    '/newmann_tracking/public/driver_dashboard',
    '/newmann_tracking/public/driver/scan',
    '/newmann_tracking/public/driver/update_status', // Will cache the page shell
    '/newmann_tracking/public/driver/report_anomaly', // Will cache the page shell
    '/newmann_tracking/public/assets/driver_style.css',
    '/newmann_tracking/public/assets/main.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'
];

// On install, cache the core application shell
self.addEventListener('install', event => {
    console.log('[Service Worker] Install event');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[Service Worker] Caching App Shell...');
                return cache.addAll(urlsToCache);
            })
            .then(() => self.skipWaiting())
    );
});

// On activate, clean up old caches
self.addEventListener('activate', event => {
    console.log('[Service Worker] Activate event');
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        console.log('[Service Worker] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// On fetch, use a "Stale-While-Revalidate" strategy
self.addEventListener('fetch', event => {
    // We only want to cache GET requests for our app's resources
    if (event.request.method !== 'GET' || event.request.url.includes('/api/')) {
        // For API calls and non-GET requests, always go to the network
        event.respondWith(fetch(event.request));
        return;
    }

    event.respondWith(
        caches.open(CACHE_NAME).then(cache => {
            return cache.match(event.request).then(cachedResponse => {
                const fetchedResponsePromise = fetch(event.request).then(networkResponse => {
                    if (networkResponse.ok) {
                        cache.put(event.request, networkResponse.clone());
                    }
                    return networkResponse;
                });
                // Return the cached response immediately, and update the cache in the background.
                return cachedResponse || fetchedResponsePromise;
            });
        })
    );
});