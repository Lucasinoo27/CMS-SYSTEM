<template>
  <div
    class="file-uploader"
    @dragover.prevent="handleDragOver"
    @dragleave.prevent="handleDragLeave"
    @drop.prevent="handleDrop"
    :class="{ 'drag-over': isDragging }"
  >
    <div class="upload-area">
      <div v-if="!uploading" class="upload-prompt">
        <i class="fas fa-cloud-upload-alt"></i>
        <p>Drag and drop files here or</p>
        <label class="upload-button">
          Browse Files
          <input
            type="file"
            multiple
            @change="handleFileSelect"
            :accept="acceptedTypes"
          />
        </label>
        <p class="upload-hint" v-if="acceptedTypes">
          Accepted file types: {{ acceptedTypes }}
        </p>
      </div>

      <div v-else class="upload-progress">
        <div class="progress-bar">
          <div
            class="progress-fill"
            :style="{ width: `${uploadProgress}%` }"
          ></div>
        </div>
        <p>Uploading... {{ uploadProgress }}%</p>
      </div>
    </div>

    <div v-if="files.length > 0" class="file-list">
      <h3>Uploaded Files</h3>
      <div class="file-grid">
        <div v-for="file in files" :key="file.id" class="file-item">
          <div class="file-icon">
            <i :class="getFileIcon(file.type)"></i>
          </div>
          <div class="file-info">
            <div class="file-name">{{ file.name }}</div>
            <div class="file-meta">
              {{ formatFileSize(file.size) }} • {{ formatDate(file.created_at) }}
            </div>
          </div>
          <div class="file-actions">
            <button
              @click="downloadFile(file)"
              class="btn-icon"
              title="Download"
            >
              <i class="fas fa-download"></i>
            </button>
            <button
              v-if="canDelete"
              @click="deleteFile(file)"
              class="btn-icon delete"
              title="Delete"
            >
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { fileApi } from '@/services/api'

const props = defineProps({
  acceptedTypes: {
    type: String,
    default: ''
  },
  maxFileSize: {
    type: Number,
    default: 5 * 1024 * 1024 // 5MB
  },
  canDelete: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['upload-complete', 'upload-error', 'file-deleted'])

const files = ref([])
const isDragging = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)

const handleDragOver = () => {
  isDragging.value = true
}

const handleDragLeave = () => {
  isDragging.value = false
}

const handleDrop = (e) => {
  isDragging.value = false
  const droppedFiles = Array.from(e.dataTransfer.files)
  uploadFiles(droppedFiles)
}

const handleFileSelect = (e) => {
  const selectedFiles = Array.from(e.target.files)
  uploadFiles(selectedFiles)
  e.target.value = null // Reset input
}

const uploadFiles = async (fileList) => {
  const validFiles = fileList.filter(file => {
    if (file.size > props.maxFileSize) {
      emit('upload-error', `${file.name} exceeds maximum file size of ${formatFileSize(props.maxFileSize)}`)
      return false
    }
    if (props.acceptedTypes && !props.acceptedTypes.includes(file.type)) {
      emit('upload-error', `${file.name} is not an accepted file type`)
      return false
    }
    return true
  })

  if (validFiles.length === 0) return

  uploading.value = true
  uploadProgress.value = 0

  const formData = new FormData()
  validFiles.forEach(file => {
    formData.append('files[]', file)
  })

  try {
    const response = await fileApi.upload(formData, {
      onUploadProgress: (progressEvent) => {
        uploadProgress.value = Math.round(
          (progressEvent.loaded * 100) / progressEvent.total
        )
      }
    })

    files.value = [...files.value, ...response.data]
    emit('upload-complete', response.data)
  } catch (error) {
    emit('upload-error', error.response?.data?.message || 'Upload failed')
  } finally {
    uploading.value = false
    uploadProgress.value = 0
  }
}

const downloadFile = (file) => {
  window.open(file.url, '_blank')
}

const deleteFile = async (file) => {
  try {
    await fileApi.delete(file.id)
    files.value = files.value.filter(f => f.id !== file.id)
    emit('file-deleted', file)
  } catch (error) {
    emit('upload-error', 'Failed to delete file')
  }
}

const getFileIcon = (type) => {
  const icons = {
    'image': 'fas fa-image',
    'video': 'fas fa-video',
    'audio': 'fas fa-music',
    'application/pdf': 'fas fa-file-pdf',
    'application/msword': 'fas fa-file-word',
    'application/vnd.ms-excel': 'fas fa-file-excel',
    'text/plain': 'fas fa-file-alt'
  }

  for (const [key, value] of Object.entries(icons)) {
    if (type.includes(key)) return value
  }

  return 'fas fa-file'
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

const fetchFiles = async () => {
  try {
    const response = await fileApi.getAll()
    files.value = response.data
  } catch (error) {
    emit('upload-error', 'Failed to fetch files')
  }
}

onMounted(() => {
  fetchFiles()
})
</script>

<style lang="scss" scoped>
.file-uploader {
  .upload-area {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;

    &.drag-over {
      border-color: #3498db;
      background: rgba(52, 152, 219, 0.1);
    }
  }

  .upload-prompt {
    i {
      font-size: 3rem;
      color: #95a5a6;
      margin-bottom: 1rem;
    }

    p {
      margin: 0.5rem 0;
      color: #7f8c8d;
    }
  }

  .upload-button {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: #3498db;
    color: white;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s;

    &:hover {
      background: #2980b9;
    }

    input {
      display: none;
    }
  }

  .upload-hint {
    font-size: 0.9rem;
    color: #95a5a6;
  }

  .upload-progress {
    .progress-bar {
      height: 8px;
      background: #eee;
      border-radius: 4px;
      margin: 1rem 0;
      overflow: hidden;

      .progress-fill {
        height: 100%;
        background: #3498db;
        transition: width 0.3s ease;
      }
    }
  }

  .file-list {
    margin-top: 2rem;

    h3 {
      margin: 0 0 1rem;
      color: #2c3e50;
    }
  }

  .file-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }

  .file-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

    .file-icon {
      width: 40px;
      height: 40px;
      background: #f5f6fa;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 1rem;

      i {
        font-size: 1.2rem;
        color: #3498db;
      }
    }

    .file-info {
      flex-grow: 1;
      overflow: hidden;

      .file-name {
        color: #2c3e50;
        font-weight: 500;
        margin-bottom: 0.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .file-meta {
        font-size: 0.9rem;
        color: #7f8c8d;
      }
    }

    .file-actions {
      display: flex;
      gap: 0.5rem;

      .btn-icon {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 4px;
        background: #f5f6fa;
        color: #3498db;
        cursor: pointer;
        transition: all 0.3s;

        &:hover {
          background: #3498db;
          color: white;
        }

        &.delete {
          color: #e74c3c;

          &:hover {
            background: #e74c3c;
            color: white;
          }
        }
      }
    }
  }
}
</style> 