<template>
  <div class="user-conference-manager">
    <h2>Manage User Conferences</h2>
    
    <div v-if="loading" class="loading">
      Loading...
    </div>
    
    <div v-else>
      <div class="user-list">
        <h3>Users</h3>
        <div class="user-item" v-for="user in users" :key="user.id" @click="selectUser(user)">
          <span>{{ user.name }}</span>
          <span class="role-badge">{{ user.role }}</span>
        </div>
      </div>

      <div v-if="selectedUser" class="conference-assignment">
        <h3>Assign Conferences to {{ selectedUser.name }}</h3>
        
        <div class="conference-list">
          <div v-for="conference in conferences" :key="conference.id" class="conference-item">
            <label>
              <input
                type="checkbox"
                :checked="isConferenceAssigned(conference.id)"
                @change="toggleConference(conference.id)"
              >
              {{ conference.name }}
            </label>
          </div>
        </div>

        <div class="actions">
          <button @click="saveAssignments" :disabled="saving">
            {{ saving ? 'Saving...' : 'Save Assignments' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'UserConferenceManager',
  
  setup() {
    const users = ref([])
    const conferences = ref([])
    const selectedUser = ref(null)
    const assignedConferences = ref([])
    const loading = ref(true)
    const saving = ref(false)

    const fetchUsers = async () => {
      try {
        const response = await axios.get('/api/users')
        users.value = response.data
      } catch (error) {
        console.error('Error fetching users:', error)
      }
    }

    const fetchConferences = async () => {
      try {
        const response = await axios.get('/api/conferences')
        conferences.value = response.data
      } catch (error) {
        console.error('Error fetching conferences:', error)
      }
    }

    const fetchUserConferences = async (userId) => {
      try {
        const response = await axios.get(`/api/users/${userId}/conferences`)
        assignedConferences.value = response.data.map(c => c.id)
      } catch (error) {
        console.error('Error fetching user conferences:', error)
      }
    }

    const selectUser = async (user) => {
      selectedUser.value = user
      await fetchUserConferences(user.id)
    }

    const isConferenceAssigned = (conferenceId) => {
      return assignedConferences.value.includes(conferenceId)
    }

    const toggleConference = (conferenceId) => {
      const index = assignedConferences.value.indexOf(conferenceId)
      if (index === -1) {
        assignedConferences.value.push(conferenceId)
      } else {
        assignedConferences.value.splice(index, 1)
      }
    }

    const saveAssignments = async () => {
      if (!selectedUser.value) return

      saving.value = true
      try {
        const response = await axios.post(`/api/users/${selectedUser.value.id}/conferences`, {
          conference_ids: assignedConferences.value
        })
        
        // Show success message
        alert('Conferences assigned successfully')
        
        // Refresh the user's conferences
        await fetchUserConferences(selectedUser.value.id)
      } catch (error) {
        console.error('Error saving assignments:', error)
        
        // Show error message to user
        if (error.response?.data?.errors) {
          const errorMessage = Object.values(error.response.data.errors)
            .flat()
            .join('\n')
          alert('Error: ' + errorMessage)
        } else {
          alert('Failed to save conference assignments. Please try again.')
        }
      } finally {
        saving.value = false
      }
    }

    onMounted(async () => {
      await Promise.all([fetchUsers(), fetchConferences()])
      loading.value = false
    })

    return {
      users,
      conferences,
      selectedUser,
      assignedConferences,
      loading,
      saving,
      selectUser,
      isConferenceAssigned,
      toggleConference,
      saveAssignments
    }
  }
}
</script>

<style scoped>
.user-conference-manager {
  padding: 20px;
}

.loading {
  text-align: center;
  padding: 20px;
}

.user-list {
  margin-bottom: 20px;
}

.user-item {
  padding: 10px;
  border: 1px solid #ddd;
  margin-bottom: 5px;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.user-item:hover {
  background-color: #f5f5f5;
}

.role-badge {
  background-color: #e0e0e0;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.8em;
}

.conference-assignment {
  margin-top: 20px;
}

.conference-list {
  margin: 20px 0;
}

.conference-item {
  padding: 10px;
  border-bottom: 1px solid #eee;
}

.conference-item label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.actions {
  margin-top: 20px;
}

button {
  padding: 10px 20px;
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

button:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}

button:hover:not(:disabled) {
  background-color: #45a049;
}
</style> 