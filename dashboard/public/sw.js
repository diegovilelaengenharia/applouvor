const CACHE_NAME = 'pib-louvor-dashboard-v1.0.0';
const PRECACHE_ASSETS = [
  '/dashboard/',
  '/dashboard/index.html'
];

// Instalação do Service Worker - Pre-cache da casca do SPA (Shell)
self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return Promise.allSettled(
        PRECACHE_ASSETS.map(url => cache.add(url))
      );
    })
  );
});

// Ativação do Service Worker - Limpeza de caches antigos
self.addEventListener('activate', event => {
  event.waitUntil(self.clients.claim());
  
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Interceptação de requisições (Fetch)
self.addEventListener('fetch', event => {
  const request = event.request;
  const url = new URL(request.url);

  // Ignorar métodos não-GET (mutações, POSTs, etc.)
  if (request.method !== 'GET') {
    return;
  }

  // --- ESTRATÉGIA 1: API DATA - NETWORK FIRST com Fallback para Cache ---
  // Interceptar as requisições de API (ex: php ou endpoints dinâmicos)
  if (url.pathname.includes('/api/') || url.pathname.endsWith('.php')) {
    event.respondWith(
      fetch(request)
        .then(networkResponse => {
          // Se obteve sucesso, clona e atualiza no cache
          if (networkResponse && networkResponse.status === 200) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(request, responseToCache);
            });
          }
          return networkResponse;
        })
        .catch(() => {
          // Em caso de falha de rede (offline), tenta buscar do cache
          return caches.match(request).then(cachedResponse => {
            if (cachedResponse) {
              return cachedResponse;
            }
            // Fallback genérico se não tiver cache de API
            return new Response(JSON.stringify({ 
              error: true, 
              message: "Você está offline e estes dados não estão em cache." 
            }), { 
              status: 503, 
              headers: { 'Content-Type': 'application/json' } 
            });
          });
        })
    );
    return;
  }

  // --- ESTRATÉGIA 2: SPA NAVIGATION - CACHE FIRST / NETWORK-FALLBACK ---
  // Para requisições de navegação direta do React (rotas amigáveis como /dashboard/metronomo)
  if (request.mode === 'navigate' || (request.destination === 'document' && url.pathname.startsWith('/dashboard/'))) {
    event.respondWith(
      fetch(request)
        .catch(() => {
          // Se falhar (offline), serve o index.html do SPA que gerencia a rota no client-side
          return caches.match('/dashboard/index.html') || caches.match('/dashboard/');
        })
    );
    return;
  }

  // --- ESTRATÉGIA 3: STATIC ASSETS - CACHE FIRST com Network Fallback ---
  // Para bundles de CSS, JS, imagens locais e fontes do Google Fonts
  const isStatic = request.destination === 'style' ||
                   request.destination === 'script' ||
                   request.destination === 'image' ||
                   request.destination === 'font' ||
                   url.pathname.includes('/assets/') ||
                   url.origin.includes('fonts.googleapis.com') ||
                   url.origin.includes('fonts.gstatic.com');

  if (isStatic) {
    event.respondWith(
      caches.match(request).then(cachedResponse => {
        if (cachedResponse) {
          return cachedResponse;
        }

        return fetch(request).then(networkResponse => {
          if (networkResponse && networkResponse.status === 200) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(request, responseToCache);
            });
          }
          return networkResponse;
        }).catch(() => {
          // Retorna vazio ou tenta buscar o index.html se for imagem/css crítico
          return new Response('', { status: 404 });
        });
      })
    );
    return;
  }

  // Fallback genérico (Cache com fallback para Rede)
  event.respondWith(
    caches.match(request).then(response => {
      return response || fetch(request);
    })
  );
});
