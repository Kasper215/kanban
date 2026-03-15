// stores/chat.js
import { defineStore } from 'pinia'
import axios from 'axios'

export const useChatStore = defineStore('chat', {
    state: () => ({
        messages: [],
        cardId: null,
        loading: false,
        error: null
    }),
    actions: {
        async loadMessages(cardId) {
            this.loading = true
            this.error = null
            this.cardId = cardId
            try {
                const { data } = await axios.get(`/api/cards/${cardId}/messages`)
                this.messages = data
            } catch (e) {
                this.error = 'Ошибка загрузки сообщений'
                console.error(e)
            } finally {
                this.loading = false
            }
        },
        async sendMessage(text) {
            if (!text || !this.cardId) return
            try {
                const { data } = await axios.post(`/api/cards/${this.cardId}/send`, {
                    message: text
                })
                // добавляем новое сообщение в список
                this.messages.push(data)
            } catch (e) {
                this.error = 'Ошибка отправки сообщения'
                console.error(e)
            }
        },
        async markAsRead(messageId) {
            if (messageId) return
            try {
                const { data } = await axios.post(`/api/cards/mark-as-read/${messageId}`)
                // добавляем новое сообщение в список
                this.messages.push(data)
            } catch (e) {
                this.error = 'Ошибка отправки сообщения'
                console.error(e)
            }
        }
    }
})
