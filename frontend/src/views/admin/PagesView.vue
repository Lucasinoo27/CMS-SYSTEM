<template>
  <div class="pages-view">
    <div class="page-header">
      <h1>Page Management</h1>
      <button class="btn-primary" @click="createPage">
        <i class="fas fa-plus"></i> Create New Page
      </button>
    </div>

    <div class="pages-grid" v-if="!showEditor">
      <div v-if="loading" class="loading">Loading pages...</div>
      <div v-else-if="error" class="error">{{ error }}</div>
      <div v-else-if="pages.length === 0" class="empty">
        No pages found. Create your first page to get started.
      </div>
      <div v-else class="page-cards">
        <div v-for="page in pages" :key="page.id" class="page-card">
          <div class="page-info">
            <h3>{{ page.title }}</h3>
            <p class="page-url">/{{ page.slug }}</p>
            <div class="page-meta">
              <span>
                <i class="fas fa-user"></i>
                {{ page.author }}
              </span>
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
              @click="togglePageStatus(page)"
              :title="page.status === 'published' ? 'Unpublish' : 'Publish'"
            >
              <i :class="page.status === 'published' ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
            </button>
            <button
              class="btn-icon delete"
              @click="confirmDelete(page)"
              title="Delete Page"
            >
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="editor-container">
      <PageEditor
        :initialData="selectedPage"
        @save="savePage"
        @cancel="closeEditor"
      />
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal">
      <div class="modal-content">
        <h3>Delete Page</h3>
        <p>Are you sure you want to delete "{{ selectedPage?.title }}"?</p>
        <p class="warning">This action cannot be undone.</p>
        <div class="modal-actions">
          <button
            class="btn-secondary"
            @click="showDeleteModal = false"
          >
            Cancel
          </button>
          <button
            class="btn-danger"
            @click="deletePage"
            :disabled="deleting"
          >
            {{ deleting ? 'Deleting...' : 'Delete' }}
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

const pages = ref([])
const loading = ref(false)
const error = ref('')
const showEditor = ref(false)
const showDeleteModal = ref(false)
const selectedPage = ref(null)
const deleting = ref(false)

const fetchPages = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await pageApi.getAll()
    pages.value = response.data
  } catch (err) {
    error.value = 'Failed to load pages. Please try again.'
    console.error('Error fetching pages:', err)
  } finally {
    loading.value = false
  }
}

const createPage = () => {
  selectedPage.value = {
    title: '',
    slug: '',
    blocks: []
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
    await fetchPages()
    closeEditor()
  } catch (error) {
    console.error('Error saving page:', error)
    throw error
  }
}

const togglePageStatus = async (page) => {
  try {
    const newStatus = page.status === 'published' ? 'draft' : 'published'
    await pageApi.update(page.id, { status: newStatus })
    await fetchPages()
  } catch (error) {
    console.error('Error updating page status:', error)
  }
}

const confirmDelete = (page) => {
  selectedPage.value = page
  showDeleteModal.value = true
}

const deletePage = async () => {
  if (!selectedPage.value) return
  
  deleting.value = true
  try {
    await pageApi.delete(selectedPage.value.id)
    await fetchPages()
    showDeleteModal.value = false
  } catch (error) {
    console.error('Error deleting page:', error)
  } finally {
    deleting.value = false
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  fetchPages()
})
</script>

<style lang="scss" scoped>
.pages-view {
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

  .pages-grid {
    .page-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
      gap: 1.5rem;
    }

    .page-card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      padding: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;

      .page-info {
        flex-grow: 1;
        margin-right: 1rem;

        h3 {
          margin: 0 0 0.5rem;
          color: #2c3e50;
        }

        .page-url {
          color: #7f8c8d;
          font-size: 0.9rem;
          margin: 0 0 1rem;
        }

        .page-meta {
          display: flex;
          gap: 1rem;
          font-size: 0.9rem;
          color: #7f8c8d;

          span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
          }

          .page-status {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 500;

            &.published {
              background: #e8f6ef;
              color: #27ae60;
            }

            &.draft {
              background: #f8f9fa;
              color: #7f8c8d;
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

  .editor-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 2rem;
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
    padding: 2rem;
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
        margin: 0 0 1rem;
        color: #2c3e50;
      }

      p {
        margin: 0 0 1rem;
        color: #2c3e50;

        &.warning {
          color: #e74c3c;
          font-weight: 500;
        }
      }

      .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 2rem;
      }
    }
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

  .btn-danger {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.3s;

    &:hover {
      background: #c0392b;
    }

    &:disabled {
      background: #95a5a6;
      cursor: not-allowed;
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

    &.delete {
      color: #e74c3c;

      &:hover {
        background: #e74c3c;
        color: white;
      }
    }
  }
}
</style> 