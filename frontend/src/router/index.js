import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import AdminLayout from '@/layouts/AdminLayout.vue';
import EditorLayout from '@/layouts/EditorLayout.vue';

// Auth views
import LoginView from '../views/auth/LoginView.vue';
import RegisterView from '../views/auth/RegisterView.vue';
import LandingPage from '../views/LandingPage.vue';
import ConferencePage from '../views/ConferencePage.vue';

// Lazy-loaded views
const ConferencesView = { template: "<div>Conferences Page</div>" };
const NotFoundView = { template: "<div>404 - Page Not Found</div>" };

// Admin Views (lazy-loaded)
const AdminDashboardView = () => import('../views/admin/DashboardView.vue');

// Editor Views (lazy-loaded)
const EditorDashboardView = () => import("../views/editor/DashboardView.vue");

const routes = [
  // Public routes
  {
    path: "/",
    name: "landing",
    component: LandingPage,
  },
  {
    path: "/conferences",
    name: "conferences",
    component: ConferencesView,
  },
  {
    path: "/conferences/:id",
    name: "conference-page",
    component: ConferencePage,
  },
  {
    path: "/login",
    name: "login",
    component: LoginView,
    meta: { guest: true }, // Only accessible if not logged in
  },
  {
    path: "/register",
    name: "register",
    component: RegisterView,
    meta: { guest: true }, // Only accessible if not logged in
  },
  
  // Admin routes
  {
    path: "/admin",
    component: AdminLayout,
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: "dashboard",
        name: "AdminDashboard",
        component: AdminDashboardView,
      },
      {
        path: "conferences",
        name: "Conferences",
        component: () => import("../views/admin/ConferencesView.vue"),
      },
      {
        path: "pages",
        name: "Pages",
        component: () => import("../views/admin/PagesView.vue"),
      },
      {
        path: "users",
        name: "Users",
        component: () => import("../views/admin/UsersView.vue"),
      },
      {
        path: "files",
        name: "AdminFiles",
        component: () => import("../views/admin/FilesView.vue"),
      },
    ],
  },
  
  // Editor routes
  {
    path: "/editor",
    component: EditorLayout,
    meta: { requiresAuth: true, requiresEditor: true },
    children: [
      {
        path: "dashboard",
        name: "EditorDashboard",
        component: EditorDashboardView,
      },
      {
        path: "content",
        name: "Content",
        component: () => import("../views/editor/ContentView.vue"),
      },
      {
        path: "files",
        name: "EditorFiles",
        component: () => import("../views/editor/FilesView.vue"),
      },
    ],
  },
  
  // Catch-all route
  {
    path: "/:pathMatch(.*)*",
    redirect: "/login",
  },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  
  // Check if auth is initialized
  if (!authStore.isAuthenticated && localStorage.getItem("token")) {
    try {
      await authStore.initializeAuth();
    } catch (error) {
      console.error("Auth initialization failed:", error);
    }
  }

  // Handle authentication requirements
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next("/login");
    return;
  }

  // Handle role-based access
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next("/login");
    return;
  }

  if (to.meta.requiresEditor && !authStore.isEditor) {
    next("/login");
    return;
  }

  // Redirect authenticated users from login page
  if (to.path === "/login" && authStore.isAuthenticated) {
    next(authStore.isAdmin ? "/admin/dashboard" : "/editor/dashboard");
    return;
  }

  next();
});

export default router;
