import { defineStore } from 'pinia';
import api from '../services/api';
import authService from '../services/authService';

export const useAuthStore = defineStore('auth', {
  state: () => {
    // Add safe parsing for user data
    let user = null;
    try {
      const userData = localStorage.getItem('user');
      if (userData) {
        user = JSON.parse(userData);
      }
    } catch (error) {
      console.error('Failed to parse user data from localStorage:', error);
      localStorage.removeItem('user'); // Remove invalid data
    }

    return {
      user,
      token: localStorage.getItem('token') || null,
      isAuthenticated: !!localStorage.getItem('token')
    };
  },

  getters: {
    isAdmin: (state) => state.user && state.user.role === 'admin',
    isEditor: (state) => state.user && state.user.role === 'editor'
  },

  actions: {
    async login(credentials) {
      try {
        const response = await authService.login(credentials);
        const { token, user } = response;
        
        this.token = token;
        this.user = user;
        this.isAuthenticated = true;
        
        return response;
      } catch (error) {
        console.error('Login failed:', error);
        throw error;
      }
    },

    async logout() {
      try {
        await authService.logout();
        this.token = null;
        this.user = null;
        this.isAuthenticated = false;
      } catch (error) {
        console.error('Logout error:', error);
        this.clearAuth();
      }
    },

    async fetchUser() {
      try {
        const response = await authService.getCurrentUser();
        this.user = response.user;
        this.isAuthenticated = true;
        localStorage.setItem('user', JSON.stringify(response.user));
        return response.user;
      } catch (error) {
        console.error('Failed to fetch user:', error);
        this.clearAuth();
        throw error;
      }
    },

    async initializeAuth() {
      const token = localStorage.getItem('token');
      
      if (token) {
        this.token = token;
        this.isAuthenticated = true;
        
        try {
          await this.fetchUser();
          return true;
        } catch (error) {
          this.clearAuth();
          throw error;
        }
      }
      
      return false;
    },

    clearAuth() {
      this.token = null;
      this.user = null;
      this.isAuthenticated = false;
      localStorage.removeItem('token');
      localStorage.removeItem('user');
    }
  }
});
