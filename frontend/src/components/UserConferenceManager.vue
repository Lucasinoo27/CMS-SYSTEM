<template>
  <div class="user-conference-manager">
    <div class="card">
      <div class="card-header">
        <h3>Assign Conferences to User</h3>
      </div>
      <div class="card-body">
        <div v-if="loading" class="text-center">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        <div v-else>
          <div class="mb-3">
            <label for="userSelect" class="form-label">Select User</label>
            <select
              id="userSelect"
              v-model="selectedUser"
              class="form-select"
              @change="loadUserConferences"
            >
              <option value="">Select a user...</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }} ({{ user.email }})
              </option>
            </select>
          </div>

          <div v-if="selectedUser" class="mb-3">
            <label class="form-label">Assign Conferences</label>
            <div class="conference-list">
              <div v-for="conference in conferences" :key="conference.id" class="form-check">
                <input
                  type="checkbox"
                  :id="'conference-' + conference.id"
                  v-model="selectedConferences"
                  :value="conference.id"
                  class="form-check-input"
                >
                <label :for="'conference-' + conference.id" class="form-check-label">
                  {{ conference.name }}
                </label>
              </div>
            </div>
          </div>

          <div v-if="selectedUser" class="mt-3">
            <button
              class="btn btn-primary"
              @click="saveAssignments"
              :disabled="saving"
            >
              {{ saving ? 'Saving...' : 'Save Assignments' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'UserConferenceManager',
  data() {
    return {
      users: [],
      conferences: [],
      selectedUser: '',
      selectedConferences: [],
      loading: false,
      saving: false
    };
  },
  async created() {
    await this.loadUsers();
    await this.loadConferences();
  },
  methods: {
    async loadUsers() {
      try {
        const response = await axios.get('/users');
        this.users = response.data;
      } catch (error) {
        console.error('Error loading users:', error);
        this.$toast.error('Failed to load users');
      }
    },
    async loadConferences() {
      try {
        const response = await axios.get('/conferences');
        this.conferences = response.data;
      } catch (error) {
        console.error('Error loading conferences:', error);
        this.$toast.error('Failed to load conferences');
      }
    },
    async loadUserConferences() {
      if (!this.selectedUser) return;
      
      this.loading = true;
      try {
        const response = await axios.get(`/users/${this.selectedUser}/conferences`);
        this.selectedConferences = response.data.map(conf => conf.id);
      } catch (error) {
        console.error('Error loading user conferences:', error);
        this.$toast.error('Failed to load user conferences');
      } finally {
        this.loading = false;
      }
    },
    async saveAssignments() {
      if (!this.selectedUser) return;
      
      this.saving = true;
      try {
        await axios.put(`/users/${this.selectedUser}/conferences`, {
          conference_ids: this.selectedConferences
        });
        this.$toast.success('Conference assignments saved successfully');
      } catch (error) {
        console.error('Error saving conference assignments:', error);
        this.$toast.error('Failed to save conference assignments');
      } finally {
        this.saving = false;
      }
    }
  }
};
</script>

<style scoped>
.user-conference-manager {
  max-width: 800px;
  margin: 0 auto;
}

.conference-list {
  max-height: 400px;
  overflow-y: auto;
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
  padding: 1rem;
}

.form-check {
  margin-bottom: 0.5rem;
}
</style> 