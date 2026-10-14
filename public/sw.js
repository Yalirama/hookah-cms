const CACHE = 'hookah-static-v1';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', e => {
    e.waitUntil(caches.open(CACHE).then(c => c.addAll([OFFLINE_URL, '/assets/img/icons/icon-192.png'])));
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', e => {
    const req = e.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== location.origin) return;

    if (req.mode === 'navigate') {
        e.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    if (!/^\/(assets|build)\//.test(url.pathname)) return;

    if (/\.(js|css)$/.test(url.pathname)) {
        e.respondWith(
            fetch(req)
                .then(res => {
                    const copy = res.clone();
                    caches.open(CACHE).then(c => c.put(req, copy));
                    return res;
                })
                .catch(() => caches.match(req))
        );
        return;
    }

    e.respondWith(
        caches.match(req).then(hit => hit || fetch(req).then(res => {
            const copy = res.clone();
            caches.open(CACHE).then(c => c.put(req, copy));
            return res;
        }))
    );
});
