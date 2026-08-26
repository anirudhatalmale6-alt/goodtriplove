const CACHE='goodtriplove-public-v1';
const OFFLINE='/offline';
self.addEventListener('install',e=>e.waitUntil(caches.open(CACHE).then(c=>c.addAll([OFFLINE]))));
self.addEventListener('fetch',e=>{
 if(e.request.method!=='GET') return;
 const u=new URL(e.request.url);
 if(u.pathname.startsWith('/admin')||u.pathname.startsWith('/api')||u.pathname.startsWith('/account')) return;
 e.respondWith(fetch(e.request).catch(()=>caches.match(e.request).then(r=>r||caches.match(OFFLINE))));
});
