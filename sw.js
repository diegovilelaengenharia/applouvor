// CACHE_NAME deve seguir APP_VERSION em includes/config.php
const CACHE_NAME = 'louvor-pib-v5.2.0';
const urlsToCache = [
  '/',
  '/index.php',
  '/assets/css/core/variables.css',
  '/assets/css/app-main.css',
  '/assets/css/theme-premium.css',
  '/assets/css/components/mobile-bottom-nav.css',
  '/assets/css/components/sidebar.css',
  '/assets/css/components/pib-cards.css',
  '/assets/js/theme-toggle.js',
  '/assets/images/logo-black.png',
  '/assets/images/logo-white.png',
  '/admin/index.php',
  '/admin/metronomo.php',
  '/admin/escalas.php',
  '/admin/repertorio.php',
  '/admin/leitura.php',
  '/admin/devocionais.php',
  '/admin/oracao.php',
  '/offline.html',
  'https://unpkg.com/lucide@latest'
];

self.addEventListener('install', event => {
  // Força o SW a ativar imediatamente
  self.skipWaiting();

  // Tolerância a falha individual: 1 URL ruim não derruba o install inteiro
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache =>
      Promise.allSettled(urlsToCache.map(url => cache.add(url)))
    )
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
  const request = event.request;
  const url = new URL(request.url);

  // Ignorar requisições POST, PUT, DELETE para APIs (não cachear mutações)
  if (request.method !== 'GET') {
    return;
  }

  // Ignorar requisições externas que não estejam na lista de cache (ex: Analytics, Avatares dinâmicos)
  // Mas aceitar lucide
  if (url.origin !== location.origin && !url.href.includes('lucide')) {
    return;
  }

  // --- ESTRATÉGIA 1: CACHE FIRST (Fallback to Network) ---
  // Para arquivos estáticos: Imagens, CSS, JS, Fontes
  const isStaticAsset = request.destination === 'style' || 
                        request.destination === 'script' || 
                        request.destination === 'image' || 
                        request.destination === 'font' ||
                        url.href.includes('lucide');

  if (isStaticAsset) {
    event.respondWith(
      caches.match(request).then(cachedResponse => {
        if (cachedResponse) {
          return cachedResponse; // Retorna do cache se existir
        }
        // Se não, busca na rede e guarda no cache
        return fetch(request).then(networkResponse => {
          if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
            if(!url.href.includes('lucide')) return networkResponse; // Ignora opaco
          }
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(request, responseToCache);
          });
          return networkResponse;
        }).catch(() => {
          // Falha na rede para assets (não tem fallback para assets no momento, falha silenciosa)
          return new Response('');
        });
      })
    );
    return;
  }

  // --- ESTRATÉGIA 2: NETWORK FIRST (Fallback to Cache) ---
  // Para arquivos HTML/PHP (Views dinâmicas)
  if (request.mode === 'navigate' || request.destination === 'document' || url.pathname.endsWith('.php') || url.pathname === '/') {
    event.respondWith(
      fetch(request).then(networkResponse => {
        // Guarda uma cópia atualizada no cache se o fetch deu sucesso
        const responseToCache = networkResponse.clone();
        caches.open(CACHE_NAME).then(cache => {
          cache.put(request, responseToCache);
        });
        return networkResponse;
      }).catch(() => {
        // Se a rede falhar (OFFLINE), tenta pegar do cache
        return caches.match(request).then(cachedResponse => {
          if (cachedResponse) {
            return cachedResponse;
          }
          // Se a página não estiver no cache, retorna a página offline.html genérica
          if (request.mode === 'navigate') {
            return caches.match('/offline.html');
          }
          return new Response('Offline', { status: 503, statusText: 'Offline' });
        });
      })
    );
    return;
  }

  // Fallback genérico para outras requisições
  event.respondWith(
    caches.match(request).then(response => {
      return response || fetch(request);
    }).catch(() => {
      if (request.mode === 'navigate') {
        return caches.match('/offline.html');
      }
    })
  );
});

// Listener de Push
self.addEventListener('push', function (event) {
  if (event.data) {
    const data = event.data.json();

    const options = {
      body: data.body,
      icon: data.icon || '/assets/images/logo-black.png',
      badge: '/assets/images/logo-white.png', // Ícone pequeno monocromático para Android
      vibrate: [100, 50, 100],
      data: data.data || {}
    };

    event.waitUntil(
      self.registration.showNotification(data.title, options)
    );
  }
});

// Listener de Clique na Notificação
self.addEventListener('notificationclick', function (event) {
  event.notification.close();

  // URL para abrir
  const urlToOpen = event.notification.data.url || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      // Tenta focar em uma aba já aberta
      for (let i = 0; i < clientList.length; i++) {
        const client = clientList[i];
        if (client.url.includes(urlToOpen) && 'focus' in client) {
          return client.focus();
        }
      }
      // Se não, abre uma nova
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    })
  );
});
