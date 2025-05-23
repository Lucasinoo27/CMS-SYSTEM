import axios from 'axios';

// Base URL is empty because axios.defaults.baseURL already includes the /api path
const API_URL = '';

const authService = {
  /**
   * Register a new user
   * @param {Object} userData - User registration data
   * @returns {Promise} - API response
   */
  register: async (userData) => {
    try {
      const response = await axios.post(`${API_URL}/register`, userData);
      return response.data;
    } catch (error) {
      throw error.response?.data || { message: 'An error occurred during registration' };
    }
  },

  /**
   * Login a user
   * @param {Object} credentials - User login credentials
   * @returns {Promise} - API response
   */
  login: async (credentials) => {
    try {
      const response = await axios.post(`${API_URL}/login`, credentials);
      if (response.data.token) {
        localStorage.setItem('token', response.data.token);
        
        // Make sure user data is valid before storing
        if (response.data.user && typeof response.data.user === 'object') {
          try {
            localStorage.setItem('user', JSON.stringify(response.data.user));
          } catch (e) {
            console.error('Failed to store user data:', e);
          }
        }
      }
      return response.data;
    } catch (error) {
      throw error.response?.data || { message: 'Invalid login credentials' };
    }
  },

  /**
   * Logout a user
   * @returns {Promise} - API response
   */
  logout: async () => {
    const token = localStorage.getItem('token');
    if (!token) {
      return; // No token to logout with
    }

    try {
      // Set the token in headers for this specific request
      const response = await axios.post(`${API_URL}/logout`, {}, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      return response.data;
    } catch (error) {
      // Log the error but don't throw - we want to clear local state regardless
      console.error('Logout API error:', error);
    } finally {
      // Always clear local storage, even if the API call fails
      localStorage.removeItem('token');
      localStorage.removeItem('user');
    }
  },

  /**
   * Get the current authenticated user
   * @returns {Promise} - API response
   */
  getCurrentUser: async () => {
    const token = localStorage.getItem('token');
    if (!token) {
      return { user: null }; // Return null user for anonymous users
    }
    
    try {
      const response = await axios.get(`${API_URL}/user`);
      return response.data;
    } catch (error) {
      throw error.response?.data || { message: 'Failed to get user data' };
    }
  },

  /**
   * Setup axios interceptors for authentication
   */
  setupInterceptors: () => {
    axios.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('token');
        if (token) {
          config.headers['Authorization'] = `Bearer ${token}`;
        }
        return config;
      },
      (error) => {
        return Promise.reject(error);
      }
    );

    axios.interceptors.response.use(
      (response) => {
        return response;
      },
      (error) => {
        if (error.response && error.response.status === 401 && !error.config.url.includes('/login')) {
          localStorage.removeItem('token');
          localStorage.removeItem('user');
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }
};

export default authService;
