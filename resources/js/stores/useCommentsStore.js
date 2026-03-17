import { defineStore } from 'pinia'
import axios from 'axios'

export const useCommentsStore = defineStore('comments', {
    state: () => ({
        comments: [],
        loading: false,
    }),

    actions: {
        async fetchComments(taskId) {
            this.loading = true
            const { data } = await axios.get(`/api/task/${taskId}/comments`)
            this.comments = data
            this.loading = false
        },

        async addComment(taskId, payload) {
            const form = new FormData()

            if (payload.text) form.append('text', payload.text)
            if (payload.author) form.append('author', payload.author)

            if (payload.files?.length) {
                payload.files.forEach(file => form.append('files[]', file))
            }

            const { data } = await axios.post(`/api/task/${taskId}/comment`, form, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })

            this.comments.push(data)
        },

        async deleteComment(commentId) {
            await axios.delete(`/api/comments/${commentId}`)
            this.comments = this.comments.filter(c => c.id !== commentId)
        },

        async removeAttachment(commentId, path) {
            const { data } = await axios.delete(`/api/comments/${commentId}/attachment`, {
                data: { path }
            })
            // Обновляем коммент в списке
            const idx = this.comments.findIndex(c => c.id === commentId)
            if (idx !== -1) {
                this.comments[idx] = data
            }
        }
    }
})
