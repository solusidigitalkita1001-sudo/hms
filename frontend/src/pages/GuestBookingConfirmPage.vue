<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiCheckCircleOutline from '@iconify-icons/mdi/check-circle-outline'
import mdiBedOutline from '@iconify-icons/mdi/bed-outline'
import mdiCalendarRangeOutline from '@iconify-icons/mdi/calendar-range-outline'
import mdiHomeOutline from '@iconify-icons/mdi/home-outline'
import mdiPrinter from '@iconify-icons/mdi/printer'
import mdiShareVariant from '@iconify-icons/mdi/share-variant'
import mdiWhatsapp from '@iconify-icons/mdi/whatsapp'
import mdiQrcode from '@iconify-icons/mdi/qrcode'
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAppLocale } from '../composables/useAppLocale'
import { buildApiUrl } from '../lib/api'

const route = useRoute()
const router = useRouter()
const { t, locale } = useAppLocale()

const propertyCode = route.params.propertyCode as string
const bookingCode = route.params.bookingCode as string

const booking = ref<any>(null)
const loading = ref(true)
const error = ref('')

function formatDate(dateStr: string): string {
  if (!dateStr) return ''
  const d = new Date(dateStr + 'T00:00:00')
  return d.toLocaleDateString(locale.value === 'en' ? 'en-US' : 'id-ID', {
    weekday: 'short', day: 'numeric', month: 'short', year: 'numeric',
  })
}

function formatCurrency(amount: number): string {
  return `Rp ${amount.toLocaleString('id-ID')}`
}

async function loadBooking() {
  loading.value = true
  try {
    const res = await fetch(buildApiUrl(`/api/v1/public/bookings/${bookingCode}`))
    const json = await res.json()
    if (json.success) {
      booking.value = json.data
    } else {
      error.value = json.message || 'Booking tidak ditemukan.'
    }
  } catch {
    error.value = 'Gagal memuat data booking.'
  } finally {
    loading.value = false
  }
}

function goToPortal() {
  router.push({ name: 'guest-portal', params: { propertyCode } })
}

function shareWhatsApp() {
  if (booking.value) {
    const text = `Halo, saya telah melakukan booking dengan kode: ${bookingCode}. Mohon infonya.`
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank')
  }
}

function printPage() {
  window.print()
}

onMounted(loadBooking)
</script>

<template>
  <div class="confirm-page">
    <header class="confirm-header">
      <button class="back-btn" @click="goToPortal">
        <Icon :icon="mdiHomeOutline" width="24" />
      </button>
      <h1>{{ t('E-Ticket', 'E-Ticket') }}</h1>
    </header>

    <div v-if="loading" class="loading-state">{{ t('Memuat...', 'Loading...') }}</div>

    <div v-else-if="error" class="error-state">{{ error }}</div>

    <div v-else-if="booking" class="ticket">
      <div class="ticket-header">
        <Icon :icon="mdiCheckCircleOutline" width="32" class="success-icon" />
        <h2>{{ t('Booking Berhasil!', 'Booking Successful!') }}</h2>
      </div>

      <!-- QR Code Placeholder -->
      <div class="qr-section">
        <div class="qr-placeholder">
          <Icon :icon="mdiQrcode" width="64" />
        </div>
        <div class="booking-code-display">{{ bookingCode }}</div>
        <p class="booking-hint">{{ t('Tunjukkan kode ini saat check-in', 'Show this code at check-in') }}</p>
      </div>

      <!-- Booking Detail -->
      <div class="detail-card">
        <div class="detail-row">
          <span class="label">{{ t('Hotel', 'Hotel') }}</span>
          <span class="value">{{ booking.property?.name }}</span>
        </div>
        <div class="detail-row">
          <span class="label">{{ t('Alamat', 'Address') }}</span>
          <span class="value">{{ booking.property?.address }}</span>
        </div>
        <div class="divider"></div>
        <div class="detail-row">
          <span class="label">{{ t('Kamar', 'Room') }}</span>
          <span class="value">{{ booking.room_type }}</span>
        </div>
        <div class="detail-row">
          <span class="label">{{ t('Check-in', 'Check-in') }}</span>
          <span class="value">{{ formatDate(booking.check_in_date) }}</span>
        </div>
        <div class="detail-row">
          <span class="label">{{ t('Check-out', 'Check-out') }}</span>
          <span class="value">{{ formatDate(booking.check_out_date) }}</span>
        </div>
        <div class="detail-row">
          <span class="label">{{ t('Tamu', 'Guest') }}</span>
          <span class="value">{{ booking.guest?.name }}</span>
        </div>
        <div class="detail-row" v-if="booking.assigned_room">
          <span class="label">{{ t('Nomor Kamar', 'Room No.') }}</span>
          <span class="value">{{ booking.assigned_room }}</span>
        </div>
        <div class="divider"></div>
        <div class="detail-row status">
          <span class="label">{{ t('Status', 'Status') }}</span>
          <span class="badge confirmed">{{ booking.status }}</span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="actions">
        <button class="action-btn" @click="shareWhatsApp">
          <Icon :icon="mdiWhatsapp" width="20" />
          {{ t('Bagikan ke WA', 'Share via WhatsApp') }}
        </button>
        <button class="action-btn" @click="printPage">
          <Icon :icon="mdiPrinter" width="20" />
          {{ t('Cetak', 'Print') }}
        </button>
        <button class="action-btn" @click="goToPortal">
          <Icon :icon="mdiHomeOutline" width="20" />
          {{ t('Beranda', 'Home') }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.confirm-page {
  max-width: 600px; margin: 0 auto; padding: 16px;
  min-height: 100vh; background: var(--color-bg, #f8f9fa);
}
.confirm-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.confirm-header h1 { margin: 0; font-size: 1.3rem; }
.back-btn { background: none; border: none; cursor: pointer; padding: 4px; color: var(--color-primary, #2563eb); }
.loading-state, .error-state { text-align: center; padding: 60px 20px; color: #888; }
.ticket { display: flex; flex-direction: column; gap: 16px; }
.ticket-header { text-align: center; padding: 20px; }
.ticket-header h2 { margin: 8px 0 0; color: #22c55e; }
.success-icon { color: #22c55e; }
.qr-section { text-align: center; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
.qr-placeholder { width: 120px; height: 120px; margin: 0 auto 12px; background: #f3f4f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #888; }
.booking-code-display { font-size: 1.4rem; font-weight: 700; letter-spacing: 3px; color: var(--color-primary, #2563eb); }
.booking-hint { font-size: 0.8rem; color: #888; margin: 4px 0 0; }
.detail-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
.detail-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 0.9rem; }
.detail-row .label { color: #888; }
.detail-row .value { font-weight: 500; text-align: right; max-width: 60%; }
.divider { border-top: 1px solid #eee; margin: 4px 0; }
.badge { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; }
.badge.confirmed { background: #dbeafe; color: #1d4ed8; }
.actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }
.action-btn { flex: 1; min-width: 110px; padding: 12px; border: 1px solid #ddd; border-radius: 10px; background: #fff; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.85rem; cursor: pointer; color: #555; }
@media print { .back-btn, .actions { display: none; } }
</style>
