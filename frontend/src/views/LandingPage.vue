<template>
  <div class="landing-container">
    <!-- Header Section -->
    <header class="header">
      <div class="header-content">
        <h1 class="logo">University CMS</h1>
        <div 
          class="connection-status"
          :class="{ 'status-error': backendStatus !== 'Connected' }"
        >
          API Status: {{ backendStatus }}
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <div class="hero-section">
      <div class="hero-content">
        <h2>Welcome to the University Consortium</h2>
        <p>A comprehensive platform for managing academic conferences, papers, and events</p>
        <div class="auth-buttons">
          <router-link to="/login" class="btn btn-primary">Login</router-link>
          <router-link to="/register" class="btn btn-secondary">Register</router-link>
        </div>
      </div>
    </div>

    <!-- Conferences Section -->
    <div class="conferences-section">
      <h2>Upcoming Conferences</h2>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading conferences...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <div class="error-icon">!</div>
        <p>{{ error }}</p>
      </div>

      <!-- Conferences Grid -->
      <div v-else class="conferences-grid">
        <div 
          v-for="conference in conferences" 
          :key="conference.id" 
          class="conference-card"
          @click="navigateToConference(conference.id)"
        >
          <h3>{{ conference.name }}</h3>
          <div class="conference-details">
            <p class="conference-date">
              <span class="label">Date:</span> 
              {{ formatDate(conference.start_date) }} - {{ formatDate(conference.end_date) }}
            </p>
            <p class="conference-location">
              <span class="label">Location:</span> {{ conference.location }}
            </p>
            <p class="conference-description">
              {{ conference.description }}
            </p>
          </div>
          <div class="conference-status" :class="conference.status.toLowerCase()">
            {{ conference.status }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import axios from 'axios'

const router = useRouter()
const authStore = useAuthStore()
const backendStatus = ref('Checking connection...')
const conferences = ref([])
const loading = ref(true)
const error = ref(null)

const navigateToConference = (conferenceId) => {
  router.push(`/conferences/${conferenceId}`)
}

const formatDate = (dateString) => {
  if (!dateString) return 'Date not set'
  
  try {
    const date = new Date(dateString)
    if (isNaN(date.getTime())) {
      return 'Invalid date'
    }
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  } catch (error) {
    return 'Invalid date'
  }
}

const fetchConferences = async () => {
  try {
    const response = await axios.get('/conferences')
    conferences.value = response.data
    loading.value = false
  } catch (err) {
    error.value = 'Failed to load conferences'
    loading.value = false
    console.error('Error fetching conferences:', err)
  }
}

onMounted(async () => {
  try {
    await axios.get('/health')
    backendStatus.value = 'Connected'
  } catch (error) {
    backendStatus.value = 'Connection issue'
    console.error('Backend connection issue:', error)
  }
  
  await fetchConferences()
  
  if (authStore.isAuthenticated) {
    if (authStore.isAdmin) {
      router.push('/admin/dashboard')
    } else if (authStore.isEditor) {
      router.push('/editor/dashboard')
    } else {
      router.push('/home')
    }
  }
})
</script>

<style scoped>
.landing-container {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.header {
  background-color: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 1rem 0;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  font-size: 1.5rem;
  font-weight: bold;
  color: #333;
}

.connection-status {
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  background-color: #e6f4ea;
  color: #1e7e34;
}

.status-error {
  background-color: #fbe9e7;
  color: #d32f2f;
}

.hero-section {
  background-color: white;
  padding: 4rem 0;
}

.hero-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
  text-align: center;
}

.hero-content h2 {
  font-size: 2.5rem;
  font-weight: bold;
  color: #333;
  margin-bottom: 1rem;
}

.hero-content p {
  font-size: 1.25rem;
  color: #666;
  margin-bottom: 2rem;
}

.auth-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 0.375rem;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-primary {
  background-color: #4a6cf7;
  color: white;
  border: 1px solid #4a6cf7;
}

.btn-primary:hover {
  background-color: #3a5ad9;
}

.btn-secondary {
  background-color: transparent;
  color: #4a6cf7;
  border: 1px solid #4a6cf7;
}

.btn-secondary:hover {
  background-color: rgba(74, 108, 247, 0.1);
}

.conferences-section {
  max-width: 1200px;
  margin: 0 auto;
  padding: 4rem 1rem;
}

.conferences-section h2 {
  text-align: center;
  font-size: 2rem;
  font-weight: bold;
  color: #333;
  margin-bottom: 2rem;
}

.loading-state {
  text-align: center;
  padding: 2rem;
}

.spinner {
  display: inline-block;
  width: 2rem;
  height: 2rem;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #4a6cf7;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-state {
  text-align: center;
  padding: 2rem;
  color: #d32f2f;
}

.error-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  background-color: #fbe9e7;
  border-radius: 50%;
  margin-bottom: 1rem;
  font-weight: bold;
}

.conferences-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
}

.conference-card {
  background: white;
  border-radius: 0.5rem;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.conference-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.conference-card h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 1rem;
}

.conference-details {
  margin-bottom: 1rem;
}

.conference-date,
.conference-location {
  font-size: 0.875rem;
  color: #666;
  margin-bottom: 0.5rem;
}

.label {
  font-weight: 500;
  color: #333;
}

.conference-description {
  font-size: 0.875rem;
  color: #666;
  margin-bottom: 1rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.conference-status {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.conference-status.active {
  background-color: #e6f4ea;
  color: #1e7e34;
}

.conference-status.upcoming {
  background-color: #fff3cd;
  color: #856404;
}

.conference-status.completed {
  background-color: #e2e3e5;
  color: #383d41;
}

@media (max-width: 768px) {
  .conferences-grid {
    grid-template-columns: 1fr;
  }
  
  .auth-buttons {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
    text-align: center;
  }
}
</style>
