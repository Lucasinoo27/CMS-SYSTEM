<template>
  <div class="admin-layout">
    <nav class="admin-nav">
      <div class="logo">
        <h1>CMS Admin</h1>
      </div>
      <div class="user-info">
        <span>{{ user?.name || 'Guest' }}</span>
        <button @click="handleLogout">Logout</button>
      </div>
      <ul class="nav-links">
        <li><router-link to="/admin/dashboard">Dashboard</router-link></li>
        <li><router-link to="/admin/conferences">Conferences</router-link></li>
        <li><router-link to="/admin/pages">Pages</router-link></li>
        <li><router-link to="/admin/users">Users</router-link></li>
        <li><router-link to="/admin/files">File Manager</router-link></li>
      </ul>
    </nav>
    <main class="admin-content">
      <router-view></router-view>
    </main>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/authStore'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'

const router = useRouter()
const authStore = useAuthStore()
const { user } = storeToRefs(authStore)

const handleLogout = async () => {
  try {
    await authStore.logout()
    // Wait for the next tick to ensure state is cleared
    await router.push('/login')
  } catch (error) {
    console.error('Logout error:', error)
    // Force redirect to login even if there's an error
    router.push('/login')
  }
}
</script>

<style lang="scss" scoped>
.admin-layout {
  display: grid;
  grid-template-columns: 250px 1fr;
  min-height: 100vh;

  .admin-nav {
    background: #2c3e50;
    color: white;
    padding: 1rem;

    .logo {
      padding: 1rem 0;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      h1 {
        margin: 0;
        font-size: 1.5rem;
      }
    }

    .user-info {
      padding: 1rem 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .nav-links {
      list-style: none;
      padding: 0;
      margin: 1rem 0;

      li {
        margin: 0.5rem 0;
        
        a {
          color: white;
          text-decoration: none;
          padding: 0.5rem;
          display: block;
          border-radius: 4px;

          &:hover, &.router-link-active {
            background: rgba(255,255,255,0.1);
          }
        }
      }
    }
  }

  .admin-content {
    padding: 2rem;
    background: #f5f6fa;
  }
}
</style> 