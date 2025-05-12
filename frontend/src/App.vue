<template>
  <router-view />
</template>

<script>
import { onMounted } from 'vue';
import { useAuthStore } from './stores/auth';

export default {
  name: 'App',
  setup() {
    const authStore = useAuthStore();

    onMounted(async () => {
      // Check if user is already authenticated
      if (localStorage.getItem('token')) {
        try {
          await authStore.fetchUser();
        } catch (error) {
          console.error('Failed to fetch user:', error);
        }
      }
    });
  }
}
</script>

<style>
/* Base styles */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  color: #333;
  line-height: 1.6;
  background-color: #f8f9fa;
}

a {
  text-decoration: none;
  color: #4a6cf7;
}

button {
  cursor: pointer;
}

h1, h2, h3, h4, h5, h6 {
  line-height: 1.3;
}
</style>
