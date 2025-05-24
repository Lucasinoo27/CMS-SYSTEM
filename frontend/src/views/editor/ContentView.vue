<template>
  <div class="content-view">
    <div class="page-header">
      <h1>My Content</h1>
    </div>

    <div v-if="loading" class="loading">Loading content...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <div v-else-if="conferences.length === 0" class="empty">
      No conferences assigned to you yet.
    </div>
    <div v-else class="conferences-grid">
      <div v-for="conference in conferences" :key="conference.id" class="conference-section">
        <div class="conference-header">
          <h2>{{ conference.name }}</h2>
          <button class="btn-primary" @click="createPage(conference)">
            <i class="fas fa-plus"></i> Create New Page
          </button>
        </div>

        <div class="pages-grid">
          <div v-if="conference.pages.length === 0" class="empty">
            No pages in this conference yet.
          </div>
          <div v-else class="page-cards">
            <div v-for="page in conference.pages" :key="page.id" class="page-card">
              <div class="page-info">
                <h3>{{ page.title }}</h3>
                <p class="page-url">/{{ page.slug }}</p>
                <div class="page-meta">
                  <span>
                    <i class="fas fa-calendar"></i>
                    {{ formatDate(page.updated_at) }}
                  </span>
                  <span :class="['page-status', page.status]">
                    {{ page.status }}
                  </span>
                </div>
              </div>
              <div class="page-actions">
                <button
                  class="btn-icon"
                  @click="editPage(page)"
                  title="Edit Page"
                >
                  <i class="fas fa-edit"></i>
                </button>
                <button
                  class="btn-icon"
                  @click="previewPage(page)"
                  title="Preview Page"
                >
                  <i class="fas fa-eye"></i>
                </button>
                <button
                  class="btn-icon"
                  @click="requestPublish(page)"
                  :disabled="page.status === 'pending'"
                  title="Request Publish"
                >
                  <i class="fas fa-paper-plane"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showEditor" class="editor-container">
      <PageEditor
        :initialData="selectedPage"
        @save="savePage"
        @cancel="closeEditor"
      />
    </div>

    <!-- Publish Request Modal -->
    <div v-if="showPublishModal" class="modal">
      <div class="modal-content">
        <h3>Request Publication</h3>
        <p>Are you ready to submit "{{ selectedPage?.title }}" for review?</p>
        <div class="form-group">
          <label for="notes">Notes for Reviewer (optional)</label>
          <textarea
            id="notes"
            v-model="publishNotes"
            rows="4"
            placeholder="Add any notes for the reviewer..."
          ></textarea>
        </div>
        <div class="modal-actions">
          <button
            class="btn-secondary"
            @click="showPublishModal = false"
          >
            Cancel
          </button>
          <button
            class="btn-primary"
            @click="submitPublishRequest"
            :disabled="submitting"
          >
            {{ submitting ? 'Submitting...' : 'Submit for Review' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageEditor from '@/components/PageEditor.vue'
import { pageApi } from '@/services/api'
import axios from '@/services/axios'

const conferences = ref([])
const loading = ref(false)
const error = ref('')
const showEditor = ref(false)
const showPublishModal = ref(false)
const selectedPage = ref(null)
const publishNotes = ref('')
const submitting = ref(false)

const fetchConferences = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await axios.get('/users/me/conferences')
    
    if (response.data) {
      conferences.value = response.data
    } else {
      error.value = 'No data received from server'
    }
  } catch (err) {
    console.error('Error fetching conferences:', err)
    if (err.response) {
      error.value = err.response.data.message || 'Failed to load content. Please try again.'
    } else if (err.request) {
      error.value = 'No response from server. Please check your connection.'
    } else {
      error.value = 'Failed to load content. Please try again.'
    }
  } finally {
    loading.value = false
  }
}

const createPage = (conference) => {
  selectedPage.value = {
    title: '',
    slug: '',
    blocks: [],
    conference_id: conference.id
  }
  showEditor.value = true
}

const editPage = (page) => {
  selectedPage.value = { ...page }
  showEditor.value = true
}

const previewPage = (page) => {
  window.open(`/preview/${page.slug}`, '_blank')
}

const closeEditor = () => {
  showEditor.value = false
  selectedPage.value = null
}

const savePage = async (formData) => {
  try {
    if (selectedPage.value.id) {
      await pageApi.update(selectedPage.value.id, formData)
    } else {
      await pageApi.create(formData)
    }
    await fetchConferences()
    closeEditor()
  } catch (error) {
    console.error('Error saving page:', error)
    throw error
  }
}

const requestPublish = (page) => {
  selectedPage.value = page
  publishNotes.value = ''
  showPublishModal.value = true
}

const submitPublishRequest = async () => {
  if (!selectedPage.value) return
  
  submitting.value = true
  try {
    await pageApi.requestPublish(selectedPage.value.id, {
      notes: publishNotes.value
    })
    await fetchConferences()
    showPublishModal.value = false
  } catch (error) {
    console.error('Error requesting publish:', error)
  } finally {
    submitting.value = false
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  fetchConferences()
})
</script>

<style lang="scss" scoped>
.content-view {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;

    h1 {
      margin: 0;
      color: #2c3e50;
    }
  }

  .conferences-grid {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }

  .conference-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;

    .conference-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;

      h2 {
        margin: 0;
        color: #2c3e50;
      }
    }
  }

  .pages-grid {
    .page-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1rem;
    }

    .page-card {
      background: #f8f9fa;
      border-radius: 6px;
      padding: 1rem;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;

      .page-info {
        h3 {
          margin: 0 0 0.5rem;
          color: #2c3e50;
        }

        .page-url {
          margin: 0 0 0.5rem;
          color: #7f8c8d;
          font-size: 0.9rem;
        }

        .page-meta {
          display: flex;
          gap: 1rem;
          font-size: 0.9rem;
          color: #666;

          .page-status {
            text-transform: capitalize;
            
            &.draft {
              color: #f39c12;
            }
            
            &.pending {
              color: #3498db;
            }
            
            &.published {
              color: #27ae60;
            }
          }
        }
      }

      .page-actions {
        display: flex;
        gap: 0.5rem;
      }
    }
  }

  .loading {
    text-align: center;
    padding: 2rem;
    color: #666;
  }

  .error {
    color: #e74c3c;
    text-align: center;
    padding: 1rem;
    background: #fadbd8;
    border-radius: 4px;
    margin: 1rem 0;
  }

  .empty {
    text-align: center;
    padding: 1rem;
    color: #666;
    background: #f8f9fa;
    border-radius: 4px;
  }

  .modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;

    .modal-content {
      background: white;
      padding: 2rem;
      border-radius: 8px;
      width: 100%;
      max-width: 500px;

      h3 {
        margin: 0 0 1.5rem;
        color: #2c3e50;
      }
    }
  }

  .form-group {
    margin-bottom: 1.5rem;

    label {
      display: block;
      margin-bottom: 0.5rem;
      color: #2c3e50;
    }

    textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
      resize: vertical;

      &:focus {
        outline: none;
        border-color: #3498db;
      }
    }
  }

  .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
  }

  .btn-primary {
    background: #3498db;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.3s;

    i {
      font-size: 0.9rem;
    }

    &:hover {
      background: #2980b9;
    }

    &:disabled {
      background: #95a5a6;
      cursor: not-allowed;
    }
  }

  .btn-secondary {
    background: #95a5a6;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.3s;

    &:hover {
      background: #7f8c8d;
    }
  }

  .btn-icon {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 4px;
    background: #f5f6fa;
    color: #3498db;
    cursor: pointer;
    transition: all 0.3s;

    &:hover {
      background: #3498db;
      color: white;
    }

    &:disabled {
      background: #95a5a6;
      color: #666;
      cursor: not-allowed;
    }
  }
}
</style> 