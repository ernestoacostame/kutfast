// KutFast Service Worker
const CACHE_NAME = 'kutfast-v1';
const STATIC_ASSETS = [
  './assets/icon.svg',
  './manifest.json'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  // Never intercept or cache POST/PUT requests (audio uploads and actions)
  if (event.request.method !== 'GET') {
    return;
  }
  
  // Cache fonts or SVG icon, pass everything else network-first
  const url = new URL(event.request.url);
  if (url.pathname.includes('/assets/') || url.hostname.includes('fonts.gstatic.com')) {
    event.respondWith(
      caches.match(event.request).then(response => {
        return response || fetch(event.request).then(fetchRes => {
          return caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, fetchRes.clone());
            return fetchRes;
          });
        });
      })
    );
  }
});
