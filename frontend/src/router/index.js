import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/store/authStore';

// Auth views
import LoginView from '../views/auth/LoginView.vue';
import RegisterView from '../views/auth/RegisterView.vue';
import LandingPage from '../views/LandingPage.vue';
import HomePage from '../views/HomePage.vue';

// Lazy-loaded views
const ConferencesView = { template: '<div>Conferences Page</div>' };
const NotFoundView = { template: '<div>404 - Page Not Found</div>' };

// Admin Views (lazy-loaded)
const AdminDashboardView = () => import('../views/admin/DashboardView.vue');

// Editor Views (lazy-loaded)
const EditorDashboardView = () => import('../views/editor/DashboardView.vue');

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // Public routes
    {
      path: '/',
      name: 'landing',
      component: LandingPage
    },
    {
      path: '/home',
      name: 'home',
      component: HomePage,
      meta: { requiresAuth: true }
    },
    {
      path: '/conferences',
      name: 'conferences',
      component: ConferencesView
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guest: true } // Only accessible if not logged in
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: { guest: true } // Only accessible if not logged in
    },
    
    // Admin routes
    {
      path: '/admin',
      name: 'admin',
      redirect: { name: 'admin-dashboard' },
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        {
          path: 'dashboard',
          name: 'admin-dashboard',
          component: AdminDashboardView,
          meta: { requiresAuth: true, requiresAdmin: true }
        }
      ]
    },
    
    // Editor routes
    {
      path: '/editor',
      name: 'editor',
      redirect: { name: 'editor-dashboard' },
      meta: { requiresAuth: true, requiresEditor: true },
      children: [
        {
          path: 'dashboard',
          name: 'editor-dashboard',
          component: EditorDashboardView,
          meta: { requiresAuth: true, requiresEditor: true }
        }
      ]
    },
    
    // Catch-all route
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: NotFoundView
    }
  ]
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated;
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  const requiresAdmin = to.matched.some(record => record.meta.requiresAdmin);
  const requiresEditor = to.matched.some(record => record.meta.requiresEditor);
  const isGuestRoute = to.matched.some(record => record.meta.guest);

  // If the user is already authenticated, redirect from guest routes
  if (isAuthenticated && isGuestRoute) {
    if (authStore.isAdmin) {
      return next({ name: 'admin-dashboard' });
    } else if (authStore.isEditor) {
      return next({ name: 'editor-dashboard' });
    }
    return next({ name: 'home' });
  }

  // If route requires authentication but user is not logged in
  if (requiresAuth && !isAuthenticated) {
    return next({ name: 'login' });
  }

  // If route requires admin role but user is not an admin
  if (requiresAdmin && !authStore.isAdmin) {
    return next({ name: 'home' });
  }

  // If route requires editor role but user is not an editor or admin
  if (requiresEditor && !(authStore.isEditor || authStore.isAdmin)) {
    return next({ name: 'home' });
  }

  next();
});

export default router;
