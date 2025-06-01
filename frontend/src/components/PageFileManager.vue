<template>
  <div class="page-file-manager">
    <div class="tabs">
      <button
        :class="{ active: activeTab === 'upload' }"
        @click="activeTab = 'upload'"
      >
        Upload New File
      </button>
      <button
        :class="{ active: activeTab === 'existing' }"
        @click="activeTab = 'existing'"
      >
        Assign Existing File
      </button>
      <button
        :class="{ active: activeTab === 'assigned' }"
        @click="activeTab = 'assigned'"
      >
        Assigned Files
      </button>
    </div>

    <div class="tab-content">
      <!-- Upload New File Tab -->
      <div v-if="activeTab === 'upload'" class="upload-tab">
        <form
          @submit.prevent="uploadFile"
          class="upload-container"
          enctype="multipart/form-data"
        >
          <div class="file-input-container">
            <label for="file-upload" class="file-input-label">
              <i class="fas fa-cloud-upload-alt"></i>
              <span>{{
                selectedFile ? selectedFile.name : "Choose a file"
              }}</span>
            </label>
            <input
              type="file"
              id="file-upload"
              @change="handleFileChange"
              class="file-input"
              accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.jpg,.jpeg,.png,.gif"
            />
          </div>
          <button
            type="submit"
            class="upload-button"
            :disabled="!selectedFile || uploading"
          >
            {{ uploading ? "Uploading..." : "Upload & Assign to Page" }}
          </button>
        </form>
      </div>

      <!-- Assign Existing File Tab -->
      <div v-if="activeTab === 'existing'" class="existing-tab">
        <div v-if="loading" class="loading">Loading files...</div>

        <div v-else-if="error" class="error-message">{{ error }}</div>

        <div v-else-if="allFiles.length === 0" class="empty-state">
          No files have been uploaded yet.
        </div>

        <div v-else class="file-grid">
          <div v-for="file in allFiles" :key="file.id" class="file-item">
            <div class="file-content">
              <div class="file-icon">
                <i :class="getFileIcon(file.mime_type)"></i>
              </div>
              <div class="file-info">
                <div class="file-name">{{ file.original_filename }}</div>
                <div class="file-meta">
                  {{ formatFileSize(file.size) }} •
                  {{ formatDate(file.created_at) }}
                </div>
              </div>
            </div>
            <div class="file-actions">
              <button
                @click="assignToPage(file)"
                class="btn-assign"
                :disabled="isAlreadyAssigned(file)"
              >
                {{
                  isAlreadyAssigned(file)
                    ? "Already Assigned"
                    : "Assign to Page"
                }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Assigned Files Tab -->
      <div v-if="activeTab === 'assigned'" class="assigned-tab">
        <div v-if="loadingPageFiles" class="loading">
          Loading assigned files...
        </div>

        <div v-else-if="errorPageFiles" class="error-message">
          {{ errorPageFiles }}
        </div>

        <div v-else-if="pageFiles.length === 0" class="empty-state">
          No files have been assigned to this page yet.
        </div>

        <div v-else class="file-grid">
          <div v-for="file in pageFiles" :key="file.id" class="file-item">
            <div class="file-content">
              <div class="file-icon">
                <i :class="getFileIcon(file.mime_type)"></i>
              </div>
              <div class="file-info">
                <div class="file-name">{{ file.original_filename }}</div>
                <div class="file-meta">
                  {{ formatFileSize(file.size) }} •
                  {{ formatDate(file.created_at) }}
                </div>
              </div>
            </div>
            <div class="file-actions">
              <button
                @click="downloadFile(file)"
                class="btn-icon download"
                title="Download File"
              >
                <i class="fas fa-download"></i>
              </button>
              <button
                @click="confirmRemove(file)"
                class="btn-icon remove"
                title="Remove from Page"
              >
                <i class="fas fa-unlink"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <div v-if="showRemoveModal" class="modal">
      <div class="modal-content">
        <h3>Remove File</h3>
        <p>
          Are you sure you want to remove "{{
            fileToRemove?.original_filename
          }}" from this page? This will not delete the file, only remove it from
          this page.
        </p>
        <div class="modal-actions">
          <button @click="showRemoveModal = false" class="btn-secondary">
            Cancel
          </button>
          <button @click="removeFile" class="btn-danger" :disabled="submitting">
            {{ submitting ? "Removing..." : "Remove" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import { fileApi } from "@/services/api";
import axios from "axios";

const props = defineProps({
  conferenceId: {
    type: [Number, String],
    required: true,
  },
  pageId: {
    type: [Number, String],
    required: true,
  },
  isNewPage: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  "file-assigned",
  "file-removed",
  "file-uploaded",
  "temp-file-uploaded",
  "temp-file-assigned",
  "temp-file-removed",
  "error",
]);

// State
const activeTab = ref("upload");
const allFiles = ref([]);
const pageFiles = ref([]);
const loading = ref(false);
const loadingPageFiles = ref(false);
const error = ref("");
const errorPageFiles = ref("");
const selectedFile = ref(null);
const uploading = ref(false);
const submitting = ref(false);
const showRemoveModal = ref(false);
const fileToRemove = ref(null);

// Load all files when component is mounted
onMounted(() => {
  fetchAllFiles();
  fetchPageFiles();
});

// Watch for tab changes to refresh data
watch(activeTab, (newVal) => {
  if (newVal === "existing") {
    fetchAllFiles();
  } else if (newVal === "assigned") {
    fetchPageFiles();
  }
});

// Fetch all files available in the system
const fetchAllFiles = async () => {
  loading.value = true;
  error.value = "";

  try {
    const response = await fileApi.getAll();
    allFiles.value = response.data.data || [];
  } catch (err) {
    console.error("Error fetching files:", err);
    error.value = err.response?.data?.message || "Failed to load files";
    emit("error", error.value);
  } finally {
    loading.value = false;
  }
};

// Fetch files assigned to this page
const fetchPageFiles = async () => {
  loadingPageFiles.value = true;
  errorPageFiles.value = "";

  try {
    // If it's a new page, we don't have any files yet, but we still want to show
    // the files that have been temporarily assigned in the current session
    if (props.isNewPage) {
      // Don't clear the pageFiles array, as it contains temporary assignments
      // that should be displayed to the user
      loadingPageFiles.value = false;
      return;
    }

    const response = await fileApi.getPageFiles(
      props.conferenceId,
      props.pageId
    );
    pageFiles.value = response.data.data || [];
  } catch (err) {
    console.error("Error fetching page files:", err);
    errorPageFiles.value =
      err.response?.data?.message || "Failed to load page files";
    emit("error", errorPageFiles.value);
  } finally {
    loadingPageFiles.value = false;
  }
};

// Check if a file is already assigned to the page
const isAlreadyAssigned = (file) => {
  return pageFiles.value.some((f) => f.id === file.id);
};

// File upload handling
const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  selectedFile.value = file;
  error.value = "";
};

const uploadFile = async () => {
  if (!selectedFile.value) return;

  uploading.value = true;
  error.value = "";

  try {
    const formData = new FormData();
    formData.append("file", selectedFile.value);

    if (props.isNewPage) {
      const response = await fileApi.upload(formData);
      const fileData = response.data.data;

      // Add to pageFiles for display
      pageFiles.value.push(fileData);

      // Emit for parent component to track this file
      emit("temp-file-uploaded", fileData);

      // Reset the file input
      selectedFile.value = null;
      document.getElementById("file-upload").value = "";

      // Switch to the assigned files tab
      activeTab.value = "assigned";
      return;
    }

    const response = await fileApi.uploadToPage(
      props.conferenceId,
      props.pageId,
      formData
    );

    // Add the new file to the page files list
    fetchPageFiles();

    // Reset the file input
    selectedFile.value = null;
    document.getElementById("file-upload").value = "";

    // Switch to the assigned files tab
    activeTab.value = "assigned";

    // Emit event
    emit("file-uploaded", response.data.data);
  } catch (err) {
    console.error("Error uploading file:", err);
    error.value = err.response?.data?.message || "Failed to upload file";
    emit("error", error.value);
  } finally {
    uploading.value = false;
  }
};

// Assign an existing file to the page
const assignToPage = async (file) => {
  if (isAlreadyAssigned(file)) return;

  submitting.value = true;
  error.value = "";

  try {
    // For new pages, just add to our temporary collection
    if (props.isNewPage) {
      // Add to page files for display
      pageFiles.value.push(file);

      // Emit an event for the parent to capture this file
      emit("temp-file-assigned", file);

      // Switch to assigned tab
      activeTab.value = "assigned";
      submitting.value = false;
      return;
    }

    await fileApi.assignToPage(props.conferenceId, props.pageId, file.id);

    // Refresh the page files list
    fetchPageFiles();

    // Switch to the assigned files tab
    activeTab.value = "assigned";

    // Emit event
    emit("file-assigned", file);
  } catch (err) {
    console.error("Error assigning file:", err);
    error.value = err.response?.data?.message || "Failed to assign file";
    emit("error", error.value);
  } finally {
    submitting.value = false;
  }
};

// Confirm file removal
const confirmRemove = (file) => {
  fileToRemove.value = file;
  showRemoveModal.value = true;
};

// Remove a file from the page
const removeFile = async () => {
  if (!fileToRemove.value) return;

  submitting.value = true;
  errorPageFiles.value = "";

  try {
    // For new pages, just remove from our temporary collection
    if (props.isNewPage) {
      // Remove from pageFiles
      const fileIndex = pageFiles.value.findIndex(
        (f) => f.id === fileToRemove.value.id
      );
      if (fileIndex !== -1) {
        pageFiles.value.splice(fileIndex, 1);
      }

      // Emit an event for the parent to handle
      emit("temp-file-removed", fileToRemove.value);

      // Close the modal
      showRemoveModal.value = false;
      fileToRemove.value = null;
      submitting.value = false;
      return;
    }

    console.log(
      `Removing file ${fileToRemove.value.id} from page ${props.pageId}`
    );

    const response = await fileApi.removeFromPage(
      props.conferenceId,
      props.pageId,
      fileToRemove.value.id
    );

    console.log("File removal response:", response);

    // Refresh the page files list
    fetchPageFiles();

    // Close the modal
    showRemoveModal.value = false;

    // Emit event
    emit("file-removed", fileToRemove.value);
  } catch (err) {
    console.error("Error removing file:", err);
    // More detailed error logging
    if (err.response) {
      console.error("Server response:", err.response.status, err.response.data);
      errorPageFiles.value = `Error (${err.response.status}): ${
        err.response.data.message || "Failed to remove file"
      }`;
    } else if (err.request) {
      console.error("No response received:", err.request);
      errorPageFiles.value = "Server did not respond. Please try again.";
    } else {
      errorPageFiles.value = `Error: ${err.message || "Failed to remove file"}`;
    }
    emit("error", errorPageFiles.value);
  } finally {
    submitting.value = false;
    if (!errorPageFiles.value) {
      fileToRemove.value = null;
      showRemoveModal.value = false;
    }
  }
};

// Download a file
const downloadFile = async (file) => {
  try {
    // Generate direct download URL
    const baseUrl = import.meta.env.VITE_API_URL || "/api";
    const downloadUrl = `${baseUrl}/files/${file.id}/download`;

    console.log("Downloading file from:", downloadUrl);

    // Use axios directly to get blob response
    const response = await axios.get(downloadUrl, {
      responseType: "blob",
    });

    // Create a download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", file.original_filename);
    document.body.appendChild(link);
    link.click();

    // Clean up
    window.URL.revokeObjectURL(url);
    document.body.removeChild(link);
  } catch (err) {
    console.error("Error downloading file:", err);
    emit("error", "Failed to download file");
  }
};

// Helper functions
const formatFileSize = (bytes) => {
  if (!bytes) return "0 Bytes";

  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

const formatDate = (dateString) => {
  if (!dateString) return "";

  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

const getFileIcon = (mimeType) => {
  const icons = {
    image: "fas fa-image",
    video: "fas fa-video",
    audio: "fas fa-music",
    "application/pdf": "fas fa-file-pdf",
    "application/msword": "fas fa-file-word",
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
      "fas fa-file-word",
    "application/vnd.ms-excel": "fas fa-file-excel",
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
      "fas fa-file-excel",
    "application/vnd.ms-powerpoint": "fas fa-file-powerpoint",
    "application/vnd.openxmlformats-officedocument.presentationml.presentation":
      "fas fa-file-powerpoint",
    "application/zip": "fas fa-file-archive",
    "text/plain": "fas fa-file-alt",
  };

  for (const [key, value] of Object.entries(icons)) {
    if (mimeType && mimeType.includes(key)) return value;
  }

  return "fas fa-file"; // Default icon
};
</script>

<style lang="scss" scoped>
.page-file-manager {
  margin-bottom: 2rem;
}

.section-header {
  margin-bottom: 1rem;

  h3 {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
  }

  p {
    color: #666;
    font-size: 0.9rem;
  }
}

.tabs {
  display: flex;
  border-bottom: 1px solid #ddd;
  margin-bottom: 1rem;

  button {
    background: none;
    border: none;
    padding: 0.75rem 1rem;
    cursor: pointer;
    font-size: 0.9rem;
    position: relative;

    &.active {
      font-weight: 600;

      &:after {
        content: "";
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: #4a6cf7;
      }
    }

    &:hover:not(.active) {
      background-color: #f5f5f5;
    }
  }
}

.tab-content {
  min-height: 200px;
}

.file-input-container {
  margin-bottom: 1rem;
}

.file-input-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: 1px dashed #ccc;
  border-radius: 4px;
  cursor: pointer;

  &:hover {
    border-color: #4a6cf7;
  }

  i {
    font-size: 1.25rem;
    color: #4a6cf7;
  }
}

.file-input {
  display: none;
}

.upload-button {
  padding: 0.75rem 1.5rem;
  background-color: #4a6cf7;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;

  &:disabled {
    background-color: #ccc;
    cursor: not-allowed;
  }

  &:hover:not(:disabled) {
    background-color: #3a5ce6;
  }
}

.file-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.file-item {
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 1rem;

  &.selected {
    border-color: #4a6cf7;
    background-color: #f0f4ff;
  }
}

.file-content {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.file-icon {
  font-size: 2rem;
  color: #4a6cf7;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
}

.file-info {
  flex: 1;
}

.file-name {
  font-weight: 500;
  margin-bottom: 0.25rem;
  word-break: break-word;
}

.file-meta {
  font-size: 0.8rem;
  color: #666;
}

.file-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.btn-icon {
  background: none;
  border: none;
  color: #555;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 4px;

  &:hover {
    background-color: #f5f5f5;
  }

  &.download {
    color: #4a6cf7;
  }

  &.remove {
    color: #dc3545;
  }
}

.btn-assign {
  padding: 0.5rem 1rem;
  background-color: #4a6cf7;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.8rem;

  &:disabled {
    background-color: #ccc;
    cursor: not-allowed;
  }

  &:hover:not(:disabled) {
    background-color: #3a5ce6;
  }
}

.loading {
  text-align: center;
  padding: 2rem;
  color: #666;
}

.error-message {
  color: #dc3545;
  padding: 1rem;
  background-color: #fdf2f2;
  border-radius: 4px;
}

.empty-state {
  text-align: center;
  padding: 2rem 1rem;
  color: #6c757d;
  background: #f8f9fa;
  border-radius: 4px;

  .helper-text {
    margin-top: 1rem;
    font-size: 0.9rem;
    color: #3498db;
    padding: 0.5rem;
    background-color: rgba(52, 152, 219, 0.1);
    border-radius: 4px;
    font-style: italic;
  }
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  padding: 2rem;
  border-radius: 8px;
  max-width: 500px;
  width: 100%;

  h3 {
    margin-top: 0;
    margin-bottom: 1rem;
  }

  p {
    margin-bottom: 1.5rem;
  }
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;

  button {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }

  .btn-secondary {
    background-color: #f0f0f0;

    &:hover {
      background-color: #e0e0e0;
    }
  }

  .btn-danger {
    background-color: #dc3545;
    color: white;

    &:hover:not(:disabled) {
      background-color: #c82333;
    }

    &:disabled {
      background-color: #e9a8af;
      cursor: not-allowed;
    }
  }
}
</style>
