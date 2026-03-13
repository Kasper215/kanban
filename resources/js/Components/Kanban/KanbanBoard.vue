<template>
    <div class="container" data-board-root>
        <header class="d-flex justify-content-between align-items-center my-3 p-3">
            <div class="d-flex align-items-center gap-2">
                <!-- Просмотр -->
                <h2 v-if="!editingBoardTitle"
                    class="h3 m-0"
                    @dblclick="startEditingBoardTitle"
                >
                    <i class="fas fa-tasks"></i> {{ store.board?.title || initialBoard.title }}
                </h2>

                <!-- Редактирование -->
                <div v-else class="d-flex align-items-center gap-2">
                    <input
                        v-model="localBoardTitle"
                        class="form-control form-control-sm"
                        @keyup.enter="saveBoardTitle"
                        @keyup.esc="cancelBoardEditing"
                        autofocus
                    >
                    <button class="btn btn-sm btn-primary" @click="saveBoardTitle">✔</button>
                </div>
            </div>


            <div class="btn-group">

                <button class="btn btn-secondary" @click="copyLink">
                    <i class="fas fa-link"></i>
                </button>


                <button class="btn btn-primary d-flex align-items-center gap-2" @click="openTokenModal"><i
                    class="fa-solid fa-key"></i>
                </button>


                <button class="btn btn-secondary" @click="exportData">
                    <i class="fas fa-file-export"></i>
                </button>

                <button class="btn btn-primary" @click="openColumnModal">
                    <i class="fas fa-plus"></i>
                </button>

                <button class="btn btn-danger" @click="showDeleteModal = true">
                    <i class="fas fa-trash-alt"></i>
                </button>

            </div>

        </header>

        <div

            class="kanban-board d-flex gap-3 ">
            <div
                :key="column?.id||'column'+index"
                v-for="(column, index) in store.columns"
                class="kanban-column-wrapper">


                <KanbanColumn
                    :column="column"
                    @add-task="openTaskModal">
                    <template #head>
                        <button
                            draggable="true"
                            @dragstart="onDragStart(index)"
                            @dragover.prevent
                            @drop="onDrop(index)"
                            class="w-100 btn btn-primary mb-2">

                        </button>
                    </template>
                </KanbanColumn>

            </div>

        </div>

        <TaskModal
            v-if="showTaskModal"
            :task="editingTask"
            :column-id="currentColumnId"
            @close="closeTaskModal"
            @save="saveTask"
        />

        <ColumnModal
            v-if="showColumnModal"
            @close="closeColumnModal"
            @save="addColumn"
        />

        <ConfirmModal
            v-model:show="showDeleteModal"
            title="Удалить доску?"
            description="Это действие удалит все колонки и карточки."
            @accept="clearBoard"
            @reject="showDeleteModal = false"
        />

        <TokenModal
            v-if="showTokenModal" @close="showTokenModal = false"/>
    </div>
</template>
<script>
import {useKanbanStore} from '@/stores/useKanbanStore'
import KanbanColumn from './KanbanColumn.vue'
import TaskModal from './TaskModal.vue'
import ColumnModal from './ColumnModal.vue'
import ConfirmModal from "@/Components/Kanban/ConfirmModal.vue";
import TokenModal from '@/Components/Kanban/TokenModal.vue'


export default {
    components: {KanbanColumn, TaskModal, ColumnModal, ConfirmModal, TokenModal},
    props: {initialBoard: Object},

    data() {
        return {
            showTokenModal: false,
            showDeleteModal: false,
            showTaskModal: false,
            showColumnModal: false,
            editingTask: null,
            currentColumnId: null,
            dragIndex: null,
            store: useKanbanStore(),

            editingBoardTitle: false,
            localBoardTitle: this.initialBoard.title
        }
    },

    mounted() {
        this.store.columns = this.initialBoard.columns
        this.store.board = this.initialBoard

        /*   this.store.columns.forEach(col => {
               this.store.loadTasks(col.id)
           })
   */

    },

    methods: {
        openTokenModal() {
            this.showTokenModal = true
        },
        exportData() {
            window.open(`/api/boards/${this.initialBoard.id}/export`, '_blank')
        },
        onDragStart(index) {
            this.dragIndex = index
        },

        onDrop(index) {
            if (this.dragIndex === null) return
            this.store.moveColumn(this.dragIndex, index)
            this.dragIndex = null

        },
        openTaskModal(columnId, task = null) {
            this.currentColumnId = columnId
            this.editingTask = task

            if (task)
                if (!task.last_viewed_at) {
                    this.store.markTaskViewed(task.id)
                }

            this.showTaskModal = true
        },

        closeTaskModal() {
            this.showTaskModal = false
            this.editingTask = null
        },

        async saveTask(task) {

            if (task.id) {
                await this.updateTask(task)
            } else {
                await this.createTask(this.store.board.uuid, task)
            }

            this.closeTaskModal()
        },

        openColumnModal() {
            this.showColumnModal = true
        },

        closeColumnModal() {
            this.showColumnModal = false
        },

        addColumn(title) {
            this.store.createColumn(this.store.board.uuid, title)
            this.closeColumnModal()
        },

        clearBoard() {
            this.store.clearBoard()
        },
        copyLink() {
            navigator.clipboard.writeText(window.location.href)
            alert('Ссылка скопирована!')
        },
        startEditingBoardTitle() {
            this.localBoardTitle = this.store.board.title
            this.editingBoardTitle = true
        },

        async saveBoardTitle() {
            if (!this.localBoardTitle.trim()) return

            await this.store.renameBoard(this.store.board.uuid, this.localBoardTitle)

            this.editingBoardTitle = false
        },

        cancelBoardEditing() {
            this.localBoardTitle = this.store.board.title
            this.editingBoardTitle = false
        }

    }
}
</script>
<style>
.kanban-column-wrapper {
    display: inline-block;
}


</style>
