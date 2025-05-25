<template>
  <div class="conference-assignment">
    <h2>Assign Conferences to User</h2>
    <div v-if="loading" class="loading">Loading...</div>
    <div v-else>
      <div class="user-info">
        <h3>{{ user.name }}</h3>
        <p>{{ user.email }}</p>
      </div>
      
      <div class="conference-list">
        <h4>Available Conferences</h4>
        <div class="conference-grid">
          <div v-for="conference in conferences" :key="conference.id" class="conference-item">
            <label class="checkbox-container">
              <input
                type="checkbox"
                :value="conference.id"
                v-model="selectedConferences"
                @change="handleConferenceChange"
              >
              <span class="checkmark"></span>
              {{ conference.name }}
            </label>
          </div>
        </div>
      </div>

      <div class="actions">
        <button 
          @click="saveAssignments" 
          :disabled="saving"
          class="btn btn-primary"
        >
          {{ saving ? 'Saving...' : 'Save Assignments' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'ConferenceAssignment',
  props: {
    userId: {
      type: [String, Number],
      required: true
    }
  },
  setup(props) {
    const user = ref({})
    const conferences = ref([])
    const selectedConferences = ref([])
    const loading = ref(true)
    const saving = ref(false)

    const fetchUserConferences = async () => {
      try {
        const response = await axios.get(`/api/users/${props.userId}/conferences`)
        selectedConferences.value = response.data.map(c => c.id)
      } catch (error) {
        console.error('Error fetching user conferences:', error)
      }
    }

    const fetchConferences = async () => {
      try {
        const response = await axios.get('/api/conferences/assignable')
        conferences.value = response.data
      } catch (error) {
        console.error('Error fetching conferences:', error)
      }
    }

    const fetchUser = async () => {
      try {
        const response = await axios.get(`/api/users/${props.userId}`)
        user.value = response.data
      } catch (error) {
        console.error('Error fetching user:', error)
      }
    }

    const saveAssignments = async () => {
      saving.value = true
      try {
        await axios.post(`/api/users/${props.userId}/conferences`, {
          conference_ids: selectedConferences.value
        })
        // Emit success event
        this.$emit('assignments-saved')
      } catch (error) {
        console.error('Error saving assignments:', error)
      } finally {
        saving.value = false
      }
    }

    onMounted(async () => {
      await Promise.all([
        fetchUser(),
        fetchConferences(),
        fetchUserConferences()
      ])
      loading.value = false
    })

    return {
      user,
      conferences,
      selectedConferences,
      loading,
      saving,
      saveAssignments
    }
  }
}
</script>

<style scoped>
.conference-assignment {
  padding: 20px;
}

.user-info {
  margin-bottom: 20px;
}

.conference-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 15px;
  margin: 20px 0;
}

.conference-item {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.checkbox-container {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.checkbox-container input {
  margin-right: 8px;
}

.actions {
  margin-top: 20px;
}

.btn {
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-primary {
  background-color: #4CAF50;
  color: white;
  border: none;
}

.btn-primary:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}

.loading {
  text-align: center;
  padding: 20px;
}
</style> 