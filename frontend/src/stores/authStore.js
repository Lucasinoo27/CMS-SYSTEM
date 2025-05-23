import { defineStore } from "pinia";
import authService from "../services/authService";

export const useAuthStore = defineStore("auth", {
  state: () => {
    let user = null;
    let token = null;
    let isAuthenticated = false;
    
    try {
      // Check if token exists and is valid
      token = localStorage.getItem("token");
      isAuthenticated = !!token;
      
      // Only try to parse user if token exists
      if (token) {
        const userData = localStorage.getItem("user");
        if (userData && userData.trim()) {
          try {
            user = JSON.parse(userData);
          } catch (parseError) {
            console.error("Failed to parse user data from localStorage:", parseError);
            // Clear invalid user data but keep token for now
            localStorage.removeItem("user");
          }
        }
      }
    } catch (error) {
      console.error("Error accessing localStorage:", error);
      // Clear auth data in case of any error
      localStorage.removeItem("user");
      localStorage.removeItem("token");
      token = null;
      isAuthenticated = false;
    }

    return {
      user,
      token,
      isAuthenticated,
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
        // First try to call the logout endpoint
        await authService.logout();
      } catch (error) {
        console.error("Logout error:", error);
        // Even if the API call fails, we still want to clear local auth
      } finally {
        // Clear auth data after API call attempt
        this.clearAuth();
        this.loading = false;
      }
    },

    async fetchUser() {
      try {
        const response = await authService.getCurrentUser();
        // Only update state if we got a valid user
        if (response.user) {
          this.user = response.user;
          this.isAuthenticated = true;
          
          // Safely store user data
          try {
            if (response.user && typeof response.user === 'object') {
              localStorage.setItem("user", JSON.stringify(response.user));
            }
          } catch (storageError) {
            console.error("Failed to store user data in localStorage:", storageError);
          }
        }
        return response.user;
      } catch (error) {
        console.error("Failed to fetch user:", error);
        this.clearAuth();
        throw error;
      }
    },

    async fetchCurrentUser() {
      if (!this.token) {
        this.user = null;
        this.isAuthenticated = false;
        return null;
      }

      this.loading = true;
      this.error = null;
      try {
        const response = await authService.getCurrentUser();
        if (response.user) {
          this.user = response.user;
          localStorage.setItem("user", JSON.stringify(response.user));
        }
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

      if (!token) {
        return false;
      }

      this.token = token;
      this.isAuthenticated = true;
      this.loading = true;

      try {
        await this.fetchUser();
        this.loading = false;
        return true;
      } catch (error) {
        console.error("Auth initialization failed:", error);
        this.clearAuth();
        this.loading = false;
        return false;
      }
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
