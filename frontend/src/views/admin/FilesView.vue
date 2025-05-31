<template>
  <div class="files-view">
    <header class="page-header">
      <h1>File Management</h1>
      <p class="page-description">
        Upload, download, and manage document files across the system
      </p>
    </header>

    <div class="page-content">
      <div class="file-management-section">
        <h2>Upload Files</h2>
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
              accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"
            />
          </div>
          <button
            type="submit"
            class="upload-button"
            :disabled="!selectedFile || uploading"
          >
            {{ uploading ? "Uploading..." : "Upload" }}
          </button>
        </form>
      </div>

      <div class="file-list-section" v-if="!loading">
        <h2>All Files</h2>

        <div v-if="error" class="error-message">{{ error }}</div>

        <div v-else-if="files.length === 0" class="empty-state">
          No files have been uploaded yet.
        </div>

        <div v-else class="file-grid">
          <div v-for="file in files" :key="file.id" class="file-item">
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
            <div class="file-actions">
              <button
                @click="downloadFile(file)"
                class="btn-icon download"
                title="Download"
              >
                <i class="fas fa-download"></i>
              </button>
              <button
                @click="confirmDeleteFile(file)"
                class="btn-icon delete"
                title="Delete"
              >
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="loading">Loading files...</div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal">
      <div class="modal-content">
        <h3>Delete File</h3>
        <p>
          Are you sure you want to delete "{{
            selectedFile?.original_filename
          }}"?
        </p>
        <p class='warning'>This action cannot be undone.</p>
        <div class="modal-actions">
          <button @click="showDeleteModal = false" class="btn-secondary">
            Cancel
          </button>
          <button @click="deleteFile" class="btn-danger" :disabled="submitting">
            {{ submitting ? "Deleting..." : "Delete" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { fileApi } from "@/services/api";

// State
const files = ref([]);
const loading = ref(false);
const error = ref("");
const submitting = ref(false);
const showDeleteModal = ref(false);
const selectedFile = ref(null);
const uploading = ref(false);

// File upload handling
const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  // Check if file type is allowed
  const allowedTypes = [
    "application/pdf",
    "application/msword",
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "application/vnd.ms-excel",
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    "application/vnd.ms-powerpoint",
    "application/vnd.openxmlformats-officedocument.presentationml.presentation",
    "application/zip",
    "text/plain",
  ];

  if (!allowedTypes.includes(file.type)) {
    error.value =
      "Only document files are allowed (.pdf, .doc, .docx, .xls, .xlsx, .ppt, .pptx, .txt, .zip)";
    e.target.value = ""; // Clear the file input
    return;
  }

  selectedFile.value = file;
  error.value = ""; // Clear any previous error
};

const uploadFile = async () => {
  if (!selectedFile.value) return;

  uploading.value = true;
  error.value = "";

  try {
    const formData = new FormData();
    formData.append("file", selectedFile.value);

    const response = await fileApi.upload(formData);
    console.log("Upload response:", response);

    // Refresh the file list
    fetchFiles();

    // Reset the file input
    selectedFile.value = null;
    document.getElementById("file-upload").value = "";
  } catch (err) {
    console.error("Error uploading file:", err);
    error.value =
      err.response?.data?.message || "Failed to upload file. Please try again.";
  } finally {
    uploading.value = false;
  }
};

// Fetch all files
const fetchFiles = async () => {
  loading.value = true;
  error.value = "";

  try {
    // Check if getAll method exists in fileApi
    if (!fileApi.getAll) {
      console.error("fileApi.getAll method is not defined");
      error.value = "API method not available. Please contact support.";
      return;
    }

    const response = await fileApi.getAll();

    // Debug response
    console.log("Files API response:", response);

    // Handle different response formats
    if (response.data && response.data.data) {
      files.value = response.data.data;
    } else if (response.data && Array.isArray(response.data)) {
      files.value = response.data;
    } else {
      console.error("Unexpected response format:", response.data);
      error.value = "Received invalid data format from server.";
      files.value = [];
    }
  } catch (err) {
    console.error("Error fetching files:", err);
    if (err.response) {
      console.error("Error response:", err.response.status, err.response.data);
      error.value = `Server error: ${err.response.status} ${err.response.statusText}`;
    } else if (err.request) {
      console.error("No response received:", err.request);
      error.value = "Server did not respond. Please check your connection.";
    } else {
      error.value = `Error: ${err.message}`;
    }
  } finally {
    loading.value = false;
  }
};

const downloadFile = async (file) => {
  try {
    // Generate download URL
    const baseUrl = import.meta.env.VITE_API_URL || "/api";
    const downloadUrl = `${baseUrl}/files/${file.id}/download`;

    // Set loading indicator
    const downloadingFile = ref(file.id);

    // Use the fileApi.download method
    const response = await fileApi.download(downloadUrl);

    // Create a download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.download = file.original_filename;
    document.body.appendChild(link);
    link.click();

    // Clean up
    window.URL.revokeObjectURL(url);
    document.body.removeChild(link);

    // Clear loading indicator
    setTimeout(() => {
      downloadingFile.value = null;
    }, 1000);
  } catch (err) {
    console.error("Error downloading file:", err);
    error.value = "Failed to download file. Please try again.";
    setTimeout(() => {
      error.value = "";
    }, 5000);
  }
};

const confirmDeleteFile = (file) => {
  selectedFile.value = file;
  showDeleteModal.value = true;
};

const deleteFile = async () => {
  if (!selectedFile.value) return;

  submitting.value = true;

  try {
    await fileApi.delete(selectedFile.value.id);
    files.value = files.value.filter(
      (file) => file.id !== selectedFile.value.id
    );
    showDeleteModal.value = false;
    selectedFile.value = null;
  } catch (err) {
    console.error("Error deleting file:", err);
    error.value = "Failed to delete file. Please try again.";
  } finally {
    submitting.value = false;
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

// Initialize
onMounted(() => {
  fetchFiles();
});
</script>

<style scoped>
.files-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.page-header {
  margin-bottom: 40px;
}

.page-header h1 {
  margin-bottom: 10px;
  color: #2c3e50;
}

.page-description {
  color: #6c757d;
  font-size: 1.1rem;
}

.page-content {
  overflow: hidden;
  background: white;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.file-management-section {
  margin-bottom: 40px;
}

.file-management-section h2,
.file-list-section h2 {
  margin-bottom: 20px;
  color: #2c3e50;
}

.upload-container {
  display: flex;
  gap: 15px;
}

.file-input-container {
  display: flex;
  flex-direction: column;
}

.file-input-label {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 15px;
  border: 2px dashed #ced4da;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s;
}

.file-input-label:hover {
  border-color: #3498db;
  background-color: rgba(52, 152, 219, 0.05);
}

.file-input-label i {
  font-size: 1.5rem;
  color: #3498db;
}

.file-input {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}

.upload-button {
  background-color: #3498db;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.3s;
}

.upload-button:hover {
  background-color: #2980b9;
}

.upload-button:disabled {
  background-color: #95a5a6;
  cursor: not-allowed;
}

.file-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.file-item {
  display: flex;
  align-items: center;
  padding: 15px;
  border-radius: 8px;
  border: 1px solid #e9ecef;
  transition: all 0.3s ease;
}

.file-item:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  border-color: #ced4da;
}

.file-icon {
  font-size: 24px;
  margin-right: 15px;
  color: #3498db;
  width: 40px;
  text-align: center;
}

.file-info {
  flex: 1;
  min-width: 0;
}

.file-name {
  font-weight: 500;
  margin-bottom: 5px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.file-meta {
  font-size: 0.85rem;
  color: #6c757d;
}

.file-actions {
  display: flex;
  gap: 8px;
}

.btn-icon {
  background: none;
  border: none;
  font-size: 16px;
  cursor: pointer;
  padding: 5px;
  border-radius: 4px;
  transition: all 0.2s;
}

.btn-icon.download {
  color: #3498db;
}

.btn-icon.download:hover {
  background-color: rgba(52, 152, 219, 0.1);
}

.btn-icon.delete {
  color: #e74c3c;
}

.btn-icon.delete:hover {
  background-color: rgba(231, 76, 60, 0.1);
}

.empty-state {
  text-align: center;
  padding: 40px 0;
  color: #6c757d;
}

.error-message {
  padding: 15px;
  background-color: #f8d7da;
  color: #721c24;
  border-radius: 4px;
  margin-bottom: 20px;
}

.loading {
  text-align: center;
  padding: 40px 0;
  color: #6c757d;
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
  background: white;
  padding: 30px;
  border-radius: 8px;
  max-width: 500px;
  width: 100%;
}

.warning {
  color: #e74c3c;
  font-weight: bold;
}

.modal-content h3 {
  margin-top: 0;
  margin-bottom: 20px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
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

.btn-danger {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.3s;

    i {
      font-size: 0.9rem;
    }

    &:hover {
      background: #2980b9;
    }

    &:disabled {
      background: #95a5a6;
      cursor: not-allowed;
    }
  }
</style>
