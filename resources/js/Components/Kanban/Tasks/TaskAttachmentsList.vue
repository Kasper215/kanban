<template>
    <div class="attachments-container mt-3">
        <div v-if="store.loading" class="text-muted mb-2">Загрузка вложений...</div>

        <div v-if="!store.loading && store.attachments.length === 0" class="text-muted mb-2 small italic">
            Нет прикрепленных файлов
        </div>

        <div class="row row-cols-1 row-cols-md-2 g-2">
            <div v-for="file in store.attachments" :key="file.path" class="col">
                <div class="attachment-card h-100 p-2 border rounded shadow-sm bg-light d-flex flex-column">
                    
                    <!-- Preview for images -->
                    <div v-if="isImage(file)" class="attachment-preview mb-2 text-center">
                        <img :src="getFileUrl(file)" :alt="file.name" class="img-fluid rounded border shadow-sm pointer" @click="openFile(file)" />
                    </div>
                    
                    <!-- Generic icon for non-images -->
                    <div v-else class="attachment-icon mb-2 text-center py-3 bg-secondary bg-opacity-10 rounded border">
                        <span v-if="isVideo(file)" style="font-size: 2rem;">🎬</span>
                        <span v-else-if="isAudio(file)" style="font-size: 2rem;">🎵</span>
                        <span v-else style="font-size: 2rem;">📄</span>
                    </div>

                    <div class="attachment-info flex-grow-1 overflow-hidden">
                        <div class="attachment-name text-truncate fw-bold small" :title="file.name">
                            {{ file.name }}
                        </div>
                        <div class="attachment-meta text-muted small" style="font-size: 10px;">
                            {{ formatSize(file.size) }} • {{ file.mime }}
                        </div>
                    </div>

                    <div class="attachment-actions mt-2 d-flex gap-1 justify-content-end">
                        <a :href="getFileUrl(file)" target="_blank" class="btn btn-sm btn-outline-primary" title="Открыть/Просмотреть">
                            👁️
                        </a>
                        <a :href="getFileUrl(file)" :download="file.name" class="btn btn-sm btn-primary" title="Скачать">
                            ⬇️
                        </a>
                        <button v-if="showDelete" @click="removeFile(file)" class="btn btn-sm btn-outline-danger" title="Удалить">
                            ✕
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useTaskAttachmentsStore } from '@/stores/useTaskAttachmentsStore'

export default {
    name: 'TaskAttachmentsList',

    props: {
        taskId: { type: Number, required: true },
        showDelete: { type: Boolean, default: false }
    },

    data() {
        return {
            store: useTaskAttachmentsStore()
        }
    },

    methods: {
        getFileUrl(file) {
            return `/storage/${file.path}`;
        },
        isImage(file) {
            return file.mime && file.mime.startsWith('image/');
        },
        isVideo(file) {
            return file.mime && file.mime.startsWith('video/');
        },
        isAudio(file) {
            return file.mime && file.mime.startsWith('audio/');
        },
        formatSize(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },
        openFile(file) {
            window.open(this.getFileUrl(file), '_blank');
        },
        removeFile(file) {
            if (confirm('Удалить этот файл?')) {
                // В сторе еще нет метода удаления, но можно добавить если нужно
                alert('Удаление пока не реализовано в API');
            }
        }
    },

    mounted() {
        this.store.fetch(this.taskId)
    }
}
</script>

<style scoped>
.attachment-card {
    transition: all 0.2s;
}
.attachment-card:hover {
    border-color: #0d6efd !important;
}
.attachment-preview img {
    max-height: 100px;
    object-fit: cover;
    width: 100%;
}
.pointer {
    cursor: pointer;
}
.attachment-name {
    line-height: 1.2;
}
</style>
