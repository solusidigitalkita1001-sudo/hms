<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiChevronLeft from '@iconify-icons/mdi/chevron-left'
import mdiBedOutline from '@iconify-icons/mdi/bed-outline'
import mdiCalendarRangeOutline from '@iconify-icons/mdi/calendar-range-outline'
import mdiAccountOutline from '@iconify-icons/mdi/account-outline'
import mdiHomeOutline from '@iconify-icons/mdi/home-outline'
import mdiClipboardListOutline from '@iconify-icons/mdi/clipboard-list-outline'
import mdiFileDocumentOutline from '@iconify-icons/mdi/file-document-outline'
import mdiAlertOutline from '@iconify-icons/mdi/alert-outline'
import mdiCheckCircleOutline from '@iconify-icons/mdi/check-circle-outline'
import mdiQrcode from '@iconify-icons/mdi/qrcode'
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAppLocale } from '../composables/useAppLocale'
import { buildApiUrl } from '../lib/api'

const route = useRoute()
const router = useRouter()
const { t, locale } = useAppLocale()

const propertyCode = route.params.propertyCode as string
const bookingId = parseInt(route.params.bookingId as string)

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

function getStatusBadgeClass(status: string): string {
  const map: Record<string, string> = {
    confirmed: 'confirmed', checked_in: 'checked-in', checked_out: 'checked-out',
    cancelled: 'cancelled', no_show: 'cancelled',
  }
  return map[status] || 'confirmed'
}

async function loadDetail() {
  const token = localStorage.getItem('guest-token')
  if (!token) {
    error.value = 'Silakan login terlebih dahulu.'
    loading.value = false
    return
  }

  loading.value = true
  try {
    const res = await fetch(buildApiUrl(`/api/v1/guest/bookings/${bookingId}`), {
      headers: { Authorization: `Bearer ${token}` },
    })
    const json = await res.json()
    if (json.success) {
      booking.value = json.data
    } else {
      error.value = json.message || 'Booking tidak ditemukan.'
    }
  } catch {
    error.value = 'Gagal memuat detail booking.'
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push({ name: 'guest-my-bookings', params: { propertyCode } })
}

function goToConditionReport() {
  router.push({
    name: 'guest-condition-report',
    params: { propertyCode, bookingId },
  })
}

function goToInvoice() {
  router.push({ name: 'guest-portal', params: { propertyCode } })
}

onMounted(loadDetail)
</script>

<template>
  <div class="detail-page">
    <header class="page-header">
      <button class="back-btn" @click="goBack">
        <Icon :icon="mdiChevronLeft" width="24" />
      </button>
      <h1>{{ t('Detail Booking', 'Booking Detail') }}</h1>
    </header>

    <div v-if="loading" class="loading-state">{{ t('Memuat...', 'Loading...') }}</div>
    <div v-else-if="error" class="error-state">{{ error }}</div>

    <template v-else-if="booking">
      <!-- Status & Code -->
      <div class="status-card">
        <div class="qr-mini">
          <Icon :icon="mdiQrcode" width="32" />
        </div>
        <div class="status-info">
          <div class="booking-code">{{ booking.booking_code }}</div>
          <span :class="['badge', getStatusBadgeClass(booking.status)]">
            {{ booking.status }}
          </span>
        </div>
        <div v-if="booking.stay_status" class="stay-badge">
          {{ booking.stay_status }}
        </div>
      </div>

      <!-- Property Info -->
      <div class="section">
        <div class="section-header">
          <Icon :icon="mdiHomeOutline" width="18" />
          <h3>{{ booking.property?.name || t('Hotel', 'Hotel') }}</h3>
        </div>
        <p class="property-address">{{ booking.property?.address }}</p>
      </div>

      <!-- Room & Date Info -->
      <div class="section">
        <div class="info-grid">
          <div class="info-item">
            <Icon :icon="mdiBedOutline" width="18" />
            <div>
              <span class="info-label">{{ t('Kamar', 'Room') }}</span>
              <span class="info-value">{{ booking.room_type?.name || '-' }}</span>
            </div>
          </div>
          <div class="info-item" v-if="booking.assigned_room">
            <Icon :icon="mdiBedOutline" width="18" />
            <div>
              <span class="info-label">{{ t('Nomor Kamar', 'Room No.') }}</span>
              <span class="info-value">#{{ booking.assigned_room.room_number }}</span>
            </div>
          </div>
          <div class="info-item">
            <Icon :icon="mdiCalendarRangeOutline" width="18" />
            <div>
              <span class="info-label">{{ t('Check-in', 'Check-in') }}</span>
              <span class="info-value">{{ formatDate(booking.check_in_date) }}</span>
            </div>
          </div>
          <div class="info-item">
            <Icon :icon="mdiCalendarRangeOutline" width="18" />
            <div>
              <span class="info-label">{{ t('Check-out', 'Check-out') }}</span>
              <span class="info-value">{{ formatDate(booking.check_out_date) }}</span>
            </div>
          </div>
          <div class="info-item">
            <Icon :icon="mdiAccountOutline" width="18" />
            <div>
              <span class="info-label">{{ t('Tamu', 'Guests') }}</span>
              <span class="info-value">{{ booking.adult_count }} dewasa, {{ booking.child_count }} anak</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Guest Info -->
      <div class="section">
        <h3>{{ t('Data Tamu', 'Guest Info') }}</h3>
        <div class="guest-info">
          <p><strong>{{ t('Nama', 'Name') }}:</strong> {{ booking.guest?.name }}</p>
          <p><strong>{{ t('HP', 'Phone') }}:</strong> {{ booking.guest?.phone }}</p>
          <p v-if="booking.guest?.email"><strong>Email:</strong> {{ booking.guest?.email }}</p>
        </div>
      </div>

      <!-- Invoice -->
      <div v-if="booking.invoice" class="section">
        <div class="section-header">
          <Icon :icon="mdiFileDocumentOutline" width="18" />
          <h3>{{ t('Invoice', 'Invoice') }}</h3>
          <span :class="['badge', booking.invoice.status === 'paid' ? 'checked-in' : 'confirmed']">
            {{ booking.invoice.status }}
          </span>
        </div>
        <div class="invoice-items">
          <div v-for="item in booking.invoice.items" :key="item.name" class="invoice-row">
            <span class="item-name">{{ item.name }}</span>
            <span class="item-price">{{ formatCurrency(item.line_total) }}</span>
          </div>
        </div>
        <div class="invoice-total">
          <span>{{ t('Total', 'Total') }}</span>
          <span>{{ formatCurrency(booking.invoice.grand_total) }}</span>
        </div>
        <div class="invoice-paid" v-if="booking.invoice.paid_amount > 0">
          <span>{{ t('Terbayar', 'Paid') }}</span>
          <span class="paid">{{ formatCurrency(booking.invoice.paid_amount) }}</span>
        </div>
        <div class="invoice-remaining" v-if="booking.invoice.remaining > 0">
          <span>{{ t('Sisa', 'Remaining') }}</span>
          <span class="remaining">{{ formatCurrency(booking.invoice.remaining) }}</span>
        </div>
      </div>

      <!-- Special Requests -->
      <div v-if="booking.special_requests" class="section">
        <div class="section-header">
          <Icon :icon="mdiClipboardListOutline" width="18" />
          <h3>{{ t('Permintaan Khusus', 'Special Requests') }}</h3>
        </div>
        <p>{{ booking.special_requests }}</p>
      </div>

      <!-- Actions -->
      <div class="actions">
        <button class="action-btn" @click="goToConditionReport" v-if="booking.status === 'checked_in'">
          <Icon :icon="mdiAlertOutline" width="20" />
          {{ t('Lapor Kondisi Kamar', 'Report Room Condition') }}
        </button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.detail-page {
  max-width: 700px; margin: 0 auto; padding: 16px;
  min-height: 100vh; background: var(--color-bg, #f8f9fa);
}
.page-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.page-header h1 { margin: 0; font-size: 1.3rem; }
.back-btn { background: none; border: none; cursor: pointer; padding: 4px; color: var(--color-primary, #2563eb); }
.loading-state, .error-state { text-align: center; padding: 60px 20px; color: #888; }
.status-card { display: flex; align-items: center; gap: 12px; background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 16px; }
.qr-mini { width: 48px; height: 48px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #888; }
.status-info { flex: 1; }
.booking-code { font-weight: 700; font-size: 1rem; margin-bottom: 4px; }
.badge { padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
.badge.confirmed { background: #dbeafe; color: #1d4ed8; }
.badge.checked-in { background: #dcfce7; color: #16a34a; }
.badge.checked-out { background: #f3f4f6; color: #6b7280; }
.badge.cancelled { background: #fee2e2; color: #dc2626; }
.stay-badge { padding: 4px 10px; background: #fef3c7; color: #d97706; border-radius: 8px; font-size: 0.75rem; }
.section { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 16px; }
.section-header { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.section-header h3 { margin: 0; font-size: 0.95rem; flex: 1; }
.property-address { font-size: 0.85rem; color: #888; margin: 0; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.info-item { display: flex; gap: 8px; align-items: flex-start; color: #555; }
.info-label { display: block; font-size: 0.75rem; color: #999; }
.info-value { font-size: 0.9rem; font-weight: 500; }
.guest-info p { margin: 4px 0; font-size: 0.9rem; }
.invoice-items { }
.invoice-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.85rem; }
.invoice-total, .invoice-paid, .invoice-remaining { display: flex; justify-content: space-between; padding: 8px 0 0; margin-top: 4px; border-top: 1px solid #eee; font-weight: 600; }
.invoice-paid .paid { color: #16a34a; }
.invoice-remaining .remaining { color: #e53e3e; }
.actions { margin-top: 8px; display: flex; flex-direction: column; gap: 8px; }
.action-btn { padding: 14px; border: 1px solid var(--color-primary, #2563eb); color: var(--color-primary, #2563eb); border-radius: 10px; background: #fff; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; font-size: 0.9rem; }
@media (max-width: 480px) { .info-grid { grid-template-columns: 1fr; } }
</style>
