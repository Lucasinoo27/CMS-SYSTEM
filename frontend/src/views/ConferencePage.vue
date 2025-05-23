<template>
  <div class="conference-page">
    <nav class="navbar">
      <div class="navbar-container">
        <router-link to="/" class="home-link">
          <i class="fas fa-home"></i>
          <span>Home</span>
        </router-link>
      </div>
    </nav>
    <div v-if="loading" class="loading">
      Loading...
    </div>
    <div v-else-if="error" class="error">
      {{ error }}
    </div>
    <div v-else-if="conference" class="conference-content">
      <h1>{{ conference.name }}</h1>
      <div v-if="!pages || pages.length === 0" class="no-pages">
        No pages available for this conference.
      </div>
      <div v-else>
        <div v-for="page in pages" :key="page.id" class="page-section">
          <h2>{{ page.title }}</h2>
          <div v-if="page.contents && page.contents.length > 0">
            <div v-for="content in page.contents" :key="content.id" v-html="content.content"></div>
          </div>
          <div v-else class="no-content">
            No content available for this page.
          </div>
        </div>
      </div>
    </div>
    <div v-else class="error">
      Conference not found
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { conferenceApi } from '@/services/api';

const route = useRoute();
const conference = ref(null);
const pages = ref([]);
const loading = ref(true);
const error = ref(null);

const fetchConferenceData = async () => {
  try {
    loading.value = true;
    error.value = null;

    // First fetch the conference details
    const conferenceResponse = await conferenceApi.get(route.params.id);
    console.log('Conference response data:', JSON.stringify(conferenceResponse.data, null, 2));
    conference.value = conferenceResponse.data;
    console.log('Conference value:', conference.value);

    // Then fetch the pages
    const pagesResponse = await conferenceApi.getPages(route.params.id);
    console.log('Pages response data:', JSON.stringify(pagesResponse.data, null, 2));
    pages.value = pagesResponse.data;
    console.log('Pages value:', pages.value);
  } catch (err) {
    error.value = err.message || 'Failed to load conference data';
    console.error('Error fetching conference:', err);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchConferenceData);
</script>

<style scoped>
.conference-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.loading, .error, .no-pages, .no-content {
  text-align: center;
  padding: 2rem;
  font-size: 1.2rem;
}

.error {
  color: red;
}

.no-pages, .no-content {
  color: #666;
  font-style: italic;
}

.page-section {
  margin-bottom: 2rem;
  padding: 1rem;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

h1 {
  margin-bottom: 2rem;
  color: #2c3e50;
}

h2 {
  color: #34495e;
  margin-bottom: 1rem;
}

.navbar {
  background: #fff;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.navbar-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem 2rem;
}

.home-link {
  color: #2c3e50;
  text-decoration: none;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  background: #f8f9fa;
}

.home-link i {
  font-size: 1.1rem;
}

.home-link:hover {
  color: #3498db;
  background: #f1f3f5;
}
</style> 