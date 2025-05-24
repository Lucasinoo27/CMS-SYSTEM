<template>
  <div class="editor-layout">
    <nav class="editor-nav">
      <div class="logo">
        <h1>CMS Editor</h1>
      </div>
      <ul class="nav-links">
        <li><router-link to="/editor/dashboard">Dashboard</router-link></li>
        <li><router-link to="/editor/content">My Content</router-link></li>
        <li><router-link to="/editor/files">Files</router-link></li>
      </ul>
    </nav>
    <main class="editor-content">
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

const logout = async () => {
  try {
    await authStore.logout()
    // Navigate after logout is complete
    await router.push('/login')
  } catch (error) {
    console.error('Logout failed:', error)
    // If logout fails, still try to navigate to login
    router.push('/login')
  }
}
</script>

<style lang="scss" scoped>
.editor-layout {
  display: grid;
  grid-template-columns: 250px 1fr;
  min-height: 100vh;

  .editor-nav {
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

  .editor-content {
    padding: 2rem;
    background: #f5f6fa;
  }
}
</style> 