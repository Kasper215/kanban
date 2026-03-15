<template>
    <div class="card-chat">


        <div
            ref="chatHistory"
            class="chat-history border rounded p-2 mb-3" style="max-height:300px; overflow-y:auto;">
            <template v-if="messages.length > 0">
                <div
                    v-for="msg in messages"
                    :key="msg.id"
                    class="chat-message"
                    :class="bubbleClass(msg.sender_type)"
                >
                    <div class="bubble">
                        <div
                            style="word-break: break-all;"
                            class="text">{{ msg.message }}</div>
                        <small class="date">{{ formatDate(msg.created_at) }}</small>
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="text-center text-muted py-5">
                    <i class="fa-solid fa-face-frown fa-2x mb-2"></i>
                    <div>Сообщений ещё нет</div>
                </div>
            </template>
        </div>
        <!-- Форма отправки -->
        <form @submit.prevent="sendMessage">
            <div class="input-group">
                <div class="form-floating">
                    <input
                        v-model="newMessage"
                        type="text"
                        id="message"
                        class="form-control"
                        placeholder="Введите сообщение..."
                    />
                    <label for="message">Сообщение</label>
                </div>
                <button class="btn btn-primary" type="submit">Отправить</button>
            </div>
        </form>
    </div>
</template>

<script>
import {useChatStore} from '@/stores/chat'

export default {
    props: {taskId: Number},
    data() {
        return {
            chatStore: useChatStore(),
            newMessage: ''
        }
    },
    computed:{
        messages(){
            return this.chatStore.messages|| []
        }
    },
    mounted() {
        this.chatStore.loadMessages(this.taskId)
    },
    methods: {
        formatDate(dateString) {
            const date = new Date(dateString)
            return new Intl.DateTimeFormat('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(date)
        },
        bubbleClass(type) {
            switch (type) {
                case 'manager':
                    return 'from-me'
                case 'system':
                    return 'system-msg'
                default:
                    return 'from-them'
            }
        },
        scrollToBottom() {
            const container = this.$refs.chatHistory
            if (container) {
                container.scrollTop = container.scrollHeight
            }
        },
        async sendMessage() {
            await this.chatStore.sendMessage(this.newMessage)
            this.newMessage = ''
            await this.$nextTick(() => this.scrollToBottom())
        }
    },
    updated() {
        // при любом обновлении сообщений прокручиваем вниз
        this.scrollToBottom()
    }
}
</script>


<style scoped>
.chat-message {
    display: flex;
    margin-bottom: 0.5rem;
}

.chat-message.from-me {
    justify-content: flex-end;
}

.chat-message.from-them {
    justify-content: flex-start;
}

.chat-message.system-msg {
    justify-content: flex-end;
}

.bubble {
    max-width: 70%;
    padding: 0.5rem 0.75rem;
    border-radius: 1rem;
    position: relative;
}

.from-me .bubble {
    background-color: #0d6efd;
    color: #fff;
}

.from-them .bubble {
    background-color: #e9ecef;
    color: #212529;
}

.system-msg .bubble {
    background-color: #198754;
    color: #fff;
}

.date {
    font-size: 0.75rem;
    opacity: 0.7;
    display: block;
    margin-top: 0.25rem;
}
</style>
