// Имя кеша (если захочешь кэшировать статику)
const CACHE_NAME = 'kanban-cache-v1'

// Список файлов для кеша (по желанию)
// const ASSETS = ['/', '/css/app.css', '/js/app.js']

// Установка service worker
self.addEventListener('install', event => {
    // Если нужен кеш — раскомментируй
    // event.waitUntil(
    //     caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS))
    // )
    self.skipWaiting()
})

// Активация
self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim())
})

// Обработка push‑уведомлений
self.addEventListener('push', event => {
    let data = {}

    try {
        data = event.data ? event.data.json() : {}
    } catch (e) {
        data = { title: 'Новое уведомление', body: event.data.text() }
    }


    const title = data.title || 'Новое уведомление'
    const options = {
        body: data.body || '',
        icon: data.icon || '/icons/icon-192.png',
        badge: data.badge || '/icons/badge.png',
        data: {
            url: data.url || '/', // куда открыть при клике
            ...data
        }
    }

    event.waitUntil(
        self.registration.showNotification(title, options)
    )

    const updateEvent = new CustomEvent('request-update', {
        detail: {
            title: title,
            body: data.body || '',
        }
    });
    window.dispatchEvent(updateEvent);
})

// Клик по уведомлению
self.addEventListener('notificationclick', event => {
    event.notification.close()

    const url = event.notification.data && event.notification.data.url
        ? event.notification.data.url
        : '/'

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(windowClients => {
                // если вкладка уже открыта — фокусируем
                for (const client of windowClients) {
                    if (client.url.includes(url) && 'focus' in client) {
                        return client.focus()
                    }
                }
                // иначе открываем новую
                if (clients.openWindow) {
                    return clients.openWindow(url)
                }
            })
    )
})

// (опционально) Сообщения от страницы
self.addEventListener('message', event => {
    // например, можно принять команду и что‑то сделать
    // if (event.data && event.data.type === 'PING') {
    //     event.ports[0].postMessage({ ok: true })
    // }
})

// (опционально) Кеширование запросов
// self.addEventListener('fetch', event => {
//     event.respondWith(
//         caches.match(event.request).then(response => {
//             return response || fetch(event.request)
//         })
//     )
// })
