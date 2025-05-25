<template>
  <div class="users-view">
    <div class="page-header">
      <h1>User Management</h1>
      <button class="btn-primary" @click="showCreateModal = true">
        <i class="fas fa-plus"></i> Add New User
      </button>
    </div>

    <div v-if="success" class="success-message">
      {{ success }}
    </div>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <div class="users-grid">
      <div v-if="loading" class="loading">Loading users...</div>
      <div v-else-if="error" class="error">{{ error }}</div>
      <div v-else-if="users.length === 0" class="empty">
        No users found. Add your first user to get started.
      </div>
      <div v-else class="user-cards">
        <div v-for="user in users" :key="user.id" class="user-card">
          <div class="user-info">
            <div class="user-avatar">
              <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
              <h3>{{ user.name }}</h3>
              <p class="user-email">{{ user.email }}</p>
              <span :class="['user-role', user.role]">{{ user.role }}</span>
              <div v-if="user.role === 'editor' && user.conferences" class="user-conferences">
                <p class="conferences-label">Assigned Conferences:</p>
                <div class="conference-tags">
                  <span v-if="user.conferences.length === 0" class="no-conferences">No conferences assigned</span>
                  <span v-for="conf in user.conferences" :key="conf.id" class="conference-tag">
                    {{ conf.name }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="user-actions">
            <button
              class="btn-icon"
              @click="editUser(user)"
              title="Edit User"
            >
              <i class="fas fa-edit"></i>
            </button>
            <button
              class="btn-icon delete"
              @click="confirmDelete(user)"
              title="Delete User"
            >
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="modal">
      <div class="modal-content">
        <h3>{{ isEditing ? 'Edit User' : 'Create User' }}</h3>
        <form @submit.prevent="handleSubmit">
          <div class="form-group">
            <label for="name">Name</label>
            <input
              type="text"
              id="name"
              v-model="form.name"
              required
              placeholder="Enter user name"
            />
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input
              type="email"
              id="email"
              v-model="form.email"
              required
              placeholder="Enter user email"
            />
          </div>

          <div class="form-group">
            <label for="role">Role</label>
            <select id="role" v-model="form.role" required>
              <option value="admin">Admin</option>
              <option value="editor">Editor</option>
            </select>
          </div>

          <div class="form-group" v-if="form.role === 'editor'">
            <label>Assigned Conferences</label>
            <div class="conference-list">
              <div v-if="loadingConferences" class="loading">Loading conferences...</div>
              <div v-else-if="conferences.length === 0" class="empty">No conferences available</div>
              <div v-else class="conference-grid">
                <div v-for="conference in conferences" :key="conference.id" class="conference-item">
                  <label class="conference-checkbox">
                    <input
                      type="checkbox"
                      :value="Number(conference.id)"
                      :checked="form.conference_ids.includes(Number(conference.id))"
                      @change="(e) => handleConferenceSelection(conference.id, e.target.checked)"
                    />
                    <span>{{ conference.name }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="form-group" v-if="!isEditing">
            <label for="password">Password</label>
            <input
              type="password"
              id="password"
              v-model="form.password"
              required
              placeholder="Enter password"
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
        <h3>Delete User</h3>
        <p>Are you sure you want to delete "{{ selectedUser?.name }}"?</p>
        <p class="warning">This action cannot be undone.</p>
        <div class="modal-actions">
          <button
            class="btn-secondary"
            @click="showDeleteModal = false"
          >
            Cancel
          </button>
          <button
            class="btn-danger"
            @click="deleteUser"
            :disabled="deleting"
          >
            {{ deleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount, watch } from 'vue'
import { userApi } from '@/services/api'
import { useAuthStore } from '@/stores/authStore'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const authStore = useAuthStore()
const users = ref([])
const loading = ref(false)
const error = ref('')
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const selectedUser = ref(null)
const submitting = ref(false)
const deleting = ref(false)
const isEditing = ref(false)
const conferences = ref([])
const loadingConferences = ref(false)
const success = ref('')

const form = reactive({
  name: '',
  email: '',
  role: 'editor',
  password: '',
  conference_ids: []
})

// Initialize selectedConferences as a reactive ref with a Set
const selectedConferences = ref(new Set())

// Add a method to handle conference selection
const handleConferenceSelection = (conferenceId, isChecked) => {
  const id = Number(conferenceId)
  if (isChecked) {
    if (!form.conference_ids.includes(id)) {
      form.conference_ids.push(id)
    }
  } else {
    const index = form.conference_ids.indexOf(id)
    if (index > -1) {
      form.conference_ids.splice(index, 1)
    }
  }
  console.log('Updated conference IDs:', form.conference_ids)
}

const resetForm = () => {
  form.name = ''
  form.email = ''
  form.role = 'editor'
  form.password = ''
  form.conference_ids = []
  selectedUser.value = null
  isEditing.value = false
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  showDeleteModal.value = false
  resetForm()
  error.value = ''
  success.value = ''
}

const fetchUsers = async () => {
  // Don't fetch if not authenticated
  if (!authStore.isAuthenticated) {
    users.value = []
    return
  }

  loading.value = true
  error.value = ''
  
  try {
    const response = await userApi.getAll()
    // Fetch conferences for each editor
    const usersWithConferences = await Promise.all(
      response.data.map(async (user) => {
        if (user.role === 'editor') {
          try {
            const confResponse = await axios.get(`/users/${user.id}/conferences`)
            // Ensure we're setting the conferences array properly
            user.conferences = Array.isArray(confResponse.data) ? confResponse.data : []
            console.log(`Fetched conferences for user ${user.id}:`, user.conferences)
          } catch (err) {
            console.error(`Error fetching conferences for user ${user.id}:`, err)
            user.conferences = []
          }
        } else {
          user.conferences = []
        }
        return user
      })
    )
    users.value = usersWithConferences
    error.value = ''
  } catch (err) {
    console.error('Error fetching users:', err)
    if (err.response?.status === 401) {
      // Redirect to login on auth error
      router.push('/login')
      return
    }
    error.value = 'Failed to load users. Please try again.'
    users.value = []
  } finally {
    loading.value = false
  }
}

const fetchConferences = async () => {
  loadingConferences.value = true
  try {
    const response = await axios.get('/conferences')
    conferences.value = response.data
  } catch (err) {
    console.error('Error fetching conferences:', err)
  } finally {
    loadingConferences.value = false
  }
}

const fetchUserConferences = async (userId) => {
  try {
    const response = await axios.get(`/users/${userId}/conferences`)
    // Ensure we're setting an array of numbers
    form.conference_ids = response.data.map(c => Number(c.id))
    console.log('Loaded conference IDs:', form.conference_ids)
  } catch (err) {
    console.error('Error fetching user conferences:', err)
    form.conference_ids = []
  }
}

const editUser = async (user) => {
  selectedUser.value = user
  form.name = user.name
  form.email = user.email
  form.role = user.role
  isEditing.value = true
  showEditModal.value = true
  
  // Reset conference IDs first
  form.conference_ids = []
  
  if (user.role === 'editor') {
    // If we already have the conferences data from the user card, use it
    if (user.conferences && user.conferences.length > 0) {
      form.conference_ids = user.conferences.map(c => Number(c.id))
    } else {
      // Otherwise fetch them
      await fetchUserConferences(user.id)
    }
  }
}

const confirmDelete = (user) => {
  selectedUser.value = user
  showDeleteModal.value = true
}

const handleSubmit = async () => {
  submitting.value = true
  error.value = ''
  success.value = ''
  
  try {
    if (isEditing.value) {
      // Update user data
      await userApi.update(selectedUser.value.id, {
        name: form.name,
        email: form.email,
        role: form.role
      })
      
      // Update conference assignments if user is an editor
      if (form.role === 'editor') {
        try {
          // Ensure we have the latest conference IDs
          const conferenceIds = [...form.conference_ids]
          
          console.log('Sending conference assignment request:', {
            userId: selectedUser.value.id,
            conferenceIds: conferenceIds
          })
          
          const response = await axios.post(`/users/${selectedUser.value.id}/conferences`, {
            conference_ids: conferenceIds
          })
          
          console.log('Conference assignment response:', response.data)
          
          if (response.data.message === 'Conferences assigned successfully') {
            // Update the user's conferences in the local state
            const userIndex = users.value.findIndex(u => u.id === selectedUser.value.id)
            if (userIndex !== -1) {
              users.value[userIndex].conferences = response.data.conferences
            }
            
            success.value = 'User and conference assignments updated successfully'
            await fetchUsers() // Refresh the entire user list
            closeModal()
          } else {
            throw new Error(response.data.message || 'Unexpected response from server')
          }
        } catch (confError) {
          console.error('Error assigning conferences:', {
            error: confError,
            response: confError.response?.data,
            status: confError.response?.status,
            message: confError.message,
            stack: confError.stack
          })
          
          // Handle specific error cases
          if (confError.response?.status === 403) {
            error.value = 'Only editors can be assigned to conferences'
          } else if (confError.response?.status === 404) {
            error.value = 'User not found'
          } else if (confError.response?.status === 422) {
            error.value = 'Invalid conference data: ' + JSON.stringify(confError.response.data.errors)
          } else if (confError.response?.data?.message) {
            error.value = confError.response.data.message
          } else {
            error.value = 'Failed to assign conferences. Please try again.'
          }
          
          // Don't return here, let the user update complete
          success.value = 'User updated successfully'
          await fetchUsers()
          closeModal()
        }
      } else {
        success.value = 'User updated successfully'
        await fetchUsers()
        closeModal()
      }
    } else {
      // Create new user
      const response = await userApi.create(form)
      
      // Assign conferences if user is an editor
      if (form.role === 'editor' && form.conference_ids.length > 0) {
        try {
          const conferenceIds = [...form.conference_ids]
          
          const confResponse = await axios.post(`/users/${response.data.user.id}/conferences`, {
            conference_ids: conferenceIds
          })
          
          if (confResponse.data.message === 'Conferences assigned successfully') {
            success.value = 'User created and conferences assigned successfully'
            await fetchUsers()
            closeModal()
          } else {
            throw new Error('Unexpected response from server')
          }
        } catch (confError) {
          console.error('Error assigning conferences:', confError.response?.data || confError)
          error.value = confError.response?.data?.message || 'User created but failed to assign conferences. Please try assigning conferences again.'
          return
        }
      } else {
        success.value = 'User created successfully'
        await fetchUsers()
        closeModal()
      }
    }
  } catch (err) {
    console.error('Error saving user:', err.response?.data || err)
    error.value = err.response?.data?.message || 'Failed to save user. Please try again.'
  } finally {
    submitting.value = false
  }
}

// Watch for role changes to reset conference assignments
watch(() => form.role, (newRole) => {
  if (newRole !== 'editor') {
    form.conference_ids = []
  }
})

const toggleUserStatus = async (user) => {
  try {
    await userApi.update(user.id, { active: !user.active })
    await fetchUsers()
  } catch (error) {
    console.error('Error updating user status:', error)
  }
}

const deleteUser = async () => {
  if (!selectedUser.value) return
  
  deleting.value = true
  try {
    await userApi.delete(selectedUser.value.id)
    await fetchUsers()
    showDeleteModal.value = false
  } catch (error) {
    console.error('Error deleting user:', error)
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  fetchUsers()
  fetchConferences()
})

// Cleanup on component unmount
onBeforeUnmount(() => {
  users.value = []
  error.value = ''
})
</script>

<style lang="scss" scoped>
.users-view {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;

    h1 {
      margin: 0;
      color: #2c3e50;
    }
  }

  .users-grid {
    .user-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 1.5rem;
    }

    .user-card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      padding: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;

      .user-info {
        display: flex;
        align-items: center;
        gap: 1rem;

        .user-avatar {
          width: 48px;
          height: 48px;
          background: #f5f6fa;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;

          i {
            font-size: 1.5rem;
            color: #3498db;
          }
        }

        .user-details {
          h3 {
            margin: 0 0 0.25rem;
            color: #2c3e50;
          }

          .user-email {
            margin: 0 0 0.5rem;
            color: #7f8c8d;
            font-size: 0.9rem;
          }

          .user-role {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;

            &.admin {
              background: #e8f6ef;
              color: #27ae60;
            }

            &.editor {
              background: #f5f6fa;
              color: #3498db;
            }
          }
        }
      }

      .user-actions {
        display: flex;
        gap: 0.5rem;
      }
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
        font-weight: 500;
      }
    }
  }

  .form-group {
    margin-bottom: 1.5rem;

    label {
      display: block;
      margin-bottom: 0.5rem;
      color: #2c3e50;
    }

    input, select {
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
  }

  .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
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

    &.delete {
      color: #e74c3c;

      &:hover {
        background: #e74c3c;
        color: white;
      }
    }
  }

  .conference-list {
    margin-top: 0.5rem;
    
    .conference-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 0.5rem;
      max-height: 200px;
      overflow-y: auto;
      padding: 0.5rem;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    
    .conference-item {
      .conference-checkbox {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        
        input[type="checkbox"] {
          margin: 0;
        }
        
        span {
          font-size: 0.9rem;
        }
      }
    }
  }

  .success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
  }

  .error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
  }

  .user-conferences {
    margin-top: 0.5rem;
    font-size: 0.9rem;

    .conferences-label {
      color: #666;
      margin-bottom: 0.25rem;
    }

    .conference-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .conference-tag {
      background-color: #e3f2fd;
      color: #1976d2;
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      font-size: 0.8rem;
    }

    .no-conferences {
      color: #999;
      font-style: italic;
    }
  }
}
</style>
