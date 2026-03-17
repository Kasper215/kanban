<template>
    <div class="comments-list mt-3">
        <div v-if="store.loading" class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            <span class="ms-2 text-muted">Загрузка комментариев...</span>
        </div>

        <div v-if="!store.loading && store.comments.length === 0" class="text-center py-4 text-muted border rounded bg-light">
            Нет комментариев к этой задаче
        </div>

        <div
            v-for="comment in sortedComments"
            :key="comment.id"
            class="comment-card mb-3 p-3 border rounded shadow-sm bg-white"
        >
            <div class="comment-header d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="author-avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 10px;">
                        {{ getInitial(comment.author) }}
                    </div>
                    <strong class="author-name small text-primary">{{ comment.author || 'Пользователь' }}</strong>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="comment-date text-muted" style="font-size: 11px;">
                        {{ formatDate(comment.created_at) }}
                    </span>
                    <button class="btn btn-sm btn-link text-danger p-0 border-0" title="Удалить комментарий" @click="confirmDeleteComment(comment.id)">
                        <i class="fa-solid fa-trash-can" style="font-size: 12px;"></i>
                    </button>
                </div>
            </div>

            <div class="comment-content mb-3" style="white-space: pre-wrap; font-size: 14px;">
                {{ comment.text }}
            </div>

            <!-- Вложения -->
            <div class="comment-attachments row g-2 mt-2" v-if="comment.attachments && comment.attachments.length">
                <div
                    v-for="(file, idx) in comment.attachments"
                    :key="idx"
                    class="col-6 col-sm-4 col-md-3"
                >
                    <div class="comment-attachment-item rounded border overflow-hidden position-relative group pointer" @click="openPreview(file)">
                        <!-- Preview Thumbnail -->
                        <div class="attachment-thumb d-flex align-items-center justify-content-center bg-light" style="height: 80px;">
                            <img v-if="isImage(file)" :src="getFileUrl(file)" class="w-100 h-100 object-fit-cover" />
                            <div v-else class="text-secondary">
                                <i v-if="isPdf(file)" class="fa-solid fa-file-pdf fa-2x"></i>
                                <i v-else-if="isVideo(file)" class="fa-solid fa-file-video fa-2x"></i>
                                <i v-else-if="isAudio(file)" class="fa-solid fa-file-audio fa-2x"></i>
                                <i v-else class="fa-solid fa-file fa-2x"></i>
                            </div>
                        </div>

                        <!-- Dark Overlay with Filename and Download/Delete -->
                        <div class="attachment-overlay p-1 d-flex justify-content-between align-items-center bg-dark bg-opacity-75 text-white w-100" style="font-size: 10px;">
                            <span class="text-truncate px-1 flex-grow-1" :title="file.name">{{ file.name }}</span>
                            <div class="d-flex gap-1 ms-1">
                                <a :href="getFileUrl(file)" :download="file.name" class="btn btn-sm p-0 text-white" @click.stop title="Скачать">
                                    <i class="fa-solid fa-circle-down"></i>
                                </a>
                                <button class="btn btn-sm p-0 text-danger" @click.stop="confirmDeleteAttachment(comment.id, file.path)" title="Удалить файл">
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </button>
                            </div>
                        </div>
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
                    
                    <!-- Image Preview -->
                    <div v-if="isImage(previewFile)" class="text-center p-3 h-100 d-flex align-items-center justify-content-center bg-white">
                        <img :src="getFileUrl(previewFile)" class="img-fluid rounded shadow" style="max-height: 70vh; object-fit: contain;">
                    </div>
                    
                    <!-- PDF Preview -->
                    <div v-else-if="isPdf(previewFile)" class="h-100 flex-grow-1">
                        <embed :src="getFileUrl(previewFile)" type="application/pdf" width="100%" height="100%" style="min-height: 60vh;" />
                    </div>
                    
                    <!-- Text Preview -->
                    <div v-else-if="isText(previewFile)" class="p-3 bg-white flex-grow-1 overflow-auto h-100">
                        <pre v-if="textContent" class="small p-3 bg-light border rounded">{{ textContent }}</pre>
                        <div v-else class="text-center py-5">
                            <div class="spinner-border text-primary mb-2"></div>
                            <p class="text-muted">Загрузка содержимого...</p>
                        </div>
                    </div>

                    <!-- Unavailable Preview -->
                    <div v-else class="p-5 text-center flex-grow-1 d-flex flex-column align-items-center justify-content-center bg-white">
                        <div class="mb-4 text-secondary">
                            <i class="fa-solid fa-file-circle-xmark fa-5x"></i>
                        </div>
                        <h4>Предпросмотр недоступен</h4>
                        <p class="text-muted mb-4">Этот формат файла не поддерживает быстрый просмотр.</p>
                        <a :href="getFileUrl(previewFile)" :download="previewFile.name" class="btn btn-primary px-5">
                            Скачать и открыть
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useCommentsStore } from '@/stores/useCommentsStore'

export default {
    name: 'CommentsList',

    props: {
        taskId: { type: Number, required: true }
    },

    data() {
        return {
            store: useCommentsStore(),
            previewFile: null,
            textContent: null
        }
    },

    computed: {
        sortedComments() {
            return [...this.store.comments].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        }
    },

    methods: {
        getFileUrl(file) {
            return `/storage/${file.path}`;
        },
        isImage(file) {
            return file.mime && file.mime.startsWith('image/');
        },
        isPdf(file) {
            return (file.mime === 'application/pdf') || (file.name && file.name.toLowerCase().endsWith('.pdf'));
        },
        isText(file) {
            return (file.mime && file.mime.startsWith('text/')) || (file.name && file.name.toLowerCase().endsWith('.txt'));
        },
        isVideo(file) {
            return file.mime && file.mime.startsWith('video/');
        },
        isAudio(file) {
            return file.mime && file.mime.startsWith('audio/');
        },
        formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },
        getInitial(name) {
            if (!name) return 'П';
            return name.charAt(0).toUpperCase();
        },
        async openPreview(file) {
            this.previewFile = file;
            this.textContent = null;
            
            if (this.isText(file)) {
                try {
                    const response = await axios.get(this.getFileUrl(file));
                    this.textContent = response.data;
                } catch (e) {
                    console.error("Text load error:", e);
                    this.textContent = "Ошибка загрузки текста файла.";
                }
            }
        },
        closePreview() {
            this.previewFile = null;
            this.textContent = null;
        },
        async confirmDeleteComment(commentId) {
            if (confirm('Удалить этот комментарий вместе со всеми вложениями?')) {
                try {
                    await this.store.deleteComment(commentId);
                } catch (e) {
                    alert('Ошибка при удалении комментария');
                }
            }
        },
        async confirmDeleteAttachment(commentId, path) {
            if (confirm('Удалить этот файл из комментария?')) {
                try {
                    await this.store.removeAttachment(commentId, path);
                } catch (e) {
                    alert('Ошибка при удалении файла');
                }
            }
        }
    },

    mounted() {
        this.store.fetchComments(this.taskId)
    }
}
</script>

<style scoped>
.comment-card {
    transition: transform 0.2s;
}
.comment-card:hover {
    transform: translateY(-2px);
}
.pointer {
    cursor: pointer;
}
.object-fit-cover {
    object-fit: cover;
}
.comment-attachment-item {
    transition: border-color 0.2s;
}
.comment-attachment-item:hover {
    border-color: #0d6efd !important;
}
.attachment-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
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
