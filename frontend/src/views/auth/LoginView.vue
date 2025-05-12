<template>
  <div class="login-container">
    <h1 class="title">University Consortium CMS</h1>
    <div class="form-card">
      <h2>Login</h2>
      <div v-if="error" class="error-alert">
        {{ error }}
      </div>
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            class="form-control"
            required
            placeholder="Enter your email"
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            class="form-control"
            required
            placeholder="Enter your password"
          />
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-primary" :disabled="loading">
            {{ loading ? 'Logging in...' : 'Login' }}
          </button>
        </div>

        <div class="form-footer">
          <p>Don't have an account? <router-link to="/register">Register</router-link></p>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/store/authStore';

export default {
  name: 'LoginView',
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();
    const error = ref('');
    const loading = ref(false);
    const form = ref({
      email: '',
      password: ''
    });

    const handleLogin = async () => {
      loading.value = true;
      error.value = '';

      try {
        const response = await authStore.login(form.value);
        
        // Redirect based on user role
        if (authStore.isAdmin) {
          router.push('/admin/dashboard');
        } else if (authStore.isEditor) {
          router.push('/editor/dashboard');
        } else {
          router.push('/');
        }
        
        return response;
      } catch (err) {
        error.value = err.message || 'Login failed. Please check your credentials.';
      } finally {
        loading.value = false;
      }
    };

    return {
      form,
      error,
      loading,
      handleLogin
    };
  }
};
</script>

<style scoped>
.login-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 20px;
  background-color: #f8f9fa;
}

.title {
  font-size: 2rem;
  margin-bottom: 2rem;
  color: #333;
}

.form-card {
  width: 100%;
  max-width: 450px;
  padding: 30px;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

h2 {
  margin-bottom: 20px;
  text-align: center;
  color: #333;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 16px;
}

.btn-primary {
  width: 100%;
  padding: 12px;
  background-color: #4a6cf7;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  cursor: pointer;
  transition: background-color 0.3s;
}

.btn-primary:hover {
  background-color: #3a5ad9;
}

.btn-primary:disabled {
  background-color: #a2b2fb;
  cursor: not-allowed;
}

.form-actions {
  margin-top: 30px;
}

.form-footer {
  margin-top: 20px;
  text-align: center;
}

.form-footer a {
  color: #4a6cf7;
  text-decoration: none;
}

.form-footer a:hover {
  text-decoration: underline;
}

.error-alert {
  background-color: #f8d7da;
  color: #721c24;
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 20px;
}
</style>
