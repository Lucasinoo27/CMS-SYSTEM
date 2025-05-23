<template>
  <div class="login-container">
    <nav class="navbar">
      <div class="navbar-container">
        <router-link to="/" class="home-link">
          <i class="fas fa-home"></i>
          <span>Home</span>
        </router-link>
      </div>
    </nav>
    <h1 class="title">University Consortium CMS</h1>
    <div class="login-card">
      <h2>Login</h2>
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email">Email</label>
          <input
          type="email"
          id="email"
          v-model="form.email"
          required
          placeholder="Enter your email"
          />
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <input
          type="password"
          id="password"
          v-model="form.password"
          required
          placeholder="Enter your password"
          />
        </div>
        
        <div v-if="error" class="error-alert">
          {{ error }}
        </div>
        
        <button type="submit" :disabled="loading">
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>
      </form>

      <p class="redirect-text">
        Don't have an account?<a
          @click.prevent="$router.push('/register')"
          href="#"
          >Register</a
        >
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: ''
})

const loading = ref(false)
const error = ref('')

const handleLogin = async () => {
  loading.value = true
  error.value = ''

  try {
    await authStore.login(form)
    // Log success and redirect
    router.push(authStore.isAdmin ? '/admin/dashboard' : '/editor/dashboard')
  } catch (err) {
    console.error('Login error:', err)
    error.value = err.message || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f8f9fa;
}

.login-card {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;

  h2 {
    text-align: center;
    margin-bottom: 2rem;
    color: #333;
  }
}

.login-form {
  .form-group {
    margin-bottom: 1.5rem;

    label {
      display: block;
      margin-bottom: 0.5rem;
      color: #333;
    }

    input {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;

      &:focus {
        outline: none;
        border-color: #4a6cf7;
      }
    }
  }

  button {
    width: 100%;
    padding: 0.75rem;
    background: #4a6cf7;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.3s;
    margin-top: 0.75rem;

    &:hover {
      background: #3a5ad9;
    }

    &:disabled {
      background: #a2b2fb;
      cursor: not-allowed;
    }
  }
}

.title {
  font-size: 2rem;
  margin-bottom: 2rem;
  text-align: center;
  color: #333;
}

.redirect-text {
  margin-top: 1rem;
  text-align: center;
  font-size: 0.9rem;
  color: #333;

  a {
    color: #4a6cf7;
    text-decoration: none;
    font-weight: 500;
    margin-left: 0.25rem;

    &:hover {
      text-decoration: underline;
    }
  }
}

.error-alert {
  background-color: #f8d7da;
  color: #ca0011ff;
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.navbar {
  background: #fff;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.navbar-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem 2rem;
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
