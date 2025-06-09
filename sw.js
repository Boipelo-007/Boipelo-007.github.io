/**
 * Eduvos C2C Marketplace - Service Worker
 * Progressive Web App with Offline Support
 */

const CACHE_NAME = 'eduvos-c2c-v2.1.4';
const OFFLINE_PAGE = '/offline.html';
const FALLBACK_IMAGE = '/assets/images/placeholder.jpg';

// Critical app shell files that should always be cached
const APP_SHELL = [
  '/',
  '/index.html',
  '/listings.php',
  '/about.html',
  '/contact.html',
  '/login.html',
  '/register.html',
  '/sell.html',
  '/product-detail.php',
  '/offline.html',
  
  // CSS Files
  '/assets/css/style.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css',
  'https://kit.fontawesome.com/4d21d6d70f.js',
  
  // JavaScript Files
  '/assets/js/main.js',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js',
  
  // Fonts (critical for offline experience)
  'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
  
  // Essential images
  '/assets/images/logo.png',
  '/assets/images/placeholder.jpg',
  '/assets/images/offline.svg',
  
  // Manifest
  '/manifest.json'
];

// API endpoints to cache
const API_CACHE_PATTERNS = [
  /\/api\/v1\/listings/,
  /\/api\/v1\/categories/,
  /\/api\/v1\/locations/,
  /\/api\/v1\/user\/profile/
];

// Image patterns to cache
const IMAGE_CACHE_PATTERNS = [
  /\.(?:png|jpg|jpeg|svg|gif|webp)$/,
  /images\.unsplash\.com/,
  /randomuser\.me\/api\/portraits/
];

// Dynamic content that should be network-first
const NETWORK_FIRST_PATTERNS = [
  /\/api\/v1\/transactions/,
  /\/api\/v1\/messages/,
  /\/api\/v1\/notifications/,
  /\/api\/v1\/search/
];

// Install Event - Cache app shell
self.addEventListener('install', event => {
  console.log('[SW] Installing Service Worker...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[SW] Caching app shell');
        return cache.addAll(APP_SHELL);
      })
      .then(() => {
        console.log('[SW] App shell cached successfully');
        // Force activation of new service worker
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('[SW] Failed to cache app shell:', error);
      })
  );
});

// Activate Event - Clean up old caches
self.addEventListener('activate', event => {
  console.log('[SW] Activating Service Worker...');
  
  event.waitUntil(
    Promise.all([
      // Clean up old caches
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== CACHE_NAME && cacheName.startsWith('eduvos-c2c-')) {
              console.log('[SW] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      }),
      
      // Take control of all clients
      self.clients.claim()
    ])
  );
});

// Fetch Event - Handle network requests with caching strategies
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }
  
  // Skip chrome-extension and other non-http(s) requests
  if (!url.protocol.startsWith('http')) {
    return;
  }
  
  // Handle different types of requests with appropriate strategies
  if (isAppShell(request)) {
    event.respondWith(cacheFirst(request));
  } else if (isNetworkFirst(request)) {
    event.respondWith(networkFirst(request));
  } else if (isAPIRequest(request)) {
    event.respondWith(staleWhileRevalidate(request));
  } else if (isImageRequest(request)) {
    event.respondWith(cacheFirstWithFallback(request, FALLBACK_IMAGE));
  } else if (isNavigationRequest(request)) {
    event.respondWith(networkFirstWithOfflinePage(request));
  } else {
    event.respondWith(staleWhileRevalidate(request));
  }
});

// Background Sync - Handle offline actions
self.addEventListener('sync', event => {
  console.log('[SW] Background sync triggered:', event.tag);
  
  if (event.tag === 'sync-messages') {
    event.waitUntil(syncMessages());
  } else if (event.tag === 'sync-favorites') {
    event.waitUntil(syncFavorites());
  } else if (event.tag === 'sync-listings') {
    event.waitUntil(syncListings());
  } else if (event.tag === 'sync-analytics') {
    event.waitUntil(syncAnalytics());
  }
});

// Push Notifications
self.addEventListener('push', event => {
  console.log('[SW] Push message received:', event);
  
  let data = {};
  
  if (event.data) {
    data = event.data.json();
  }
  
  const options = {
    title: data.title || 'Eduvos C2C',
    body: data.body || 'You have a new notification',
    icon: '/assets/images/logo-192x192.png',
    badge: '/assets/images/badge-72x72.png',
    image: data.image || null,
    data: data.data || {},
    actions: [
      {
        action: 'view',
        title: 'View',
        icon: '/assets/images/view-icon.png'
      },
      {
        action: 'dismiss',
        title: 'Dismiss',
        icon: '/assets/images/dismiss-icon.png'
      }
    ],
    tag: data.tag || 'general',
    renotify: true,
    requireInteraction: data.requireInteraction || false,
    silent: false,
    timestamp: Date.now(),
    vibrate: [200, 100, 200]
  };
  
  event.waitUntil(
    self.registration.showNotification(options.title, options)
  );
});

// Notification Click Handler
self.addEventListener('notificationclick', event => {
  console.log('[SW] Notification clicked:', event);
  
  event.notification.close();
  
  const action = event.action;
  const data = event.notification.data;
  
  if (action === 'dismiss') {
    return;
  }
  
  let urlToOpen = '/';
  
  if (data && data.url) {
    urlToOpen = data.url;
  } else if (action === 'view' && data && data.listingId) {
    urlToOpen = `/product-detail.php?id=${data.listingId}`;
  }
  
  event.waitUntil(
    clients.matchAll({
      type: 'window',
      includeUncontrolled: true
    }).then(clientList => {
      // Try to focus existing window
      for (const client of clientList) {
        if (client.url.includes(urlToOpen) && 'focus' in client) {
          return client.focus();
        }
      }
      
      // Open new window if no existing window found
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    })
  );
});

// ===================================
// CACHING STRATEGIES
// ===================================

// Cache First - For app shell and static assets
async function cacheFirst(request) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.error('[SW] Cache first failed:', error);
    throw error;
  }
}

// Network First - For real-time data
async function networkFirst(request) {
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.log('[SW] Network failed, checking cache:', error);
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    throw error;
  }
}

// Stale While Revalidate - For API data
async function staleWhileRevalidate(request) {
  const cache = await caches.open(CACHE_NAME);
  const cachedResponse = await cache.match(request);
  
  const fetchPromise = fetch(request).then(networkResponse => {
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  }).catch(error => {
    console.log('[SW] Network request failed:', error);
  });
  
  return cachedResponse || fetchPromise;
}

// Cache First with Fallback - For images
async function cacheFirstWithFallback(request, fallbackUrl) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
      return networkResponse;
    }
  } catch (error) {
    console.log('[SW] Image request failed, using fallback:', error);
  }
  
  // Return fallback image
  return caches.match(fallbackUrl);
}

// Network First with Offline Page - For navigation
async function networkFirstWithOfflinePage(request) {
  try {
    const networkResponse = await fetch(request);
    return networkResponse;
  } catch (error) {
    console.log('[SW] Navigation failed, showing offline page:', error);
    return caches.match(OFFLINE_PAGE);
  }
}

// ===================================
// HELPER FUNCTIONS
// ===================================

function isAppShell(request) {
  return APP_SHELL.some(url => request.url.endsWith(url));
}

function isNetworkFirst(request) {
  return NETWORK_FIRST_PATTERNS.some(pattern => pattern.test(request.url));
}

function isAPIRequest(request) {
  return API_CACHE_PATTERNS.some(pattern => pattern.test(request.url));
}

function isImageRequest(request) {
  return IMAGE_CACHE_PATTERNS.some(pattern => pattern.test(request.url)) ||
         request.destination === 'image';
}

function isNavigationRequest(request) {
  return request.mode === 'navigate';
}

// ===================================
// BACKGROUND SYNC FUNCTIONS
// ===================================

async function syncMessages() {
  console.log('[SW] Syncing offline messages...');
  
  try {
    // Get offline messages from IndexedDB
    const offlineMessages = await getOfflineData('messages');
    
    for (const message of offlineMessages) {
      try {
        const response = await fetch('/api/v1/messages', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(message)
        });
        
        if (response.ok) {
          // Remove from offline storage
          await removeOfflineData('messages', message.id);
          console.log('[SW] Message synced successfully');
        }
      } catch (error) {
        console.error('[SW] Failed to sync message:', error);
      }
    }
  } catch (error) {
    console.error('[SW] Message sync failed:', error);
  }
}

async function syncFavorites() {
  console.log('[SW] Syncing offline favorites...');
  
  try {
    const offlineFavorites = await getOfflineData('favorites');
    
    for (const favorite of offlineFavorites) {
      try {
        const response = await fetch('/api/v1/favorites', {
          method: favorite.action === 'add' ? 'POST' : 'DELETE',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ listingId: favorite.listingId })
        });
        
        if (response.ok) {
          await removeOfflineData('favorites', favorite.id);
          console.log('[SW] Favorite synced successfully');
        }
      } catch (error) {
        console.error('[SW] Failed to sync favorite:', error);
      }
    }
  } catch (error) {
    console.error('[SW] Favorites sync failed:', error);
  }
}

async function syncListings() {
  console.log('[SW] Syncing offline listings...');
  
  try {
    const offlineListings = await getOfflineData('listings');
    
    for (const listing of offlineListings) {
      try {
        const response = await fetch('/api/v1/listings', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(listing)
        });
        
        if (response.ok) {
          await removeOfflineData('listings', listing.id);
          console.log('[SW] Listing synced successfully');
          
          // Show success notification
          self.registration.showNotification('Listing Published', {
            body: 'Your listing has been published successfully!',
            icon: '/assets/images/logo-192x192.png',
            tag: 'listing-published'
          });
        }
      } catch (error) {
        console.error('[SW] Failed to sync listing:', error);
      }
    }
  } catch (error) {
    console.error('[SW] Listings sync failed:', error);
  }
}

async function syncAnalytics() {
  console.log('[SW] Syncing offline analytics...');
  
  try {
    const analyticsEvents = await getOfflineData('analytics');
    
    if (analyticsEvents.length > 0) {
      const response = await fetch('/api/v1/analytics/batch', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ events: analyticsEvents })
      });
      
      if (response.ok) {
        await clearOfflineData('analytics');
        console.log('[SW] Analytics synced successfully');
      }
    }
  } catch (error) {
    console.error('[SW] Analytics sync failed:', error);
  }
}

// ===================================
// INDEXEDDB HELPERS
// ===================================

async function getOfflineData(storeName) {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('EduvosC2COfflineDB', 1);
    
    request.onerror = () => reject(request.error);
    
    request.onsuccess = () => {
      const db = request.result;
      const transaction = db.transaction([storeName], 'readonly');
      const store = transaction.objectStore(storeName);
      const getAllRequest = store.getAll();
      
      getAllRequest.onsuccess = () => resolve(getAllRequest.result);
      getAllRequest.onerror = () => reject(getAllRequest.error);
    };
    
    request.onupgradeneeded = () => {
      const db = request.result;
      if (!db.objectStoreNames.contains(storeName)) {
        db.createObjectStore(storeName, { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}

async function removeOfflineData(storeName, id) {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('EduvosC2COfflineDB', 1);
    
    request.onsuccess = () => {
      const db = request.result;
      const transaction = db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const deleteRequest = store.delete(id);
      
      deleteRequest.onsuccess = () => resolve();
      deleteRequest.onerror = () => reject(deleteRequest.error);
    };
  });
}

async function clearOfflineData(storeName) {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('EduvosC2COfflineDB', 1);
    
    request.onsuccess = () => {
      const db = request.result;
      const transaction = db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const clearRequest = store.clear();
      
      clearRequest.onsuccess = () => resolve();
      clearRequest.onerror = () => reject(clearRequest.error);
    };
  });
}

// ===================================
// CACHE MANAGEMENT
// ===================================

// Periodic cache cleanup (called when app is idle)
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'CLEAN_CACHE') {
    event.waitUntil(cleanupCache());
  } else if (event.data && event.data.type === 'PREFETCH_CRITICAL') {
    event.waitUntil(prefetchCriticalResources());
  } else if (event.data && event.data.type === 'UPDATE_CACHE') {
    event.waitUntil(updateCache());
  }
});

async function cleanupCache() {
  console.log('[SW] Cleaning up cache...');
  
  const cache = await caches.open(CACHE_NAME);
  const requests = await cache.keys();
  
  // Remove old cached responses (older than 7 days)
  const oneWeekAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
  
  for (const request of requests) {
    const response = await cache.match(request);
    const dateHeader = response.headers.get('date');
    
    if (dateHeader) {
      const responseDate = new Date(dateHeader).getTime();
      if (responseDate < oneWeekAgo) {
        await cache.delete(request);
        console.log('[SW] Removed old cache entry:', request.url);
      }
    }
  }
}

async function prefetchCriticalResources() {
  console.log('[SW] Prefetching critical resources...');
  
  const criticalUrls = [
    '/api/v1/categories',
    '/api/v1/locations',
    '/api/v1/listings?featured=true'
  ];
  
  const cache = await caches.open(CACHE_NAME);
  
  for (const url of criticalUrls) {
    try {
      const response = await fetch(url);
      if (response.ok) {
        await cache.put(url, response);
      }
    } catch (error) {
      console.log('[SW] Failed to prefetch:', url, error);
    }
  }
}

async function updateCache() {
  console.log('[SW] Updating cache with fresh content...');
  
  const cache = await caches.open(CACHE_NAME);
  
  try {
    await cache.addAll(APP_SHELL);
    console.log('[SW] Cache updated successfully');
  } catch (error) {
    console.error('[SW] Cache update failed:', error);
  }
}

// Log service worker registration
console.log('[SW] Service Worker script loaded');

// Enable navigation preload if supported
if ('navigationPreload' in self.registration) {
  self.addEventListener('activate', event => {
    event.waitUntil(self.registration.navigationPreload.enable());
  });
}