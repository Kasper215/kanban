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
                            <i class="fa-solid fa-pen-to-square me-2 text-muted"></i> Редактировать
                        </button>
                    </li>

                    <li>
                        <button
                            class="dropdown-item"
                            @click.stop="$emit('duplicate', task)"
                        >
                            <i class="fa-solid fa-copy me-2 text-muted"></i> Дублировать
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
                            <i class="fa-solid fa-trash-can me-2"></i> Удалить
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
            class="badge bg-secondary mx-2"
        >
    {{ label }}
</span>

        <div class="d-flex flex-wrap mt-2 mb-1">
            <span
                v-for="tag in task.tags"
                :key="tag.id"
                class="bg-info badge mx-2"
                :style="{ background: tag.color }"
            >
                #{{ tag.name }}
            </span>
        </div>


        <!-- Инфо-строка: счётчики -->
        <div v-if="hasCounters" class="task-counters d-flex align-items-center flex-wrap gap-2 mt-2 pt-1 border-top">

            <!-- Подзадачи -->
            <span v-if="task.subtasks && task.subtasks.length"
                  class="task-counter"
                  title="Показать/скрыть подзадачи"
                  @click.stop="showSubtasks = !showSubtasks"
                  style="cursor: pointer;">
                <i class="fa-solid fa-list-check me-1"></i>
                <span :class="{ 'text-success': subtasksDone === task.subtasks.length && task.subtasks.length > 0 }">
                    {{ subtasksDone }}/{{ task.subtasks.length }}
                    <i :class="showSubtasks ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down'" class="ms-1" style="font-size: 9px;"></i>
                </span>
            </span>

            <!-- Сообщения -->
            <span v-if="task.messages?.length>0" class="task-counter" title="Сообщения">
                <i class="fa-regular fa-comment-dots me-1"></i>
                {{ task.messages?.length }}
            </span>

            <!-- Комментарии -->
            <span v-if="task.comments_count" class="task-counter" title="Комментарии">
                <i class="fa-regular fa-comment me-1"></i>
                {{ task.comments_count }}
            </span>

            <!-- Вложения по типам -->
            <template v-for="(stat, idx) in attachmentStats" :key="idx">
                <span class="task-counter" :title="stat.title">
                    <i :class="[stat.icon, stat.class, 'me-1']"></i>
                    {{ stat.count }}
                </span>
            </template>

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
                || (this.task.messages?.length > 0)
                || (this.task.attachments?.length > 0)
        },
        attachmentStats() {
            if (!this.task.attachments || !this.task.attachments.length) return [];

            const stats = {
                image: { count: 0, icon: 'fa-regular fa-image', class: 'text-primary', title: 'Изображения' },
                pdf: { count: 0, icon: 'fa-regular fa-file-pdf', class: 'text-danger', title: 'PDF документы' },
                word: { count: 0, icon: 'fa-regular fa-file-word', class: 'text-primary', title: 'Документы Word' },
                excel: { count: 0, icon: 'fa-regular fa-file-excel', class: 'text-success', title: 'Таблицы Excel' },
                video: { count: 0, icon: 'fa-regular fa-file-video', class: '', title: 'Видео' },
                other: { count: 0, icon: 'fa-solid fa-paperclip', class: '', title: 'Другие файлы' }
            };

            this.task.attachments.forEach(file => {
                const mime = file.mime || '';
                const name = (file.name || '').toLowerCase();

                if (mime.startsWith('image/')) stats.image.count++;
                else if (mime === 'application/pdf' || name.endsWith('.pdf')) stats.pdf.count++;
                else if (mime.includes('word') || name.endsWith('.doc') || name.endsWith('.docx')) stats.word.count++;
                else if (mime.includes('sheet') || mime.includes('excel') || name.endsWith('.xls') || name.endsWith('.xlsx')) stats.excel.count++;
                else if (mime.startsWith('video/')) stats.video.count++;
                else stats.other.count++;
            });

            return Object.values(stats).filter(s => s.count > 0);
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

.task-counter i {
    flex-shrink: 0;
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
