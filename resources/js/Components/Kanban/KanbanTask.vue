<template>
    <div
        :data-card-id="task.id"
        draggable="true"
        @dragstart="$emit('dragstart', task)"
        @dragover.prevent
        @drop.stop="$emit('drop', task)"
        :class="{ 'bg-warning bg-opacity-25': !task.last_viewed_at }"
        class="kanban-task p-2 mb-2 bg-white rounded shadow-sm"
        @dblclick="$emit('edit', task)">

        <div v-if="firstImage" class="task-image-preview mb-2">
            <img :src="`/storage/${firstImage.path}`" :alt="firstImage.name" class="task-preview-img" />
        </div>

        <div class="d-flex justify-content-between align-items-start mb-1">

            <strong style="line-height: 100%;">{{ task.title }}</strong>

            <!-- Dropdown -->
            <div class="dropdown">
                <button
                    class="btn btn-sm btn-light dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                ></button>

                <ul class="dropdown-menu dropdown-menu-end">

                    <li>
                        <button
                            class="dropdown-item"
                            @click.stop="$emit('edit', task)"
                        >
                            ✏️ Редактировать
                        </button>
                    </li>

                    <li>
                        <button
                            class="dropdown-item"
                            @click.stop="$emit('duplicate', task)"
                        >
                            📄 Дублировать
                        </button>
                    </li>

                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>
                        <button
                            class="dropdown-item text-danger"
                            @click.stop="$emit('delete', task)"
                        >
                            🗑️ Удалить
                        </button>
                    </li>

                </ul>
            </div>
        </div>


        <div
            :class="[
            task.priority==='low'?'bg-secondary':'',
            task.priority==='medium'?'bg-warning':'',
            task.priority==='high'?'bg-success':'',
        ]"
            class="small badge text-white">{{ priority[task.priority] || '-' }}
        </div>

        <span
            v-for="label in task.labels"
            :key="label"
            class="badge bg-secondary me-2"
        >
    {{ label }}
</span>

        <div class="d-flex flex-wrap mt-2 mb-1">
            <span
                v-for="tag in task.tags"
                :key="tag.id"
                class="bg-info badge"
                :style="{ background: tag.color }"
            >
                #{{ tag.name }}
            </span>
        </div>

        <!-- Инфо-строка: счётчики -->
        <div v-if="hasCounters" class="task-counters d-flex align-items-center flex-wrap gap-2 mt-2 pt-1 border-top">

            <!-- Подзадачи (теперь как кнопка-переключатель видимости) -->
            <span v-if="task.subtasks && task.subtasks.length" 
                  class="task-counter" 
                  title="Показать/скрыть подзадачи"
                  @click.stop="showSubtasks = !showSubtasks"
                  style="cursor: pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2 10.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1h-10a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1h-10a.5.5 0 0 1-.5-.5zM13 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
                <span :class="{ 'text-success': subtasksDone === task.subtasks.length && task.subtasks.length > 0 }">
                    {{ subtasksDone }}/{{ task.subtasks.length }}
                    <span style="font-size:10px;">{{ showSubtasks ? '▲' : '▼' }}</span>
                </span>
            </span>

            <!-- Комментарии -->
            <span v-if="task.comments_count" class="task-counter" title="Комментарии">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.678 11.894a1 1 0 0 1 .287.801 11 11 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8 8 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894m-.493 3.905a22 22 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a10 10 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9 9 0 0 1-2.088-.253 10.5 10.5 0 0 1-4.73 1.052"/>
                </svg>
                {{ task.comments_count }}
            </span>

            <!-- Вложения с динамической иконкой типа -->
            <span v-if="task.attachments && task.attachments.length" class="task-counter" :title="`Вложений: ${task.attachments.length}`">
                <!-- Иконка Картинки -->
                <svg v-if="hasFilesOfType('image/')" xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="text-primary" viewBox="0 0 16 16">
                    <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/>
                    <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                </svg>
                <!-- Иконка PDF -->
                <svg v-else-if="hasFilesOfType('application/pdf')" xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="text-danger" viewBox="0 0 16 16">
                    <path d="M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4zm0 1h8a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/>
                    <path d="M4.603 12.087a.81.81 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.166-.257.433-.484.758-.657a.5.5 0 0 1 .482.876c-.204.112-.353.225-.438.357-.035.053-.053.11-.053.18 0 .073.023.136.064.18.067.072.186.115.353.115.114 0 .237-.032.355-.084a.5.5 0 0 1 .404.915c-.23.102-.455.158-.69.158a.822.822 0 0 1-.517-.123zm3.112-2.822c.127-.188.297-.405.462-.603.144-.172.275-.33.376-.457.104-.131.183-.244.243-.338a.5.5 0 1 1 .842.541c-.056.088-.13.195-.232.323a10.04 10.04 0 0 1-.365.443c-.161.192-.328.398-.462.595a.5.5 0 0 1-.824-.544zm.03 2.136c-.112 0-.214-.047-.301-.132a.5.5 0 0 1 .705-.705c.038.038.077.058.118.058.05 0 .093-.016.142-.053a.5.5 0 0 1 .632.775c-.144.117-.294.157-.456.157z"/>
                </svg>
                <!-- Иконка Word -->
                <svg v-else-if="hasFilesOfType('application/vnd.openxmlformats-officedocument.wordprocessingml.document')" xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="text-primary" viewBox="0 0 16 16">
                    <path d="M5.485 6.879l.697 2.756h.056l.647-2.756h.654l-.77 3.13h-.624l-.621-2.489h-.034l-.62 2.489h-.624l-.77-3.13h.654zm2.147 0h2.956v.534h-.543v2.596h-.565V7.413h-1.283v2.596h-.565V6.879zm1.8 1.258a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/>
                    <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5v2z"/>
                </svg>
                <!-- Иконка Видео -->
                <svg v-else-if="hasFilesOfType('video/')" xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M0 1a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H1 a1 1 0 0 1-1-1V1zm4 0v6h8V1H4zm8 8H4v6h8V9zM1 1v2h2V1H1zm2 3H1v2h2V4zM1 7v2h2V7H1zm2 3H1v2h2v-2zm-2 3v2h2v-2H1zM15 1h-2v2h2V1zm-2 3v2h2V4h-2zm2 3h-2v2h2V7zm-2 3v2h2v-2h-2zm2 3h-2v2h2v-2z"/>
                </svg>
                <!-- Дефолтная скрепка для всего остального -->
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0z"/>
                </svg>
                {{ task.attachments.length }}
            </span>

        </div>
        
        <!-- Раскрывающийся список подзадач (компактный вид) -->
        <div v-if="showSubtasks && task.subtasks && task.subtasks.length" class="subtasks-panel mt-2 pt-2 border-top">
            <div v-for="sub in task.subtasks" :key="sub.id" class="subtask-mini-item d-flex align-items-center mb-1">
                <span class="subtask-bullet me-2" :class="sub.done ? 'bg-success' : 'bg-light border'"></span>
                <span class="subtask-mini-text text-truncate" :class="{'text-muted text-decoration-line-through': sub.done}" :title="sub.text">
                    {{ sub.text }}
                </span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        task: Object
    },
    data() {
        return {
            priority: {
                low: 'Низкий',
                medium: 'Средний',
                high: 'Высокий',
            },
            showSubtasks: false
        }
    },
    computed: {
        firstImage() {
            if (!this.task.attachments || !this.task.attachments.length) return null;
            let img = this.task.attachments.find(f => f.mime && f.mime.startsWith('image/'));
            if(img) return img;
            return null;
        },
        subtasksDone() {
            return this.task.subtasks?.filter(s => s.done).length ?? 0
        },
        hasCounters() {
            return (this.task.subtasks?.length > 0)
                || (this.task.comments_count > 0)
                || (this.task.attachments?.length > 0)
        }
    },
    methods: {
        hasFilesOfType(typePrefix) {
            if (!this.task.attachments) return false;
            return this.task.attachments.some(f => f.mime && f.mime.startsWith(typePrefix));
        }
    }
}
</script>

<style scoped>
.task-preview-img {
    width: 100%;
    max-height: 120px;
    object-fit: cover;
    border-radius: 6px;
    display: block;
}

.task-counters {
    font-size: 11px;
    color: #6c757d;
    border-top-color: #e9ecef !important;
}

.task-counter {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    color: #6c757d;
    font-weight: 500;
}

.task-counter svg {
    flex-shrink: 0;
    opacity: 0.75;
}

.task-counter .text-success {
    color: #198754 !important;
    font-weight: 600;
}

.subtasks-panel {
    max-height: 150px;
    overflow-y: auto;
    border-top-color: #f1f3f5 !important;
}

.subtask-mini-item {
    line-height: normal;
}

.subtask-bullet {
    width: 8px;
    height: 8px;
    border-radius: 2px;
    flex-shrink: 0;
}

.subtask-mini-text {
    font-size: 11px;
    color: #495057;
}
</style>
