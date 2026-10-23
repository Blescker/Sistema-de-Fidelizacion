// Service worker de la PWA.
// - Archivos estáticos (css, js, íconos): se guardan en el celular para abrir rápido.
// - Páginas: SIEMPRE se piden al servidor (son datos del negocio y dependen del login);
//   si no hay internet se muestra offline.html.
// Al cambiar archivos estáticos, sube la versión para que los celulares se actualicen.
const VERSION = 'v1';
const CACHE = `fidelizacion-${VERSION}`;
const STATIC = [
  '/offline.html',
  '/css/app.css?v=1',
  '/js/app.js?v=1',
  '/vendor/bootstrap/bootstrap.min.css',
  '/vendor/bootstrap/bootstrap.bundle.min.js',
  '/vendor/bootstrap-icons/bootstrap-icons.min.css',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(STATIC)));
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;

  // Navegación (páginas): red primero, offline.html si no hay conexión
  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
    return;
  }

  // Estáticos: caché primero
  const url = new URL(req.url);
  if (url.origin === location.origin && /^\/(css|js|vendor|icons)\//.test(url.pathname)) {
    e.respondWith(
      caches.match(req).then((hit) => hit || fetch(req).then((res) => {
        const copy = res.clone();
        caches.open(CACHE).then((c) => c.put(req, copy));
        return res;
      }))
    );
  }
});
