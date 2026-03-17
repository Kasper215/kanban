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
                <p class="small text-white">Сделано с <i class="fa-solid fa-heart text-danger"></i> в мире АйТи</p>

            </div>
        </footer>

    </div>


    <!-- Модалка выбора шаблона -->
    <div class="modal fade" id="templateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">


                <div class="modal-body">

                    <div class="template-list">
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

                </div>

            </div>
        </div>
    </div>

    <!-- Твоя PWA-модалка -->
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

export default {
    props: {
        board: Object,
        vapidPublicKey: String
    },
    components: {KanbanBoard},
    data() {
        return {
            store: useKanbanStore(),
            templateStore: useBoardTemplateStore(),
            progress: 0,
            progressTimer: null,
            refreshTimer: null,
            need_request_updates: false,
        }
    },

    watch: {
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
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: center;
}

.template-card {
    width: 180px;
    padding: 20px;
    border-radius: 12px;
    background: #f7f7f7;
    text-align: center;
    cursor: pointer;
    transition: 0.2s;
    border: 1px solid #ddd;
}

.template-card:hover {
    background: #eaeaea;
    transform: translateY(-3px);
}

.template-icon {
    font-size: 32px;
    margin-bottom: 10px;
}

.template-title {
    font-size: 16px;
    font-weight: 600;
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
    font-size: 42px;

    /* градиент по тексту */
    background:white;
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;

    /* лёгкий tech‑вайб */
    text-transform: none;
    text-shadow: 0 0 12px rgba(186, 104, 255, 0.35);
}


</style>
