const CACHE_NAME = 'decklogistic-cache-v1';
const urlsToCache = [
  '/',
  '/index.php',
  '/pages/auth/lojas/cadastro.php',
  '/pages/dashboard/estoque.php',
  '/css/style.css',
  '/js/script.js',
  '/img/logo2.svg',
  '/img/logoTeste-192.png',
  '/img/logoTeste-512.png'
];

// Instalação: cache arquivos essenciais
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

// Ativação: limpar caches antigos
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => 
      Promise.all(
        cacheNames.map(name => {
          if (name !== CACHE_NAME) return caches.delete(name);
        })
      )
    )
  );
});

// Intercepta requisições
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(response => response || fetch(event.request))
  );
});
