<template>

    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">

                <!-- HEADER -->
                <div class="modal-header">
                    <h5 class="modal-title">
                        Настройки уведомлений
                    </h5>
                    <button type="button" class="btn-close" @click="$emit('close')"></button>
                </div>

                <!-- BODY -->
                <div class="modal-body">
                    <form @submit.prevent="submit">
                        <!-- Webhook URL -->
                        <div class="form-floating mb-2">
                            <input
                                v-model="settings.webhook_url"
                                type="text"
                                class="form-control"
                                id="webhookUrl"
                                placeholder="Webhook URL"
                            />
                            <label for="webhookUrl">Webhook URL</label>
                        </div>

                        <!-- Email -->
                        <div class="form-floating mb-2">
                            <input
                                v-model="settings.email_for_notification"
                                type="email"
                                class="form-control"
                                id="emailNotification"
                                placeholder="Email для уведомлений"
                            />
                            <label for="emailNotification">Email для уведомлений</label>
                        </div>

                        <!-- Checkbox -->
                        <div class="form-check mb-2">
                            <input
                                v-model="settings.need_email_notification"
                                type="checkbox"
                                class="form-check-input"
                                id="needEmailNotification"
                            />
                            <label class="form-check-label" for="needEmailNotification">
                                Отправлять уведомления на email
                            </label>
                        </div>

                    </form>
                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="$emit('close')">Отмена</button>
                    <button class="btn btn-primary" @click="submit">Сохранить</button>
                </div>

            </div>
        </div>
    </div>

</template>
<script>


import {useKanbanStore} from "@/stores/useKanbanStore.js";

export default {
    name: 'BoardSettings',

    data() {
        return {
            boardStore: useKanbanStore(),
            settings: {
                webhook_url: null,
                email_for_notification: null,
                need_email_notification: false,
                need_auto_request_updates: false,
            }
        }
    },
    mounted() {
        this.settings = {
            ...this.settings,
            ...this.boardStore.board?.config || null
        }
    },
    methods: {
        async submit() {
            this.$emit('save', this.settings)
            this.$emit("close")
        }
    },

}
</script>
