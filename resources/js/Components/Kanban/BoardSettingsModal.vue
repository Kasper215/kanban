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

                        <div class="input-group mb-3">

                            <div class="form-floating">
                                <input
                                    v-model="settings.webhook_url"
                                    type="text"
                                    class="form-control"
                                    id="webhookUrl"
                                    placeholder="Webhook URL"
                                />
                                <label for="webhookUrl">Webhook URL</label>
                            </div>
                            <button
                                :disabled="!settings.webhook_url||boardStore.loading"
                                @click="testWebhook"
                                type="button"
                                class="input-group-text btn btn-outline-primary"
                                id="basic-addon1">
                                <span v-if="!boardStore.loading">Проверить</span>
                                <template v-else>
                                <span class="spinner-border spinner-border-sm" role="status">
                                  <span class="visually-hidden">Loading...</span>
                                   </span> Проверяем...
                                </template>
                            </button>

                        </div>

                        <template v-if="boardStore.webhookTestResult">
                            <p class="alert alert-info mb-2">
                                {{ boardStore.webhookTestResult || '-' }}
                            </p>
                        </template>


                        <div class="input-group mb-3">

                            <div class="form-floating">
                                <input
                                    v-model="settings.email_for_notification"
                                    type="email"
                                    class="form-control"
                                    id="emailNotification"
                                    placeholder="Email для уведомлений"
                                />
                                <label for="emailNotification">Email для уведомлений</label>
                            </div>
                            <button
                                :disabled="!settings.email_for_notification||boardStore.loading"
                                @click="testEmail"
                                type="button" class="input-group-text btn btn-outline-primary"
                                id="basic-addon1">
                                <span v-if="!boardStore.loading">Проверить</span>
                                <template v-else>
                                <span class="spinner-border spinner-border-sm" role="status">
                                  <span class="visually-hidden">Loading...</span>
                                   </span> Проверяем...
                                </template>
                            </button>

                        </div>

                        <template v-if="boardStore.emailTestResult">
                            <p class="alert alert-info mb-2">
                                {{ boardStore.emailTestResult || '-' }}
                            </p>
                        </template>

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
        async testWebhook() {
            await this.boardStore.testWebhook({
                url: this.settings.webhook_url || null
            })
        },
        async testEmail() {
            await this.boardStore.testEmail({
                email: this.settings.email_for_notification || null
            })
        },
        async submit() {
            this.$emit('save', this.settings)
            this.$emit("close")
        }
    },

}
</script>
