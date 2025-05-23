<template>
  <div class="page-editor">
    <div class="editor-header">
      <div class="form-group">
        <label for="title">Page Title</label>
        <input
          type="text"
          id="title"
          v-model="form.title"
          required
          placeholder="Enter page title"
        />
      </div>

      <div class="form-group">
        <label for="slug">URL Slug</label>
        <input
          type="text"
          id="slug"
          v-model="form.slug"
          required
          placeholder="page-url-slug"
        />
      </div>

      <div class="form-actions">
        <button
          type="button"
          class="btn-secondary"
          @click="$emit('cancel')"
        >
          Cancel
        </button>
        <button
          type="button"
          class="btn-primary"
          @click="savePage"
          :disabled="saving"
        >
          {{ saving ? 'Saving...' : 'Save Page' }}
        </button>
      </div>
    </div>

    <div class="content-blocks">
      <div v-for="(block, index) in form.blocks" :key="index" class="content-block">
        <div class="block-header">
          <select v-model="block.type" @change="updateBlockType(index)">
            <option value="text">Text Block</option>
            <option value="image">Image Block</option>
            <option value="video">Video Block</option>
            <option value="file">File Block</option>
          </select>

          <div class="block-actions">
            <button
              type="button"
              class="btn-icon"
              @click="moveBlockUp(index)"
              :disabled="index === 0"
              title="Move Up"
            >
              <i class="fas fa-arrow-up"></i>
            </button>
            <button
              type="button"
              class="btn-icon"
              @click="moveBlockDown(index)"
              :disabled="index === form.blocks.length - 1"
              title="Move Down"
            >
              <i class="fas fa-arrow-down"></i>
            </button>
            <button
              type="button"
              class="btn-icon delete"
              @click="removeBlock(index)"
              title="Delete Block"
            >
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </div>

        <div class="block-content">
          <!-- Text Block with WYSIWYG Editor -->
          <div v-if="block.type === 'text'" class="text-editor">
            <WysiwygEditor 
              v-model="block.content"
              @image-upload="handleWysiwygImageUpload"
              :publicMode="false"
            />
          </div>

          <!-- Image Block -->
          <div v-else-if="block.type === 'image'" class="media-block">
            <div v-if="block.content" class="preview">
              <img :src="block.content" :alt="block.alt || ''" />
              <input
                type="text"
                v-model="block.alt"
                placeholder="Image alt text"
                class="media-caption"
              />
            </div>
            <div v-else class="upload-prompt">
              <FileUploader
                acceptedTypes="image/*"
                :maxFileSize="2 * 1024 * 1024"
                @upload-complete="(files) => handleMediaUpload(files, index)"
                @upload-error="handleError"
              />
            </div>
          </div>

          <!-- Video Block -->
          <div v-else-if="block.type === 'video'" class="media-block">
            <input
              type="text"
              v-model="block.content"
              placeholder="Enter video URL (YouTube, Vimeo)"
              @input="updateVideoEmbed(index)"
            />
            <div v-if="block.embed" class="video-preview" v-html="block.embed"></div>
          </div>

          <!-- File Block -->
          <div v-else-if="block.type === 'file'" class="media-block">
            <div v-if="block.content" class="file-preview">
              <i class="fas fa-file"></i>
              <span>{{ block.fileName }}</span>
              <button type="button" class="btn-link" @click="removeFile(index)">
                Remove
              </button>
            </div>
            <div v-else class="upload-prompt">
              <FileUploader
                acceptedTypes=".pdf,.doc,.docx,.xls,.xlsx"
                :maxFileSize="5 * 1024 * 1024"
                @upload-complete="(files) => handleFileUpload(files, index)"
                @upload-error="handleError"
              />
            </div>
          </div>
        </div>
      </div>

      <button type="button" class="add-block-btn" @click="addBlock">
        <i class="fas fa-plus"></i> Add Content Block
      </button>
    </div>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import FileUploader from './FileUploader.vue'
import WysiwygEditor from './WysiwygEditor.vue'

const props = defineProps({
  initialData: {
    type: Object,
    default: () => ({
      title: '',
      slug: '',
      blocks: []
    })
  }
})

const emit = defineEmits(['save', 'cancel', 'error'])

const form = reactive({
  title: props.initialData.title,
  slug: props.initialData.slug,
  blocks: [...props.initialData.blocks] || []
})

const saving = ref(false)
const error = ref('')

const addBlock = () => {
  form.blocks.push({
    type: 'text',
    content: ''
  })
}

const removeBlock = (index) => {
  form.blocks.splice(index, 1)
}

const moveBlockUp = (index) => {
  if (index > 0) {
    const block = form.blocks[index]
    form.blocks.splice(index, 1)
    form.blocks.splice(index - 1, 0, block)
  }
}

const moveBlockDown = (index) => {
  if (index < form.blocks.length - 1) {
    const block = form.blocks[index]
    form.blocks.splice(index, 1)
    form.blocks.splice(index + 1, 0, block)
  }
}

const updateBlockType = (index) => {
  form.blocks[index].content = ''
}

const handleWysiwygImageUpload = (imageData) => {
  // You can handle additional logic here if needed
  console.log('Image uploaded via WYSIWYG editor:', imageData)
}

const handleMediaUpload = (files, index) => {
  if (files && files.length > 0) {
    form.blocks[index].content = files[0].url
    form.blocks[index].alt = files[0].name
  }
}

const handleFileUpload = (files, index) => {
  if (files && files.length > 0) {
    form.blocks[index].content = files[0].url
    form.blocks[index].fileName = files[0].name
  }
}

const updateVideoEmbed = (index) => {
  const url = form.blocks[index].content
  if (url.includes('youtube.com') || url.includes('youtu.be')) {
    const videoId = url.split('v=')[1] || url.split('/').pop()
    form.blocks[index].embed = `<iframe width="100%" height="315" src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe>`
  } else if (url.includes('vimeo.com')) {
    const videoId = url.split('/').pop()
    form.blocks[index].embed = `<iframe width="100%" height="315" src="https://player.vimeo.com/video/${videoId}" frameborder="0" allowfullscreen></iframe>`
  }
}

const removeFile = (index) => {
  form.blocks[index].content = ''
  form.blocks[index].fileName = ''
}

const handleError = (error) => {
  this.error = error
}

const savePage = async () => {
  saving.value = true
  error.value = ''

  try {
    emit('save', form)
  } catch (err) {
    error.value = 'Failed to save page. Please try again.'
    emit('error', err)
  } finally {
    saving.value = false
  }
}

// Initialize with at least one block if empty
onMounted(() => {
  if (form.blocks.length === 0) {
    addBlock()
  }
})
</script>

<style lang="scss" scoped>
.page-editor {
  .editor-header {
    margin-bottom: 2rem;
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 1rem;
    align-items: start;
  }

  .form-group {
    margin-bottom: 1rem;

    label {
      display: block;
      margin-bottom: 0.5rem;
      color: #2c3e50;
    }

    input {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;

      &:focus {
        outline: none;
        border-color: #3498db;
      }
    }
  }

  .form-actions {
    display: flex;
    gap: 1rem;
  }

  .content-blocks {
    .content-block {
      background: white;
      border: 1px solid #ddd;
      border-radius: 8px;
      margin-bottom: 1rem;
      overflow: hidden;

      .block-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: #f8f9fa;
        border-bottom: 1px solid #ddd;

        select {
          padding: 0.5rem;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 1rem;
        }

        .block-actions {
          display: flex;
          gap: 0.5rem;
        }
      }

      .block-content {
        padding: 1rem;
      }
    }
  }

  .text-editor {
    .editor-toolbar {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 1rem;
      padding: 0.5rem;
      background: #f8f9fa;
      border: 1px solid #ddd;
      border-radius: 4px;
    }

    .format-btn {
      padding: 0.5rem;
      border: none;
      background: none;
      cursor: pointer;
      color: #2c3e50;
      border-radius: 4px;

      &:hover {
        background: #eee;
      }

      &.active {
        background: #3498db;
        color: white;
      }
    }

    .editor-content {
      min-height: 200px;
      padding: 1rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      outline: none;

      &:focus {
        border-color: #3498db;
      }
    }
  }

  .media-block {
    .preview {
      img {
        max-width: 100%;
        border-radius: 4px;
      }

      .media-caption {
        width: 100%;
        padding: 0.75rem;
        margin-top: 1rem;
        border: 1px solid #ddd;
        border-radius: 4px;
      }
    }

    .video-preview {
      margin-top: 1rem;
      
      iframe {
        border-radius: 4px;
      }
    }

    .file-preview {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      background: #f8f9fa;
      border-radius: 4px;

      i {
        font-size: 1.5rem;
        color: #3498db;
      }
    }
  }

  .add-block-btn {
    width: 100%;
    padding: 1rem;
    background: #f8f9fa;
    border: 2px dashed #ddd;
    border-radius: 8px;
    color: #7f8c8d;
    cursor: pointer;
    transition: all 0.3s;

    &:hover {
      background: #eee;
      border-color: #3498db;
      color: #3498db;
    }
  }

  .btn-primary {
    background: #3498db;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.3s;

    &:hover {
      background: #2980b9;
    }

    &:disabled {
      background: #95a5a6;
      cursor: not-allowed;
    }
  }

  .btn-secondary {
    background: #95a5a6;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.3s;

    &:hover {
      background: #7f8c8d;
    }
  }

  .btn-icon {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 4px;
    background: white;
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

    &:disabled {
      background: #eee;
      color: #95a5a6;
      cursor: not-allowed;
    }
  }

  .btn-link {
    background: none;
    border: none;
    color: #e74c3c;
    cursor: pointer;
    padding: 0;
    font-size: 0.9rem;

    &:hover {
      text-decoration: underline;
    }
  }

  .error-message {
    margin-top: 1rem;
    padding: 1rem;
    background: #fadbd8;
    color: #e74c3c;
    border-radius: 4px;
  }
}
</style> 