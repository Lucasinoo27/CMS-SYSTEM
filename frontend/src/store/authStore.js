import { defineStore } from 'pinia';
import authService from '../services/authService';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('auth_token') || null,
    loading: false,
    error: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user && state.user.roles && state.user.roles.includes('admin'),
    isEditor: (state) => state.user && state.user.roles && state.user.roles.includes('editor')
  },

  actions: {
    async register(userData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await authService.register(userData);
        this.user = response.user;
        this.token = response.token;
        return response;
      } catch (error) {
        this.error = error.message || 'Registration failed';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async login(credentials) {
      this.loading = true;
      this.error = null;
      try {
        const response = await authService.login(credentials);
        this.user = response.user;
        this.token = response.token;
        return response;
      } catch (error) {
        this.error = error.message || 'Login failed';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      this.loading = true;
      this.error = null;
      try {
        await authService.logout();
        this.clearAuth();
      } catch (error) {
        this.error = error.message || 'Logout failed';
        // Clear auth anyway even if API call fails
        this.clearAuth();
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchCurrentUser() {
      if (!this.token) return null;
      
      this.loading = true;
      this.error = null;
      try {
        const response = await authService.getCurrentUser();
        this.user = response.user;
        return response.user;
      } catch (error) {
        this.error = error.message || 'Failed to get user data';
        // If unauthorized, clear auth
        if (error.status === 401) {
          this.clearAuth();
        }
        throw error;
      } finally {
        this.loading = false;
      }
    },

    clearAuth() {
      this.user = null;
      this.token = null;
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
    }
  },
});
