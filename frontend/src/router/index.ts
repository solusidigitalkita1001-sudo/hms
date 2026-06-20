import { createRouter, createWebHistory } from 'vue-router'

import { useAuthSession } from '../composables/useAuthSession'
import { workspaceRoutes } from '../config/workspace'
import AuthCallbackPage from '../pages/AuthCallbackPage.vue'
import BillingInvoicesPage from '../pages/BillingInvoicesPage.vue'
import DashboardPage from '../pages/DashboardPage.vue'
import FrontDeskArrivalsPage from '../pages/FrontDeskArrivalsPage.vue'
import FrontDeskDeparturesPage from '../pages/FrontDeskDeparturesPage.vue'
import GuestBookingConfirmPage from '../pages/GuestBookingConfirmPage.vue'
import GuestBookingDetailPage from '../pages/GuestBookingDetailPage.vue'
import GuestBookingPage from '../pages/GuestBookingPage.vue'
import GuestConditionReportPage from '../pages/GuestConditionReportPage.vue'
import GuestMyBookingsPage from '../pages/GuestMyBookingsPage.vue'
import GuestPortalPage from '../pages/GuestPortalPage.vue'
import GuestRoomSearchPage from '../pages/GuestRoomSearchPage.vue'
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
    path: '/portal/:propertyCode/search',
    name: 'guest-room-search',
    component: GuestRoomSearchPage,
    meta: {
      title: 'Search Rooms',
      public: true,
    },
  },
  {
    path: '/portal/:propertyCode/book',
    name: 'guest-booking',
    component: GuestBookingPage,
    meta: {
      title: 'Booking',
      public: true,
    },
  },
  {
    path: '/portal/:propertyCode/confirm/:bookingCode',
    name: 'guest-booking-confirm',
    component: GuestBookingConfirmPage,
    meta: {
      title: 'E-Ticket',
      public: true,
    },
  },
  {
    path: '/portal/:propertyCode/my-bookings',
    name: 'guest-my-bookings',
    component: GuestMyBookingsPage,
    meta: {
      title: 'My Bookings',
      public: true,
    },
  },
  {
    path: '/portal/:propertyCode/my-bookings/:bookingId',
    name: 'guest-booking-detail',
    component: GuestBookingDetailPage,
    meta: {
      title: 'Booking Detail',
      public: true,
    },
  },
  {
    path: '/portal/:propertyCode/my-bookings/:bookingId/condition-report',
    name: 'guest-condition-report',
    component: GuestConditionReportPage,
    meta: {
      title: 'Condition Report',
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
  {
    path: '/front-desk/departures',
    name: 'front-desk-departures-page',
    component: FrontDeskDeparturesPage,
    meta: {
      title: 'Departures',
      requiresAuth: true,
    },
  },
  {
    path: '/billing/invoices/:id',
    name: 'billing-invoice-detail-page',
    component: BillingInvoicesPage,
    meta: {
      title: 'Invoice Detail',
      requiresAuth: true,
    },
  },
  ...workspaceRoutes
    .filter((route) => route.path !== '/front-desk/arrivals' && route.path !== '/front-desk/departures' && route.path !== '/billing/invoices')
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
        'front-desk-departures-page': 'Departures',
        'reservation-inquiries-page': 'Booking Inquiries',
        'billing-invoice-detail-page': 'Invoice Detail',
      }
    : {
        dashboard: 'Dashboard',
        login: 'Login',
        'auth-callback': 'Google Callback',
        'guest-portal': 'Portal Tamu',
        'guest-room-search': 'Cari Kamar',
        'guest-booking': 'Booking',
        'guest-booking-confirm': 'E-Ticket',
        'guest-my-bookings': 'Booking Saya',
        'guest-booking-detail': 'Detail Booking',
        'guest-condition-report': 'Laporan Kondisi Kamar',
        'settings-general-page': 'Settings',
        'settings-portal-cms-page': 'Portal CMS',
        'front-desk-arrivals-page': 'Arrivals',
        'front-desk-departures-page': 'Departures',
        'reservation-inquiries-page': 'Booking Inquiries',
        'billing-invoice-detail-page': 'Invoice Detail',
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
