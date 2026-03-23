<template>
    <div class="refresh-progress" :style="{ width: progress + '%' }"></div>

    <div class="d-flex flex-column min-vh-100">
        <main class="flex-grow-1">
            <KanbanBoard :initial-board="board"/>
        </main>

        <!-- Footer -->
        <footer class="text-light py-4 mt-auto">
            <div class="container text-center">

                <div class="d-flex justify-content-center my-3">
                    <!-- Checkbox -->
                    <div class="form-check form-switch ">
                        <input
                            v-model="need_request_updates"
                            type="checkbox"
                            class="form-check-input"
                            id="needRequestUpdates"
                        />
                        <label class="form-check-label text-white" for="needRequestUpdates">
                            Запрашивать обновление доски раз в минуту
                        </label>
                    </div>
                </div>

                <h2 class="kanbancrm-logo mb-3">
                    <i class="fa-solid fa-layer-group me-2"></i>
                    KanbanCRM
                </h2>



                <p class="mb-1">© 2026 KanbanCRM. Все права защищены.</p>
                <p class="small text-white mb-2">Сделано с <i class="fa-solid fa-heart text-danger"></i> в мире АйТи</p>

                <div class="mt-3 position-relative d-inline-block">
                    <button class="btn btn-secondary rounded-circle" style="width: 40px; height: 40px;" @click="showColorPicker = !showColorPicker" title="Выбрать цвет фона">
                        <i class="fa-solid fa-palette"></i>
                    </button>
                    <!-- Обертка для позиционирования пикера над кнопкой -->
                    <div v-show="showColorPicker" class="position-absolute bg-white p-2 rounded shadow" style="bottom: 50px; left: 50%; transform: translateX(-50%); z-index: 1050; min-width: 260px;">
                        <ul class="nav nav-pills nav-fill mb-2">
                            <li class="nav-item">
                                <a class="nav-link py-1 px-2" style="font-size: 14px; cursor: pointer;" :class="{active: bgMode === 'solid'}" @click.prevent="bgMode = 'solid'">Сплошной</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 px-2" style="font-size: 14px; cursor: pointer;" :class="{active: bgMode === 'gradient'}" @click.prevent="bgMode = 'gradient'">Градиент</a>
                            </li>
                        </ul>
                        <div v-show="bgMode === 'solid'">
                            <ColorPicker v-model:pureColor="solidColor" useType="pure" format="hex" isWidget />
                        </div>
                        <div v-show="bgMode === 'gradient'">
                            <ColorPicker v-model:gradientColor="gradientColor" useType="gradient" format="hex" isWidget />
                        </div>
                    </div>
                </div>

            </div>
        </footer>

    </div>


    <!-- Модалка выбора шаблона -->
    <div class="modal fade" id="templateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">


                <div class="modal-body">

                    <div class="template-list ">
                        <div
                            v-for="tpl in templateStore.templates"
                            :key="tpl.id"
                            class="template-card"
                            @click="selectTemplate(tpl.id)"
                        >
                            <i :class="['fa', tpl.icon, 'template-icon']"></i>
                            <div class="template-title">{{ tpl.title }}</div>
                        </div>
                    </div>

                    <div v-if="templateStore.loading" class="loading">
                        Создаём доску...
                    </div>

                    <!-- Кнопка "У меня уже есть доска" -->
                    <div class="join-board-section mt-4 pt-3 border-top text-center">
                        <div v-if="!showJoinInput">
                            <button class="btn-join-existing" @click="showJoinInput = true">
                                <i class="fa-solid fa-link me-2"></i>
                                У меня уже есть доска
                            </button>
                        </div>
                        <div v-else class="join-form mt-2">
                            <p class="text-muted small mb-2">Введите ссылку или ключ своей доски</p>
                            <div class="input-group">
                                <input
                                    v-model="joinKey"
                                    type="text"
                                    class="form-control"
                                    placeholder="Например: 550e8400-... или https://..."
                                    @keyup.enter="handleJoinBoard"
                                    :disabled="loadingJoin"
                                    autofocus
                                />
                                <button
                                    class="btn btn-primary"
                                    @click="handleJoinBoard"
                                    :disabled="!joinKey.trim() || loadingJoin"
                                >
                                    <span v-if="loadingJoin" class="spinner-border spinner-border-sm"></span>
                                    <i v-else class="fa-solid fa-arrow-right"></i>
                                    Перейти
                                </button>
                            </div>
                            <button class="btn btn-link btn-sm text-muted mt-1" @click="showJoinInput = false">Отмена</button>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="installPwaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Установить приложение</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p>Вы можете установить Kanban как приложение и запускать его прямо с рабочего стола.</p>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Позже</button>
                    <button class="btn btn-primary" @click="installPWA">Установить</button>
                </div>

            </div>
        </div>
    </div>

</template>

<script>
import KanbanBoard from '@/Components/Kanban/KanbanBoard.vue'
import {useKanbanStore} from "@/stores/useKanbanStore.js";
import {useBoardTemplateStore} from "@/stores/useBoardTemplateStore.js";
import { ColorPicker } from "vue3-colorpicker";
import "vue3-colorpicker/style.css";

export default {
    props: {
        board: Object,
        vapidPublicKey: String
    },
    components: {KanbanBoard, ColorPicker},
    data() {
        return {
            store: useKanbanStore(),
            templateStore: useBoardTemplateStore(),
            progress: 0,
            progressTimer: null,
            refreshTimer: null,
            need_request_updates: false,
            showJoinInput: false,
            joinKey: '',
            loadingJoin: false,
            showColorPicker: false,
            bgMode: (localStorage.getItem('board_bg_color') || '').includes('gradient') ? 'gradient' : 'solid',
            solidColor: (!((localStorage.getItem('board_bg_color') || '').includes('gradient'))) ? (localStorage.getItem('board_bg_color') || '#4f46e5') : '#4f46e5',
            gradientColor: ((localStorage.getItem('board_bg_color') || '').includes('gradient')) ? localStorage.getItem('board_bg_color') : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        }
    },

    watch: {
        solidColor(newVal) {
            if (this.bgMode === 'solid') this.applyBgColor(newVal);
        },
        gradientColor(newVal) {
            if (this.bgMode === 'gradient') this.applyBgColor(newVal);
        },
        bgMode(newMode) {
            if (newMode === 'solid') this.applyBgColor(this.solidColor);
            if (newMode === 'gradient') this.applyBgColor(this.gradientColor);
        },
        'need_request_updates': {
            handler(newData) {

                if (this.need_request_updates)
                    this.updateTimer()
                else {
                    this.progress = 0

                    clearInterval(this.progressTimer)
                    clearInterval(this.refreshTimer)
                }
            },
            deep: true,
        }
    },

    async mounted() {

        this.need_request_updates = JSON.parse(localStorage.getItem("need_request_updates") || 'false'  )

        // Если колонок нет → показываем модалку выбора шаблона
        if (!this.board.columns || this.board.columns.length === 0) {
            await this.templateStore.loadTemplates()

            const modal = new bootstrap.Modal(document.getElementById('templateModal'), {
                backdrop: 'static', keyboard: false
            })
            modal.show()
        }

        this.initPush()

        if (this.need_request_updates) {
            this.updateTimer()
        }


        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data?.type === 'request-update') {
                this.updateTimer()
            }
        });


    },

    methods: {
        applyBgColor(newColor) {
            localStorage.setItem('board_bg_color', newColor);
            document.body.style.setProperty('background', newColor, 'important');
        },
        updateTimer() {
            this.progress = 0

            clearInterval(this.progressTimer)
            clearInterval(this.refreshTimer)

            this.progressTimer = setInterval(() => {
                this.progress += 100 / 600
                if (this.progress >= 100) {
                    this.progress = 100
                }
            }, 100)

            this.refreshTimer = setInterval(async () => {
                await this.store.loadBoard(this.board.uuid)
                this.progress = 0
            }, 60000)
        },
        async selectTemplate(templateId) {
            await this.templateStore.applyTemplate(this.board.uuid, templateId)

            // Закрываем модалку
            const modal = bootstrap.Modal.getInstance(document.getElementById('templateModal'))
            modal.hide()

            // Обновляем доску
            await this.store.loadBoard(this.board.uuid)
        },

        installPWA() {
            window.installPWA()
        },

        async handleJoinBoard() {
            if (!this.joinKey.trim()) return
            this.loadingJoin = true
            try {
                const { data } = await axios.post('/board/join', { key: this.joinKey })
                if (data.redirect_url) window.location.href = data.redirect_url
            } catch (e) {
                alert('Не удалось. Проверьте ключ или ссылку.')
            } finally {
                this.loadingJoin = false
            }
        },

        async initPush() {

          /*  const oldRegistration = await navigator.serviceWorker.ready
            const oldSubscription =  await oldRegistration.pushManager.getSubscription()
            if (oldSubscription) {
                oldSubscription.unsubscribe()
                console.log('Старая подписка удалена')
            }
*/
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                console.warn('Push notifications not supported')
                return
            }

            const registration = await navigator.serviceWorker.register('/sw.js')

            const permission = await Notification.requestPermission()
            if (permission !== 'granted') {
                console.warn('User denied notifications')
                return
            }

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.vapidPublicKey,

            })

            await axios.post('/api/push/subscribe', {
                subscription,
                board_uuid: this.board.uuid
            })
        }
    }
}
</script>

<style>
.refresh-progress {
    position: fixed;
    top: 0;
    left: 0;
    height: 3px;
    background: #0d6efd; /* Bootstrap primary */
    transition: width 0.1s linear;
    z-index: 9999;
}

.template-list {
    display: grid;
    grid-template-columns: 1fr; /* 👈 мобилка: 1 колонка */
    gap: 12px;
}

.template-card {
    cursor: pointer;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid #eee;
    text-align: center;
    transition: 0.2s;
}

.template-card:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
}

.template-icon {
    font-size: 24px;
    margin-bottom: 8px;
}

.template-title {
    font-size: 14px;
}

/* 👇 планшеты */
@media (min-width: 768px) {
    .template-list {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* 👇 десктоп: 3 в ряд */
@media (min-width: 1024px) {
    .template-list {
        grid-template-columns: repeat(3, 1fr);
    }
}

.loading {
    margin-top: 20px;
    text-align: center;
    font-size: 18px;
}

.kanbancrm-logo {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-weight: 700;
    letter-spacing: 0.04em;
    font-size: 36px;
    background:white;
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    text-transform: none;

}

.join-board-section {
    border-top-color: #e9ecef !important;
    position: sticky;
    bottom: 20px;
    background-color: white;
}

.btn-join-existing {
    background: none;
    border: 1px dashed #ced4da;
    color: #6c757d;
    padding: 8px 22px;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.25s ease;
}

.btn-join-existing:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: rgba(13, 110, 253, 0.04);
}

.join-form .input-group {
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}

</style>
