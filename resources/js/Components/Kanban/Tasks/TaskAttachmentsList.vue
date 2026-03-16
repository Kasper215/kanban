<template>
    <div class="attachments-container mt-3">
        <div v-if="store.loading" class="text-muted mb-2">Загрузка вложений...</div>

        <div v-if="!store.loading && store.attachments.length === 0" class="text-muted mb-2 small italic">
            Нет прикрепленных файлов
        </div>

        <div class="row row-cols-1 row-cols-md-2 g-2">
            <div v-for="file in store.attachments" :key="file.path" class="col">
                <div class="attachment-card h-100 p-2 border rounded shadow-sm bg-light d-flex flex-column">
                    
                    <!-- Preview Area -->
                    <div class="attachment-preview mb-2 text-center py-2 bg-white rounded border overflow-hidden position-relative pointer" @click="openPreview(file)">
                        <img v-if="isImage(file)" :src="getFileUrl(file)" :alt="file.name" class="img-fluid rounded" />
                        <div v-else class="py-3">
                            <span v-if="isPdf(file)" style="font-size: 2.5rem;">📕</span>
                            <span v-else-if="isWord(file)" style="font-size: 2.5rem;">📘</span>
                            <span v-else-if="isText(file)" style="font-size: 2.5rem;">📄</span>
                            <span v-else-if="isVideo(file)" style="font-size: 2.5rem;">🎬</span>
                            <span v-else-if="isAudio(file)" style="font-size: 2.5rem;">🎵</span>
                            <span v-else style="font-size: 2.5rem;">📎</span>
                            <div v-if="isPdf(file) || isText(file) || isImage(file)" class="preview-overlay">
                                <span class="badge bg-dark bg-opacity-50">Нажми для просмотра</span>
                            </div>
                        </div>
                    </div>

                    <div class="attachment-info flex-grow-1 overflow-hidden">
                        <div class="attachment-name text-truncate fw-bold small" :title="file.name">
                            {{ file.name }}
                        </div>
                        <div class="attachment-meta text-muted small" style="font-size: 10px;">
                            {{ formatSize(file.size) }} • {{ getFileExt(file.name) }}
                        </div>
                    </div>

                    <div class="attachment-actions mt-2 d-flex gap-1 justify-content-end">
                        <button type="button" v-if="canPreview(file)" @click="openPreview(file)" class="btn btn-sm btn-outline-primary" title="Быстрый просмотр">
                            👁️
                        </button>
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

        <!-- Quick Preview Modal -->
        <div v-if="previewFile" class="preview-modal-backdrop" @click.self="closePreview">
            <div class="preview-modal-content card shadow-lg">
                <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
                    <h6 class="mb-0 text-truncate">{{ previewFile.name }}</h6>
                    <button type="button" class="btn-close btn-close-white" @click="closePreview"></button>
                </div>
                <div class="card-body p-0 d-flex flex-column bg-secondary bg-opacity-10" style="min-height: 400px; max-height: 80vh;">
                    
                    <div v-if="isImage(previewFile)" class="text-center p-3">
                        <img :src="getFileUrl(previewFile)" class="img-fluid rounded shadow" style="max-height: 70vh;">
                    </div>
                    
                    <iframe v-else-if="isPdf(previewFile)" :src="getFileUrl(previewFile)" class="w-100 h-100 flex-grow-1 border-0" style="min-height: 60vh;"></iframe>
                    
                    <div v-else-if="isText(previewFile)" class="p-3 bg-white flex-grow-1 overflow-auto">
                        <pre v-if="textContent" class="small">{{ textContent }}</pre>
                        <div v-else class="text-center py-5"><div class="spinner-border text-primary"></div></div>
                    </div>

                    <div v-else class="p-5 text-center">
                        <div class="mb-3" style="font-size: 4rem;">📎</div>
                        <h5>Предпросмотр недоступен</h5>
                        <p class="text-muted">Этот формат файла лучше просматривать в оригинальном приложении.</p>
                        <a :href="getFileUrl(previewFile)" :download="previewFile.name" class="btn btn-primary">Скачать файл</a>
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
            store: useTaskAttachmentsStore(),
            previewFile: null,
            textContent: null
        }
    },
 
    methods: {
        getFileUrl(file) {
            return `/storage/${file.path}`;
        },
        getFileExt(filename) {
            return filename.split('.').pop().toUpperCase();
        },
        isImage(file) {
            return file.mime && file.mime.startsWith('image/');
        },
        isPdf(file) {
            return file.mime === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
        },
        isWord(file) {
            const wordMimes = [
                'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            return wordMimes.includes(file.mime) || file.name.toLowerCase().endsWith('.doc') || file.name.toLowerCase().endsWith('.docx');
        },
        isText(file) {
            return (file.mime && file.mime.startsWith('text/')) || file.name.toLowerCase().endsWith('.txt') || file.name.toLowerCase().endsWith('.log');
        },
        isVideo(file) {
            return file.mime && file.mime.startsWith('video/');
        },
        isAudio(file) {
            return file.mime && file.mime.startsWith('audio/');
        },
        canPreview(file) {
            return this.isImage(file) || this.isPdf(file) || this.isText(file);
        },
        formatSize(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },
        async openPreview(file) {
            this.previewFile = file;
            this.textContent = null;
            
            if (this.isText(file)) {
                try {
                    const response = await fetch(this.getFileUrl(file));
                    this.textContent = await response.text();
                } catch (e) {
                    this.textContent = "Ошибка загрузки текста";
                }
            }
        },
        closePreview() {
            this.previewFile = null;
            this.textContent = null;
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
.attachment-preview {
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.attachment-preview img {
    max-height: 100%;
    object-fit: contain;
}
.preview-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
}
.attachment-preview:hover .preview-overlay {
    opacity: 1;
}
.pointer {
    cursor: pointer;
}
.attachment-name {
    line-height: 1.2;
}

/* Modal styles */
.preview-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}
.preview-modal-content {
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    overflow: hidden;
}
pre {
    white-space: pre-wrap;
    word-break: break-all;
    font-family: monospace;
}
</style>
