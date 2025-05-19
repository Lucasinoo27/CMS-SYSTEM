<template>
  <div class="admin-dashboard">
    <header class="dashboard-header">
      <h1>Admin Dashboard</h1>
      <div class="user-actions">
        <span class="user-name">{{ user ? user.name : '' }}</span>
        <button @click="logout" class="logout-btn">Logout</button>
      </div>
    </header>

    <div class="dashboard-content">
      <div class="dashboard-welcome">
        <h2>Welcome to the Admin Dashboard</h2>
        <p>From here you can manage all aspects of the University Consortium CMS.</p>
      </div>

      <div class="dashboard-cards">
        <div class="card">
          <h3>Conferences</h3>
          <p>Manage all conferences in the system</p>
        </div>
        <div class="card">
          <h3>Users</h3>
          <p>Manage users and their roles</p>
        </div>
        <div class="card">
          <h3>Events</h3>
          <p>Manage events across all conferences</p>
        </div>
        <div class="card">
          <h3>Papers</h3>
          <p>Review and manage submitted papers</p>
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Conferences</h3>
          <div class="stat-value">{{ stats.conferences }}</div>
        </div>
        <div class="stat-card">
          <h3>Active Pages</h3>
          <div class="stat-value">{{ stats.pages }}</div>
        </div>
        <div class="stat-card">
          <h3>Total Users</h3>
          <div class="stat-value">{{ stats.users }}</div>
        </div>
        <div class="stat-card">
          <h3>Uploaded Files</h3>
          <div class="stat-value">{{ stats.files }}</div>
        </div>
      </div>

      <div class="dashboard-section">
        <ConferenceManager
          title="Manage Conferences"
          :canCreate="true"
          :canDelete="true"
          @refresh="fetchStats"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { computed, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import ConferenceManager from '@/components/ConferenceManager.vue';
import api from '@/services/api';

export default {
  name: 'AdminDashboardView',
  components: {
    ConferenceManager
  },
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

    const stats = ref({
      conferences: 0,
      pages: 0,
      users: 0,
      files: 0
    });

    const fetchStats = async () => {
      try {
        const response = await api.get('/admin/stats');
        stats.value = response.data;
      } catch (error) {
        console.error('Error fetching admin stats:', error);
        // Set default values if stats fail to load
        stats.value = {
          conferences: 0,
          pages: 0,
          users: 0,
          files: 0
        };
      }
    };

    onMounted(() => {
      fetchStats();
    });

    return {
      user,
      logout,
      stats,
      fetchStats
    };
  }
}
</script>

<style scoped>
.admin-dashboard {
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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.stat-card h3 {
  margin: 0 0 1rem;
  color: #2c3e50;
  font-size: 1rem;
}

.stat-card .stat-value {
  font-size: 2rem;
  font-weight: bold;
  color: #3498db;
}

.dashboard-section {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-top: 2rem;
}
</style>
