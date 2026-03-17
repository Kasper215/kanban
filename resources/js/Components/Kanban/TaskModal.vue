<script setup>
import CardUser from './Cards/CardUser.vue'
import CardOrder from './Cards/CardOrder.vue'
import CardText from './Cards/CardText.vue'
import CardFinance from './Cards/CardFinance.vue'
import CardDevelopment from './Cards/CardDevelopment.vue'
import CommentsList from "@/Components/Kanban/Comments/CommentsList.vue";
import CommentAddForm from "@/Components/Kanban/Comments/CommentAddForm.vue";
import TaskAttachmentsList from "@/Components/Kanban/Tasks/TaskAttachmentsList.vue";
import TaskAttachmentsUpload from "@/Components/Kanban/Tasks/TaskAttachmentsUpload.vue";
</script>
<template>
    <div class="modal modal-lg fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">

                <!-- HEADER -->
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ task ? 'Редактировать задачу' : 'Новая задача' }}
                    </h5>
                    <button type="button" class="btn-close" @click="$emit('close')"></button>
                </div>

                <!-- BODY -->
                <div class="modal-body">


                    <form @submit.prevent="submit">

                        <!-- Название -->
                        <div class="form-floating mb-3">
                            <input
                                type="text"
                                class="form-control"
                                id="taskTitle"
                                placeholder="Название"
                                v-model="local.title"
                                required
                            >
                            <label for="taskTitle">Название</label>
                        </div>

                        <!-- Описание -->
                        <div class="form-floating mb-3">
                            <textarea
                                class="form-control"
                                id="taskDescription"
                                placeholder="Описание"
                                style="height: 120px"
                                v-model="local.description"
                            ></textarea>
                            <label for="taskDescription">Описание</label>
                        </div>

                        <template v-if="task">
                            <CardUser v-if="task.type === 1" :card="task"/>
                            <CardOrder v-if="task.type === 2" :card="task"/>
                            <CardText v-if="task.type === 3" :card="task"/>
                            <CardFinance v-if="task.type === 4" :card="task"/>
                            <CardDevelopment v-if="task.type === 5" :card="task"/>
                        </template>


                        <!-- Приоритет -->
                        <div class="form-floating mb-3">
                            <select
                                class="form-select"
                                id="taskPriority"
                                v-model="local.priority"
                            >
                                <option value="low">Низкий</option>
                                <option value="medium">Средний</option>
                                <option value="high">Высокий</option>
                            </select>
                            <label for="taskPriority">Приоритет</label>
                        </div>

                        <!-- Дата -->
                        <div class="form-floating mb-3">
                            <input
                                type="date"
                                class="form-control"
                                id="taskDueDate"
                                placeholder="Дата"
                                v-model="local.dueDate"
                            >
                            <label for="taskDueDate">Срок выполнения</label>
                        </div>

                        <!-- Теги -->
                        <div class="mb-3">
                            <label class="form-label">Теги</label>

                            <div class="d-flex flex-wrap gap-1">
                                <label
                                    v-for="tag in store.tags"
                                    :key="tag.id"
                                    class="badge d-flex align-items-center px-2 py-1 border border-secondary text-white"
                                    :style="{ background: tag.color, cursor: 'pointer' }"
                                >
                                    <input
                                        type="checkbox"
                                        :value="tag.id"
                                        v-model="local.tagIds"
                                        class="form-check-input me-2"
                                    >
                                    {{ tag.name }}
                                </label>
                            </div>
                        </div>

                        <!-- Новый тег -->
                        <div class="mb-3">
                            <label class="form-label">Создать новый тег</label>

                            <div class="d-flex gap-2">
                                <input
                                    class="form-control"
                                    v-model="newTagName"
                                    placeholder="Название тега"
                                >
                                <input
                                    v-model="newTagColor"
                                    type="color"
                                    class="form-control form-control-color"
                                    style="width: 60px;"
                                >
                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    @click="addTag"
                                >
                                    Добавить
                                </button>
                            </div>
                        </div>


                        <div class="mb-3">
                            <label class="form-label">Категории</label>

                            <div class="d-flex flex-wrap gap-2">
                                <label
                                    v-for="label in availableLabels"
                                    :key="label"
                                    class="badge d-flex align-items-center px-2 py-1 border border-primary text-primary"
                                    style="cursor: pointer;"
                                >
                                    <input
                                        type="checkbox"
                                        class="form-check-input me-2"
                                        :value="label"
                                        v-model="local.labels"
                                    >
                                    {{ label }}
                                </label>
                            </div>
                        </div>


                        <!-- ПОДЗАДАЧИ -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Подзадачи
                                <span class="text-muted ms-2">
            {{ completedSubtasks }} / {{ local.subtasks.length }}
        </span>
                            </label>

                            <!-- Добавление новой подзадачи -->
                            <div class="input-group mb-2">
                                <input
                                    type="text"
                                    class="form-control"
                                    placeholder="Новая подзадача"
                                    v-model="newSubtask"
                                    @keyup.enter="addSubtask"
                                >
                                <button class="btn btn-outline-primary" type="button" @click="addSubtask">
                                    Добавить
                                </button>
                            </div>

                            <!-- Список подзадач -->
                            <ul class="list-group" ref="subtasksList">
                                <li
                                    v-for="(sub, index) in local.subtasks"
                                    :key="sub.id"
                                    class="list-group-item d-flex align-items-center"
                                    :class="{ 'bg-success bg-opacity-10': sub.done }"
                                >
                                    <!-- Drag handle -->
                                    <span class="drag-handle me-3" style="cursor: grab;">☰</span>

                                    <!-- Чекбокс -->
                                    <input
                                        type="checkbox"
                                        class="form-check-input me-2"
                                        v-model="sub.done"
                                    >

                                    <!-- Текст -->
                                    <span
                                        class="flex-grow-1"
                                        :class="{ 'text-decoration-line-through text-success': sub.done }"
                                    >
                {{ sub.text }}
            </span>

                                    <!-- Удаление -->
                                    <button
                                        class="btn btn-sm btn-outline-danger ms-2"
                                        @click="removeSubtask(index)"
                                    >
                                        ✕
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <template v-if="local.id">
                            <h6 class="fw-bold mb-3">Вложения к задаче</h6>
                            <TaskAttachmentsUpload
                                :taskId="local.id"
                                @uploaded="(attachments) => local.attachments = attachments"
                            />
                            <TaskAttachmentsList :taskId="local.id" :showDelete="true"/>
                            <h6 class="fw-bold my-3">Комментарии к задаче</h6>
                            <CommentAddForm :taskId="local.id"/>
                            <CommentsList :taskId="local.id"/>
                        </template>

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
import {useKanbanStore} from '@/stores/useKanbanStore'
import Sortable from 'sortablejs'

export default {
    props: {
        task: Object,
        columnId: Number
    },
    computed: {
        completedSubtasks() {
            return this.local.subtasks.filter(s => s.done).length
        }
    },
    data() {
        const store = useKanbanStore()

        return {

            store,
            availableLabels: [
                'development',
                'bug',
                'client',
                'urgent',
                'design',
                'finance'
            ],

            local: this.task
                ? {
                    ...this.task,
                    tagIds: this.task.tags?.map(t => t.id) ?? [],
                    subtasks: this.task.subtasks ?? []
                }
                : {
                    title: '',
                    description: '',
                    priority: 'low',
                    dueDate: '',
                    tagIds: [],
                    labels: ['development'], // дефолт
                    columnId: this.columnId,
                    subtasks: []
                },

            newSubtask: '',
            newTagName: '',
            newTagColor: '#999999'
        }
    },
    mounted() {
        this.initSortable()
    },

    methods: {
        initSortable() {
            if (!this.$refs.subtasksList) return

            Sortable.create(this.$refs.subtasksList, {
                animation: 150,
                handle: '.drag-handle',
                onEnd: (evt) => {
                    const moved = this.local.subtasks.splice(evt.oldIndex, 1)[0]
                    this.local.subtasks.splice(evt.newIndex, 0, moved)
                }
            })
        },

        addSubtask() {
            if (!this.newSubtask.trim()) return

            this.local.subtasks.push({
                id: Date.now(),
                text: this.newSubtask.trim(),
                done: false
            })

            this.newSubtask = ''
        },

        removeSubtask(index) {
            this.local.subtasks.splice(index, 1)
        },

        async addTag() {
            if (!this.newTagName.trim()) return

            const tag = await this.store.createTag(
                this.store.board.uuid,
                this.newTagName,
                this.newTagColor
            )

            this.local.tagIds.push(tag.id)

            this.newTagName = ''
            this.newTagColor = '#999999'
        },

        async submit() {
            this.local.columnId = this.columnId
            this.local.tagIds = [...this.local.tagIds]

            if (!this.local.id) {
                await this.store.createTask(this.store.board.uuid, this.local)
                this.$emit('add-task', this.local)
            } else {
                await this.store.updateTask(this.local)
                this.$emit('edit-task', this.local)
            }


            this.$emit("close")
        }
    }
}
</script>
