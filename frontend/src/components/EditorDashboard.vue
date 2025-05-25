<template>
  <div class="editor-dashboard">
    <div v-if="loading" class="text-center">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    <div v-else>
      <h2 class="mb-4">My Conferences</h2>
      
      <div v-if="conferences.length === 0" class="alert alert-info">
        You haven't been assigned to any conferences yet.
      </div>
      
      <div v-else class="conference-list">
        <div v-for="conference in conferences" :key="conference.id" class="card mb-4">
          <div class="card-header">
            <h3>{{ conference.name }}</h3>
          </div>
          <div class="card-body">
            <div v-if="conference.pages.length === 0" class="alert alert-info">
              No pages available for this conference.
            </div>
            <div v-else class="page-list">
              <div v-for="page in conference.pages" :key="page.id" class="page-item">
                <div class="d-flex justify-content-between align-items-center">
                  <h4>{{ page.title }}</h4>
                  <button
                    class="btn btn-primary"
                    @click="editPage(page)"
                  >
                    Edit Page
                  </button>
                </div>
                <p class="text-muted">{{ page.description }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Page Editor Modal -->
    <div
      v-if="showEditor"
      class="modal fade show"
      style="display: block;"
      tabindex="-1"
    >
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Page: {{ currentPage?.title }}</h5>
            <button
              type="button"
              class="btn-close"
              @click="closeEditor"
            ></button>
          </div>
          <div class="modal-body">
            <wysiwyg-editor
              v-if="currentPage"
              v-model="currentPage.content"
              @save="savePage"
              :loading="saving"
            />
          </div>
        </div>
      </div>
    </div>
    <div
      v-if="showEditor"
      class="modal-backdrop fade show"
    ></div>
  </div>
</template>

<script>
import axios from 'axios';
import WysiwygEditor from './WysiwygEditor.vue';

export default {
  name: 'EditorDashboard',
  components: {
    WysiwygEditor
  },
  data() {
    return {
      conferences: [],
      loading: true,
      showEditor: false,
      currentPage: null,
      saving: false
    };
  },
  async created() {
    await this.loadConferences();
  },
  methods: {
    async loadConferences() {
      try {
        const response = await axios.get('/api/editor/conferences');
        this.conferences = response.data;
      } catch (error) {
        console.error('Error loading conferences:', error);
        this.$toast.error('Failed to load conferences');
      } finally {
        this.loading = false;
      }
    },
    editPage(page) {
      this.currentPage = { ...page };
      this.showEditor = true;
    },
    closeEditor() {
      this.showEditor = false;
      this.currentPage = null;
    },
    async savePage() {
      if (!this.currentPage) return;
      
      this.saving = true;
      try {
        await axios.put(`/api/pages/${this.currentPage.id}`, {
          content: this.currentPage.content
        });
        
        // Update the page in the local state
        const conference = this.conferences.find(c => 
          c.pages.some(p => p.id === this.currentPage.id)
        );
        if (conference) {
          const pageIndex = conference.pages.findIndex(p => p.id === this.currentPage.id);
          if (pageIndex !== -1) {
            conference.pages[pageIndex].content = this.currentPage.content;
          }
        }
        
        this.$toast.success('Page saved successfully');
        this.closeEditor();
      } catch (error) {
        console.error('Error saving page:', error);
        this.$toast.error('Failed to save page');
      } finally {
        this.saving = false;
      }
    }
  }
};
</script>

<style scoped>
.editor-dashboard {
  padding: 2rem;
}

.conference-list {
  max-width: 1200px;
  margin: 0 auto;
}

.page-list {
  display: grid;
  gap: 1rem;
}

.page-item {
  padding: 1rem;
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
}

.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.5);
}
</style> 