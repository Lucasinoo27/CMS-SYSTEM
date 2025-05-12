import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import authService from './services/authService'
import { useAuthStore } from './stores/auth'

import './assets/styles/main.css'

// Set axios default base URL
axios.defaults.baseURL = import.meta.env.VITE_API_URL || 'http://localhost/api'

// Setup authentication interceptors
authService.setupInterceptors()

// Initialize auth state from localStorage if available
const initAuth = async () => {
  const pinia = createPinia()
  const app = createApp(App)
  app.use(pinia)
  
  const authStore = useAuthStore(pinia)
  if (localStorage.getItem('token')) {
    try {
      await authStore.initializeAuth()
    } catch (error) {
      console.error('Failed to initialize auth:', error)
    }
  }

  app.use(router)
  app.mount('#app')
}

initAuth()

// App creation happens in initAuth
