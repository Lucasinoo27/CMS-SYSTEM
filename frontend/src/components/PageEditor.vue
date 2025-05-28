<template>
  <div class="page-editor">
    <!-- Top toolbar with save/cancel actions -->
    <div class="editor-toolbar">
      <div class="toolbar-actions">
        <button type="button" class="btn-secondary" @click="$emit('cancel')">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button
          type="button"
          class="btn-primary"
          @click="savePage"
          :disabled="saving || !form.title || !form.slug"
        >
          <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-save'"></i>
          {{ saving ? " Saving..." : "Save Page" }}
        </button>
      </div>
    </div>

    <!-- Main editor content in a card layout -->
    <div class="editor-card">
      <!-- Basic page information section -->
      <div class="editor-section">
        <h3 class="section-title">
          <i class="fas fa-info-circle"></i> Basic Information
        </h3>

        <div class="form-row">
          <div class="form-group">
            <label for="title">Page Title</label>
            <input
              type="text"
              id="title"
              v-model="form.title"
              required
              placeholder="Enter page title"
              class="form-control"
            />
          </div>

          <div class="form-group">
            <label for="slug">URL Slug</label>
            <div class="input-group">
              <input
                type="text"
                id="slug"
                v-model="form.slug"
                required
                placeholder="page-url-slug"
                class="form-control"
              />
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="layout">Layout Template</label>
            <select
              id="layout"
              v-model="form.layout"
              required
              class="form-control"
            >
              <option value="default">Default Layout</option>
              <option value="full-width">Full Width</option>
              <option value="sidebar">With Sidebar</option>
            </select>
          </div>

          <div class="form-group">
            <label for="meta_description">Meta Description</label>
            <input
              type="text"
              id="meta_description"
              v-model="form.meta_description"
              placeholder="Brief description for search engines"
              class="form-control"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group visibility-toggle">
            <label class="toggle-label">
              <span class="status-text">Status:</span>
              <button
                type="button"
                @click="toggleStatus"
                class="btn-toggle"
                :class="form.status === 'published' ? 'visible' : 'hidden'"
              >
                <i
                  class="fas"
                  :class="
                    form.status === 'published' ? 'fa-eye' : 'fa-eye-slash'
                  "
                ></i>
                {{ form.status === "published" ? "Published" : "Draft" }}
              </button>
            </label>
          </div>
        </div>
      </div>

      <!-- Content blocks section -->
      <div class="editor-section">
        <h3 class="section-title">
          <i class="fas fa-paragraph"></i> Page Content
        </h3>

        <div class="content-blocks">
          <div
            v-for="(block, index) in form.blocks"
            :key="index"
            class="content-block"
          >
            <div class="block-header">
              <div class="block-type">
                <select
                  v-model="block.type"
                  @change="updateBlockType(index)"
                  class="form-control"
                >
                  <option value="text">Text Block</option>
                  <option value="image">Image Block</option>
                  <option value="video">Video Block</option>
                  <option value="file">File Block</option>
                </select>
              </div>

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
                    class="media-caption form-control"
                  />
                </div>
                <div v-else class="upload-prompt">
                  <FileUploader
                    acceptedTypes="image/*"
                    :maxFileSize="2 * 1024 * 1024"
                    @upload-complete="
                      (files) => handleMediaUpload(files, index)
                    "
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
                  class="form-control"
                />
                <div
                  v-if="block.embed"
                  class="video-preview"
                  v-html="block.embed"
                ></div>
              </div>

              <!-- File Block -->
              <div v-else-if="block.type === 'file'" class="media-block">
                <div v-if="block.content" class="file-preview">
                  <i class="fas fa-file"></i>
                  <span>{{ block.fileName }}</span>
                  <button
                    type="button"
                    class="btn-link"
                    @click="removeFile(index)"
                  >
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
      </div>
    </div>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from "vue";
import FileUploader from "./FileUploader.vue";
import WysiwygEditor from "./WysiwygEditor.vue";

const props = defineProps({
  initialData: {
    type: Object,
    default: () => ({
      title: "",
      slug: "",
      meta_description: "",
      layout: "default",
      status: "draft",
      blocks: [],
    }),
  },
});

const emit = defineEmits(["save", "cancel", "error"]);

const form = reactive({
  title: props.initialData?.title || "",
  slug: props.initialData?.slug || "",
  meta_description: props.initialData?.meta_description || "",
  layout: props.initialData?.layout || "default",
  status: props.initialData?.status || "draft",
  blocks: [],
});

watch(
  () => props.initialData,
  (newValue) => {
    console.log("PageEditor initialData changed:", newValue);
    if (newValue) {
      form.title = newValue.title;
      form.slug = newValue.slug;
      form.meta_description = newValue.meta_description || "";
      form.layout = newValue.layout || "default";
      form.status = newValue.status || "draft";

      // Only update blocks if they exist and are an array
      if (Array.isArray(newValue.blocks) && newValue.blocks.length > 0) {
        console.log("Updating form blocks with:", newValue.blocks);
        form.blocks = [...newValue.blocks];
      }

      console.log("Form updated with new initialData:", form);
    }
  },
  { deep: true }
);

const saving = ref(false);
const error = ref("");
const saveSuccess = ref(false);

const addBlock = () => {
  form.blocks.push({
    type: "text",
    content: "",
  });
};

const removeBlock = (index) => {
  form.blocks.splice(index, 1);
};

const moveBlockUp = (index) => {
  if (index > 0) {
    const block = form.blocks[index];
    form.blocks.splice(index, 1);
    form.blocks.splice(index - 1, 0, block);
  }
};

const moveBlockDown = (index) => {
  if (index < form.blocks.length - 1) {
    const block = form.blocks[index];
    form.blocks.splice(index, 1);
    form.blocks.splice(index + 1, 0, block);
  }
};

const updateBlockType = (index) => {
  form.blocks[index].content = "";
};

const handleWysiwygImageUpload = (imageData) => {
  // You can handle additional logic here if needed
  console.log("Image uploaded via WYSIWYG editor:", imageData);
};

const handleMediaUpload = (files, index) => {
  if (files && files.length > 0) {
    form.blocks[index].content = files[0].url;
    form.blocks[index].alt = files[0].name;
  }
};

const handleFileUpload = (files, index) => {
  if (files && files.length > 0) {
    form.blocks[index].content = files[0].url;
    form.blocks[index].fileName = files[0].name;
  }
};

const updateVideoEmbed = (index) => {
  const url = form.blocks[index].content;
  if (url.includes("youtube.com") || url.includes("youtu.be")) {
    const videoId = url.split("v=")[1] || url.split("/").pop();
    form.blocks[
      index
    ].embed = `<iframe width="100%" height="315" src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe>`;
  } else if (url.includes("vimeo.com")) {
    const videoId = url.split("/").pop();
    form.blocks[
      index
    ].embed = `<iframe width="100%" height="315" src="https://player.vimeo.com/video/${videoId}" frameborder="0" allowfullscreen></iframe>`;
  }
};

const removeFile = (index) => {
  form.blocks[index].content = "";
  form.blocks[index].fileName = "";
};

const handleError = (error) => {
  this.error = error;
};

const savePage = async () => {
  if (!form.title || !form.slug) {
    error.value = "Title and slug are required.";
    return;
  }

  saving.value = true;
  error.value = "";
  saveSuccess.value = false;

  try {
    await emit("save", form);
    saveSuccess.value = true;
    // Clear success message after 3 seconds
    setTimeout(() => {
      saveSuccess.value = false;
    }, 3000);
  } catch (err) {
    error.value = "Failed to save page. Please try again.";
    emit("error", err);
  } finally {
    saving.value = false;
  }
};

const generateSlug = () => {
  if (form.title) {
    // Get the conference slug prefix
    const conferenceSlug = props.initialData.conference_slug || "";

    // Convert title to slug format
    let pageSlug = form.title
      .toLowerCase()
      .replace(/\s+/g, "-") // Replace spaces with hyphens
      .replace(/[^\w\-]+/g, "") // Remove all non-word chars
      .replace(/\-\-+/g, "-") // Replace multiple hyphens with single hyphen
      .replace(/^-+/, "") // Trim hyphens from start
      .replace(/-+$/, ""); // Trim hyphens from end

    // Combine conference slug with page slug if conference slug exists
    if (conferenceSlug && !pageSlug.startsWith(conferenceSlug)) {
      // Check if the title already includes the conference name to avoid duplication
      if (!form.title.toLowerCase().includes(conferenceSlug.toLowerCase())) {
        form.slug = `${conferenceSlug}-${pageSlug}`;
      } else {
        form.slug = pageSlug;
      }
    } else {
      form.slug = pageSlug;
    }

    console.log("Generated slug:", form.slug);
  }
};

// Watch the title for changes and update the slug in real-time
watch(
  () => form.title,
  (newTitle) => {
    generateSlug();
  },
  { immediate: true }
);

const toggleStatus = () => {
  form.status = form.status === "published" ? "draft" : "published";
};

// Initialize with at least one block if empty
onMounted(() => {
  if (form.blocks.length === 0) {
    addBlock();
  }
});
</script>

<style lang="scss" scoped>
.page-editor {
  max-width: 1200px;
  margin: 0 auto;

  .editor-toolbar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;

    .toolbar-actions {
      display: flex;
      gap: 0.75rem;
    }
  }

  .editor-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    overflow: hidden;
  }

  .editor-section {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;

    &:last-child {
      border-bottom: none;
    }

    .section-title {
      margin: 0 0 1.5rem;
      font-size: 1.2rem;
      color: #2c3e50;
      display: flex;
      align-items: center;
      gap: 0.5rem;

      i {
        color: #3498db;
      }
    }
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;

    &:last-child {
      margin-bottom: 0;
    }

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .form-group {
    margin-bottom: 0;

    label {
      display: block;
      margin-bottom: 0.5rem;
      color: #2c3e50;
      font-weight: 500;
    }

    .input-group {
      display: flex;

      .form-control {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        flex: 1;
      }

      .btn-icon {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        background: #f8f9fa;
        border: 1px solid #ddd;
        border-left: none;
        width: 42px;

        &:hover {
          background: #e9ecef;
        }
      }
    }
  }

  .form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;

    &:focus {
      outline: none;
      border-color: #3498db;
      box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    }
  }

  .toggle-switch {
    position: relative;
    display: inline-flex;
    align-items: center;
    cursor: pointer;

    input {
      opacity: 0;
      width: 0;
      height: 0;

      &:checked + .toggle-slider {
        background-color: #2ecc71;

        &:before {
          transform: translateX(22px);
        }
      }
    }

    .toggle-slider {
      position: relative;
      width: 48px;
      height: 26px;
      background-color: #95a5a6;
      border-radius: 34px;
      transition: 0.4s;
      margin-right: 10px;

      &:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        border-radius: 50%;
        transition: 0.4s;
      }
    }

    .toggle-label {
      font-weight: 500;
    }
  }

  .content-blocks {
    .content-block {
      background: white;
      border: 1px solid #ddd;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);

      .block-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: #f8f9fa;
        border-bottom: 1px solid #ddd;

        .block-type {
          display: flex;
          align-items: center;
          gap: 0.75rem;

          select {
            min-width: 140px;
          }
        }

        .block-actions {
          display: flex;
          gap: 0.5rem;
        }
      }

      .block-content {
        padding: 1.5rem;
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
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;

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
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;

    &:hover {
      background: #2980b9;
    }

    &:disabled {
      background: #95a5a6;
      cursor: not-allowed;
    }
  }

  .btn-secondary {
    background: #f8f9fa;
    color: #2c3e50;
    border: 1px solid #ddd;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;

    &:hover {
      background: #e9ecef;
      border-color: #ccc;
    }
  }

  .btn-icon {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 4px;
    background: white;
    color: #3498db;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;

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
    color: #3498db;
    cursor: pointer;
    padding: 0;
    font-size: 0.9rem;
    text-decoration: underline;

    &:hover {
      color: #2980b9;
    }
  }

  .error-message {
    margin-top: 1rem;
    padding: 1rem;
    background: #fadbd8;
    color: #e74c3c;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 0.5rem;

    &::before {
      content: "\f06a";
      font-family: "Font Awesome 5 Free";
      font-weight: 900;
    }
  }
}
</style>
