import { defineStore } from 'pinia'
import axios from 'axios'

export const useTaskAttachmentsStore = defineStore('taskAttachments', {
    state: () => ({
        attachments: [],
        loading: false,
    }),

    actions: {
        async fetch(taskId) {
            this.loading = true
            const { data } = await axios.get(`/api/task/${taskId}/attachments`)
            this.attachments = data || []
            this.loading = false
        },

        async upload(taskId, files) {
            const form = new FormData()
            files.forEach(f => form.append('files[]', f))

            const { data } = await axios.post(
                `/api/task/${taskId}/attachments`,
                form,
                { headers: { 'Content-Type': 'multipart/form-data' } }
            )

            this.attachments = data
            return data
        },

        async remove(taskId, path) {
            const { data } = await axios.delete(`/api/task/${taskId}/attachments`, {
                data: { path }
            })
            this.attachments = data
            return data
        }
    }
})
