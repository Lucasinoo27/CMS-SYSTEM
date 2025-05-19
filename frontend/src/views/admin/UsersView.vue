<template>
  <div class="users-view">
    <div class="page-header">
      <h1>User Management</h1>
      <button class="btn-primary" @click="showCreateModal = true">
        <i class="fas fa-plus"></i> Add New User
      </button>
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
              class="btn-icon"
              @click="toggleUserStatus(user)"
              :title="user.active ? 'Deactivate' : 'Activate'"
            >
              <i :class="user.active ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
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
import { ref, reactive, onMounted } from 'vue'
import { userApi } from '@/services/api'
import { useAuthStore } from '@/stores/authStore'

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

const form = reactive({
  name: '',
  email: '',
  role: 'editor',
  password: ''
})

const resetForm = () => {
  form.name = ''
  form.email = ''
  form.role = 'editor'
  form.password = ''
  selectedUser.value = null
  isEditing.value = false
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  showDeleteModal.value = false
  resetForm()
}

const fetchUsers = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await userApi.getAll()
    users.value = response.data
    // Clear error on success
    error.value = ''
  } catch (err) {
    console.error('Error fetching users:', err)
    error.value = 'Failed to load users. Please try again.'
    users.value = [] // Ensure users is empty if there's an error
  } finally {
    loading.value = false
  }
}

const editUser = (user) => {
  selectedUser.value = user
  form.name = user.name
  form.email = user.email
  form.role = user.role
  isEditing.value = true
  showEditModal.value = true
}

const confirmDelete = (user) => {
  selectedUser.value = user
  showDeleteModal.value = true
}

const handleSubmit = async () => {
  submitting.value = true
  
  try {
    if (isEditing.value) {
      await userApi.update(selectedUser.value.id, {
        name: form.name,
        email: form.email,
        role: form.role
      })
    } else {
      await userApi.create(form)
    }
    
    await fetchUsers()
    closeModal()
  } catch (err) {
    error.value = 'Failed to save user. Please try again.'
    console.error('Error saving user:', err)
  } finally {
    submitting.value = false
  }
}

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
}
</style>
