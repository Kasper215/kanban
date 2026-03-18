import {defineStore} from 'pinia'
import axios from 'axios'
import {detectChanges, notifyChange} from './utils/boardChanges'
import {apiRequest} from '@/stores/utils/api.js'


export const useKanbanStore = defineStore('kanban', {
    state: () => ({
        board: null,
        columns: [],
        tags: [],
        loading: false,
        taskPagination: {},
        error: null,

        webhookTestResult: null,
        emailTestResult: null,
    }),

    getters: {
        getColumnById: (state) => (id) => {
            return state.columns.find(c => c.id === id)
        },

        getTaskById: (state) => (id) => {
            for (const col of state.columns) {
                const task = col.tasks.find(t => t.id === id)
                if (task) return task
            }
            return null
        }
    },

    actions: {
        async testWebhook(payload = {
            url: null
        }) {
            this.webhookTestResult = null
            this.loading = true
            this.error = null
            try {
                const {data} = await axios.post('/api/test/webhook', payload)
                this.webhookTestResult = data
                return data
            } catch (e) {
                this.error = 'Ошибка теста вебхука'
                console.error(e)
            } finally {
                this.loading = false
            }
        },

        async testEmail(payload = {
            email: null,
        }) {
            this.emailTestResult = null
            this.loading = true
            this.error = null
            try {
                const {data} = await axios.post('/api/test/email', payload)
                this.emailTestResult = data
                return data
            } catch (e) {
                this.error = 'Ошибка теста email'
                console.error(e)
            } finally {
                this.loading = false
            }
        },
        async saveConfig(uuid, config) {
            const {data} = await apiRequest('post', `/api/boards/${uuid}/config`, config)
            this.board.config = data
        },
        async renameBoard(uuid, title) {
            const {data} = await apiRequest('put', `/api/boards/${uuid}`, {title})
            this.board.title = data.title
        },

        async loadTasks(columnId) {
            let page = 1
            const info = this.taskPagination[columnId]
            if (info && info.page < info.lastPage) {
                page = (info.page || 1) + 1
            }

            const {data} = await axios.get(`/api/columns/${columnId}/tasks?page=${page}`)
            const column = this.getColumnById(columnId)
            if (!column) return

            column.tasks = [...column.tasks, ...data.data]
            this.taskPagination[columnId] = {
                page: data.current_page,
                lastPage: data.last_page
            }
        },
        async createColumn(uuid, title) {
            const {data} = await apiRequest('post', `/api/boards/${uuid}/columns`, {title})
            this.columns.push({...data, tasks: []})
            return data
        },


        // Загрузка всей доски
        async loadBoard(uuid) {
            this.loading = true
            this.error = null

            // сохраняем старые данные
            const oldBoard = JSON.parse(JSON.stringify(this.board))
            const oldColumns = JSON.parse(JSON.stringify(this.columns))

            try {
                const {data} = await axios.get(`/api/boards/${uuid}`)

                detectChanges(oldBoard, oldColumns, data, notifyChange)

                this.board = data
                this.columns = data.columns
            } catch (e) {
                this.error = 'Не удалось загрузить доску'
                console.error(e)
            } finally {
                this.loading = false
            }
        },
        async markTaskViewed(taskId) {
            await apiRequest('post', `/api/tasks/${taskId}/view`)
            const task = this.columns.flatMap(col => col.tasks).find(t => t.id === taskId)
            if (task) task.last_viewed_at = new Date().toISOString()
        },


        async updateColumn(columnId, payload) {
            const {data} = await apiRequest('put', `/api/columns/${columnId}`, payload)
            const idx = this.columns.findIndex(c => c.id === columnId)
            if (idx !== -1) this.columns[idx] = data
        },

        async deleteColumn(columnId) {
            await apiRequest('delete', `/api/columns/${columnId}`)
            this.columns = this.columns.filter(c => c.id !== columnId)
        },

        async clearBoard() {
            const ids = this.columns.map(c => c.id)
            for (const id of ids) {
                await this.deleteColumn(id)
            }
        },

        async createTask(uuid, task) {
            this.loading = true
            const {data} = await apiRequest('post', `/api/boards/${uuid}/tasks`, task)
            const column = this.getColumnById(data.column_id)
            if (column) column.tasks.unshift(data)
            this.loading = false
            return data
        },

        async updateTask(task) {
            this.loading = true
            const {data} = await apiRequest('put', `/api/tasks/${task.id}`, task)
            const column = this.getColumnById(data.column_id)
            if (column) {
                const idx = column.tasks.findIndex(t => t.id === data.id)
                if (idx !== -1) column.tasks[idx] = data
            }
            this.loading = false
            return data
        },

        async deleteTask(taskId) {
            await apiRequest('delete', `/api/tasks/${taskId}`)
            this.columns.forEach(col => {
                col.tasks = col.tasks.filter(t => t.id !== taskId)
            })
        },

        async moveTask(taskId, toColumnId, newPosition = 0) {
            if (taskId <= 0 || !taskId)
                return

            await apiRequest('post', '/api/tasks/move', {
                task_id: taskId,
                to_column_id: toColumnId,
                position: newPosition
            })

            let task = null, fromColumn = null
            this.columns.forEach(col => {
                const found = col.tasks.find(t => t.id === taskId)
                if (found) {
                    task = found
                    fromColumn = col
                }
            })
            if (!task || !fromColumn) return

            fromColumn.tasks = fromColumn.tasks.filter(t => t.id !== taskId)
            const toColumn = this.getColumnById(toColumnId)
            if (toColumn) toColumn.tasks.push({...task, column_id: toColumnId})
        },
        async renameColumn(columnId, newTitle) {
            const {data} = await apiRequest('put', `/api/columns/${columnId}`, {title: newTitle})
            const idx = this.columns.findIndex(c => c.id === columnId)
            if (idx !== -1) this.columns[idx].title = data.title
            return data
        },

        async reorderTask(taskId, targetTaskId, columnId) {
            const column = this.getColumnById(columnId)
            if (!column) return
            const tasks = [...column.tasks]
            const fromIndex = tasks.findIndex(t => t.id === taskId)
            const toIndex = tasks.findIndex(t => t.id === targetTaskId)
            const [moved] = tasks.splice(fromIndex, 1)
            tasks.splice(toIndex, 0, moved)
            column.tasks = tasks

            await apiRequest('put', `/api/columns/${columnId}/tasks/reorder`, {
                order: tasks.map(t => t.id)
            })
        },

        async duplicateTask(task) {
            const {data} = await apiRequest('post', `/api/tasks/${task.id}/duplicate`)
            const column = this.getColumnById(data.column_id)
            if (column) column.tasks.push(data)
            return data
        },

        async loadTags(uuid) {
            const {data} = await axios.get(`/api/boards/${uuid}/tags`)
            this.tags = data
        },

        async createTag(uuid, name, color = '#999999') {
            const {data} = await axios.post(`/api/boards/${uuid}/tags`, {name, color})
            this.tags.push(data)
            return data
        },

        async moveColumn(fromIndex, toIndex) {
            const cols = [...this.columns]
            const moved = cols.splice(fromIndex, 1)[0]
            cols.splice(toIndex, 0, moved)
            this.columns = cols
            const order = this.columns.map(c => c.id)
            return await apiRequest('put', `/api/boards/${this.board.uuid}/columns/reorder`, {order})
        },

        async deleteTag(tagId) {
            await axios.delete(`/api/tags/${tagId}`)
            this.tags = this.tags.filter(t => t.id !== tagId)
        }
    }
})
