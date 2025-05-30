<template>
  <div class="wysiwyg-editor">
    <Editor v-model="editorContent" :init="editorConfig" :disabled="disabled" @onInit="handleEditorInit" />
    <div v-if="error" class="error-message">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import Editor from '@tinymce/tinymce-vue';
import axios from 'axios';
import { useAuthStore } from '../stores/authStore';

const authStore = useAuthStore();

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  height: {
    type: Number,
    default: 400
  },
  publicMode: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'editor-ready', 'image-upload']);

const editorContent = ref(props.modelValue);
const error = ref("");

// Determine the upload endpoint based on authentication status
const uploadEndpoint = computed(() => {
  if (props.publicMode || !authStore.isAuthenticated) {
    return `/uploads/public`;
  }
  return `/uploads`;
});

// Watch for changes in the model value
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue !== editorContent.value) {
      editorContent.value = newValue;
    }
  }
);

// Watch for changes in the editor content
watch(
  () => editorContent.value,
  (newValue) => {
    emit("update:modelValue", newValue);
  }
);

const handleEditorInit = (editor) => {
  emit("editor-ready", editor);
};

// Handle image uploads
const handleImageUpload = (blobInfo, progress) => {
  return new Promise((resolve, reject) => {
    const formData = new FormData();
    formData.append("file", blobInfo.blob(), blobInfo.filename());

    const headers = {};
    // Add authorization header if authenticated
    if (authStore.isAuthenticated && !props.publicMode) {
      headers["Authorization"] = `Bearer ${authStore.token}`;
    }

    axios.post(uploadEndpoint.value, formData, {
      baseURL: import.meta.env.VITE_API_URL || "/api",
      headers: {
        "Content-Type": "multipart/form-data",
        ...headers,
      },
      onUploadProgress: (e) => {
        progress((e.loaded / e.total) * 100);
      },
    })
      .then((response) => {
        console.log("Upload response:", response.data);
        resolve(response.data.url);
        emit("image-upload", response.data);
      })
      .catch((err) => {
        console.error("Image upload error:", err);
        error.value = "Failed to upload image. Please try again.";
        reject({ message: "Image upload failed", remove: true });
      });
  });
};

// TinyMCE configuration
const editorConfig = {
  height: props.height,
  menubar: true,
  plugins: [
    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
    'insertdatetime', 'media', 'table', 'help', 'wordcount'
  ],
  toolbar: 'undo redo | formatselect | ' +
    'bold italic underline strikethrough | alignleft aligncenter ' +
    'alignright alignjustify | bullist numlist outdent indent | ' +
    'link image table | removeformat | help',
  content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 14px; }',
  images_upload_handler: handleImageUpload,
  file_picker_types: 'file image media',
  promotion: false,
  branding: false,
  table_default_attributes: {
    border: '1'
  },
  table_default_styles: {
    width: '100%'
  },
  table_responsive_width: true
};
</script>

<style scoped>
.wysiwyg-editor {
  margin-bottom: 1rem;
}

.error-message {
  color: #e74c3c;
  margin-top: 0.5rem;
  font-size: 0.875rem;
}

:deep(.tox-tinymce) {
  border-radius: 4px;
  border-color: #ddd;
}

:deep(.tox-editor-container) {
  background: white;
}

:deep(.tox-statusbar) {
  border-top: 1px solid #ddd;
}
</style>