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

                <button class="btn btn-secondary d-flex align-items-center gap-2" @click="openConfigModal">
                    <i class="fa-solid fa-gear"></i>
                </button>

                <button class="btn btn-secondary d-flex align-items-center gap-2" @click="openTokenModal"><i
                    class="fa-solid fa-key"></i>
                </button>




                <button class="btn btn-secondary" @click="openExportModal">
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

        <div class="flex flex-col h-screen d-flex d-md-none ">
            <!-- вкладки -->
            <div
                style="position: sticky;z-index: 100;top: 5px;"
                class="flex overflow-x-auto border-b mb-2 p-2">
                <button
                    type="button"
                    v-for="col in store.columns"
                    :key="col.id"
                    class="px-4 mx-1 py-2 whitespace-nowrap cursor-pointer btn border-none"
                    :class="{'btn-warning': activeColumn === col.id ,'btn-secondary': activeColumn !== col.id}"
                    @click="openActiveColumn(col)"
                >
                    {{ col.title }}
                </button>
            </div>

            <!-- контент -->
            <div class="flex-1">

                <template v-if="getActiveColumn">
                    <KanbanColumn
                        :column="getActiveColumn"
                        @add-task="openTaskModal">
                    </KanbanColumn>

                </template>

            </div>
        </div>

        <div
            class="kanban-board gap-3 d-none d-md-flex">
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

        <BoardSettings
            v-if="showConfigModal"
            @close="closeConfigModal"
            @save="saveSettings"
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

        <ConfirmModal
            v-model:show="showExportModal"
            title="Выгрузить данные в эксель?"
            description="Сейчас вас направит на страницу выгрузки данных в файле эксель."
            @accept="exportData"
            @reject="showExportModal = false"
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
import KanbanTask from './KanbanTask.vue'
import BoardSettings from "@/Components/Kanban/BoardSettingsModal.vue";

export default {
    components: {BoardSettings, KanbanColumn, TaskModal, ColumnModal, ConfirmModal, TokenModal,KanbanTask},
    props: {initialBoard: Object},

    data() {
        return {
            activeColumn: 0,
            showTokenModal: false,
            showDeleteModal: false,
            showExportModal: false,
            showTaskModal: false,
            showConfigModal: false,
            showColumnModal: false,
            editingTask: null,
            currentColumnId: null,
            dragIndex: null,
            store: useKanbanStore(),

            editingBoardTitle: false,
            localBoardTitle: this.initialBoard.title
        }
    },

    computed:{
        getActiveColumn()  {
            return this.store.columns.find(c => c.id === this.activeColumn)
        }
    },
    mounted() {
        this.store.columns = this.initialBoard.columns
        this.store.board = this.initialBoard

        this.activeColumn = this.store.columns[0]?.id || null
        /*   this.store.columns.forEach(col => {
               this.store.loadTasks(col.id)
           })
   */

    },

    methods: {
        openActiveColumn(col){
            this.activeColumn = null
            this.$nextTick(()=>{
                this.activeColumn = col.id
            })
        },
        getTasks(columnId) {
            return this.store.columns.find(c => c.id === columnId)?.tasks ?? []
        },
        openConfigModal(){
            this.showConfigModal = true
        },
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
        async saveSettings(settings) {
            await this.store.saveConfig(this.initialBoard.uuid, settings)
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
        closeConfigModal(){
            this.showConfigModal = false
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
        openExportModal() {
            this.showExportModal = true
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
