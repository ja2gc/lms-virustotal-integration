// Retire the upstream offline worker: private pages must always use the network.
self.addEventListener('install', event => event.waitUntil(self.skipWaiting()));
self.addEventListener('activate', event => event.waitUntil(
  caches.keys().then(keys => Promise.all(
    keys.filter(key => key.startsWith('doodle-')).map(key => caches.delete(key))
  )).then(() => self.registration.unregister())
));
// No fetch handler and no offline response fallback.
