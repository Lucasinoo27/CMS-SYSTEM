<template>
  <div class="conference-manager">
    <div class="header">
      <h2>{{ title }}</h2>
      <button v-if="canCreate" @click="createConference" class="btn-primary">
        <i class="fas fa-plus"></i> Create Conference
      </button>
    </div>

    <!-- Conference List -->
    <div class="conference-list">
      <div v-if="loading" class="loading">Loading conferences...</div>
      <div v-else-if="error" class="error">{{ error }}</div>
      <div v-else-if="conferences.length === 0" class="empty">
        No conferences found.
      </div>
      <div v-else class="conference-grid">
        <div v-for="conference in conferences" :key="conference.id" class="conference-card">
          <div class="conference-info">
            <h3>{{ conference.name }}</h3>

            <div class="conference-location">
              <i class="fas fa-map-marker-alt"></i>
              <span>{{ conference.location || "No location specified" }}</span>
            </div>

            <div class="conference-dates">
              <span title="Conference Period">
                <i class="fas fa-calendar-alt"></i>
                <span class="date-text">{{ formatDate(conference.start_date) }} -
                  {{ formatDate(conference.end_date) }}</span>
              </span>
            </div>

            <p class="description">{{ conference.description }}</p>

            <div class="meta-dates">
              <span title="Created Date">
                <i class="fas fa-plus-circle"></i>
                <span class="date-text">{{
                  formatDate(conference.created_at)
                  }}</span>
              </span>
              <span title="Last Updated">
                <i class="fas fa-edit"></i>
                <span class="date-text">{{
                  formatDate(conference.updated_at)
                  }}</span>
              </span>
            </div>
          </div>

          <div class="conference-actions">
            <button class="btn-icon" @click="editConference(conference)" title="Edit Conference">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn-icon" :class="conference.status === 'published' ? 'published' : 'draft'"
              @click="toggleVisibility(conference)" :title="conference.status === 'published'
                  ? 'Set to Draft'
                  : 'Set to Published'
                ">
              <i class="fas" :class="conference.status === 'published' ? 'fa-eye' : 'fa-eye-slash'
                "></i>
            </button>
            <button v-if="canDelete" class="btn-icon danger" @click="confirmDelete(conference)"
              title="Delete Conference">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Conference Editor -->
    <div v-if="showCreateModal || showEditModal" class="editor-container">
      <div class="editor-toolbar">
        <h3 class="editor-title">
          {{ isEditing ? "Edit Conference" : "Create Conference" }}
        </h3>
        <div class="toolbar-actions">
          <button type="button" class="btn-secondary" @click="closeModal">
            <i class="fas fa-times"></i> Cancel
          </button>
          <button type="button" class="btn-primary" @click="handleSubmit" :disabled="submitting || !form.name">
            <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : 'fa-save'"></i>
            {{ submitting ? "Saving..." : "Save Conference" }}
          </button>
        </div>
      </div>

      <div class="editor-card">
        <div class="editor-section">
          <h3 class="section-title">
            <i class="fas fa-info-circle"></i> Conference Information
          </h3>

          <div class="form-row">
            <div class="form-group full-width">
              <label for="name">Conference Name</label>
              <input type="text" id="name" v-model="form.name" required placeholder="Enter conference name"
                class="form-control" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group full-width">
              <label for="location">Location</label>
              <input type="text" id="location" v-model="form.location" required placeholder="Enter conference location"
                class="form-control" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="start_date">Start Date</label>
              <input type="date" id="start_date" v-model="form.start_date" required class="form-control" />
            </div>

            <div class="form-group">
              <label for="end_date">End Date</label>
              <input type="date" id="end_date" v-model="form.end_date" required class="form-control" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group full-width">
              <label for="description">Description</label>
              <textarea id="description" v-model="form.description" required placeholder="Enter conference description"
                class="form-control" rows="4"></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal">
      <div class="modal-content">
        <h3>Delete Conference</h3>
        <p>Are you sure you want to delete "{{ selectedConference?.name }}"?</p>
        <p class="warning">This action cannot be undone.</p>
        <div class="modal-actions">
          <button @click="showDeleteModal = false" class="btn-secondary">
            Cancel
          </button>
          <button @click="handleDelete" class="btn-danger" :disabled="submitting">
            {{ submitting ? "Deleting..." : "Delete" }}
          </button>
        </div>
      </div>
    </div>

    <!-- Notifications -->
    <div v-if="showNotification" class="notification" :class="notificationType">
      {{ notificationMessage }}
      <button class="close-btn" @click="closeNotification">×</button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { conferenceApi } from '@/services/api'
import axios from 'axios'

const props = defineProps({
  title: {
    type: String,
    default: 'Conferences'
  },
  canCreate: {
    type: Boolean,
    default: true
  },
  canDelete: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['refresh'])

const conferences = ref([])
const loading = ref(false)
const error = ref('')
const submitting = ref(false)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const selectedConference = ref(null)
const isEditing = ref(false)

// Notification system
const showNotification = ref(false)
const notificationMessage = ref('')
const notificationType = ref('success')
const hideNotificationTimer = ref(null)

const closeNotification = () => {
  showNotification.value = false
};

// Auto-hide notifications after 5 seconds
watch(showNotification, (newValue) => {
  if (newValue && hideNotificationTimer.value) {
    clearTimeout(hideNotificationTimer.value)
  }
  if (newValue) {
    hideNotificationTimer.value = setTimeout(() => {
      showNotification.value = false
    }, 5000);
  }
});

// Show notifications
const showSuccessNotification = (message) => {
  notificationMessage.value = message
  notificationType.value = 'success'
  showNotification.value = true
}

const showErrorNotification = (message) => {
  notificationMessage.value = message
  notificationType.value = 'error'
  showNotification.value = true
}

const showDeleteNotification = (message) => {
  notificationMessage.value = message
  notificationType.value = 'delete'
  showNotification.value = true
}

const showVisibilityNotification = (message, isPublished) => {
  notificationMessage.value = message
  notificationType.value = isPublished ? 'published' : 'draft'
  showNotification.value = true
}

const form = reactive({
  name: '',
  description: '',
  start_date: '',
  end_date: '',
  location: '',
  status: 'published'
})

const resetForm = () => {
  form.name = ''
  form.description = ''
  form.start_date = ''
  form.end_date = ''
  form.location = ''
  form.status = 'published'
  selectedConference.value = null
  isEditing.value = false
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  showDeleteModal.value = false
  resetForm()
}

const createConference = () => {
  resetForm();
  isEditing.value = false
  showCreateModal.value = true
  showEditModal.value = false
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

const fetchConferences = async () => {
  loading.value = true
  error.value = ''

  try {
    // Add a small delay to ensure loading indicator is visible
    const startTime = Date.now()
    
    // Make API request
    const response = await conferenceApi.getAll()
    conferences.value = response.data
    
    // Ensure loading indicator shows for at least 300ms to avoid flicker
    const elapsedTime = Date.now() - startTime
    if (elapsedTime < 300) {
      await new Promise(resolve => setTimeout(resolve, 300 - elapsedTime))
    }
    
    // Clear any error if successful
    error.value = ''
  } catch (err) {
    console.error('Error fetching conferences:', err)
    error.value = 'Failed to load conferences. Please try again.'
    conferences.value = [] // Ensure conferences is empty if there's an error
  } finally {
    loading.value = false
  }
}

const editConference = (conference) => {
  selectedConference.value = conference
  form.name = conference.name
  form.description = conference.description
  
  // Format dates to YYYY-MM-DD for the date input fields
  if (conference.start_date) {
    const startDate = new Date(conference.start_date);
    form.start_date = startDate.toISOString().split("T")[0];
  } else {
    form.start_date = "";
  }

  if (conference.end_date) {
    const endDate = new Date(conference.end_date);
    form.end_date = endDate.toISOString().split("T")[0];
  } else {
    form.end_date = "";
  }

  form.location = conference.location || ''
  form.status = conference.status || 'draft'
  isEditing.value = true
  showEditModal.value = true
  showCreateModal.value = false
}

const confirmDelete = (conference) => {
  selectedConference.value = conference
  showDeleteModal.value = true
}

const handleSubmit = async () => {
  if (!form.name || !form.description || !form.location || !form.start_date || !form.end_date) {
    showErrorNotification('Please fill in all required fields')
    return
  }

  submitting.value = true

  try {
    if (isEditing.value) {
      await conferenceApi.update(selectedConference.value.id, form)
      showSuccessNotification(`Conference '${form.name}' updated successfully`)
    } else {
      await conferenceApi.create(form)
      showSuccessNotification(`Conference '${form.name}' created successfully`)
    }

    await fetchConferences()
    emit("refresh")
    closeModal()
  } catch (err) {
    // Show more specific error message if available
    if (err.response && err.response.data && err.response.data.errors) {
      const errorMessages = Object.values(err.response.data.errors)
        .flat()
        .join(', ')
      showErrorNotification(`Validation error: ${errorMessages}`)
    } else {
      showErrorNotification('Failed to save conference. Please try again.')
    }
    console.error('Error saving conference:', err)
  } finally {
    submitting.value = false;
  }
};

const toggleVisibility = async (conference) => {
  try {
    // Create a copy of the conference with updated visibility
    const updatedConference = {
      ...conference,
      status: conference.status === "published" ? "draft" : "published",
    };

    // Send the update to the API
    await conferenceApi.update(conference.id, updatedConference);

    // Update the local conference data
    const conferenceIndex = conferences.value.findIndex(
      (c) => c.id === conference.id
    );
    if (conferenceIndex !== -1) {
      conferences.value[conferenceIndex].status = updatedConference.status;
    }

    // Show notification with appropriate color
    const newStatus =
      updatedConference.status === "published" ? "published" : "draft";
    showVisibilityNotification(
      `Conference "${conference.name}" is now ${newStatus}`,
      updatedConference.status === "published"
    );
  } catch (err) {
    showErrorNotification("Failed to update conference visibility");
    console.error("Error toggling conference visibility:", err);
  }
};

const handleDelete = async () => {
  if (!selectedConference.value) return;

  submitting.value = true;

  try {
    await conferenceApi.delete(selectedConference.value.id);
    await fetchConferences();
    showDeleteNotification(
      `Conference "${selectedConference.value.name}" deleted successfully`
    );
    emit("refresh");
    closeModal();
  } catch (err) {
    showErrorNotification("Failed to delete conference. Please try again.");
    console.error("Error deleting conference:", err);
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  fetchConferences();
});
</script>

<style lang="scss" scoped>
.conference-manager {
  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;

    h2 {
      margin: 0;
      color: #2c3e50;
    }

    .btn-primary {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
  }

  .conference-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1rem;
  }

  .conference-card {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;

    .conference-info {
      flex: 1;
      overflow: hidden;

      h3 {
        margin: 0 0 0.5rem;
        color: #2c3e50;
      }

      .conference-location {
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
        color: #2c3e50;

        i {
          color: #e74c3c;
        }
      }

      .conference-dates {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 0.75rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;

        span {
          display: flex;
          align-items: center;
          gap: 0.5rem;
        }

        i {
          color: #3498db;
        }
      }

      .description {
        margin: 0.5rem 0 0.75rem;
        color: #2c3e50;
        line-height: 1.4;
      }

      .meta-dates {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        font-size: 0.9rem;
        color: #666;
        margin-top: 0.75rem;
        align-items: center;

        span {
          display: flex;
          align-items: center;
          gap: 0.5rem;

          .date-text {
            white-space: nowrap;
          }
        }
      }
    }

    .conference-actions {
      display: flex;
      gap: 0.5rem;
      flex-shrink: 0
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
    display: flex;
    align-items: center;
    justify-content: center;

    &:hover {
      background: #3498db;
      color: white;
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

  .editor-container {
    margin-top: 2rem;
    margin-bottom: 2rem;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid #e9ecef;

    .editor-toolbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #e9ecef;

      .editor-title {
        margin: 0;
        font-size: 1.5rem;
        color: #2c3e50;
      }

      .toolbar-actions {
        display: flex;
        gap: 0.75rem;
      }
    }

    .editor-card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      overflow: hidden;

      .editor-section {
        padding: 1.5rem;

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
    max-width: 800px;

    h3 {
      margin: 0 0 1.5rem;
      color: #2c3e50;
    }

    .warning {
      color: #e74c3c;
      font-weight: bold;
    }
  }

  .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
  }
}

.form-group {
  margin-bottom: 1.5rem;

  label {
    display: block;
    margin-bottom: 0.5rem;
    color: #2c3e50;
  }

  input,
  textarea,
  .form-control {
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

  textarea {
    min-height: 100px;
    resize: vertical;
  }

  &.full-width {
    grid-column: 1 / -1;
  }

  &.visibility-toggle {
    .toggle-label {
      display: flex;
      align-items: center;
      gap: 1rem;

      .status-text {
        font-weight: 500;
      }

      .btn-toggle {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 0.9rem;

        &.visible {
          background-color: #d4edda;
          color: #155724;

          &:hover {
            background-color: #c3e6cb;
          }
        }

        &.hidden {
          background-color: #f8d7da;
          color: #721c24;

          &:hover {
            background-color: #f5c6cb;
          }
        }
      }
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
  padding: 2rem;
  color: #666;
  background: #f8f9fa;
  border-radius: 4px;
}
</style>
