import axios from 'axios'
import { useAuthStore } from '@/stores/authStore'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  timeout: 10000, // 10 seconds timeout
  timeoutErrorMessage: 'Request timed out. Please try again.',
})

// Helper function to check if a route is public
const isPublicRoute = (url) => {
  const publicPatterns = [
    /^\/conferences$/, // GET /conferences
    /^\/conferences\/\d+$/, // GET /conferences/{id}
    /^\/conferences\/[a-zA-Z0-9-]+$/, // GET /conferences/{slug}
    /^\/conferences\/\d+\/pages$/, // GET /conferences/{id}/pages
    /^\/conferences\/\d+\/pages\/\d+$/, // GET /conferences/{id}/pages/{id}
  ]

  // Always include auth token if available, regardless of route
  const token = localStorage.getItem('token')
  if (token) {
    return false // Not treating as public if authenticated
  }

  return publicPatterns.some((pattern) => pattern.test(url))
}

// Request interceptor
api.interceptors.request.use(
  (config) => {
    // Always include token if available
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.code === 'ECONNABORTED') {
      return Promise.reject({
        message:
          'Request timed out. Please check your internet connection and try again.',
      })
    }

    if (error.response?.status === 401) {
      if (!isPublicRoute(error.config.url)) {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        window.location.href = '/login'
      }
    }

    return Promise.reject(error)
  }
)

export const conferenceApi = {
  getAll: () => api.get('/conferences'),
  get: (idOrSlug) => api.get(`/conferences/${idOrSlug}`),
  getPages: (id) => api.get(`/conferences/${id}/pages`),
  create: (data) => api.post('/conferences', data),
  update: (id, data) => api.put(`/conferences/${id}`, data),
  delete: (id) => api.delete(`/conferences/${id}`),
}

export const pageApi = {
  getAll: () => api.get('/pages'),
  get: (id) => api.get(`/pages/${id}`),
  create: (data) => api.post('/pages', data),
  update: (id, data) => api.put(`/pages/${id}`, data),
  delete: (id) => api.delete(`/pages/${id}`),
  getAssigned: () => api.get('/editor/pages'),
}

export const userApi = {
  getAll: () => api.get('/users'),
  get: (id) => api.get(`/users/${id}`),
  create: (data) => api.post('/users', data),
  update: (id, data) => api.put(`/users/${id}`, data),
  delete: (id) => api.delete(`/users/${id}`),
}

export const fileApi = {
  upload: (formData) =>
    api.post('/files', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    }),
  getAll: () => api.get('/files'),
  get: (id) => api.get(`/files/${id}`),
  download: (url) => axios.get(url, { responseType: 'blob' }),
  delete: (id) => api.delete(`/files/${id}`),

  // Page file management
  getPageFiles: (conferenceId, pageId) =>
    axios.get(
      `${
        import.meta.env.VITE_API_URL || '/api'
      }/conferences/${conferenceId}/pages/${pageId}/files`
    ),
  uploadToPage: (conferenceId, pageId, formData) =>
    api.post(`/conferences/${conferenceId}/pages/${pageId}/files`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    }),
  assignToPage: (conferenceId, pageId, fileId) =>
    api.post(`/conferences/${conferenceId}/pages/${pageId}/files/assign`, {
      file_id: fileId,
    }),
  removeFromPage: (conferenceId, pageId, fileId) =>
    axios.delete(
      `${
        import.meta.env.VITE_API_URL || '/api'
      }/conferences/${conferenceId}/pages/${pageId}/files/${fileId}`,
      {
        validateStatus: (status) => status < 500, // Only reject if server error
        timeout: 15000, // Increase timeout to 15 seconds
      }
    ),
}

export default api
