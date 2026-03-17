<template>
    <div class="comment-add-container mt-4 pt-3 border-top">
        <h6 class="fw-bold mb-3">Добавить комментарий</h6>
        
        <div class="card shadow-sm border-0 bg-light p-3">
            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text"
                               v-model="author"
                               class="form-control form-control-sm" 
                               id="authorInput" 
                               placeholder="Ваше имя">
                        <label for="authorInput">Ваше имя</label>
                    </div>
                </div>
            </div>

            <div class="form-floating mb-2">
                <textarea
                    v-model="text"
                    class="form-control"
                    id="commentTextarea"
                    placeholder="Написать комментарий..."
                    style="height: 100px"
                    required
                ></textarea>
                <label for="commentTextarea">Написать комментарий...</label>
            </div>

            <div class="file-upload-block mb-2">
                <label class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0z"/>
                    </svg>
                    <span>Прикрепить фото/файлы</span>
                    <input
                        type="file"
                        multiple
                        @change="onFilesSelected"
                        class="d-none"
                        accept="image/*,application/pdf,.doc,.docx"
                    />
                </label>
                
                <div v-if="files.length" class="mt-2 d-flex flex-wrap gap-2">
                    <div v-for="(file, index) in files" :key="index" class="badge bg-info text-white d-flex align-items-center gap-2 p-2">
                        <span class="text-truncate" style="max-width: 150px;">{{ file.name }}</span>
                        <button type="button" class="btn-close btn-close-white" style="font-size: 0.5rem" @click="removeFile(index)"></button>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button 
                    class="btn btn-primary d-flex align-items-center gap-2" 
                    :disabled="!text.trim() || isSubmitting"
                    @click="submit"
                >
                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm"></span>
                    <span>Отправить</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { useCommentsStore } from '@/stores/useCommentsStore'

export default {
    name: 'CommentAddForm',

    props: {
        taskId: { type: Number, required: true }
    },

    data() {
        return {
            author: localStorage.getItem('last_author') || '',
            text: '',
            files: [],
            isSubmitting: false,
            store: useCommentsStore()
        }
    },

    methods: {
        onFilesSelected(e) {
            const newFiles = Array.from(e.target.files)
            this.files = [...this.files, ...newFiles]
        },

        removeFile(index) {
            this.files.splice(index, 1);
        },

        async submit() {
            if (!this.text.trim()) return;
            
            this.isSubmitting = true;
            try {
                // Сохраняем автора для следующего раза
                if (this.author) {
                    localStorage.setItem('last_author', this.author);
                }

                await this.store.addComment(this.taskId, {
                    text: this.text,
                    files: this.files,
                    author: this.author || 'Пользователь'
                })

                this.text = ''
                this.files = []
            } catch (error) {
                console.error('Ошибка при добавлении комментария:', error);
                alert('Не удалось отправить комментарий');
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>

<style scoped>
.comment-add-container {
    margin-bottom: 2rem;
}
.btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
}
</style>
