<template>
  <div class="conference-manager">
    <div class="header">
      <h2>{{ title }}</h2>
      <button v-if="canCreate" @click="showCreateModal = true" class="btn-primary">
        Create Conference
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
          <h3>{{ conference.name }}</h3>
          <p>{{ conference.description }}</p>
          <div class="dates">
            <span>Start: {{ formatDate(conference.start_date) }}</span>
            <span>End: {{ formatDate(conference.end_date) }}</span>
          </div>
          <div class="actions">
            <button @click="editConference(conference)" class="btn-secondary">
              Edit
            </button>
            <button v-if="canDelete" @click="confirmDelete(conference)" class="btn-danger">
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="modal">
      <div class="modal-content">
        <h3>{{ isEditing ? 'Edit Conference' : 'Create Conference' }}</h3>
        <form @submit.prevent="handleSubmit">
          <div class="form-group">
            <label for="name">Conference Name</label>
            <input
              type="text"
              id="name"
              v-model="form.name"
              required
              placeholder="Enter conference name"
            />
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <textarea
              id="description"
              v-model="form.description"
              required
              placeholder="Enter conference description"
            ></textarea>
          </div>

          <div class="form-group">
            <label for="start_date">Start Date</label>
            <input
              type="date"
              id="start_date"
              v-model="form.start_date"
              required
            />
          </div>

          <div class="form-group">
            <label for="end_date">End Date</label>
            <input
              type="date"
              id="end_date"
              v-model="form.end_date"
              required
            />
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-secondary">
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
    <div v-if="showDeleteModal" class="modal">
      <div class="modal-content">
        <h3>Delete Conference</h3>
        <p>Are you sure you want to delete "{{ selectedConference?.name }}"?</p>
        <div class="modal-actions">
          <button @click="showDeleteModal = false" class="btn-secondary">
            Cancel
          </button>
          <button
            @click="handleDelete"
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
import { ref, reactive, onMounted } from 'vue'
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

const form = reactive({
  name: '',
  description: '',
  start_date: '',
  end_date: ''
})

const resetForm = () => {
  form.name = ''
  form.description = ''
  form.start_date = ''
  form.end_date = ''
  selectedConference.value = null
  isEditing.value = false
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  showDeleteModal.value = false
  resetForm()
}

const formatDate = (date) => {
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
  form.start_date = conference.start_date
  form.end_date = conference.end_date
  isEditing.value = true
  showEditModal.value = true
}

const confirmDelete = (conference) => {
  selectedConference.value = conference
  showDeleteModal.value = true
}

const handleSubmit = async () => {
  submitting.value = true
  
  try {
    if (isEditing.value) {
      await conferenceApi.update(selectedConference.value.id, form)
    } else {
      await conferenceApi.create(form)
    }
    
    await fetchConferences()
    emit('refresh')
    closeModal()
  } catch (err) {
    error.value = 'Failed to save conference. Please try again.'
    console.error('Error saving conference:', err)
  } finally {
    submitting.value = false
  }
}

const handleDelete = async () => {
  if (!selectedConference.value) return
  
  submitting.value = true
  
  try {
    await conferenceApi.delete(selectedConference.value.id)
    await fetchConferences()
    emit('refresh')
    closeModal()
  } catch (err) {
    error.value = 'Failed to delete conference. Please try again.'
    console.error('Error deleting conference:', err)
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchConferences()
})
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
    }
  }

  .conference-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }

  .conference-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

    h3 {
      margin: 0 0 1rem;
      color: #2c3e50;
    }

    .dates {
      margin: 1rem 0;
      display: flex;
      justify-content: space-between;
      font-size: 0.9rem;
      color: #666;
    }

    .actions {
      display: flex;
      gap: 0.5rem;
      margin-top: 1rem;
    }
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

  input, textarea {
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
