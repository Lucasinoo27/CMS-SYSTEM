<template>
  <div class="pages-view">
    <header class="page-header">
      <h1>Page Management</h1>
      <p class="page-description">
        Manage pages across all conferences in the system
      </p>
    </header>

    <div v-if="loading" class="loading">Loading content...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <div v-else-if="conferences.length === 0" class="empty">
      No conferences in the system yet.
    </div>
    <div v-else class="conferences-grid">
      <div
        v-for="conference in conferences"
        :key="conference.id"
        class="conference-section"
      >
        <div class="conference-header">
          <h2>{{ conference.name }}</h2>
          <button class="btn-primary" @click="createPage(conference)">
            <i class="fas fa-plus"></i> Create New Page
          </button>
        </div>

        <div class="pages-grid">
          <div
            v-if="getConferencePages(conference.id).length === 0"
            class="empty"
          >
            No pages in this conference yet.
          </div>
          <div v-else class="page-cards">
            <div
              v-for="page in getConferencePages(conference.id)"
              :key="page.id"
              class="page-card"
            >
              <div class="page-info">
                <h3
                  @click="previewPage(page)"
                  class="page-title"
                  :title="`/${page.slug}`"
                >
                  {{ page.title }}
                  <i class="fas fa-eye hover-icon"></i>
                </h3>

                <div class="page-dates">
                  <span title="Created Date">
                    <i class="fas fa-plus-circle"></i>
                    <span class="date-text">{{
                      formatDate(page.created_at)
                    }}</span>
                  </span>
                  <span title="Last Updated">
                    <i class="fas fa-edit"></i>
                    <span class="date-text">{{
                      formatDate(page.updated_at)
                    }}</span>
                  </span>
                </div>
              </div>

              <div class="page-actions">
                <button
                  class="btn-icon"
                  @click="editPage(page)"
                  title="Edit Page"
                >
                  <i class="fas fa-edit"></i>
                </button>
                <button
                  class="btn-icon"
                  :class="page.status === 'published' ? 'published' : 'draft'"
                  @click="togglePageVisibility(page)"
                  :title="
                    page.status === 'published'
                      ? 'Set to Draft'
                      : 'Set to Published'
                  "
                >
                  <i
                    class="fas"
                    :class="
                      page.status === 'published' ? 'fa-eye' : 'fa-eye-slash'
                    "
                  ></i>
                </button>
                <button
                  class="btn-icon danger"
                  @click="confirmDeletePage(page)"
                  title="Delete Page"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Show editor within the conference section when editing a page from this conference -->
        <div
          v-if="
            showEditor &&
            selectedPage &&
            selectedPage.conference_id === conference.id
          "
          class="editor-container"
        >
          <PageEditor
            :initialData="selectedPage"
            @save="savePage"
            @cancel="closeEditor"
          />
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal">
      <div class="modal-content">
        <h3>Delete Page</h3>
        <p>Are you sure you want to delete "{{ selectedPage?.title }}"?</p>
        <p class="warning">This action cannot be undone.</p>
        <div class="modal-actions">
          <button class="btn-secondary" @click="showDeleteModal = false">
            Cancel
          </button>
          <button class="btn-danger" @click="deletePage" :disabled="submitting">
            {{ submitting ? "Deleting..." : "Delete" }}
          </button>
        </div>
      </div>
    </div>

    <!-- Success Notification -->
    <div v-if="showNotification" class="notification" :class="notificationType">
      {{ notificationMessage }}
      <button class="close-btn" @click="closeNotification">×</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import PageEditor from "@/components/PageEditor.vue";
import api, { pageApi } from "@/services/api";

const conferences = ref([]);
const pages = ref([]);
const loading = ref(false);
const error = ref("");
const showEditor = ref(false);
const showDeleteModal = ref(false);
const selectedPage = ref(null);
const submitting = ref(false);

// Notification system
const showNotification = ref(false);
const notificationMessage = ref("");
const notificationType = ref("success");
const hideNotificationTimer = ref(null);

const closeNotification = () => {
  showNotification.value = false;
};

// Auto-hide notifications after 5 seconds
watch(showNotification, (newValue) => {
  if (newValue && hideNotificationTimer.value) {
    clearTimeout(hideNotificationTimer.value);
  }
  if (newValue) {
    hideNotificationTimer.value = setTimeout(() => {
      showNotification.value = false;
    }, 5000);
  }
});

// Show a notification
const showSuccessNotification = (message) => {
  notificationMessage.value = message;
  notificationType.value = "success";
  showNotification.value = true;
};

const showErrorNotification = (message) => {
  notificationMessage.value = message;
  notificationType.value = "error";
  showNotification.value = true;
};

const showDeleteNotification = (message) => {
  notificationMessage.value = message;
  notificationType.value = "delete";
  showNotification.value = true;
};

const showVisibilityNotification = (message, isPublished) => {
  notificationMessage.value = message;
  notificationType.value = isPublished ? "published" : "draft";
  showNotification.value = true;
};

const fetchData = async () => {
  loading.value = true;
  error.value = null;
  try {
    // Use the admin endpoint to get all conferences and pages
    // Add a timestamp to prevent caching
    const timestamp = new Date().getTime();
    const response = await api.get(`/admin/pages?t=${timestamp}`, {
      timeout: 15000, // 15 second timeout
    });

    if (response.data && response.data.data) {
      // Sort conferences alphabetically
      conferences.value = (response.data.data.conferences || []).sort((a, b) =>
        a.name.localeCompare(b.name)
      );
      pages.value = response.data.data.pages || [];
    } else {
      conferences.value = [];
      pages.value = [];
      error.value = "No data received from server";
    }
  } catch (err) {
    console.error("Error fetching data:", err);
    if (err.code === "ECONNABORTED") {
      error.value =
        "Request timed out. The server is taking too long to respond.";
    } else if (err.response) {
      error.value = err.response.data.message || "Server error occurred.";
    } else if (err.request) {
      error.value = "No response from server. Please check your connection.";
    } else {
      error.value = "Failed to load content. Please try again.";
    }
  } finally {
    loading.value = false;
  }
};

const getConferencePages = (conferenceId) => {
  let conferencePages = pages.value.filter(
    (page) => page.conference_id === conferenceId
  );
  // Sort pages alphabetically
  return conferencePages.sort((a, b) => a.title.localeCompare(b.title));
};

const createPage = (conference) => {
  selectedPage.value = {
    title: "",
    slug: "",
    layout: "default",
    status: "draft",
    blocks: [],
    conference_id: conference.id,
    conference_slug: conference.slug,
  };
  showEditor.value = true;
};

const editPage = (page) => {
  console.log("Edit page clicked:", page);

  // Find the conference to get its slug
  const conference = conferences.value.find((c) => c.id === page.conference_id);
  const conferenceSlug = conference ? conference.slug : "";

  // Show editor immediately with basic data
  selectedPage.value = {
    ...page,
    blocks: [], // Initialize with empty blocks array
    conference_slug: conferenceSlug, // Add conference slug
  };
  showEditor.value = true;

  // Get the authentication token
  const token = localStorage.getItem("token");
  if (!token) {
    console.error("No authentication token found");
    error.value = "Authentication error. Please log in again.";
    return;
  }

  // Fetch the page with its contents
  api
    .get(`/conferences/${page.conference_id}/pages/${page.id}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
      timeout: 10000, // 10 second timeout
    })
    .then((response) => {
      console.log("Page details received:", response.data);
      if (response.data && response.data.data) {
        const pageWithContents = response.data.data;
        console.log("Page with contents:", pageWithContents);

        // Debug the contents array
        if (pageWithContents.contents) {
          console.log("Contents array:", pageWithContents.contents);
          console.log(
            "Contents array length:",
            pageWithContents.contents.length
          );
          if (pageWithContents.contents.length > 0) {
            console.log("First content item:", pageWithContents.contents[0]);
            console.log(
              "First content type:",
              pageWithContents.contents[0].type
            );
            console.log(
              "First content content:",
              pageWithContents.contents[0].content
            );
          }
        } else {
          console.log("No contents array in response");
        }

        // Update the page data with contents
        const updatedPageData = {
          ...pageWithContents,
          conference_slug: conferenceSlug, // Ensure conference slug is included
        };

        // Transform contents to blocks format expected by PageEditor
        if (
          pageWithContents.contents &&
          Array.isArray(pageWithContents.contents)
        ) {
          updatedPageData.blocks = pageWithContents.contents.map((content) => {
            const block = {
              type: content.type,
              content: content.content,
              alt: content.title || content.settings?.alt || "",
              embed: content.settings?.embed || "",
              fileName: content.settings?.fileName || "",
            };
            console.log(`Transformed block of type ${content.type}:`, block);
            return block;
          });

          console.log("Final blocks array:", updatedPageData.blocks);
        } else {
          updatedPageData.blocks = [];
          console.log("No contents found or contents is not an array");
        }

        // Update the selected page with complete data
        selectedPage.value = updatedPageData;
        console.log("Updated selectedPage value:", selectedPage.value);
      }
    })
    .catch((err) => {
      console.error("Error fetching page details:", err);
      // Don't hide the editor, just show the error
      error.value =
        "Failed to load complete page details. Some content may be missing.";
    });
};

const previewPage = (page) => {
  window.open(`/preview/${page.slug}`, "_blank");
};

const closeEditor = () => {
  showEditor.value = false;
  selectedPage.value = null;
};

const savePage = async (formData) => {
  try {
    // Debug the formData being sent to the API
    console.log("Saving page with data:", JSON.stringify(formData, null, 2));
    console.log("Blocks content:", formData.blocks);

    const token = localStorage.getItem("token");
    const headers = {
      Authorization: `Bearer ${token}`,
    };

    if (selectedPage.value.id) {
      console.log(
        `Updating page ${selectedPage.value.id} in conference ${selectedPage.value.conference_id}`
      );
      const response = await api.put(
        `/conferences/${selectedPage.value.conference_id}/pages/${selectedPage.value.id}`,
        formData,
        { headers }
      );
      console.log("Update response:", response.data);
      showSuccessNotification(`Page "${formData.title}" updated successfully`);
    } else {
      const pageData = {
        ...formData,
        conference_id: selectedPage.value.conference_id,
      };
      console.log(
        `Creating new page in conference ${selectedPage.value.conference_id}`
      );
      const response = await api.post(
        `/conferences/${selectedPage.value.conference_id}/pages`,
        pageData,
        { headers }
      );
      console.log("Create response:", response.data);
      showSuccessNotification(`Page "${formData.title}" created successfully`);
    }
    await fetchData();
    closeEditor();
  } catch (error) {
    console.error("Error saving page:", error);
    if (error.response) {
      console.error("Error response data:", error.response.data);
      console.error("Error status:", error.response.status);
      showErrorNotification(
        `Error: ${error.response.data.message || "Failed to save page"}`
      );
    } else {
      showErrorNotification("Failed to save page. Please try again.");
    }
    throw error;
  }
};

const confirmDeletePage = (page) => {
  selectedPage.value = page;
  showDeleteModal.value = true;
};

const deletePage = async () => {
  if (!selectedPage.value) return;

  submitting.value = true;
  try {
    const token = localStorage.getItem("token");
    await api.delete(
      `/conferences/${selectedPage.value.conference_id}/pages/${selectedPage.value.id}`,
      {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      }
    );
    await fetchData();
    showDeleteModal.value = false;
    showDeleteNotification(
      `Page "${selectedPage.value.title}" deleted successfully`
    );
  } catch (error) {
    console.error("Error deleting page:", error);
    showErrorNotification("Failed to delete page. Please try again.");
  } finally {
    submitting.value = false;
  }
};

const togglePageVisibility = async (page) => {
  try {
    const token = localStorage.getItem("token");
    const headers = {
      Authorization: `Bearer ${token}`,
    };

    // Create a copy of the page with updated visibility
    const updatedPage = {
      ...page,
      status: page.status === "published" ? "draft" : "published",
    };

    // Send the update to the API
    const response = await api.put(
      `/conferences/${page.conference_id}/pages/${page.id}`,
      updatedPage,
      { headers }
    );

    // Update the local page data
    const pageIndex = pages.value.findIndex((p) => p.id === page.id);
    if (pageIndex !== -1) {
      pages.value[pageIndex].status = updatedPage.status;
    }

    // Show notification with appropriate color
    const isPublished = updatedPage.status === "published";
    const status = isPublished ? "published" : "draft";
    showVisibilityNotification(
      `Page "${page.title}" is now ${status}`,
      isPublished
    );
  } catch (error) {
    console.error("Error toggling page visibility:", error);
    showErrorNotification("Failed to update page visibility");
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};

onMounted(() => {
  fetchData();
});
</script>

<style lang="scss" scoped>
.pages-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;

  .page-header {
    margin-bottom: 40px;

    h1 {
      margin-bottom: 10px;
      color: #2c3e50;
    }

    .page-description {
      color: #6c757d;
      font-size: 1.1rem;
    }
  }

  .conferences-grid {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }

  .conference-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    width: 100%;

    .conference-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;

      h2 {
        margin: 0;
        color: #2c3e50;
      }
    }
  }

  .pages-grid {
    .page-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
      gap: 1rem;
      margin-top: 1.5rem;
    }

    .page-card {
      background: #f8f9fa;
      border-radius: 6px;
      padding: 1rem;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 1rem;
      max-width: 100%; /* Ensure it doesn't exceed container width */

      .page-info {
        flex: 1;
        overflow: hidden;

        h3.page-title {
          margin: 0 0 0.75rem;
          color: #2c3e50;
          cursor: pointer;
          transition: color 0.3s;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          max-width: 100%;
          position: relative;
          display: inline-flex;
          align-items: center;
          gap: 0.5rem;

          .hover-icon {
            font-size: 0.8em;
            opacity: 0;
            transition: opacity 0.3s;
          }

          &:hover {
            color: #3498db;

            .hover-icon {
              opacity: 1;
            }

            &::after {
              content: attr(title);
              position: absolute;
              left: 0;
              bottom: -20px;
              font-size: 0.8rem;
              font-weight: normal;
              color: #7f8c8d;
              white-space: nowrap;
            }
          }
        }

        .page-dates {
          display: flex;
          gap: 1.5rem;
          font-size: 0.9rem;
          color: #666;
          margin-top: 0.5rem;

          span {
            display: flex;
            align-items: center;
            gap: 0.75rem;

            .date-text {
              white-space: nowrap;
            }
          }
        }
      }

      .page-actions {
        display: flex;
        gap: 0.5rem;
        flex-shrink: 0;
      }
    }
  }

  .editor-container {
    margin-top: 2rem;
    margin-bottom: 2rem;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
  }

  .editor-title {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: #2c3e50;
    font-size: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 1rem;
  }

  .loading {
    text-align: center;
    padding: 2rem;
    color: #666;
  }

  .error {
    color: #e74c3c;
    text-align: center;
    padding: 1rem;
    background: #fadbd8;
    border-radius: 4px;
    margin: 1rem 0;
  }

  .empty {
    text-align: center;
    padding: 1rem;
    color: #666;
    background: #f8f9fa;
    border-radius: 4px;
  }

  .modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;

    .modal-content {
      background: white;
      padding: 2rem;
      border-radius: 8px;
      width: 100%;
      max-width: 500px;

      h3 {
        margin: 0 0 1.5rem;
        color: #2c3e50;
      }

      .warning {
        color: #e74c3c;
        font-weight: bold;
      }
    }
  }

  .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
  }

  .btn-primary {
    background: #3498db;
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
    transition: background 0.3s;

    &:hover {
      background: #c0392b;
    }

    &:disabled {
      background: #95a5a6;
      cursor: not-allowed;
    }
  }

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

    &:disabled {
      background: #95a5a6;
      color: #666;
      cursor: not-allowed;
    }

    &.danger {
      color: #e74c3c;

      &:hover {
        background: #e74c3c;
        color: white;
      }
    }

    &.published {
      color: #27ae60;

      &:hover {
        background: #27ae60;
        color: white;
      }
    }

    &.draft {
      color: #f39c12;

      &:hover {
        background: #f39c12;
        color: white;
      }
    }
  }
}

.notification {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  padding: 1rem 2rem;
  border-radius: 4px;
  color: white;
  display: flex;
  align-items: center;
  gap: 1rem;
  z-index: 1000;
  animation: slideIn 0.3s ease;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16);

  &.success {
    background: #2ecc71; // Green for creation/edit
  }

  &.error {
    background: #e74c3c; // Red for errors
  }

  &.delete {
    background: #e74c3c; // Red for deletion
  }

  &.published {
    background: #27ae60; // Green for published
  }

  &.draft {
    background: #f39c12; // Orange for draft
  }

  .close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    margin-left: 1rem;
    opacity: 0.8;

    &:hover {
      opacity: 1;
    }
  }
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}
</style>
