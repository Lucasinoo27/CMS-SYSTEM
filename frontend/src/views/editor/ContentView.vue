<template>
  <div class="pages-view">
    <header class="page-header">
      <h1>My Content</h1>
      <p class="page-description">
        Manage pages for conferences you are assigned to
      </p>

      <!-- Conference filter dropdown -->
      <div v-if="conferences.length > 1" class="filter-controls">
        <div class="filter-group">
          <label for="conference-filter">Filter by Conference:</label>
          <select
            id="conference-filter"
            v-model="selectedConferenceId"
            class="form-control"
          >
            <option value="all">All Conferences</option>
            <option v-for="conf in conferences" :key="conf.id" :value="conf.id">
              {{ conf.name }}
            </option>
          </select>
        </div>
      </div>
    </header>

    <div v-if="loading" class="loading">Loading content...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <div v-else-if="conferences.length === 0" class="empty">
      No conferences assigned to you yet.
    </div>
    <div v-else class="conferences-grid">
      <div
        v-for="conference in filteredConferences"
        :key="conference.id"
        class="conference-section"
      >
        <div class="conference-header">
          <h2>{{ conference.name }}</h2>
          <div class="conference-actions">
            <button
              class="btn-toggle"
              :class="conference.status === 'published' ? 'published' : 'draft'"
              @click="toggleConferenceStatus(conference)"
              :title="
                conference.status === 'published'
                  ? 'Set Conference to Draft'
                  : 'Publish Conference'
              "
            >
              <i
                class="fas"
                :class="
                  conference.status === 'published' ? 'fa-eye' : 'fa-eye-slash'
                "
              ></i>
              {{ conference.status === "published" ? "Published" : "Draft" }}
            </button>
            <button class="btn-primary" @click="createPage(conference)">
              <i class="fas fa-plus"></i> Create New Page
            </button>
          </div>
        </div>

        <div class="pages-grid">
          <div
            v-if="!conference.pages || conference.pages.length === 0"
            class="empty"
          >
            No pages in this conference yet.
          </div>
          <div v-else class="page-cards">
            <div
              v-for="page in conference.pages"
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
                </h3>

                <div class="page-dates">
                  <span title="Created Date">
                    <i class="fas fa-plus-circle"></i>
                    <span class="date-text">{{
                      formatDate(page.created_at || page.updated_at)
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
            :conferenceId="selectedPage.conference_id"
            :pageId="selectedPage.id"
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
        <p>Are you sure you want to delete '{{ selectedPage?.title }}'?</p>
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
import { ref, onMounted, computed, watch } from "vue";
import PageEditor from "@/components/PageEditor.vue";
import api, { pageApi, fileApi } from "@/services/api";

const conferences = ref([]);
const loading = ref(false);
const error = ref("");
const showEditor = ref(false);
const showDeleteModal = ref(false);
const selectedPage = ref(null);
const submitting = ref(false);
const selectedConferenceId = ref("all");

// Notification system
const showNotification = ref(false);
const notificationMessage = ref("");
const notificationType = ref("success");
const hideNotificationTimer = ref(null);

// Computed property to filter conferences based on selection
const filteredConferences = computed(() => {
  if (selectedConferenceId.value === "all") {
    return conferences.value;
  } else {
    return conferences.value.filter(
      (conf) => conf.id === selectedConferenceId.value
    );
  }
});

const closeNotification = () => {
  showNotification.value = false;
  if (hideNotificationTimer.value) {
    clearTimeout(hideNotificationTimer.value);
  }
};

// Auto-hide notifications after 5 seconds
const showSuccessNotification = (message) => {
  notificationMessage.value = message;
  notificationType.value = "success";
  showNotification.value = true;

  if (hideNotificationTimer.value) {
    clearTimeout(hideNotificationTimer.value);
  }

  hideNotificationTimer.value = setTimeout(() => {
    showNotification.value = false;
  }, 5000);
};

const showErrorNotification = (message) => {
  notificationMessage.value = message;
  notificationType.value = "error";
  showNotification.value = true;

  if (hideNotificationTimer.value) {
    clearTimeout(hideNotificationTimer.value);
  }

  hideNotificationTimer.value = setTimeout(() => {
    showNotification.value = false;
  }, 5000);
};

const showDeleteNotification = (message) => {
  notificationMessage.value = message;
  notificationType.value = "delete";
  showNotification.value = true;

  if (hideNotificationTimer.value) {
    clearTimeout(hideNotificationTimer.value);
  }

  hideNotificationTimer.value = setTimeout(() => {
    showNotification.value = false;
  }, 5000);
};

const showVisibilityNotification = (message, isPublished) => {
  notificationMessage.value = message;
  notificationType.value = isPublished ? "published" : "draft";
  showNotification.value = true;

  if (hideNotificationTimer.value) {
    clearTimeout(hideNotificationTimer.value);
  }

  hideNotificationTimer.value = setTimeout(() => {
    showNotification.value = false;
  }, 5000);
};

const fetchConferences = async () => {
  loading.value = true;
  error.value = null;
  try {
    // Get conferences that the editor is assigned to
    const response = await pageApi.getAllForEditor();

    if (response.data) {
      conferences.value = response.data;
    } else {
      error.value = "No data received from server";
    }
  } catch (err) {
    console.error("Error fetching conferences:", err);
    if (err.response) {
      const statusCode = err.response.status;
      const errorData = err.response.data;

      if (statusCode === 500) {
        error.value = "Server error: The server encountered a problem.";
      } else if (statusCode === 401) {
        error.value = "Authentication error: Please log in again.";
      } else if (statusCode === 403) {
        error.value = "You do not have permission to access these conferences.";
      } else {
        error.value =
          errorData.message || "Failed to load content. Please try again.";
      }
    } else if (err.request) {
      error.value = "No response from server. Please check your connection.";
    } else {
      error.value = "Failed to load content. Please try again.";
    }
  } finally {
    loading.value = false;
  }
};

const createPage = (conference) => {
  selectedPage.value = {
    title: "",
    slug: "",
    meta_description: "",
    layout: "default",
    status: "draft",
    blocks: [],
    conference_id: conference.id,
    conference_slug: conference.slug,
  };
  showEditor.value = true;
};

const editPage = (page) => {
  // Find the conference this page belongs to
  const conference = conferences.value.find((c) => c.id === page.conference_id);
  const conferenceSlug = conference ? conference.slug : "";

  // Show editor immediately with basic data
  selectedPage.value = {
    ...page,
    blocks: [],
    conference_slug: conferenceSlug,
  };
  showEditor.value = true;

  const url = `/conferences/${page.conference_id}/pages/${page.id}`;

  // Fetch the page with its contents
  api
    .get(url)
    .then((response) => {
      if (response.data && response.data.data) {
        const pageWithContents = response.data.data;

        // Update the page data with contents
        const updatedPageData = {
          ...pageWithContents,
          conference_slug: conferenceSlug,
        };

        // Transform contents to blocks format expected by PageEditor
        if (
          pageWithContents.contents &&
          Array.isArray(pageWithContents.contents)
        ) {
          updatedPageData.blocks = pageWithContents.contents.map((content) => {
            return {
              type: content.type,
              content: content.content,
              alt: content.title || content.settings?.alt || "",
              embed: content.settings?.embed || "",
              fileName: content.settings?.fileName || "",
            };
          });
        } else {
          updatedPageData.blocks = [];
        }

        // Update the selected page with complete data
        selectedPage.value = updatedPageData;
      }
    })
    .catch((err) => {
      showErrorNotification(
        `Failed to load page details: ${
          err.response?.data?.message || err.message || "Unknown error"
        }`
      );
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
    // Handle temporary files for new pages
    const tempFiles = formData.tempFiles || [];
    delete formData.tempFiles;

    let response;

    if (selectedPage.value.id) {
      // Update existing page
      response = await api.put(
        `/conferences/${selectedPage.value.conference_id}/pages/${selectedPage.value.id}`,
        formData
      );
      showSuccessNotification(`Page '${formData.title}' updated successfully`);
    } else {
      // Create new page
      response = await api.post(
        `/conferences/${selectedPage.value.conference_id}/pages`,
        { ...formData, conference_id: selectedPage.value.conference_id }
      );

      // Assign files to the new page if needed
      if (tempFiles.length > 0 && response.data?.data?.id) {
        const newPageId = response.data.data.id;
        for (const fileIdOrObject of tempFiles) {
          try {
            // Extract the file ID, whether it's just an ID or a file object
            const fileId =
              typeof fileIdOrObject === "object"
                ? fileIdOrObject.id
                : fileIdOrObject;

            await fileApi.assignToPage(
              selectedPage.value.conference_id,
              newPageId,
              fileId
            );
          } catch (err) {
            console.error(`Failed to assign file to new page:`, err);
          }
        }
      }

      showSuccessNotification(`Page '${formData.title}' created successfully`);
    }

    await fetchConferences();
    closeEditor();
  } catch (error) {
    showErrorNotification(
      `Error: ${error.response?.data?.message || "Failed to save page"}`
    );
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
    await api.delete(
      `/conferences/${selectedPage.value.conference_id}/pages/${selectedPage.value.id}`
    );
    await fetchConferences();
    showDeleteModal.value = false;
    showDeleteNotification(
      `Page '${selectedPage.value.title}' deleted successfully`
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
    // Create a copy of the page with updated visibility
    const updatedPage = {
      ...page,
      status: page.status === "published" ? "draft" : "published",
    };

    // Send the update to the API
    await api.put(
      `/conferences/${page.conference_id}/pages/${page.id}`,
      updatedPage
    );

    // Update the local page data
    const conference = conferences.value.find(
      (c) => c.id === page.conference_id
    );
    if (conference && conference.pages) {
      const pageIndex = conference.pages.findIndex((p) => p.id === page.id);
      if (pageIndex !== -1) {
        conference.pages[pageIndex].status = updatedPage.status;
      }
    }

    // Show notification with appropriate color
    const isPublished = updatedPage.status === "published";
    const status = isPublished ? "published" : "draft";
    showVisibilityNotification(
      `Page '${page.title}' is now ${status}`,
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

const toggleConferenceStatus = async (conference) => {
  try {
    // Toggle the status between 'draft' and 'published'
    const newStatus = conference.status === "published" ? "draft" : "published";

    // Update the conference in the API
    const response = await api.put(`/conferences/${conference.id}`, {
      status: newStatus,
    });

    // Update the local state
    conference.status = newStatus;

    // Show notification
    const statusText = newStatus === "published" ? "published" : "set to draft";
    showVisibilityNotification(
      `Conference '${conference.name}' has been ${statusText}`,
      newStatus === "published"
    );
  } catch (error) {
    console.error("Error updating conference status:", error);
    showErrorNotification("Failed to update conference status");
  }
};

onMounted(() => {
  fetchConferences();
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
      margin-bottom: 1.5rem;
    }

    .filter-controls {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: center;
      margin-top: 1rem;
      padding: 1rem;
      background: #f8f9fa;
      border-radius: 8px;

      .filter-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;

        label {
          font-weight: 500;
          color: #2c3e50;
          white-space: nowrap;
        }

        .form-control {
          min-width: 200px;
          padding: 0.5rem;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 1rem;

          &:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
          }
        }
      }
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

          &:hover {
            color: #3498db;

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

  .notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 4px;
    color: white;
    font-weight: 500;
    z-index: 1050;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    max-width: 400px;

    &.success {
      background-color: #27ae60;
    }

    &.error {
      background-color: #e74c3c;
    }

    &.delete {
      background-color: #e74c3c;
    }

    &.published {
      background-color: #27ae60;
    }

    &.draft {
      background-color: #f39c12;
    }

    .close-btn {
      background: none;
      border: none;
      color: white;
      font-size: 1.2rem;
      cursor: pointer;
      padding: 0;
      line-height: 1;
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

  .conference-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
  }

  .btn-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    border: none;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    color: white;

    i {
      font-size: 0.9rem;
    }

    &.published {
      background-color: #27ae60;

      &:hover {
        background-color: #219955;
      }
    }

    &.draft {
      background-color: #f39c12;

      &:hover {
        background-color: #e67e22;
      }
    }
  }
}
</style>
