<template>
    <div class="attachment-upload border rounded p-3 bg-light mb-3">
        <label class="form-label d-flex align-items-center gap-2 small fw-bold text-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/>
            </svg>
            Загрузить новые вложения
        </label>

        <div class="input-group">
            <input
                type="file"
                multiple
                @change="onFilesSelected"
                class="form-control form-control-sm"
                id="fileInputGroup"
                ref="fileInput"
            />
            <button 
                class="btn btn-primary btn-sm d-flex align-items-center gap-1" 
                type="button" 
                :disabled="!files.length || isUploading"
                @click="upload"
            >
                <span v-if="isUploading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <span>{{ isUploading ? 'Загрузка...' : 'Загрузить' }}</span>
            </button>
        </div>

        <div v-if="files.length" class="selected-files mt-2">
            <div v-for="(file, idx) in files" :key="idx" class="small text-muted d-flex align-items-center gap-1">
                <span class="text-truncate" style="max-width: 250px;">{{ file.name }}</span>
                <span style="font-size: 10px;">({{ formatSize(file.size) }})</span>
            </div>
        </div>
    </div>
</template>

<script>
import { useTaskAttachmentsStore } from '@/stores/useTaskAttachmentsStore'

export default {
    name: 'TaskAttachmentsUpload',

    props: {
        taskId: { type: Number, required: true }
    },

    data() {
        return {
            files: [],
            isUploading: false,
            store: useTaskAttachmentsStore()
        }
    },

    methods: {
        onFilesSelected(e) {
            this.files = Array.from(e.target.files)
        },

        async upload() {
            if (!this.files.length) return;
            
            this.isUploading = true;
            try {
                const response = await this.store.upload(this.taskId, this.files)
                this.$emit('uploaded', response)
                this.files = []
                if (this.$refs.fileInput) {
                    this.$refs.fileInput.value = '';
                }
            } catch (error) {
                console.error('Ошибка при загрузке файлов:', error);
                alert('Не удалось загрузить файлы');
            } finally {
                this.isUploading = false;
            }
        },

        formatSize(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }
    }
}
</script>

<style scoped>
.attachment-upload {
    transition: all 0.2s;
}
.attachment-upload:hover {
    background-color: #f8f9fa !important;
    border-color: #dee2e6 !important;
}
</style>
