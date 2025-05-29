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
        <div class="card" @click="navigateTo('/admin/conferences')" style="cursor: pointer;">
          <h3>Conferences</h3>
          <p>Manage all conferences in the system</p>
        </div>
        <div class="card" @click="navigateTo('/admin/pages')" style="cursor: pointer;">
          <h3>Pages</h3>
          <p>Manage pages across system</p>
        </div>
        <div class="card" @click="navigateTo('/admin/users')" style="cursor: pointer;">
          <h3>Users</h3>
          <p>Manage users and their roles</p>
        </div>
        <div class="card" @click="navigateTo('/admin/files')" style="cursor: pointer;">
          <h3>File Manager</h3>
          <p>Upload files for certain event</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

export default {
  name: 'AdminDashboardView',
  components: {
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

    const navigateTo = (path) => {
      router.push(path);
    };

    return {
      user,
      logout,
      navigateTo
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
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
  margin-top: 2rem;
}

.card {
  padding: 1.5rem;
  border-radius: 8px;
  background-color: #fff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.card h3 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
}

.card p {
  margin: 0;
  color: #666;
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
