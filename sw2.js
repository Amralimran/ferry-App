self.addEventListener('install', event => {
  self.skipWaiting(); // Force activation immediately
  event.waitUntil(
    caches.open('ferry-v1').then(cache => {
      return cache.addAll([
        '/',
        '/index.php',
        '/manifest.json'
      ]);
    })
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(clients.claim()); // Take control of open pages right away
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request);
    })
  );
});