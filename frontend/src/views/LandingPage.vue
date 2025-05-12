<template>
  <div class="landing-container">
    <div class="logo-section">
      <h1 class="logo">University CMS</h1>
      <div class="connection-status" :class="{ 'status-error': backendStatus !== 'Connected' }">
        API Status: {{ backendStatus }}
      </div>
    </div>
    
    <div class="welcome-section">
      <h2>Welcome to the University Consortium Management System</h2>
      <p>A comprehensive platform for managing academic conferences, papers, and events</p>
      <p>Collaborate with editors and administrators across multiple universities</p>
    </div>
    
    <div class="auth-buttons">
      <router-link to="/login" class="btn btn-primary">Login</router-link>
      <router-link to="/register" class="btn btn-secondary">Register</router-link>
    </div>

    <div class="features-section">
      <div class="feature">
        <h3>Conference Management</h3>
        <p>Organize and track academic conferences</p>
      </div>
      <div class="feature">
        <h3>Role-Based Access</h3>
        <p>Separate interfaces for editors and administrators</p>
      </div>
      <div class="feature">
        <h3>Collaboration</h3>
        <p>Work together on events and papers</p>
      </div>
    </div>
  </div>
</template>

<script>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/store/authStore';
import axios from 'axios';

export default {
  name: 'LandingPage',
  setup() {
    const router = useRouter();
    const authStore = useAuthStore();
    const backendStatus = ref('Checking connection...');

    onMounted(async () => {
      try {
        // Check API health endpoint
        await axios.get('/health');
        backendStatus.value = 'Connected';
      } catch (error) {
        backendStatus.value = 'Connection issue';
        console.error('Backend connection issue:', error);
      }
      
      // Check if user is already authenticated
      if (authStore.isAuthenticated) {
        if (authStore.isAdmin) {
          router.push('/admin/dashboard');
        } else if (authStore.isEditor) {
          router.push('/editor/dashboard');
        } else {
          router.push('/home');
        }
      }
    });

    return {
      backendStatus
    };
  }
};
</script>

<style scoped>
.landing-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-height: 100vh;
  padding: 20px;
  background-color: #f8f9fa;
}

.logo-section {
  margin: 2rem 0;
  text-align: center;
}

.logo {
  font-size: 2.5rem;
  color: #333;
  text-align: center;
}

.connection-status {
  margin-top: 0.5rem;
  font-size: 0.8rem;
  color: #4caf50;
  background-color: rgba(76, 175, 80, 0.1);
  padding: 0.3rem 0.6rem;
  border-radius: 4px;
  display: inline-block;
}

.status-error {
  color: #f44336;
  background-color: rgba(244, 67, 54, 0.1);
}

.welcome-section {
  max-width: 800px;
  text-align: center;
  margin-bottom: 2rem;
}

.welcome-section h2 {
  font-size: 2rem;
  color: #333;
  margin-bottom: 1rem;
}

.welcome-section p {
  font-size: 1.1rem;
  color: #555;
  margin-bottom: 0.5rem;
  line-height: 1.5;
}

.auth-buttons {
  display: flex;
  gap: 1rem;
  margin-bottom: 3rem;
}

.btn {
  padding: 0.8rem 2rem;
  font-size: 1rem;
  border-radius: 4px;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-primary {
  background-color: #4a6cf7;
  color: white;
  border: 1px solid #4a6cf7;
}

.btn-primary:hover {
  background-color: #3a5ad9;
}

.btn-secondary {
  background-color: transparent;
  color: #4a6cf7;
  border: 1px solid #4a6cf7;
}

.btn-secondary:hover {
  background-color: rgba(74, 108, 247, 0.1);
}

.features-section {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 2rem;
  max-width: 1000px;
  margin: 0 auto;
}

.feature {
  flex: 1;
  min-width: 250px;
  background-color: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  text-align: center;
}

.feature h3 {
  color: #333;
  margin-bottom: 0.8rem;
}

.feature p {
  color: #666;
  line-height: 1.4;
}

@media (max-width: 768px) {
  .features-section {
    flex-direction: column;
  }
  
  .feature {
    min-width: 100%;
  }
  
  .auth-buttons {
    flex-direction: column;
    width: 100%;
    max-width: 300px;
  }
  
  .btn {
    width: 100%;
    text-align: center;
  }
}
</style>
