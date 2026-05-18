// CACHE_NAME deve seguir APP_VERSION em includes/config.php
const CACHE_NAME = 'louvor-pib-v5.1.0';
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
  // Ignorar requisições POST para APIs (não cachear)
  if (event.request.method === 'POST') {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request);
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
