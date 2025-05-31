<template>
  <div class="register-container">
    <h1 class="title">University Consortium CMS</h1>
    <div class="home-link-wrapper">
      <router-link to="/" class="home-link">
        <i class="fas fa-home"></i>
        <span>Home</span>
      </router-link>
    </div>
    <div class="form-card">
      <h2>Register</h2>
      <div v-if="error" class="error-alert">
        {{ error }}
      </div>
      <form @submit.prevent="handleRegister">
        <div class="form-group">
          <label for="name">Full Name</label>
          <input
          id="name"
          v-model="form.name"
          type="text"
          class="form-control"
          required
          placeholder="Enter your full name"
          />
        </div>
        
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
        
        <div class="form-group">
          <label for="password_confirmation">Confirm Password</label>
          <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          class="form-control"
          required
          placeholder="Confirm your password"
          />
        </div>
        
        <div class="form-actions">
          <button type="submit" class="btn-primary" :disabled="loading">
            {{ loading ? 'Registering...' : 'Register' }}
          </button>
        </div>
        
        <div class="form-footer">
          <p>Already have an account? <router-link to="/login">Login</router-link></p>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

export default {
  name: 'RegisterView',
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();
    const error = ref('');
    const loading = ref(false);
    const form = ref({
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
    });

    const handleRegister = async () => {
      loading.value = true;
      error.value = '';

      if (form.value.password !== form.value.password_confirmation) {
        error.value = 'Passwords do not match';
        loading.value = false;
        return;
      }

      try {
        const response = await authStore.register(form.value);
        router.push('/login');
        
        return response;
      } catch (err) {
        if (err.errors) {
          // Format validation errors
          const errorMessages = Object.values(err.errors).flat();
          error.value = errorMessages.join(', ');
        } else {
          error.value = err.message || 'Registration failed. Please try again.';
        }
      } finally {
        loading.value = false;
      }
    };

    return {
      form,
      error,
      loading,
      handleRegister
    };
  }
};
</script>

<style scoped>
.register-container {
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
  margin-bottom: 1rem;
  color: #333;
}

.form-card {
  width: 100%;
  max-width: 420px;
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
  color: #ca0011ff;
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 20px;
}
.home-link-wrapper {
  display: flex;
  align-items: start;
  max-width: 450px;
  width: 100%;
  margin-bottom: 1rem;
}
.home-link {
  color: #2c3e50;
  text-decoration: none;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  background: #f8f9fa;
}

.home-link i {
  font-size: 1.1rem;
}

.home-link:hover {
  color: #3498db;
  background: #f1f3f5;
}
</style>
