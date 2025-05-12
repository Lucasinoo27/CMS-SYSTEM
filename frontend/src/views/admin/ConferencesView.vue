<template>
  <div class="conferences-view">
    <div class="page-header">
      <h1>Conference Management</h1>
      <p>Manage all conferences in the system</p>
    </div>

    <div class="conferences-container">
      <ConferenceManager
        title="All Conferences"
        :canCreate="true"
        :canDelete="true"
        @refresh="handleRefresh"
      />
    </div>

    <div v-if="showNotification" class="notification" :class="notificationType">
      {{ notificationMessage }}
      <button class="close-btn" @click="closeNotification">×</button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import ConferenceManager from '@/components/ConferenceManager.vue'

const showNotification = ref(false)
const notificationMessage = ref('')
const notificationType = ref('success')

const handleRefresh = () => {
  notificationMessage.value = 'Conferences updated successfully'
  notificationType.value = 'success'
  showNotification.value = true
}

const closeNotification = () => {
  showNotification.value = false
}

// Auto-hide notifications after 5 seconds
const hideNotificationTimer = ref(null)
watch(showNotification, (newValue) => {
  if (newValue && hideNotificationTimer.value) {
    clearTimeout(hideNotificationTimer.value)
  }
  if (newValue) {
    hideNotificationTimer.value = setTimeout(() => {
      showNotification.value = false
    }, 5000)
  }
})
</script>

<style lang="scss" scoped>
.conferences-view {
  .page-header {
    margin-bottom: 2rem;

    h1 {
      margin: 0 0 0.5rem;
      color: #2c3e50;
    }

    p {
      margin: 0;
      color: #7f8c8d;
    }
  }

  .conferences-container {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .notification {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    padding: 1rem 2rem;
    border-radius: 4px;
    color: white;
    display: flex;
    align-items: center;
    gap: 1rem;
    z-index: 1000;
    animation: slideIn 0.3s ease;

    &.success {
      background: #2ecc71;
    }

    &.error {
      background: #e74c3c;
    }

    .close-btn {
      background: none;
      border: none;
      color: white;
      font-size: 1.5rem;
      cursor: pointer;
      padding: 0;
      margin-left: 1rem;
      opacity: 0.8;

      &:hover {
        opacity: 1;
      }
    }
  }
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}
</style> 