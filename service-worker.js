/**
 * Service Worker for Gym Management System PWA
 * Handles offline caching and background sync
 */

const CACHE_NAME = 'gms-cache-v1';
const RUNTIME_CACHE = 'gms-runtime-v1';

// Files to cache on installation
const PRECACHE_URLS = [
    '/gms/',
    '/gms/login.php',
    '/gms/assets/css/style.css',
    '/gms/assets/css/custom.css',
    '/gms/assets/css/components.css',
    '/gms/assets/css/animations.css',
    '/gms/assets/css/responsive.css',
    '/gms/assets/css/validation.css',
    '/gms/assets/js/main.js',
    '/gms/assets/js/sidebar.js',
    '/gms/assets/js/enhanced.js',
    '/gms/assets/js/form-validator.js',
    '/gms/assets/images/web.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'
];

// Install event - cache core files
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[ServiceWorker] Pre-caching core files');
                return cache.addAll(PRECACHE_URLS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    const currentCaches = [CACHE_NAME, RUNTIME_CACHE];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (!currentCaches.includes(cacheName)) {
                        console.log('[ServiceWorker] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin) && 
        !event.request.url.startsWith('https://cdn.')) {
        return;
    }

    // Skip POST requests and API calls
    if (event.request.method !== 'GET' || event.request.url.includes('/api/')) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then(cachedResponse => {
            if (cachedResponse) {
                // Return cached version and update cache in background
                updateCache(event.request);
                return cachedResponse;
            }

            // Not in cache, fetch from network
            return fetch(event.request).then(response => {
                // Cache successful responses
                if (response.status === 200) {
                    const responseToCache = response.clone();
                    caches.open(RUNTIME_CACHE).then(cache => {
                        cache.put(event.request, responseToCache);
                    });
                }
                return response;
            }).catch(() => {
                // Network failed, show offline page if available
                return caches.match('/gms/offline.html');
            });
        })
    );
});

// Background sync for offline form submissions
self.addEventListener('sync', event => {
    if (event.tag === 'sync-attendance') {
        event.waitUntil(syncAttendance());
    }
    if (event.tag === 'sync-payment') {
        event.waitUntil(syncPayment());
    }
});

// Push notifications
self.addEventListener('push', event => {
    const options = {
        body: event.data ? event.data.text() : 'New notification from GMS',
        icon: '/gms/assets/images/icon-192x192.png',
        badge: '/gms/assets/images/badge-72x72.png',
        vibrate: [200, 100, 200],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'View',
                icon: '/gms/assets/images/checkmark.png'
            },
            {
                action: 'close',
                title: 'Dismiss',
                icon: '/gms/assets/images/close.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('GMS Notification', options)
    );
});

// Notification click handler
self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/gms/')
        );
    }
});

// Helper function to update cache in background
function updateCache(request) {
    return fetch(request).then(response => {
        if (response.status === 200) {
            return caches.open(RUNTIME_CACHE).then(cache => {
                return cache.put(request, response);
            });
        }
    }).catch(() => {
        // Failed to update, but we have cache
    });
}

// Sync attendance data when back online
async function syncAttendance() {
    const db = await openIndexedDB();
    const attendanceData = await getUnsyncedData(db, 'attendance');
    
    for (const data of attendanceData) {
        try {
            const response = await fetch('/gms/api/attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            if (response.ok) {
                await markAsSynced(db, 'attendance', data.id);
            }
        } catch (error) {
            console.error('Failed to sync attendance:', error);
        }
    }
}

// Sync payment data when back online
async function syncPayment() {
    const db = await openIndexedDB();
    const paymentData = await getUnsyncedData(db, 'payments');
    
    for (const data of paymentData) {
        try {
            const response = await fetch('/gms/api/payments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            if (response.ok) {
                await markAsSynced(db, 'payments', data.id);
            }
        } catch (error) {
            console.error('Failed to sync payment:', error);
        }
    }
}

// IndexedDB helpers
function openIndexedDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('GMS_DB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('attendance')) {
                db.createObjectStore('attendance', { keyPath: 'id', autoIncrement: true });
            }
            if (!db.objectStoreNames.contains('payments')) {
                db.createObjectStore('payments', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

function getUnsyncedData(db, storeName) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([storeName], 'readonly');
        const store = transaction.objectStore(storeName);
        const request = store.getAll();
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const data = request.result.filter(item => !item.synced);
            resolve(data);
        };
    });
}

function markAsSynced(db, storeName, id) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        const request = store.delete(id);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve();
    });
}
