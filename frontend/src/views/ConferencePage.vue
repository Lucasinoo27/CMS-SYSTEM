<template>
  <div class="conference-page">
    <!-- Page Header -->
    <header class="page-header">
      <h1>{{ conference ? conference.name : "" }}</h1>
      <router-link to="/" class="btn-secondary">
        <i class="fas fa-arrow-left"></i>
        Back to Home
      </router-link>
    </header>

    <!-- Loading and Error States -->
    <div v-if="loading" class="loading">Loading conference data...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <div v-else-if="!conference" class="error">Conference not found</div>

    <!-- Conference Content -->
    <div v-else class="conference-container">
      <!-- Conference Header -->
      <div class="conference-details">
        <div class="conference-info">
          <p class="conference-location">{{ conference.location }}</p>
          <p class="conference-date">
            {{ formatDate(conference.start_date) }} -
            {{ formatDate(conference.end_date) }}
          </p>
        </div>
        <div class="conference-description">
          <p>{{ conference.description }}</p>
        </div>
      </div>

      <!-- Main Content Area with Sidebar -->
      <div class="content-wrapper">
        <!-- Sidebar with Pages Navigation -->
        <div class="sidebar">
          <h2>Pages</h2>
          <div v-if="!pages || pages.length === 0" class="no-pages-sidebar">
            No published pages available
          </div>
          <ul v-else class="page-nav">
            <li
              v-for="page in pages"
              :key="page.id"
              :class="{ active: selectedPageId === page.id }"
              @click="selectPage(page.id)"
            >
              {{ page.title }}
            </li>
          </ul>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
          <div v-if="!selectedPage" class="select-page-prompt">
            <p>Please select a page from the sidebar to view its content</p>
          </div>
          <div v-else class="page-content">
            <h2>{{ selectedPage.title }}</h2>

            <!-- Page Files Section -->
            <div v-if="pageFiles.length > 0" class="page-files">
              <h3>Attached Files</h3>
              <div class="file-grid">
                <div v-for="file in pageFiles" :key="file.id" class="file-item">
                  <div class="file-icon">
                    <i :class="getFileIcon(file.mime_type)"></i>
                  </div>
                  <div class="file-info">
                    <div class="file-name">{{ file.original_filename }}</div>
                    <div class="file-meta">
                      {{ formatFileSize(file.size) }}
                    </div>
                  </div>
                  <div class="file-actions">
                    <button @click="downloadFile(file)" class="btn-download">
                      <i class="fas fa-download"></i> Download
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div
              v-if="selectedPage.contents && selectedPage.contents.length > 0"
              class="content-blocks"
            >
              <div
                v-for="content in selectedPage.contents"
                :key="content.id"
                class="content-block"
                v-html="content.content"
              ></div>
            </div>
            <div v-else-if="pageFiles.length === 0" class="no-content">
              No content available for this page.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRoute } from "vue-router";
import { conferenceApi, fileApi } from "@/services/api";
import axios from "axios";

const route = useRoute();
const conference = ref(null);
const pages = ref([]);
const selectedPageId = ref(null);
const loading = ref(true);
const error = ref(null);
const pageFiles = ref([]);
const loadingFiles = ref(false);

// Computed property to get the selected page
const selectedPage = computed(() => {
  if (!selectedPageId.value || !pages.value.length) return null;
  return pages.value.find((page) => page.id === selectedPageId.value) || null;
});

// Function to select a page
const selectPage = (pageId) => {
  selectedPageId.value = pageId;
  fetchPageFiles(pageId);
};

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return "Date not set";

  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) {
      return "Invalid date";
    }
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  } catch (error) {
    return "Invalid date";
  }
};

// Get appropriate icon for file type
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

// Format file size for display
const formatFileSize = (bytes) => {
  if (!bytes) return "0 Bytes";

  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

// Fetch files associated with the selected page
const fetchPageFiles = async (pageId) => {
  if (!conference.value || !pageId) return;

  try {
    loadingFiles.value = true;
    pageFiles.value = [];

    const response = await fileApi.getPageFiles(conference.value.id, pageId);

    // Handle different response formats
    if (response.data && response.data.data) {
      pageFiles.value = response.data.data;
    } else if (response.data && Array.isArray(response.data)) {
      pageFiles.value = response.data;
    }
  } catch (err) {
    console.error("Error fetching page files:", err);
    // Don't show error to the user, just log it
  } finally {
    loadingFiles.value = false;
  }
};

// Download file function
const downloadFile = async (file) => {
  try {
    // Use direct axios call instead of api service to bypass auth interceptors
    const baseUrl = import.meta.env.VITE_API_URL || "/api";
    const downloadUrl = `${baseUrl}/files/${file.id}/download`;

    console.log("Downloading file from:", downloadUrl);

    // Use axios directly to avoid authentication headers
    const response = await axios.get(downloadUrl, {
      responseType: "blob",
    });

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
  } catch (err) {
    console.error("Error downloading file:", err);
    console.error(err.response || err);
  }
};

const fetchConferenceData = async () => {
  try {
    loading.value = true;
    error.value = null;

    // First fetch the conference details
    const conferenceResponse = await conferenceApi.get(route.params.slug);
    conference.value = conferenceResponse.data;

    // Then fetch the pages
    const pagesResponse = await conferenceApi.getPages(
      conferenceResponse.data.id
    );

    // Extract pages from the response based on its structure
    let pagesData = [];

    if (pagesResponse.data && pagesResponse.data.data) {
      if (Array.isArray(pagesResponse.data.data.data)) {
        pagesData = pagesResponse.data.data.data;
      } else if (Array.isArray(pagesResponse.data.data)) {
        pagesData = pagesResponse.data.data;
      }
    } else if (Array.isArray(pagesResponse.data)) {
      pagesData = pagesResponse.data;
    }

    // to ensure only published pages are shown
    pages.value = pagesData.filter((page) => page.status === "published");

    // Select the first page by default if available
    if (pages.value.length > 0) {
      selectedPageId.value = pages.value[0].id;
      fetchPageFiles(selectedPageId.value);
    }
  } catch (err) {
    error.value = err.message || "Failed to load conference data";
    console.error("Error fetching conference:", err);
  } finally {
    loading.value = false;
  }
};

// Watch for changes to the selected page ID
watch(selectedPageId, (newPageId) => {
  if (newPageId) {
    fetchPageFiles(newPageId);
  }
});

onMounted(fetchConferenceData);
</script>

<style scoped>
.conference-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
}

.page-header h1 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.8rem;
}

.btn-secondary {
  background: #95a5a6;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: background 0.3s;
}

.btn-secondary:hover {
  background: #7f8c8d;
}

.loading,
.error {
  text-align: center;
  padding: 3rem;
  font-size: 1.2rem;
}

.error {
  color: #dc3545;
  background-color: #f8d7da;
  border-radius: 0.5rem;
}

.conference-container {
  padding: 0 0 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.conference-details {
  display: flex;
  flex-wrap: wrap;
  gap: 2rem;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid #e9ecef;
}

.conference-info {
  flex: 1;
  min-width: 250px;
}

.conference-location,
.conference-date {
  font-size: 1.1rem;
  color: #495057;
  margin-bottom: 0.75rem;
}

.conference-description {
  flex: 2;
  min-width: 300px;
}

.conference-description p {
  font-size: 1.1rem;
  line-height: 1.6;
  color: #495057;
}

.content-wrapper {
  display: flex;
  gap: 2rem;
}

.sidebar {
  width: 250px;
  flex-shrink: 0;
  background-color: #f8f9fa;
  border-radius: 0.5rem;
  padding: 1.5rem;
  height: fit-content;
}

.sidebar h2 {
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
  color: #212529;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e9ecef;
}

.no-pages-sidebar {
  color: #6c757d;
  font-style: italic;
  padding: 1rem 0;
}

.page-nav {
  list-style: none;
  padding: 0;
  margin: 0;
}

.page-nav li {
  padding: 0.75rem 1rem;
  margin-bottom: 0.5rem;
  border-radius: 0.25rem;
  cursor: pointer;
  transition: all 0.2s ease;
  color: #495057;
}

.page-nav li:hover {
  background-color: #e9ecef;
}

.page-nav li.active {
  background-color: #007bff;
  color: white;
}

.main-content {
  flex: 1;
  min-width: 0;
}

.select-page-prompt {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 300px;
  background-color: #f8f9fa;
  border-radius: 0.5rem;
  color: #6c757d;
  font-size: 1.2rem;
}

.page-content {
  overflow: hidden;
  background-color: white;
  border-radius: 0.5rem;
  padding: 2rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.page-content h2 {
  font-size: 2rem;
  margin-bottom: 1.5rem;
  color: #212529;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e9ecef;
}

.page-files {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

.page-files h3 {
  font-size: 1.3rem;
  margin-bottom: 1rem;
  color: #343a40;
}

.file-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.file-item {
  border: 1px solid #e9ecef;
  border-radius: 0.5rem;
  padding: 1rem;
  display: flex;
  flex-direction: row;
  transition: all 0.2s ease;
}

.file-item:hover {
  border-color: #007bff;
  box-shadow: 0 2px 8px rgba(0, 123, 255, 0.15);
}

.file-icon {
  font-size: 2rem;
  color: #007bff;
  margin-right: 1rem;
  text-align: center;
}

.file-info {
  flex: 1;
}

.file-name {
  font-weight: 500;
  margin-bottom: 0.5rem;
  word-break: break-word;
}

.file-meta {
  font-size: 0.85rem;
  color: #6c757d;
}

.file-actions {
  margin-top: 1rem;
  text-align: center;
}

.btn-download {
  display: inline-block;
  padding: 0.5rem 1rem;
  background-color: #007bff;
  color: white;
  border-radius: 0.25rem;
  text-decoration: none;
  font-size: 0.9rem;
  transition: background-color 0.2s;
}

.btn-download:hover {
  background-color: #0069d9;
}

.content-blocks {
  margin-top: 1.5rem;
}

.content-block {
  margin-bottom: 2rem;
}

.content-block:last-child {
  margin-bottom: 0;
}

.no-content {
  color: #6c757d;
  font-style: italic;
  padding: 2rem;
  text-align: center;
  background-color: #f8f9fa;
  border-radius: 0.25rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .content-wrapper {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
    margin-bottom: 2rem;
  }

  .conference-details {
    flex-direction: column;
    gap: 1rem;
  }

  .file-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  }
}
</style>
