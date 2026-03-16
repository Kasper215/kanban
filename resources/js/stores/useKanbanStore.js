import {defineStore} from 'pinia'
import axios from 'axios'
import { detectChanges, notifyChange } from './utils/boardChanges'

export const useKanbanStore = defineStore('kanban', {
    state: () => ({
        board: null,
        columns: [],
        tags: [],
        loading: false,
        taskPagination: {},
        error: null
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
        async renameBoard(uuid, title) {
            const { data } = await axios.put(`/api/boards/${uuid}`, {
                title
            })

            this.board.title = data.title
        },

        async loadTasks(columnId) {
            let page = 1
            const info = this.taskPagination[columnId]
            if (info && info.page < info.lastPage) {
                console.log(info.page, info.lastPage)
                page = (info.page || 1) + 1
            }

            const {data} = await axios.get(`/api/columns/${columnId}/tasks?page=${page}`)

            const column = this.getColumnById(columnId)
            if (!column) return

            column.tasks =[... column.tasks, ...data.data]

            this.taskPagination[columnId] = {
                page: data.current_page,
                lastPage: data.last_page
            }
        },
        async createColumn(uuid, title) {
            try {
                const {data} = await axios.post(`/api/boards/${uuid}/columns`, {
                    title
                })

                // Добавляем колонку в состояние
                this.columns.push({
                    ...data,
                    tasks: [] // новая колонка всегда пустая
                })

                return data
            } catch (e) {
                console.error('Ошибка при создании колонки:', e)
                throw e
            }
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
            try {
                await axios.post(`/api/tasks/${taskId}/view`)

                // обновляем локально
                const task = this.columns
                    .flatMap(col => col.tasks)
                    .find(t => t.id === taskId)

                if (task) {
                    task.last_viewed_at = new Date().toISOString()
                }

            } catch (e) {
                console.error('Ошибка при отметке просмотра задачи', e)
            }
        },


        async updateColumn(columnId, payload) {
            try {
                const {data} = await axios.put(`/api/columns/${columnId}`, payload)
                const idx = this.columns.findIndex(c => c.id === columnId)
                if (idx !== -1) this.columns[idx] = data
            } catch (e) {
                console.error(e)
            }
        },

        async deleteColumn(columnId) {
            try {
                await axios.delete(`/api/columns/${columnId}`)
                this.columns = this.columns.filter(c => c.id !== columnId)
            } catch (e) {
                console.error(e)
            }
        },

        async clearBoard() {
            // мягкий вариант — просто удаляем все колонки по одной
            const ids = this.columns.map(c => c.id)
            for (const id of ids) {
                await this.deleteColumn(id)
            }
        },

        async createTask(uuid, task) {
            try {
                const {data} = await axios.post(`/api/boards/${uuid}/tasks`, {
                    column_id: task.columnId,
                    title: task.title,
                    description: task.description,
                    priority: task.priority,
                    due_date: task.dueDate,
                    labels: task.labels ?? [],
                    tag_ids: task.tagIds ?? [],
                    subtasks: task.subtasks ?? []
                })

                const column = this.getColumnById(data.column_id)
                if (column) column.tasks.unshift(data)


                return data
            } catch (e) {
                console.error('Ошибка при создании задачи:', e)
                throw e
            }
        },
        async updateTask(task) {
            try {
                const {data} = await axios.put(`/api/tasks/${task.id}`, {
                    column_id: task.columnId,
                    title: task.title,
                    description: task.description,
                    priority: task.priority,
                    due_date: task.dueDate,
                    labels: task.labels ?? [],
                    tag_ids: task.tagIds ?? [],
                    subtasks: task.subtasks ?? []
                })

                const column = this.getColumnById(data.column_id)
                if (!column) return data

                const idx = column.tasks.findIndex(t => t.id === data.id)
                if (idx !== -1) column.tasks[idx] = data

                return data
            } catch (e) {
                console.error('Ошибка при обновлении задачи:', e)
                throw e
            }
        },

        async deleteTask(taskId) {
            try {
                await axios.delete(`/api/tasks/${taskId}`)
                this.columns.forEach(col => {
                    col.tasks = col.tasks.filter(t => t.id !== taskId)
                })
            } catch (e) {
                console.error(e)
            }
        },

        // Перемещение задачи между колонками (drag & drop)
        async moveTask(taskId, toColumnId, newPosition = 0) {
            try {
                await axios.post('/api/tasks/move', {
                    task_id: taskId,
                    to_column_id: toColumnId,
                    position: newPosition
                })

                // Локально обновляем без повторной загрузки
                let task = null
                let fromColumn = null

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
                if (!toColumn) return

                // пока просто пушим в конец
                toColumn.tasks.push({...task, column_id: toColumnId})
            } catch (e) {
                console.error(e)
            }
        },
        async renameColumn(columnId, newTitle) {
            try {
                const {data} = await axios.put(`/api/columns/${columnId}`, {
                    title: newTitle
                })

                const idx = this.columns.findIndex(c => c.id === columnId)
                if (idx !== -1) {
                    this.columns[idx].title = data.title
                }

                return data
            } catch (e) {
                console.error('Ошибка при переименовании колонки:', e)
                throw e
            }
        },
        async reorderTask(taskId, targetTaskId, columnId) {
            const column = this.getColumnById(columnId)
            if (!column) return

            const tasks = [...column.tasks]

            const fromIndex = tasks.findIndex(t => t.id === taskId)
            const toIndex = tasks.findIndex(t => t.id === targetTaskId)

            const [moved] = tasks.splice(fromIndex, 1)
            tasks.splice(toIndex, 0, moved)

            // обновляем порядок в состоянии
            column.tasks = tasks

            // отправляем новый порядок
            await axios.put(`/api/columns/${columnId}/tasks/reorder`, {
                order: tasks.map(t => t.id)
            })
        }
        ,
        async duplicateTask(task) {
            try {
                const {data} = await axios.post(`/api/tasks/${task.id}/duplicate`)

                const column = this.getColumnById(data.column_id)
                if (column) {
                    column.tasks.push(data)
                }

                return data
            } catch (e) {
                console.error('Ошибка при дублировании задачи:', e)
                throw e
            }
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

            // Отправляем новый порядок на сервер
            const order = this.columns.map(c => c.id)

           return await axios.put(`/api/boards/${this.board.uuid}/columns/reorder`, {
                order
            })
        },
        async deleteTag(tagId) {
            await axios.delete(`/api/tags/${tagId}`)
            this.tags = this.tags.filter(t => t.id !== tagId)
        }

    }
})
