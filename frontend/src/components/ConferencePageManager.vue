<template>
  <div class="conference-page-manager">
    <div class="header">
      <h2>{{ title }}</h2>
    </div>

    <!-- Loading and Error States -->
    <div v-if="loading" class="loading">Loading conferences and pages...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    
    <!-- Conference Cards with Page Counts -->
    <div v-else-if="conferences.length === 0" class="empty">
      No conferences found. Create a conference first to add pages.
    </div>
    <div v-else class="conference-grid">
      <div v-for="conference in conferences" :key="conference.id" class="conference-card">
        <div class="conference-info" @click="toggleConferencePages(conference.id)">
          <h3>{{ conference.name }}</h3>
          <div class="page-count">
            <span>{{ getConferencePageCount(conference.id) }} Pages</span>
            <i class="expand-icon" :class="{'expanded': expandedConferences.includes(conference.id)}">▼</i>
          </div>
        </div>

        <!-- Pages List (collapsible) -->
        <div v-if="expandedConferences.includes(conference.id)" class="pages-list">
          <div class="pages-header">
            <h4>Pages</h4>
            <button @click="showCreatePageModal = true; selectedConference = conference" class="btn-primary">
              Add Page
            </button>
          </div>
          
          <div v-if="getConferencePages(conference.id).length === 0" class="empty-pages">
            No pages found for this conference.
          </div>
          <div v-else class="page-items">
            <div v-for="page in getConferencePages(conference.id)" :key="page.id" class="page-item">
              <div class="page-title">
                <span :class="{'draft': !page.is_published}">{{ page.title }}</span>
                <span v-if="!page.is_published" class="draft-label">Draft</span>
              </div>
              <div class="page-actions">
                <button @click="editPage(page)" class="btn-secondary sm">Edit</button>
                <button @click="confirmDeletePage(page)" class="btn-danger sm">Delete</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Page Modal -->
    <div v-if="showCreatePageModal || showEditPageModal" class="modal">
      <div class="modal-content">
        <h3>{{ isEditingPage ? 'Edit Page' : 'Create Page' }}</h3>
        <form @submit.prevent="handlePageSubmit">
          <div class="form-group">
            <label for="title">Page Title</label>
            <input
              type="text"
              id="title"
              v-model="pageForm.title"
              required
              placeholder="Enter page title"
            />
          </div>

          <div class="form-group">
            <label for="meta_description">Meta Description</label>
            <textarea
              id="meta_description"
              v-model="pageForm.meta_description"
              placeholder="Enter meta description"
            ></textarea>
          </div>

          <div class="form-group">
            <label for="layout">Layout</label>
            <select id="layout" v-model="pageForm.layout" required>
              <option value="default">Default</option>
              <option value="full-width">Full Width</option>
              <option value="sidebar">Sidebar</option>
            </select>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="pageForm.is_published" />
              <span>Published</span>
            </label>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closePageModal" class="btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="submitting">
              {{ submitting ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeletePageModal" class="modal">
      <div class="modal-content">
        <h3>Delete Page</h3>
        <p>Are you sure you want to delete "{{ selectedPage?.title }}" from "{{ getConferenceName(selectedPage?.conference_id) }}"?</p>
        <div class="modal-actions">
          <button @click="showDeletePageModal = false" class="btn-secondary">
            Cancel
          </button>
          <button
            @click="handleDeletePage"
            class="btn-danger"
            :disabled="submitting"
          >
            {{ submitting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { conferenceApi } from '@/services/api'
import axios from 'axios'

const props = defineProps({
  title: {
    type: String,
    default: 'Conference Pages'
  }
})

const emit = defineEmits(['refresh'])

// State
const conferences = ref([])
const pages = ref([])
const loading = ref(false)
const error = ref('')
const submitting = ref(false)
const expandedConferences = ref([])
const selectedConference = ref(null)
const selectedPage = ref(null)
const showCreatePageModal = ref(false)
const showEditPageModal = ref(false)
const showDeletePageModal = ref(false)
const isEditingPage = ref(false)

// Page form
const pageForm = reactive({
  title: '',
  meta_description: '',
  layout: 'default',
  is_published: true
})

// Helper functions
const getConferencePages = (conferenceId) => {
  return pages.value.filter(page => page.conference_id === conferenceId)
}

const getConferencePageCount = (conferenceId) => {
  return getConferencePages(conferenceId).length
}

const getConferenceName = (conferenceId) => {
  const conference = conferences.value.find(c => c.id === conferenceId)
  return conference ? conference.name : ''
}

const toggleConferencePages = (conferenceId) => {
  if (expandedConferences.value.includes(conferenceId)) {
    expandedConferences.value = expandedConferences.value.filter(id => id !== conferenceId)
  } else {
    expandedConferences.value.push(conferenceId)
    // Load pages for this conference if not already loaded
    if (!getConferencePages(conferenceId).length) {
      fetchPagesForConference(conferenceId)
    }
  }
}

// Form handling
const resetPageForm = () => {
  pageForm.title = ''
  pageForm.meta_description = ''
  pageForm.layout = 'default'
  pageForm.is_published = true
  selectedPage.value = null
  isEditingPage.value = false
}

const closePageModal = () => {
  showCreatePageModal.value = false
  showEditPageModal.value = false
  showDeletePageModal.value = false
  resetPageForm()
}

const editPage = (page) => {
  selectedPage.value = page
  selectedConference.value = conferences.value.find(c => c.id === page.conference_id)
  pageForm.title = page.title
  pageForm.meta_description = page.meta_description
  pageForm.layout = page.layout
  pageForm.is_published = page.is_published
  isEditingPage.value = true
  showEditPageModal.value = true
}

const confirmDeletePage = (page) => {
  selectedPage.value = page
  showDeletePageModal.value = true
}

// API functions
const fetchConferences = async () => {
  loading.value = true
  error.value = ''
  
  try {
    // Use the admin endpoint to get all conferences and pages at once
    const response = await axios.get('/admin/pages')
    console.log('Admin pages response:', response)
    
    if (response.data && response.data.data) {
      // Set conferences
      conferences.value = response.data.data.conferences || []
      
      // Set pages with conference_id
      if (response.data.data.pages) {
        pages.value = response.data.data.pages
      }
      
      // Open the first conference by default if any exist
      if (conferences.value.length > 0) {
        expandedConferences.value = [conferences.value[0].id]
      }
    } else {
      console.error('Unexpected API response structure:', response.data)
      error.value = 'Invalid data format received from server.'
    }
    
    error.value = ''
  } catch (err) {
    console.error('Error fetching conferences and pages:', err)
    if (err.response) {
      console.error('Error response data:', err.response.data)
      console.error('Error response status:', err.response.status)
      error.value = `Failed to load data: ${err.response.status} ${err.response.statusText}`
    } else if (err.request) {
      console.error('No response received:', err.request)
      error.value = 'Server did not respond. Please try again.'
    } else {
      console.error('Error details:', err.message)
      error.value = `Request error: ${err.message}`
    }
  } finally {
    loading.value = false
  }
}

// This function is kept for backwards compatibility, but admin view uses fetchConferences directly
const fetchPagesForConference = async (conferenceId) => {
  // If we already have pages for this conference, no need to fetch again
  if (getConferencePages(conferenceId).length > 0) {
    return
  }
  
  try {
    const response = await axios.get(`/conferences/${conferenceId}/pages`)
    console.log('API response for conference pages:', response)
    
    // Check if we have a valid response with data property
    if (response.data && response.data.data) {
      // Add the fetched pages to our pages array
      const conferencePagesWithId = response.data.data.map(page => ({
        ...page,
        conference_id: conferenceId
      }))
      
      pages.value = [...pages.value, ...conferencePagesWithId]
    } else {
      console.error('Unexpected API response structure:', response.data)
    }
  } catch (err) {
    console.error(`Error fetching pages for conference ${conferenceId}:`, err)
    if (err.response) {
      console.error('Error response data:', err.response.data)
      console.error('Error response status:', err.response.status)
    }
  }
}

const handlePageSubmit = async () => {
  submitting.value = true
  
  try {
    const conferenceId = selectedConference.value.id
    
    if (isEditingPage.value) {
      // Update existing page
      await axios.put(`/conferences/${conferenceId}/pages/${selectedPage.value.id}`, pageForm)
      
      // Update the page in our local array
      const index = pages.value.findIndex(p => p.id === selectedPage.value.id)
      if (index !== -1) {
        pages.value[index] = { 
          ...pages.value[index], 
          ...pageForm,
          conference_id: conferenceId
        }
      }
    } else {
      // Create new page
      const response = await axios.post(`/conferences/${conferenceId}/pages`, pageForm)
      
      // Add the new page to our local array
      pages.value.push({
        ...response.data.data,
        conference_id: conferenceId
      })
    }
    
    emit('refresh')
    closePageModal()
  } catch (err) {
    error.value = 'Failed to save page. Please try again.'
    console.error('Error saving page:', err)
  } finally {
    submitting.value = false
  }
}

const handleDeletePage = async () => {
  if (!selectedPage.value) return
  
  submitting.value = true
  
  try {
    const conferenceId = selectedPage.value.conference_id
    await axios.delete(`/conferences/${conferenceId}/pages/${selectedPage.value.id}`)
    
    // Remove the page from our local array
    pages.value = pages.value.filter(p => p.id !== selectedPage.value.id)
    
    emit('refresh')
    closePageModal()
  } catch (err) {
    error.value = 'Failed to delete page. Please try again.'
    console.error('Error deleting page:', err)
  } finally {
    submitting.value = false
  }
}

// Initialization
onMounted(() => {
  fetchConferences()
})
</script>

<style lang="scss" scoped>
.conference-page-manager {
  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;

    h2 {
      margin: 0;
    }
  }

  .conference-grid {
    display: grid;
    gap: 1.5rem;
  }

  .conference-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    overflow: hidden;

    .conference-info {
      padding: 1.5rem;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: background-color 0.2s;

      &:hover {
        background-color: #f8f9fa;
      }

      h3 {
        margin: 0;
        color: #2c3e50;
      }

      .page-count {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #6c757d;
        
        .expand-icon {
          font-size: 0.8rem;
          transition: transform 0.3s;
          
          &.expanded {
            transform: rotate(180deg);
          }
        }
      }
    }
  }

  .pages-list {
    padding: 1.5rem;
    border-top: 1px solid #eee;
    background-color: #f8f9fa;

    .pages-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;

      h4 {
        margin: 0;
        color: #2c3e50;
      }
    }

    .page-items {
      display: grid;
      gap: 0.75rem;
    }

    .page-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem;
      background: white;
      border-radius: 4px;
      border-left: 3px solid #3498db;

      .page-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        
        .draft {
          color: #6c757d;
        }
        
        .draft-label {
          font-size: 0.7rem;
          background: #f8f9fa;
          padding: 0.2rem 0.4rem;
          border-radius: 4px;
        }
      }

      .page-actions {
        display: flex;
        gap: 0.5rem;
      }
    }

    .empty-pages {
      text-align: center;
      padding: 1rem;
      color: #6c757d;
      background: white;
      border-radius: 4px;
    }
  }
}

.loading, .error, .empty {
  text-align: center;
  padding: 2rem;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.error {
  color: #e74c3c;
  background: #fadbd8;
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

  input, textarea, select {
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
  
  .checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    
    input[type="checkbox"] {
      width: auto;
    }
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
  
  &.sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
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
  
  &.sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
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
  
  &.sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
  }
}
</style>
