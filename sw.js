/**
 * Service Worker: Alarme a Distância (PWA)
 * Versão do Cache: alarme-distancia-v2.0
 */
const CACHE_NAME = 'alarme-distancia-v2.0';

const PRECACHE_ASSETS = [
    './',
    'index.php',
    'login.php',
    'registro.php',
    'perfil.php',
    'historico.php',
    'offline.html',
    'manifest.json',
    'assets/css/style.css',
    'assets/js/app.js',
    'assets/js/audio.js',
    'assets/js/ui.js',
    'assets/icons/icon.svg',
    'assets/icons/icon-192.png',
    'assets/icons/icon-512.png',
    'assets/icons/icon-maskable.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
];

// Instalação do Service Worker e pré-cache de arquivos essenciais
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS);
        }).then(() => self.skipWaiting())
    );
});

// Ativação e limpeza de versões antigas de cache
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Estratégia de requisições:
// - APIs (/api/*): Network-First (dados dinâmicos de login, trajeto e geocode)
// - Navegação (HTML): Network-First com fallback para cache e offline.html
// - Arquivos estáticos (CSS, JS, Imagens, Fontes): Stale-While-Revalidate
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Ignora esquemas que não sejam http/https (ex: chrome-extension)
    if (!request.url.startsWith('http')) return;

    // 1. Chamadas de API dinâmica (/api/*)
    if (url.pathname.includes('/api/')) {
        event.respondWith(
            fetch(request).catch(() => {
                return new Response(JSON.stringify({ 
                    success: false, 
                    message: 'Você está offline. Verifique sua conexão.' 
                }), {
                    headers: { 'Content-Type': 'application/json' }
                });
            })
        );
        return;
    }

    // 2. Requisições de Navegação (Páginas PHP / HTML)
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    const cachedResponse = await caches.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    const offline = await caches.match('offline.html');
                    if (offline) {
                        return offline;
                    }
                    return new Response(
                        '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Offline</title></head><body style="font-family:sans-serif;text-align:center;padding:2rem;"><h2>Você está offline</h2><p>Verifique sua conexão à internet.</p><button onclick="location.reload()">Recarregar</button></body></html>',
                        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                    );
                })
        );
        return;
    }

    // 3. Recursos Estáticos (Stale-While-Revalidate)
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            const fetchPromise = fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseClone);
                    });
                }
                return networkResponse;
            }).catch(() => {
                // Se falhar a busca na rede, fica com o cache silenciosamente
            });

            return cachedResponse || fetchPromise;
        })
    );
});

