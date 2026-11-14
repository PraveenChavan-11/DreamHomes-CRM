/**
 * TalentHub Consultant — Staging Environment Service Worker
 * Version: 1.0.0 (Isolated Staging Cache Partition)
 */

const CACHE_NAME = 'talenthub-staging-cache-v2';
const OFFLINE_URL = '/offline.html';

const PRECACHE_ASSETS = [
  '/',
  '/manifest-staging.json',
  '/favicon.png',
  '/favicon.ico',
  '/logo.png',
  '/logo-white.png',
  '/icons/icon-192.png',
];

// Install: Pre-cache staging assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_ASSETS).catch(() => {
        // Tolerant precache in case some icons are dynamically resolved
      });
    }).then(() => self.skipWaiting())
  );
});

// Activate: Purge old staging caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((name) => {
          if (name.startsWith('talenthub-staging-') && name !== CACHE_NAME) {
            return caches.delete(name);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch: Network first with staging cache fallback
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // 1. Never intercept non-GET requests (mutations, uploads, logins, forms)
  if (request.method !== 'GET') {
    return;
  }

  // 2. Ignore non-HTTP/HTTPS and third-party requests
  if (!url.protocol.startsWith('http')) {
    return;
  }

  // 3. Bypass all admin, spark, and api requests
  if (url.pathname.startsWith('/admin') || url.pathname.startsWith('/api') || url.pathname.startsWith('/spark')) {
    return;
  }

  // 4. Staging Strategy: Network First to ensure testers always see latest main changes
  event.respondWith(
    fetch(request)
      .then((networkResponse) => {
        if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(request, responseToCache);
          });
        }
        return networkResponse;
      })
      .catch(() => {
        return caches.match(request);
      })
  );
});

