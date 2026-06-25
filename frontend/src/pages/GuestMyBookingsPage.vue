<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiCalendarRangeOutline from '@iconify-icons/mdi/calendar-range-outline'
import mdiBedOutline from '@iconify-icons/mdi/bed-outline'
import mdiLogin from '@iconify-icons/mdi/login'
import mdiArrowRight from '@iconify-icons/mdi/arrow-right'
import mdiChevronLeft from '@iconify-icons/mdi/chevron-left'
import mdiAccountOutline from '@iconify-icons/mdi/account-outline'
import mdiLockOutline from '@iconify-icons/mdi/lock-outline'
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAppLocale } from '../composables/useAppLocale'
import { buildApiUrl } from '../lib/api'

const route = useRoute()
const router = useRouter()
const { text: t, language } = useAppLocale()

const propertyCode = route.params.propertyCode as string

const authenticated = ref(false)
const loading = ref(false)
const bookings = ref<any[]>([])
const error = ref('')

const loginForm = reactive({
  booking_code: '',
  phone: '',
})

function formatDate(dateStr: string): string {
  if (!dateStr) return ''
  const d = new Date(dateStr + 'T00:00:00')
  return d.toLocaleDateString(language.value === 'en' ? 'en-US' : 'id-ID', {
    weekday: 'short', day: 'numeric', month: 'short', year: 'numeric',
  })
}

function formatCurrency(amount: number): string {
  return `Rp ${amount.toLocaleString('id-ID')}`
}

function getStatusBadgeClass(status: string): string {
  const map: Record<string, string> = {
    confirmed: 'confirmed', checked_in: 'checked-in', checked_out: 'checked-out',
    cancelled: 'cancelled', no_show: 'cancelled',
  }
  return map[status] || 'confirmed'
}

function getStatusLabel(status: string): string {
  const map: Record<string, string> = {
    confirmed: 'Confirmed', checked_in: 'Check-in', checked_out: 'Check-out',
    cancelled: 'Cancelled', no_show: 'No Show',
  }
  return map[status] || status
}

async function doLogin() {
  if (!loginForm.booking_code || !loginForm.phone) {
    error.value = 'Kode booking dan nomor HP wajib diisi.'
    return
  }

  loading.value = true
  error.value = ''

  try {
    const res = await fetch(buildApiUrl('/api/v1/guest/auth/login'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        booking_code: loginForm.booking_code,
        phone: loginForm.phone,
      }),
    })
    const json = await res.json()
    if (json.success) {
      localStorage.setItem('guest-token', json.data.access_token)
      localStorage.setItem('guest-booking-code', loginForm.booking_code)
      authenticated.value = true
      loadBookings()
    } else {
      error.value = json.message || 'Login gagal. Periksa kode booking dan nomor HP.'
    }
  } catch {
    error.value = 'Gagal terhubung ke server.'
  } finally {
    loading.value = false
  }
}

async function loadBookings() {
  const token = localStorage.getItem('guest-token')
  if (!token) {
    authenticated.value = false
    return
  }

  loading.value = true
  try {
    const res = await fetch(buildApiUrl('/api/v1/guest/bookings'), {
      headers: { Authorization: `Bearer ${token}` },
    })
    const json = await res.json()
    if (json.success) {
      bookings.value = json.data
      authenticated.value = true
    } else {
      authenticated.value = false
      localStorage.removeItem('guest-token')
    }
  } catch {
    error.value = 'Gagal memuat data.'
  } finally {
    loading.value = false
  }
}

function viewDetail(booking: any) {
  router.push({
    name: 'guest-booking-detail',
    params: { propertyCode, bookingId: booking.id },
  })
}

function logout() {
  localStorage.removeItem('guest-token')
  localStorage.removeItem('guest-booking-code')
  authenticated.value = false
  bookings.value = []
}

function goBack() {
  router.push({ name: 'guest-portal', params: { propertyCode } })
}

onMounted(() => {
  const savedCode = localStorage.getItem('guest-booking-code')
  if (savedCode) loginForm.booking_code = savedCode
  loadBookings()
})
</script>

<template>
  <div class="my-bookings-page">
    <header class="page-header">
      <button class="back-btn" @click="goBack">
        <Icon :icon="mdiChevronLeft" width="24" />
      </button>
      <h1>{{ t('Booking Saya', 'My Bookings') }}</h1>
      <button v-if="authenticated" class="logout-btn" @click="logout">
        {{ t('Logout', 'Logout') }}
      </button>
    </header>

    <!-- Login Form -->
    <div v-if="!authenticated" class="login-card">
      <Icon :icon="mdiLockOutline" width="40" class="lock-icon" />
      <h3>{{ t('Lihat Booking Anda', 'View Your Bookings') }}</h3>
      <p class="login-hint">{{ t('Masukkan kode booking dan nomor HP', 'Enter booking code and phone') }}</p>

      <div class="form-group">
        <label>{{ t('Kode Booking', 'Booking Code') }}</label>
        <input v-model="loginForm.booking_code" type="text" class="input" placeholder="BK-20250617-XXXXXX" />
      </div>
      <div class="form-group">
        <label>{{ t('Nomor HP', 'Phone Number') }}</label>
        <input v-model="loginForm.phone" type="tel" class="input" placeholder="0812xxxx" inputmode="numeric" />
      </div>
      <p v-if="error" class="error-msg">{{ error }}</p>
      <button class="login-btn" :disabled="loading" @click="doLogin">
        <Icon :icon="mdiLogin" width="20" />
        {{ loading ? t('Memuat...', 'Loading...') : t('Lihat Booking', 'View Bookings') }}
      </button>
    </div>

    <!-- Booking List -->
    <div v-else class="booking-list">
      <div v-if="loading && bookings.length === 0" class="loading-state">
        {{ t('Memuat...', 'Loading...') }}
      </div>

      <div v-else-if="bookings.length === 0" class="empty-state">
        <Icon :icon="mdiBedOutline" width="48" />
        <h3>{{ t('Belum ada booking', 'No bookings yet') }}</h3>
      </div>

      <div
        v-for="booking in bookings"
        :key="booking.id"
        class="booking-card"
        @click="viewDetail(booking)"
      >
        <div class="booking-header-row">
          <span class="booking-code">{{ booking.booking_code }}</span>
          <span :class="['badge', getStatusBadgeClass(booking.status)]">
            {{ getStatusLabel(booking.status) }}
          </span>
        </div>
        <div class="booking-info">
          <div class="info-row">
            <Icon :icon="mdiBedOutline" width="16" />
            <span>{{ booking.room_type || '-' }}</span>
          </div>
          <div class="info-row">
            <Icon :icon="mdiCalendarRangeOutline" width="16" />
            <span>{{ formatDate(booking.check_in_date) }} — {{ formatDate(booking.check_out_date) }}</span>
          </div>
          <div class="info-row">
            <Icon :icon="mdiAccountOutline" width="16" />
            <span>{{ booking.adult_count }} dewasa, {{ booking.child_count }} anak</span>
          </div>
        </div>
        <div class="booking-footer">
          <span class="property-name">{{ booking.property_name }}</span>
          <div class="price-info">
            <span v-if="booking.grand_total" class="price">{{ formatCurrency(booking.grand_total) }}</span>
            <Icon :icon="mdiArrowRight" width="18" class="arrow" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.my-bookings-page {
  max-width: 700px; margin: 0 auto; padding: 16px;
  min-height: 100vh; background: var(--color-bg, #f8f9fa);
}
.page-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.page-header h1 { flex: 1; font-size: 1.3rem; margin: 0; }
.back-btn { background: none; border: none; cursor: pointer; padding: 4px; color: var(--color-primary, #2563eb); }
.logout-btn { padding: 6px 14px; border: 1px solid #e53e3e; border-radius: 8px; background: none; color: #e53e3e; font-size: 0.8rem; cursor: pointer; }
.login-card { background: #fff; border-radius: 12px; padding: 30px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); text-align: center; }
.lock-icon { color: #888; margin-bottom: 8px; }
.login-card h3 { margin: 0 0 4px; }
.login-hint { font-size: 0.85rem; color: #888; margin: 0 0 20px; }
.form-group { text-align: left; margin-bottom: 12px; }
.form-group label { display: block; font-size: 0.85rem; color: #666; margin-bottom: 4px; }
.input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; }
.error-msg { color: #e53e3e; font-size: 0.85rem; margin: 8px 0; }
.login-btn { width: 100%; padding: 14px; background: var(--color-primary, #2563eb); color: #fff; border: none; border-radius: 10px; font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; margin-top: 4px; }
.login-btn:disabled { opacity: 0.7; }
.loading-state, .empty-state { text-align: center; padding: 60px 20px; color: #888; }
.booking-list { display: flex; flex-direction: column; gap: 12px; }
.booking-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); cursor: pointer; transition: transform 0.15s; }
.booking-card:active { transform: scale(0.99); }
.booking-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.booking-code { font-weight: 600; font-size: 0.9rem; }
.badge { padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
.badge.confirmed { background: #dbeafe; color: #1d4ed8; }
.badge.checked-in { background: #dcfce7; color: #16a34a; }
.badge.checked-out { background: #f3f4f6; color: #6b7280; }
.badge.cancelled { background: #fee2e2; color: #dc2626; }
.booking-info { font-size: 0.85rem; color: #666; }
.info-row { display: flex; align-items: center; gap: 8px; padding: 3px 0; }
.booking-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee; }
.property-name { font-size: 0.8rem; color: #888; }
.price-info { display: flex; align-items: center; gap: 6px; }
.price { font-weight: 600; color: var(--color-primary, #2563eb); font-size: 0.9rem; }
.arrow { color: #ccc; }
</style>
