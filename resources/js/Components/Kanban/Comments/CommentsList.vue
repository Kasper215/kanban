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
                <span class="comment-date text-muted" style="font-size: 11px;">
                    {{ formatDate(comment.created_at) }}
                </span>
            </div>

            <div class="comment-content mb-3" style="white-space: pre-wrap; font-size: 14px;">
                {{ comment.text }}
            </div>

            <div class="comment-attachments row g-2" v-if="comment.attachments && comment.attachments.length">
                <div
                    v-for="(file, idx) in comment.attachments"
                    :key="idx"
                    class="col-6 col-md-4"
                >
                    <div class="comment-attachment-item rounded border overflow-hidden position-relative group">
                        <!-- Image Preview -->
                        <div v-if="isImage(file)" class="image-preview" style="height: 100px;">
                            <img
                                :src="getFileUrl(file)"
                                class="w-100 h-100 object-fit-cover pointer"
                                @click="openFile(file)"
                            />
                        </div>
                        
                        <!-- File Icon for non-images -->
                        <div v-else class="file-icon d-flex align-items-center justify-content-center bg-light" style="height: 100px;">
                            <span v-if="isVideo(file)" style="font-size: 1.5rem;">🎬</span>
                            <span v-else-if="isAudio(file)" style="font-size: 1.5rem;">🎵</span>
                            <span v-else style="font-size: 1.5rem;">📄</span>
                        </div>

                        <!-- Info bar visible on hover or small screen -->
                        <div class="attachment-overlay p-1 d-flex justify-content-between align-items-center bg-dark bg-opacity-75 text-white w-100" style="font-size: 10px;">
                            <span class="text-truncate px-1" :title="file.name">{{ file.name }}</span>
                            <div class="d-flex gap-1">
                                <a :href="getFileUrl(file)" :download="file.name" class="btn btn-sm p-0 text-white" title="Скачать">
                                    ⬇️
                                </a>
                            </div>
                        </div>
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
            store: useCommentsStore()
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
        openFile(file) {
            window.open(this.getFileUrl(file), '_blank');
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
.comment-attachment-item .attachment-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
}
</style>
