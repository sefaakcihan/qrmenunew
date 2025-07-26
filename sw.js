// sw.js - Service Worker for Restaurant Menu PWA

const CACHE_NAME = 'restaurant-menu-v1.0.0';
const STATIC_CACHE = 'static-cache-v1.0.0';
const DYNAMIC_CACHE = 'dynamic-cache-v1.0.0';
const IMAGE_CACHE = 'image-cache-v1.0.0';

// Files to cache immediately
const STATIC_FILES = [
    '/',
    '/index.html',
    '/js/menu.js',
    '/admin/login.html',
    '/admin/dashboard.html',
    '/admin/js/dashboard.js',
    '/manifest.json',
    '/offline.html',
    // Tailwind CSS (CDN fallback)
    'https://cdn.tailwindcss.com',
    // Chart.js (CDN fallback)
    'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js'
];

// API endpoints to cache
const API_ENDPOINTS = [
    '/backend/api/menu.php',
    '/backend/api/categories.php',
    '/backend/api/themes.php'
];

// Install event - cache static files
self.addEventListener('install', (event) => {
    console.log('Service Worker: Installing...');
    
    event.waitUntil(
        Promise.all([
            // Cache static files
            caches.open(STATIC_CACHE).then((cache) => {
                console.log('Service Worker: Caching static files');
                return cache.addAll(STATIC_FILES);
            }),
            // Cache API responses
            caches.open(DYNAMIC_CACHE).then((cache) => {
                console.log('Service Worker: Pre-caching API endpoints');
                return Promise.all(
                    API_ENDPOINTS.map(url => {
                        return fetch(url + '?restaurant_id=1')
                            .then(response => {
                                if (response.ok) {
                                    return cache.put(url + '?restaurant_id=1', response);
                                }
                            })
                            .catch(err => console.log('Failed to cache:', url, err));
                    })
                );
            })
        ])
    );
    
    // Force activation
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activating...');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    // Delete old caches
                    if (cacheName !== STATIC_CACHE && 
                        cacheName !== DYNAMIC_CACHE && 
                        cacheName !== IMAGE_CACHE) {
                        console.log('Service Worker: Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    
    // Take control immediately
    self.clients.claim();
});

// Fetch event - serve cached content
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Handle different types of requests
    if (request.url.includes('/backend/api/')) {
        event.respondWith(handleApiRequest(request));
    } else if (request.url.includes('/uploads/') || request.destination === 'image') {
        event.respondWith(handleImageRequest(request));
    } else {
        event.respondWith(handleStaticRequest(request));
    }
});

// Handle API requests
async function handleApiRequest(request) {
    const url = new URL(request.url);
    
    // For read-only API calls, try cache first
    if (url.pathname.includes('/menu.php') || 
        url.pathname.includes('/categories.php') || 
        url.pathname.includes('/themes.php')) {
        
        try {
            // Try cache first
            const cachedResponse = await caches.match(request);
            if (cachedResponse) {
                console.log('Service Worker: Serving API from cache:', request.url);
                
                // Update cache in background
                fetch(request).then(response => {
                    if (response.ok) {
                        caches.open(DYNAMIC_CACHE).then(cache => {
                            cache.put(request, response.clone());
                        });
                    }
                }).catch(() => {}); // Ignore background update errors
                
                return cachedResponse;
            }
            
            // Fetch from network
            const networkResponse = await fetch(request);
            
            if (networkResponse.ok) {
                // Cache successful responses
                const cache = await caches.open(DYNAMIC_CACHE);
                cache.put(request, networkResponse.clone());
            }
            
            return networkResponse;
            
        } catch (error) {
            console.log('Service Worker: API request failed:', error);
            
            // Return cached version if available
            const cachedResponse = await caches.match(request);
            if (cachedResponse) {
                return cachedResponse;
            }
            
            // Return offline response for menu data
            return new Response(JSON.stringify({
                success: false,
                message: 'Offline - Cached data not available',
                data: []
            }), {
                headers: { 'Content-Type': 'application/json' }
            });
        }
    }
    
    // For write operations (POST, PUT, DELETE), always try network
    try {
        return await fetch(request);
    } catch (error) {
        return new Response(JSON.stringify({
            success: false,
            message: 'Network error - Please try again when online'
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Handle image requests
async function handleImageRequest(request) {
    try {
        // Try cache first
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Fetch from network
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Cache images
            const cache = await caches.open(IMAGE_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
        
    } catch (error) {
        console.log('Service Worker: Image request failed:', error);
        
        // Return placeholder image
        return caches.match('/images/no-image.jpg') || 
               new Response('', { status: 404 });
    }
}

// Handle static file requests
async function handleStaticRequest(request) {
    try {
        // Try cache first for static files
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Fetch from network
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Cache static files
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
        
    } catch (error) {
        console.log('Service Worker: Static request failed:', error);
        
        // For navigation requests, return offline page
        if (request.mode === 'navigate') {
            return caches.match('/offline.html') || 
                   caches.match('/index.html') ||
                   new Response('Offline', { status: 503 });
        }
        
        // For other requests, try to find in cache
        return caches.match(request) || 
               new Response('Not found', { status: 404 });
    }
}

// Background sync for offline actions
self.addEventListener('sync', (event) => {
    if (event.tag === 'waiter-call-sync') {
        event.waitUntil(syncWaiterCalls());
    }
});

// Sync pending waiter calls when online
async function syncWaiterCalls() {
    try {
        // Get pending calls from IndexedDB (would be implemented)
        console.log('Service Worker: Syncing waiter calls...');
        
        // This would sync any pending offline waiter calls
        // Implementation would depend on IndexedDB storage
        
    } catch (error) {
        console.log('Service Worker: Sync failed:', error);
    }
}

// Push notification handling
self.addEventListener('push', (event) => {
    const options = {
        body: 'Yeni garson çağrısı var!',
        icon: '/icons/icon-192x192.png',
        badge: '/icons/badge-72x72.png',
        vibrate: [200, 100, 200],
        data: {
            type: 'waiter-call'
        },
        actions: [
            {
                action: 'view',
                title: 'Görüntüle',
                icon: '/icons/view-icon.png'
            },
            {
                action: 'dismiss',
                title: 'Kapat',
                icon: '/icons/close-icon.png'
            }
        ]
    };
    
    if (event.data) {
        const data = event.data.json();
        options.body = data.message || options.body;
        options.data = { ...options.data, ...data };
    }
    
    event.waitUntil(
        self.registration.showNotification('Lezzet Restaurant', options)
    );
});

// Notification click handling
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow('/admin/dashboard.html')
        );
    } else if (event.action === 'dismiss') {
        // Just close the notification
        return;
    } else {
        // Default action - open the app
        event.waitUntil(
            clients.matchAll().then((clientList) => {
                // If app is already open, focus it
                for (const client of clientList) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // Otherwise open new window
                if (clients.openWindow) {
                    return clients.openWindow('/');
                }
            })
        );
    }
});

// Message handling from main thread
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            caches.open(DYNAMIC_CACHE).then((cache) => {
                return cache.addAll(event.data.urls);
            })
        );
    }
});

// Periodic background sync (when supported)
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'menu-sync') {
        event.waitUntil(syncMenuData());
    }
});

// Sync menu data periodically
async function syncMenuData() {
    try {
        console.log('Service Worker: Periodic menu sync...');
        
        const cache = await caches.open(DYNAMIC_CACHE);
        
        // Update menu data
        const menuResponse = await fetch('/backend/api/menu.php?restaurant_id=1');
        if (menuResponse.ok) {
            await cache.put('/backend/api/menu.php?restaurant_id=1', menuResponse);
        }
        
        // Update categories
        const categoriesResponse = await fetch('/backend/api/categories.php?restaurant_id=1');
        if (categoriesResponse.ok) {
            await cache.put('/backend/api/categories.php?restaurant_id=1', categoriesResponse);
        }
        
    } catch (error) {
        console.log('Service Worker: Periodic sync failed:', error);
    }
}

// Clean up old caches periodically
setInterval(() => {
    caches.keys().then((cacheNames) => {
        cacheNames.forEach((cacheName) => {
            if (cacheName.includes('image-cache') || cacheName.includes('dynamic-cache')) {
                caches.open(cacheName).then((cache) => {
                    cache.keys().then((requests) => {
                        // Remove old entries (older than 7 days)
                        const oneWeekAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
                        requests.forEach((request) => {
                            cache.match(request).then((response) => {
                                if (response) {
                                    const dateHeader = response.headers.get('date');
                                    if (dateHeader && new Date(dateHeader).getTime() < oneWeekAgo) {
                                        cache.delete(request);
                                    }
                                }
                            });
                        });
                    });
                });
            }
        });
    });
}, 24 * 60 * 60 * 1000); // Run daily