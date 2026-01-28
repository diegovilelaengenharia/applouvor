const CACHE_NAME = 'louvor-pib-v2.2.0';
const urlsToCache = [
  '/',
  '/index.php',
  '/assets/css/style.css',
  '/assets/images/logo-black.png',
  '/assets/images/logo-white.png',
  'https://unpkg.com/lucide@latest'
];

self.addEventListener('install', event => {
  // Força o SW a ativar imediatamente
  self.skipWaiting();

  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('activate', event => {
  // Reivindica o controle dos clientes imediatamente
  event.waitUntil(self.clients.claim());

  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Retorna do cache se encontrar
        if (response) {
          return response;
        }
        // Se não, busca na rede
        return fetch(event.request);
      })
  );
});
