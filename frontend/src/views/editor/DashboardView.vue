<template>
  <div class="editor-dashboard">
    <header class="dashboard-header">
      <h1>Editor Dashboard</h1>
      <div class="user-actions">
        <span class="user-name">{{ user ? user.name : '' }}</span>
        <button @click="logout" class="logout-btn">Logout</button>
      </div>
    </header>

    <div class="dashboard-content">
      <div class="dashboard-welcome">
        <h2>Welcome to the Editor Dashboard</h2>
        <p>From here you can manage your assigned conferences and their content.</p>
      </div>

      <div class="dashboard-cards">
        <div class="card">
          <h3>My Conferences</h3>
          <p>Manage your assigned conferences</p>
        </div>
        <div class="card">
          <h3>Events</h3>
          <p>Manage events for your conferences</p>
        </div>
        <div class="card">
          <h3>Papers</h3>
          <p>Review and manage submitted papers</p>
        </div>
        <div class="card">
          <h3>Participants</h3>
          <p>Manage conference participants</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/store/authStore';

export default {
  name: 'EditorDashboardView',
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();
    
    const user = computed(() => authStore.user);
    
    const logout = async () => {
      try {
        await authStore.logout();
        router.push('/login');
      } catch (error) {
        console.error('Logout failed:', error);
      }
    };
    
    return {
      user,
      logout
    };
  }
}
</script>

<style scoped>
.editor-dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
  padding-bottom: 20px;
  border-bottom: 1px solid #eee;
}

.user-actions {
  display: flex;
  align-items: center;
}

.user-name {
  margin-right: 15px;
  font-weight: 500;
}

.logout-btn {
  padding: 8px 16px;
  background-color: #f8f9fa;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s;
}

.logout-btn:hover {
  background-color: #e9ecef;
}

.dashboard-welcome {
  margin-bottom: 40px;
}

.dashboard-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
}

.card {
  background-color: white;
  border-radius: 8px;
  padding: 25px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s, box-shadow 0.3s;
  cursor: pointer;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.card h3 {
  margin-top: 0;
  color: #4a6cf7;
}
</style>
