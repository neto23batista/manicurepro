/* Fernanda Silva Nails — Service Worker (PWA) */

const CACHE_VERSION = 'fernanda-nails-v2';
const PRECACHE_URLS = [
    '/',
    '/offline.html',
    '/manifest.json',
    '/images/favicon.png',
    '/images/icon-192.png',
    '/images/apple-touch-icon.png',
];

function sameOrigin(url) {
    return url.origin === self.location.origin;
}

function isApiRequest(url) {
    return url.pathname.startsWith('/api/') || url.pathname.startsWith('/sanctum/');
}

/** Vite hashed bundles — immutable, safe for cache-first */
function isBuildAsset(url) {
    return url.pathname.startsWith('/build/');
}

/** CSS / JS / fonts (incl. public/js, public/css e assets com extensão) */
function isStyleScriptOrFont(request, url) {
    const dest = request.destination;
    if (dest === 'style' || dest === 'script' || dest === 'font' || dest === 'worker') {
        return true;
    }
    if (url.pathname.startsWith('/css/') || url.pathname.startsWith('/js/')) {
        return true;
    }
    return /\.(?:css|js|mjs|cjs|map|woff2?|ttf|otf|eot)(?:$|\?)/i.test(url.pathname);
}

function isImageAsset(request, url) {
    if (request.destination === 'image') return true;
    if (url.pathname.startsWith('/images/')) return true;
    return /\.(?:png|jpe?g|gif|webp|svg|ico|avif)(?:$|\?)/i.test(url.pathname);
}

function isHTMLRequest(request) {
    if (request.mode === 'navigate') return true;
    const accept = request.headers.get('accept') || '';
    return accept.includes('text/html');
}

function putInCache(request, response) {
    if (!response || response.status !== 200 || response.type === 'opaque') {
        return;
    }
    const clone = response.clone();
    caches.open(CACHE_VERSION).then(function (cache) {
        cache.put(request, clone);
    }).catch(function () {});
}

/** Cache-first: bom para /build/* com hash no nome do ficheiro */
function cacheFirst(request) {
    return caches.match(request).then(function (cached) {
        if (cached) return cached;
        return fetch(request).then(function (response) {
            putInCache(request, response);
            return response;
        });
    });
}

/**
 * Stale-while-revalidate: serve CSS/JS em cache de imediato e atualiza em background.
 * Se não houver cache, espera a rede; se a rede falhar, tenta o cache de novo.
 */
function staleWhileRevalidate(request) {
    return caches.open(CACHE_VERSION).then(function (cache) {
        return cache.match(request).then(function (cached) {
            const networkFetch = fetch(request).then(function (response) {
                putInCache(request, response);
                return response;
            }).catch(function () {
                return cached || Response.error();
            });

            return cached || networkFetch;
        });
    });
}

/** Network-first com fallback offline para navegações HTML */
function networkFirstHtml(request) {
    return fetch(request)
        .then(function (response) {
            putInCache(request, response);
            return response;
        })
        .catch(function () {
            return caches.match(request).then(function (cached) {
                return cached || caches.match('/offline.html');
            });
        });
}

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then(function (cache) {
                return cache.addAll(PRECACHE_URLS);
            })
            .catch(function () {
                return null;
            })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys
                    .filter(function (key) { return key !== CACHE_VERSION; })
                    .map(function (key) { return caches.delete(key); })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', function (event) {
    const request = event.request;
    if (request.method !== 'GET') return;

    let url;
    try {
        url = new URL(request.url);
    } catch (e) {
        return;
    }

    // Deixa o browser tratar cross-origin (CDNs alinhados à CSP) sem interceptar
    if (!sameOrigin(url)) return;
    if (isApiRequest(url)) return;

    if (isBuildAsset(url)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    if (isStyleScriptOrFont(request, url)) {
        event.respondWith(staleWhileRevalidate(request));
        return;
    }

    if (isImageAsset(request, url)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    if (isHTMLRequest(request)) {
        event.respondWith(networkFirstHtml(request));
        return;
    }

    event.respondWith(
        caches.match(request).then(function (cached) {
            return cached || fetch(request).then(function (response) {
                putInCache(request, response);
                return response;
            });
        })
    );
});
