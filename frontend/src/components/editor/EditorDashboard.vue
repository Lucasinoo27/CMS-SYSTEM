<template>
  <div class="editor-dashboard">
    <h2>Editor Dashboard</h2>
    
    <div v-if="loading" class="loading">
      Loading...
    </div>
    
    <div v-else>
      <div v-if="conferences.length === 0" class="no-conferences">
        <p>You haven't been assigned to any conferences yet.</p>
      </div>
      
      <div v-else class="conference-list">
        <div v-for="conference in conferences" :key="conference.id" class="conference-card">
          <h3>{{ conference.name }}</h3>
          
          <div class="pages-section">
            <h4>Pages</h4>
            <div v-if="conference.pages.length === 0" class="no-pages">
              <p>No pages available for this conference.</p>
            </div>
            
            <div v-else class="page-list">
              <div v-for="page in conference.pages" :key="page.id" class="page-item">
                <div class="page-info">
                  <h5>{{ page.title }}</h5>
                  <p class="page-status" :class="page.status">
                    {{ page.status }}
                  </p>
                </div>
                
                <div class="page-actions">
                  <button @click="editPage(conference.id, page.id)" class="edit-btn">
                    Edit
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Page Editor Modal -->
    <div v-if="showEditor" class="editor-modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Edit Page: {{ currentPage?.title }}</h3>
          <button @click="closeEditor" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="editor-container">
            <editor
              v-model="pageContent"
              :init="{
                height: 500,
                menubar: false,
                plugins: [
                  'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                  'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                  'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | blocks | ' +
                  'bold italic forecolor | alignleft aligncenter ' +
                  'alignright alignjustify | bullist numlist outdent indent | ' +
                  'removeformat | help'
              }"
            />
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="savePage" :disabled="saving" class="save-btn">
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Editor from '@tinymce/tinymce-vue'

export default {
  name: 'EditorDashboard',
  
  components: {
    Editor
  },
  
  setup() {
    const conferences = ref([])
    const loading = ref(true)
    const showEditor = ref(false)
    const currentPage = ref(null)
    const pageContent = ref('')
    const saving = ref(false)

    const fetchConferences = async () => {
      try {
        const response = await axios.get('/users/me/conferences')
        conferences.value = response.data
      } catch (error) {
        console.error('Error fetching conferences:', error)
      } finally {
        loading.value = false
      }
    }

    const editPage = async (conferenceId, pageId) => {
      try {
        const response = await axios.get(`/api/conferences/${conferenceId}/pages/${pageId}`)
        currentPage.value = response.data
        pageContent.value = response.data.content
        showEditor.value = true
      } catch (error) {
        console.error('Error fetching page:', error)
      }
    }

    const savePage = async () => {
      if (!currentPage.value) return

      saving.value = true
      try {
        await axios.put(
          `/api/conferences/${currentPage.value.conference_id}/pages/${currentPage.value.id}`,
          {
            content: pageContent.value
          }
        )
        showEditor.value = false
        // Refresh the page list
        await fetchConferences()
      } catch (error) {
        console.error('Error saving page:', error)
      } finally {
        saving.value = false
      }
    }

    const closeEditor = () => {
      showEditor.value = false
      currentPage.value = null
      pageContent.value = ''
    }

    onMounted(fetchConferences)

    return {
      conferences,
      loading,
      showEditor,
      currentPage,
      pageContent,
      saving,
      editPage,
      savePage,
      closeEditor
    }
  }
}
</script>

<style scoped>
.editor-dashboard {
  padding: 20px;
}

.loading {
  text-align: center;
  padding: 20px;
}

.no-conferences {
  text-align: center;
  padding: 40px;
  background-color: #f5f5f5;
  border-radius: 8px;
}

.conference-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.conference-card {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 20px;
}

.pages-section {
  margin-top: 15px;
}

.page-list {
  margin-top: 10px;
}

.page-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  border-bottom: 1px solid #eee;
}

.page-info {
  flex: 1;
}

.page-status {
  font-size: 0.8em;
  padding: 2px 8px;
  border-radius: 12px;
  display: inline-block;
}

.page-status.draft {
  background-color: #ffd700;
  color: #000;
}

.page-status.published {
  background-color: #4CAF50;
  color: white;
}

.edit-btn {
  padding: 5px 10px;
  background-color: #2196F3;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.edit-btn:hover {
  background-color: #1976D2;
}

/* Modal Styles */
.editor-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  border-radius: 8px;
  width: 90%;
  max-width: 1200px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
}

.editor-container {
  border: 1px solid #ddd;
  border-radius: 4px;
}

.modal-footer {
  padding: 20px;
  border-top: 1px solid #eee;
  text-align: right;
}

.save-btn {
  padding: 10px 20px;
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.save-btn:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}

.save-btn:hover:not(:disabled) {
  background-color: #45a049;
}
</style> 