import { createRouter, createWebHistory } from 'vue-router'

import { useAuthSession } from '../composables/useAuthSession'
import { workspaceRoutes } from '../config/workspace'
import AuthCallbackPage from '../pages/AuthCallbackPage.vue'
import DashboardPage from '../pages/DashboardPage.vue'
import FrontDeskArrivalsPage from '../pages/FrontDeskArrivalsPage.vue'
import GuestPortalPage from '../pages/GuestPortalPage.vue'
import LoginPage from '../pages/LoginPage.vue'
import ModuleWorkspacePage from '../pages/ModuleWorkspacePage.vue'
import PortalCmsPage from '../pages/PortalCmsPage.vue'
import ReservationInquiriesPage from '../pages/ReservationInquiriesPage.vue'
import SettingsPage from '../pages/SettingsPage.vue'

const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: DashboardPage,
    meta: {
      title: 'Dashboard',
      requiresAuth: true,
    },
  },
  {
    path: '/login',
    name: 'login',
    component: LoginPage,
    meta: {
      title: 'Login',
      public: true,
    },
  },
  {
    path: '/auth/callback',
    name: 'auth-callback',
    component: AuthCallbackPage,
    meta: {
      title: 'Google Callback',
      public: true,
    },
  },
  {
    path: '/portal',
    redirect: '/portal/main',
  },
  {
    path: '/portal/:propertyCode',
    name: 'guest-portal',
    component: GuestPortalPage,
    meta: {
      title: 'Guest Portal',
      public: true,
    },
  },
  {
    path: '/settings',
    redirect: '/settings/general',
  },
  {
    path: '/settings/general',
    name: 'settings-general-page',
    component: SettingsPage,
    meta: {
      title: 'Settings',
      requiresAuth: true,
    },
  },
  {
    path: '/settings/portal-cms',
    name: 'settings-portal-cms-page',
    component: PortalCmsPage,
    meta: {
      title: 'Portal CMS',
      requiresAuth: true,
    },
  },
  {
    path: '/front-desk/arrivals',
    name: 'front-desk-arrivals-page',
    component: FrontDeskArrivalsPage,
    meta: {
      title: 'Arrivals',
      requiresAuth: true,
    },
  },
  {
    path: '/reservations/inquiries',
    name: 'reservation-inquiries-page',
    component: ReservationInquiriesPage,
    meta: {
      title: 'Booking Inquiries',
      requiresAuth: true,
    },
  },
  ...workspaceRoutes
    .filter((route) => route.path !== '/front-desk/arrivals')
    .map((route) => ({
    ...route,
    component: ModuleWorkspacePage,
    meta: {
      ...route.meta,
      requiresAuth: true,
    },
    })),
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})

const resolveRouteTitle = (to: { name?: unknown; meta: Record<string, unknown> }) => {
  const rawPreferences = globalThis.localStorage?.getItem('booking.wpa.ui-preferences')
  const language = rawPreferences ? (JSON.parse(rawPreferences).language ?? 'id') : 'id'
  const isEnglish = language === 'en'

  const titleByName: Record<string, string> = isEnglish
    ? {
        dashboard: 'Dashboard',
        login: 'Login',
        'auth-callback': 'Google Callback',
        'guest-portal': 'Guest Portal',
        'settings-general-page': 'Settings',
        'settings-portal-cms-page': 'Portal CMS',
        'front-desk-arrivals-page': 'Arrivals',
        'reservation-inquiries-page': 'Booking Inquiries',
      }
    : {
        dashboard: 'Dashboard',
        login: 'Login',
        'auth-callback': 'Google Callback',
        'guest-portal': 'Portal Tamu',
        'settings-general-page': 'Settings',
        'settings-portal-cms-page': 'Portal CMS',
        'front-desk-arrivals-page': 'Arrivals',
        'reservation-inquiries-page': 'Booking Inquiries',
      }

  const routeName = typeof to.name === 'string' ? to.name : ''
  return titleByName[routeName] ?? String(to.meta.title ?? 'Booking WPA')
}

router.beforeEach((to) => {
  const { initialize, isAuthenticated } = useAuthSession()

  initialize()

  if (to.meta.public && isAuthenticated.value && to.path === '/login') {
    return '/'
  }

  if (to.meta.requiresAuth && !isAuthenticated.value) {
    return {
      path: '/login',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  return true
})

router.afterEach((to) => {
  document.title = `${resolveRouteTitle(to)} | Booking WPA`
})
