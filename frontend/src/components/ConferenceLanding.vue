<template>
  <div class="conference-landing">
    <div v-if="loading" class="text-center">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
    </div>
    <div v-else>
      <div class="conference-header mb-4">
        <h1>{{ conference.name }}</h1>
        <p class="lead">{{ conference.description }}</p>
      </div>

      <div v-if="conference.pages.length === 0" class="alert alert-info">
        No content available for this conference.
      </div>
      
      <div v-else class="conference-content">
        <div v-for="page in conference.pages" :key="page.id" class="page-content mb-5">
          <h2>{{ page.title }}</h2>
          <div v-html="page.content"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ConferenceLanding',
  props: {
    conferenceSlug: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      conference: null,
      loading: true,
      error: null
    };
  },
  async created() {
    await this.loadConference();
  },
  methods: {
    async loadConference() {
      try {
        const response = await axios.get(`/api/conferences/${this.conferenceSlug}`);
        this.conference = response.data;
      } catch (error) {
        console.error('Error loading conference:', error);
        this.error = 'Failed to load conference content. Please try again later.';
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.conference-landing {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.conference-header {
  text-align: center;
  margin-bottom: 3rem;
}

.page-content {
  background: #fff;
  padding: 2rem;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Style for TinyMCE content */
.page-content :deep(h1),
.page-content :deep(h2),
.page-content :deep(h3) {
  margin-bottom: 1rem;
}

.page-content :deep(p) {
  margin-bottom: 1rem;
  line-height: 1.6;
}

.page-content :deep(img) {
  max-width: 100%;
  height: auto;
  margin: 1rem 0;
}

.page-content :deep(a) {
  color: #007bff;
  text-decoration: none;
}

.page-content :deep(a:hover) {
  text-decoration: underline;
}
</style> 