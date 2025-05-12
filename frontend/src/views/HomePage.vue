<template>
  <div class="home-container">
    <header class="navbar">
      <div class="logo">University CMS</div>
      <div class="user-info">
        <span>{{ userRole }}</span>
        <button @click="logout" class="btn-logout">Logout</button>
      </div>
    </header>

    <main class="content">
      <div class="welcome-section">
        <h1>Welcome, {{ userName }}</h1>
        <p>You're logged in to the University Consortium Management System.</p>
      </div>

      <div class="dashboard-grid">
        <div class="dashboard-card">
          <h2>My Dashboard</h2>
          <p>This is your personal dashboard. Future features and statistics will appear here.</p>
        </div>
        
        <div class="dashboard-card">
          <h2>Conferences</h2>
          <p>View and manage academic conferences.</p>
          <router-link to="/conferences" class="card-link">Browse Conferences</router-link>
        </div>
        
        <div class="dashboard-card">
          <h2>Quick Actions</h2>
          <div class="action-buttons">
            <button class="action-btn">View Profile</button>
            <button class="action-btn">My Events</button>
            <button class="action-btn">My Papers</button>
          </div>
        </div>
      </div>
    </main>

    <footer class="footer">
      <p>&copy; {{ currentYear }} University Consortium Management System</p>
    </footer>
  </div>
</template>

<script>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/store/authStore';

export default {
  name: 'HomePage',
  setup() {
    const router = useRouter();
    const authStore = useAuthStore();
    const loading = ref(false);
    const error = ref('');
    const currentYear = new Date().getFullYear();

    // Computed properties for user data
    const userName = computed(() => authStore.user?.name || 'User');
    const userRole = computed(() => {
      if (authStore.isAdmin) return 'Administrator';
      if (authStore.isEditor) return 'Editor';
      return 'User';
    });

    // Logout function
    const logout = async () => {
      loading.value = true;
      try {
        await authStore.logout();
        router.push('/');
      } catch (err) {
        error.value = err.message || 'Logout failed';
        console.error('Logout error:', err);
      } finally {
        loading.value = false;
      }
    };

    // Fetch current user data
    onMounted(async () => {
      if (authStore.isAuthenticated) {
        try {
          await authStore.fetchCurrentUser();
        } catch (err) {
          console.error('Error fetching user data:', err);
          error.value = 'Could not load user data';
          
          // Redirect to login if authentication fails
          if (err.status === 401) {
            router.push('/login');
          }
        }
      } else {
        // Redirect to login if not authenticated
        router.push('/login');
      }
    });

    return {
      userName,
      userRole,
      logout,
      error,
      loading,
      currentYear
    };
  }
};
</script>

<style scoped>
.home-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: #f8f9fa;
}

.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  background-color: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.logo {
  font-size: 1.5rem;
  font-weight: bold;
  color: #333;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.user-info span {
  color: #555;
  font-weight: 500;
}

.btn-logout {
  padding: 0.5rem 1rem;
  background-color: #f44336;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: background-color 0.3s;
}

.btn-logout:hover {
  background-color: #d32f2f;
}

.content {
  flex: 1;
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
  width: 100%;
}

.welcome-section {
  margin-bottom: 2rem;
}

.welcome-section h1 {
  font-size: 2rem;
  color: #333;
  margin-bottom: 0.5rem;
}

.welcome-section p {
  color: #666;
  font-size: 1.1rem;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.dashboard-card {
  background-color: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.dashboard-card h2 {
  color: #333;
  font-size: 1.3rem;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #eee;
}

.dashboard-card p {
  color: #666;
  margin-bottom: 1rem;
  line-height: 1.4;
}

.card-link {
  display: inline-block;
  margin-top: 0.5rem;
  color: #4a6cf7;
  text-decoration: none;
  font-weight: 500;
}

.card-link:hover {
  text-decoration: underline;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.action-btn {
  padding: 0.7rem 1rem;
  background-color: #f0f2f5;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  color: #333;
  text-align: left;
  transition: background-color 0.3s;
}

.action-btn:hover {
  background-color: #e1e4e8;
}

.footer {
  background-color: white;
  padding: 1rem 2rem;
  text-align: center;
  border-top: 1px solid #eee;
  color: #777;
  font-size: 0.9rem;
}

@media (max-width: 768px) {
  .navbar {
    padding: 1rem;
    flex-direction: column;
    gap: 0.8rem;
  }
  
  .content {
    padding: 1rem;
  }
  
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}
</style>
