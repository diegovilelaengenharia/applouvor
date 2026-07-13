const CACHE_NAME = 'louvor-pib-v1';
const ASSETS_TO_CACHE = [
  '/',
  '/manifest.json',
  '/assets/css/stitch-theme.css',
  '/assets/js/app.js',
  '/assets/js/theme.js',
  'https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700&family=Open+Sans:wght@400;600;700&display=swap',
  'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap'
];

// Instalação: Cacheia os recursos estáticos principais
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(ASSETS_TO_CACHE);
    }).then(() => self.skipWaiting())
  );
});

// Ativação: Limpa caches antigos
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Interceptação de requisições (Fetch)
self.addEventListener('fetch', event => {
  const requestUrl = new URL(event.request.url);

  // Evita cachear requisições não-GET ou de métodos externos como POST de login
  if (event.request.method !== 'GET') {
    return;
  }

  // Ignora chamadas de APIs ou do live-reload do XAMPP (se houver)
  if (requestUrl.pathname.startsWith('/api/') || requestUrl.hostname === 'localhost' && requestUrl.port === '35729') {
    return;
  }

  // Estratégia Cache-First para recursos estáticos locais e fontes Google
  if (
    ASSETS_TO_CACHE.includes(requestUrl.pathname) || 
    requestUrl.hostname.includes('fonts.googleapis.com') || 
    requestUrl.hostname.includes('fonts.gstatic.com') ||
    requestUrl.pathname.startsWith('/assets/')
  ) {
    event.respondWith(
      caches.match(event.request).then(cachedResponse => {
        if (cachedResponse) {
          // Atualiza o cache em segundo plano (stale-while-revalidate)
          fetch(event.request).then(networkResponse => {
            if (networkResponse.status === 200) {
              caches.open(CACHE_NAME).then(cache => cache.put(event.request, networkResponse));
            }
          }).catch(() => {/* Ignora falha de rede */});
          return cachedResponse;
        }
        return fetch(event.request).then(networkResponse => {
          if (networkResponse.status === 200) {
            return caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request, networkResponse.clone());
              return networkResponse;
            });
          }
          return networkResponse;
        });
      })
    );
    return;
  }

  // Estratégia Network-First para páginas (como a raiz "/" ou "/dashboard")
  event.respondWith(
    fetch(event.request)
      .then(networkResponse => {
        if (networkResponse.status === 200) {
          const responseClone = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseClone));
        }
        return networkResponse;
      })
      .catch(() => {
        // Se a rede falhar, tenta retornar do cache
        return caches.match(event.request).then(cachedResponse => {
          if (cachedResponse) {
            return cachedResponse;
          }
          // Caso não tenha no cache, retorna a página inicial "/" do cache
          if (event.request.mode === 'navigate') {
            return caches.match('/');
          }
        });
      })
  );
});
