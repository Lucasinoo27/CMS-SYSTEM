import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import authService from './services/authService'

import './assets/styles/main.css'

// Set axios default base URL
axios.defaults.baseURL = import.meta.env.VITE_API_URL || 'http://localhost/api'

// Setup authentication interceptors
authService.setupInterceptors()

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

app.mount('#app')
