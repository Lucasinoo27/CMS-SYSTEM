<template>
  <div class="editor-dashboard">
    <header class="dashboard-header">
      <h1>Editor Dashboard</h1>
      <div class="user-actions">
        <span class="user-name">{{ user ? user.name : '' }}</span>
        <button @click="logout" class="logout-btn">Logout</button>
      </div>
    </header>

    <div class="dashboard-content">
      <div class="dashboard-welcome">
        <h2>Welcome to the Editor Dashboard</h2>
        <p>From here you can manage your assigned conferences and their content.</p>
      </div>

      <div class="dashboard-cards">
        <div class="card" @click="navigateTo('/editor/content')" style="cursor: pointer;">
          <h3>My Conferences</h3>
          <p>Manage your assigned conferences</p>
        </div>
        <div class="card" @click="navigateTo('/editor/files')" style="cursor: pointer;">
          <h3>Upload Files</h3>
          <p>Upload files for conferences</p>
        </div>
      </div>
    </div>

    <div class="dashboard-section">
      <h2>Recent Activities</h2>
      <div v-if="loading" class="loading">Loading activities...</div>
      <div v-else-if="activities.length === 0" class="empty">
        No recent activities found.
      </div>
      <div v-else class="activities-list">
        <div v-for="activity in activities" :key="activity.id" class="activity-item">
          <div class="activity-icon" :class="activity.type">
            <i :class="getActivityIcon(activity.type)"></i>
          </div>
          <div class="activity-content">
            <div class="activity-message">{{ activity.message }}</div>
            <div class="activity-time">{{ formatDate(activity.created_at) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { storeToRefs } from 'pinia'
import ConferenceManager from '@/components/ConferenceManager.vue'
import api from '@/services/api'

const router = useRouter()
const authStore = useAuthStore()
const { user } = storeToRefs(authStore)

const stats = ref({
  assignedConferences: 0,
  managedPages: 0,
  contentBlocks: 0,
  uploadedFiles: 0
})

const activities = ref([])
const loading = ref(false)

const logout = async () => {
  try {
    await authStore.logout()
    router.push('/login')
  } catch (error) {
    console.error('Logout failed:', error)
  }
}

const fetchStats = async () => {
  try {
    const response = await api.get('/editor/stats')
    stats.value = response.data
  } catch (error) {
    console.error('Error fetching editor stats:', error)
  }
}

const fetchActivities = async () => {
  loading.value = true
  try {
    const response = await api.get('/editor/activities')
    activities.value = response.data
  } catch (error) {
    console.error('Error fetching activities:', error)
  } finally {
    loading.value = false
  }
}

const getActivityIcon = (type) => {
  const icons = {
    'page': 'fas fa-file-alt',
    'conference': 'fas fa-calendar-alt',
    'content': 'fas fa-paragraph',
    'file': 'fas fa-file-upload'
  }
  return icons[type] || 'fas fa-info-circle'
}

const formatDate = (date) => {
  return new Date(date).toLocaleString()
}

const navigateTo = (path) => {
  router.push(path)
}

onMounted(() => {
  fetchStats()
  fetchActivities()
})
</script>

<style lang="scss" scoped>
.editor-dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;

  .dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
  }

  .user-actions {
    display: flex;
    align-items: center;

    .user-name {
      margin-right: 15px;
      font-weight: 500;
    }

    .logout-btn {
      padding: 8px 16px;
      background-color: #f8f9fa;
      border: 1px solid #ddd;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.3s;

      &:hover {
        background-color: #e9ecef;
      }
    }
  }

  .dashboard-welcome {
    margin-bottom: 40px;
  }

  .dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 2rem;
  }

  .card {
    padding: 1.5rem;
    border-radius: 8px;
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;

    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    h3 {
      margin: 0 0 0.5rem 0;
      color: #2c3e50;
    }

    p {
      margin: 0;
      color: #666;
    }
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;

    .stat-card {
      background: white;
      padding: 1.5rem;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      text-align: center;

      h3 {
        margin: 0 0 1rem;
        color: #2c3e50;
        font-size: 1rem;
      }

      .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: #3498db;
      }
    }
  }

  .dashboard-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-top: 2rem;

    h2 {
      margin: 0 0 1.5rem;
      color: #2c3e50;
    }
  }

  .activities-list {
    .activity-item {
      display: flex;
      align-items: flex-start;
      padding: 1rem 0;
      border-bottom: 1px solid #eee;

      &:last-child {
        border-bottom: none;
      }

      .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        flex-shrink: 0;

        &.page { background: #3498db; }
        &.conference { background: #2ecc71; }
        &.content { background: #9b59b6; }
        &.file { background: #e67e22; }

        i {
          color: white;
          font-size: 1.2rem;
        }
      }

      .activity-content {
        flex-grow: 1;

        .activity-message {
          color: #2c3e50;
          margin-bottom: 0.25rem;
        }

        .activity-time {
          color: #7f8c8d;
          font-size: 0.9rem;
        }
      }
    }
  }

  .loading {
    text-align: center;
    padding: 2rem;
    color: #666;
  }

  .empty {
    text-align: center;
    padding: 2rem;
    color: #666;
    background: #f8f9fa;
    border-radius: 4px;
  }
}
</style>
