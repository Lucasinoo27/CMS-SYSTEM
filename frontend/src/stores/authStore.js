import { defineStore } from "pinia";
import authService from "../services/authService";

export const useAuthStore = defineStore("auth", {
  state: () => {
    let user = null;
    try {
      const userData = localStorage.getItem("user");
      if (userData) {
        user = JSON.parse(userData);
      }
    } catch (error) {
      console.error("Failed to parse user data from localStorage:", error);
      localStorage.removeItem("user");
    }

    return {
      user,
      token: localStorage.getItem("token") || null,
      isAuthenticated: !!localStorage.getItem("token"),
      loading: false,
      error: null,
    };
  },

  getters: {
    isAdmin: (state) => state.user && state.user.role === "admin",
    isEditor: (state) => state.user && state.user.role === "editor",
  },

  actions: {
    async register(userData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await authService.register(userData);
        return response;
      } catch (error) {
        this.error = error.message || "Registration failed";
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
        const { token, user } = response;

        this.token = token;
        this.user = user;
        this.isAuthenticated = true;

        return response;
      } catch (error) {
        this.error = error.message || "Login failed";
        console.error("Login failed:", error);
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
        console.error("Logout error:", error);
        this.clearAuth();
        this.error = error.message || "Logout failed";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchUser() {
      try {
        const response = await authService.getCurrentUser();
        this.user = response.user;
        this.isAuthenticated = true;
        localStorage.setItem("user", JSON.stringify(response.user));
        return response.user;
      } catch (error) {
        console.error("Failed to fetch user:", error);
        this.clearAuth();
        throw error;
      }
    },

    async fetchCurrentUser() {
      if (!this.token) return null;

      this.loading = true;
      this.error = null;
      try {
        const response = await authService.getCurrentUser();
        this.user = response.user;
        localStorage.setItem("user", JSON.stringify(response.user));
        return response.user;
      } catch (error) {
        this.error = error.message || "Failed to get user data";
        if (error.status === 401) {
          this.clearAuth();
        }
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async initializeAuth() {
      const token = localStorage.getItem("token");

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
      localStorage.removeItem("token");
      localStorage.removeItem("user");
    },
  },
});
