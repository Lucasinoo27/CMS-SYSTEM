<template>
  <div class="conference-landing">
    <div v-if="loading" class="loading">
      Loading...
    </div>
    
    <div v-else-if="error" class="error">
      {{ error }}
    </div>
    
    <div v-else>
      <div class="conference-header">
        <h1>{{ conference.name }}</h1>
        <p class="conference-description">{{ conference.description }}</p>
      </div>

      <div class="conference-content">
        <div v-if="pages.length === 0" class="no-pages">
          <p>No content available for this conference.</p>
        </div>
        
        <div v-else class="page-list">
          <div v-for="page in pages" :key="page.id" class="page-content">
            <h2>{{ page.title }}</h2>
            <div v-html="page.content" class="page-body"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

export default {
  name: 'ConferenceLanding',
  
  setup() {
    const route = useRoute()
    const conference = ref(null)
    const pages = ref([])
    const loading = ref(true)
    const error = ref(null)

    const fetchConferenceData = async () => {
      try {
        const conferenceSlug = route.params.slug
        const [conferenceResponse, pagesResponse] = await Promise.all([
          axios.get(`/api/conferences/${conferenceSlug}`),
          axios.get(`/api/conferences/${conferenceSlug}/pages`)
        ])
        
        conference.value = conferenceResponse.data
        pages.value = pagesResponse.data
      } catch (err) {
        error.value = 'Failed to load conference content. Please try again later.'
        console.error('Error fetching conference data:', err)
      } finally {
        loading.value = false
      }
    }

    onMounted(fetchConferenceData)

    return {
      conference,
      pages,
      loading,
      error
    }
  }
}
</script>

<style scoped>
.conference-landing {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.loading {
  text-align: center;
  padding: 40px;
  font-size: 1.2em;
  color: #666;
}

.error {
  text-align: center;
  padding: 40px;
  color: #dc3545;
  background-color: #f8d7da;
  border-radius: 8px;
}

.conference-header {
  margin-bottom: 40px;
  text-align: center;
}

.conference-header h1 {
  font-size: 2.5em;
  margin-bottom: 10px;
  color: #333;
}

.conference-description {
  font-size: 1.2em;
  color: #666;
  max-width: 800px;
  margin: 0 auto;
}

.conference-content {
  margin-top: 40px;
}

.no-pages {
  text-align: center;
  padding: 40px;
  background-color: #f8f9fa;
  border-radius: 8px;
  color: #666;
}

.page-content {
  margin-bottom: 40px;
  padding: 20px;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.page-content h2 {
  color: #333;
  margin-bottom: 20px;
  font-size: 1.8em;
}

.page-body {
  line-height: 1.6;
  color: #444;
}

/* Style for TinyMCE content */
.page-body :deep(img) {
  max-width: 100%;
  height: auto;
  margin: 10px 0;
}

.page-body :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 20px 0;
}

.page-body :deep(th),
.page-body :deep(td) {
  border: 1px solid #ddd;
  padding: 8px;
  text-align: left;
}

.page-body :deep(th) {
  background-color: #f5f5f5;
}

.page-body :deep(a) {
  color: #2196F3;
  text-decoration: none;
}

.page-body :deep(a:hover) {
  text-decoration: underline;
}

.page-body :deep(blockquote) {
  border-left: 4px solid #2196F3;
  margin: 20px 0;
  padding: 10px 20px;
  background-color: #f8f9fa;
}

.page-body :deep(pre) {
  background-color: #f8f9fa;
  padding: 15px;
  border-radius: 4px;
  overflow-x: auto;
}

.page-body :deep(code) {
  font-family: monospace;
  background-color: #f8f9fa;
  padding: 2px 4px;
  border-radius: 3px;
}
</style> 