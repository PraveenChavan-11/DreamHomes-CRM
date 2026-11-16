/**
 * TalentHub Consultant — Production Service Worker
 * Version: 2.0.0 (Engagement Centre Hardened)
 */

const CACHE_NAME = 'talenthub-cache-v5';
const OFFLINE_URL = '/offline.html';

const PRECACHE_ASSETS = [
  '/',
  '/offline.html',
  '/assets/css/admin-tailwind.css',
  '/manifest.json',
  '/manifest-admin.json',
  '/manifest-crm.json',
  '/assets/crm/css/crm.css',
  '/assets/crm/js/crm.js',
  '/assets/crm/img/dhis-logo.png',
  '/assets/crm/img/talenthub-mark.png',
  '/favicon.png',
  '/favicon.ico',
  '/logo.png',
  '/logo-white.png',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/icons/badge-72.png',
];

// Install: Pre-cache core shell & offline fallback
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_ASSETS);
    }).then(() => self.skipWaiting())
  );
});

// Activate: Cleanup old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((name) => {
          if (name !== CACHE_NAME) {
            return caches.delete(name);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch: Strategy depending on request type
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // 1. Never intercept non-GET requests (mutations, uploads, logins, forms)
  if (request.method !== 'GET') {
    return;
  }

  // 2. Ignore non-HTTP/HTTPS and third-party trackers (Analytics, Tawk, Turnstile)
  if (!url.protocol.startsWith('http')) {
    return;
  }
  if (url.origin !== self.location.origin) {
    return;
  }

  // 3. Sensitive / Dynamic workflows never cached (Admin dashboard, APIs, authentication, subscription tokens, stream)
  if (url.pathname.startsWith('/admin') ||
      url.pathname.startsWith('/api/') ||
      url.pathname.startsWith('/newsletter/') ||
      url.pathname.startsWith('/job-alerts/') ||
      url.pathname.startsWith('/auth/') ||
      url.pathname.startsWith('/login') ||
      url.pathname.startsWith('/logout') ||
      url.pathname.includes('/csrf') ||
      url.pathname.includes('/documents/') ||
      url.pathname.includes('/stream') ||
      url.pathname.includes('/sync')) {
    return;
  }

  // 4. HTML Navigation Requests -> Network First, fall back to Cache, then Offline Page
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response.status === 200) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
          }
          return response;
        })
        .catch(() => {
          return caches.match(request).then((cachedResponse) => {
            return cachedResponse || caches.match(OFFLINE_URL);
          });
        })
    );
    return;
  }

  // 5. Static Assets (CSS, JS, Images, Fonts) -> Cache First with Network Revalidation
  if (request.destination === 'style' ||
      request.destination === 'script' ||
      request.destination === 'image' ||
      request.destination === 'font') {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        if (cachedResponse) {
          fetch(request).then((networkResponse) => {
            if (networkResponse && networkResponse.status === 200) {
              const clone = networkResponse.clone();
              caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
            }
          }).catch(() => {/* ignore background static refresh errors */});
          return cachedResponse;
        }

        return fetch(request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const clone = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
          }
          return networkResponse;
        }).catch(() => {
          return new Response('', { status: 204, statusText: 'No Content' });
        });
      })
    );
    return;
  }

  // Default fetch: network-first or cache-match with safe error fallback (never uncaught rejected promise)
  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) return cached;
      return fetch(request).catch(() => {
        return new Response('', { status: 503, statusText: 'Service Unavailable' });
      });
    })
  );
});



// -------------------------------------------------------------
// Push Notifications: Real-Time Mobile & Desktop Alerts (GAP-PWA)
// -------------------------------------------------------------
self.addEventListener('push', (event) => {
  let data = {
    title: 'TalentHub Alert',
    body: 'New enquiry or recruitment update received.',
    url: '/admin',
    icon: '/favicon-192x192.png',
    badge: '/favicon-96x96.png'
  };

  if (event.data) {
    try {
      data = Object.assign(data, event.data.json());
    } catch (e) {
      data.body = event.data.text();
    }
  }

  const options = {
    body: data.body,
    icon: data.icon || '/favicon-192x192.png',
    badge: data.badge || '/favicon-96x96.png',
    vibrate: [200, 100, 200],
    tag: data.tag || 'talenthub-notification',
    renotify: true,
    data: {
      url: data.url || '/admin'
    }
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Notification Click Handler: Open/Focus Destination on Mobile & Desktop
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = (event.notification.data && event.notification.data.url)
    ? event.notification.data.url
    : '/admin';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url && client.url.includes(targetUrl) && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});
