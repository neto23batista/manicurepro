/* Fernanda Silva Nails — Service Worker (PWA) */

const CACHE_VERSION = 'fernanda-nails-v1';
const STATIC_CACHE = [
    '/',
    '/offline.html',
    '/manifest.json',
];

// Install — cacheia o mínimo crítico (assets buildados são cacheados sob demanda)
self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then(cache => cache.addAll(STATIC_CACHE))
            .catch(() => null)
    );
    self.skipWaiting();
});

// Activate — limpa caches de versões anteriores
self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_VERSION).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Fetch — network-first para HTML; cache-first para assets buildados (/build/*)
self.addEventListener('fetch', function (event) {
    const req = event.request;
    if (req.method !== 'GET') return;
    if (req.url.includes('/api/')) return;

    const url = new URL(req.url);
    const isBuildAsset = url.pathname.startsWith('/build/') || url.pathname.startsWith('/images/');
    const isHTML = req.headers.get('accept')?.includes('text/html');

    if (isBuildAsset) {
        // Cache-first: assets com hash são imutáveis
        event.respondWith(
            caches.match(req).then(cached => cached || fetch(req).then(resp => {
                if (resp && resp.status === 200) {
                    const clone = resp.clone();
                    caches.open(CACHE_VERSION).then(c => c.put(req, clone));
                }
                return resp;
            }))
        );
        return;
    }

    if (isHTML) {
        // Network-first com fallback offline
        event.respondWith(
            fetch(req)
                .then(resp => {
                    const clone = resp.clone();
                    caches.open(CACHE_VERSION).then(c => c.put(req, clone));
                    return resp;
                })
                .catch(() => caches.match(req).then(c => c || caches.match('/offline.html')))
        );
        return;
    }

    // Outros recursos: cache-first com fallback de rede
    event.respondWith(
        caches.match(req).then(cached => cached || fetch(req))
    );
});
