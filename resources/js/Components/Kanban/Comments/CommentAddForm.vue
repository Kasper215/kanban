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
                    <i class="fa-solid fa-paperclip"></i>
                    <span>Прикрепить фото/файлы</span>
                    <input
                        type="file"
                        multiple
                        @change="onFilesSelected"
                        class="d-none"
                        accept="image/*,application/pdf,.doc,.docx"
                    />
                </label>
                
                <div v-if="files.length" class="mt-3 row g-2">
                    <div v-for="(fileObj, index) in files" :key="index" class="col-4 col-sm-3 col-md-2">
                        <div class="selected-file-preview position-relative border rounded bg-white overflow-hidden">
                            <!-- Image preview -->
                            <div v-if="fileObj.isImage" class="preview-thumb" style="height: 60px;">
                                <img :src="fileObj.previewUrl" class="w-100 h-100 object-fit-cover" />
                            </div>
                            <!-- Icon for other types -->
                            <div v-else class="preview-thumb d-flex align-items-center justify-content-center text-secondary bg-light" style="height: 60px;">
                                <i v-if="fileObj.isPdf" class="fa-solid fa-file-pdf fa-lg"></i>
                                <i v-else class="fa-solid fa-file fa-lg"></i>
                            </div>

                            <!-- Remove button -->
                            <button type="button" 
                                    class="btn-remove-file position-absolute top-0 end-0 bg-danger text-white border-0 rounded-circle d-flex align-items-center justify-content-center" 
                                    style="width: 18px; height: 18px; margin: 2px; font-size: 10px;"
                                    @click="removeFile(index)">
                                <i class="fa-solid fa-xmark"></i>
                            </button>

                            <!-- Mini filename -->
                            <div class="file-mini-name p-1 bg-white border-top text-truncate" style="font-size: 9px;" :title="fileObj.file.name">
                                {{ fileObj.file.name }}
                            </div>
                        </div>
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
            const rawFiles = Array.from(e.target.files)
            const processedFiles = rawFiles.map(file => {
                const isImage = file.type.startsWith('image/')
                const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')
                
                return {
                    file,
                    isImage,
                    isPdf,
                    previewUrl: isImage ? URL.createObjectURL(file) : null
                }
            })
            this.files = [...this.files, ...processedFiles]
        },

        removeFile(index) {
            const fileObj = this.files[index]
            if (fileObj.previewUrl) {
                URL.revokeObjectURL(fileObj.previewUrl)
            }
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
                    files: this.files.map(f => f.file),
                    author: this.author || 'Пользователь'
                })

                this.text = ''
                // Чистим URL-адреса предпросмотра
                this.files.forEach(f => {
                    if (f.previewUrl) URL.revokeObjectURL(f.previewUrl)
                })
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
.object-fit-cover {
    object-fit: cover;
}
.selected-file-preview {
    transition: all 0.2s;
}
.selected-file-preview:hover {
    border-color: #adb5bd !important;
}
.btn-remove-file {
    opacity: 0.8;
}
.btn-remove-file:hover {
    opacity: 1;
    transform: scale(1.1);
}
</style>
