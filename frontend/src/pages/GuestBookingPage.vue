<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiChevronLeft from '@iconify-icons/mdi/chevron-left'
import mdiBedOutline from '@iconify-icons/mdi/bed-outline'
import mdiAccountOutline from '@iconify-icons/mdi/account-outline'
import mdiCheckCircleOutline from '@iconify-icons/mdi/check-circle-outline'
import mdiArrowRight from '@iconify-icons/mdi/arrow-right'
import mdiArrowLeft from '@iconify-icons/mdi/arrow-left'
import mdiCalendarRangeOutline from '@iconify-icons/mdi/calendar-range-outline'
import mdiWifi from '@iconify-icons/mdi/wifi'
import mdiAirConditioner from '@iconify-icons/mdi/air-conditioner'
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAppLocale } from '../composables/useAppLocale'
import { buildApiUrl } from '../lib/api'

const route = useRoute()
const router = useRouter()
const { t, locale } = useAppLocale()

const propertyCode = route.params.propertyCode as string
const roomTypeCode = route.query.room_type as string
const checkIn = route.query.check_in as string
const checkOut = route.query.check_out as string
const adults = parseInt(route.query.adults as string || '1')
const children = parseInt(route.query.children as string || '0')
const basePrice = parseFloat(route.query.price as string || '0')

const step = ref(1)
const loading = ref(false)
const error = ref('')
const bookingResult = ref<any>(null)
const roomType = ref<any>(null)

const nights = computed(() => {
  if (!checkIn || !checkOut) return 1
  const ci = new Date(checkIn + 'T00:00:00')
  const co = new Date(checkOut + 'T00:00:00')
  return Math.max(1, Math.round((co.getTime() - ci.getTime()) / 86400000))
})

const totalPrice = computed(() => basePrice * nights.value)
const taxAmount = computed(() => totalPrice.value * 0.1)
const grandTotal = computed(() => totalPrice.value + taxAmount.value)

const guestForm = reactive({
  booker_name: '',
  booker_phone: '',
  booker_email: '',
  booker_nik: '',
  is_booking_for_other: false,
  guest_name: '',
  guest_phone: '',
  special_requests: '',
})

function formatCurrency(amount: number): string {
  return `Rp ${amount.toLocaleString('id-ID')}`
}

function formatDate(dateStr: string): string {
  if (!dateStr) return ''
  const d = new Date(dateStr + 'T00:00:00')
  return d.toLocaleDateString(locale.value === 'en' ? 'en-US' : 'id-ID', {
    weekday: 'short', day: 'numeric', month: 'short', year: 'numeric',
  })
}

function goBack() {
  if (step.value > 1) {
    step.value--
  } else {
    router.push({ name: 'guest-room-search', params: { propertyCode } })
  }
}

async function submitBooking() {
  if (!guestForm.booker_name || !guestForm.booker_phone) {
    error.value = 'Nama dan nomor HP wajib diisi.'
    return
  }
  if (guestForm.booker_phone.length < 10) {
    error.value = 'Nomor HP minimal 10 digit.'
    return
  }

  loading.value = true
  error.value = ''

  try {
    const body = {
      property_code: propertyCode,
      room_type_code: roomTypeCode,
      check_in_date: checkIn,
      check_out_date: checkOut,
      adult_count: adults,
      child_count: children,
      booker_name: guestForm.booker_name,
      booker_phone: guestForm.booker_phone,
      booker_email: guestForm.booker_email || undefined,
      booker_nik: guestForm.booker_nik || undefined,
      is_booking_for_other: guestForm.is_booking_for_other,
      guest_name: guestForm.guest_name || undefined,
      guest_phone: guestForm.guest_phone || undefined,
      special_requests: guestForm.special_requests || undefined,
    }

    const res = await fetch(buildApiUrl('/api/v1/public/bookings'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    })
    const json = await res.json()
    if (json.success) {
      bookingResult.value = json.data
    } else {
      error.value = json.message || 'Gagal membuat booking.'
    }
  } catch {
    error.value = 'Gagal terhubung ke server.'
  } finally {
    loading.value = false
  }
}

function goToConfirm() {
  if (bookingResult.value) {
    router.push({
      name: 'guest-booking-confirm',
      params: { propertyCode, bookingCode: bookingResult.value.booking_code },
    })
  }
}

const roomTypeInfo = computed(() => ({
  name: route.query.room_type_name || roomTypeCode,
  capacity: route.query.capacity || '2',
}))
</script>

<template>
  <div class="booking-page">
    <header class="booking-header">
      <button class="back-btn" @click="goBack">
        <Icon :icon="mdiChevronLeft" width="24" />
      </button>
      <div class="step-indicator">
        <span :class="{ active: step >= 1 }">{{ t('Kamar', 'Room') }}</span>
        <span class="step-divider">→</span>
        <span :class="{ active: step >= 2 }">{{ t('Data', 'Guest') }}</span>
        <span class="step-divider">→</span>
        <span :class="{ active: step >= 3 }">{{ t('Konfirmasi', 'Confirm') }}</span>
      </div>
    </header>

    <!-- Step 1: Room Review -->
    <div v-if="step === 1" class="step-content">
      <div class="card">
        <h3>{{ t('Review Pesanan', 'Order Review') }}</h3>
        <div class="review-row">
          <Icon :icon="mdiBedOutline" width="20" />
          <div>
            <strong>{{ t('Tipe Kamar', 'Room Type') }}:</strong>
            <span>{{ route.query.room_type_name || roomTypeCode }}</span>
          </div>
        </div>
        <div class="review-row">
          <Icon :icon="mdiCalendarRangeOutline" width="20" />
          <div>
            <strong>{{ t('Tanggal', 'Dates') }}:</strong>
            <span>{{ formatDate(checkIn) }} — {{ formatDate(checkOut) }}</span>
            <small>({{ nights }} {{ t('malam', 'nights') }})</small>
          </div>
        </div>
        <div class="review-row">
          <Icon :icon="mdiAccountOutline" width="20" />
          <div>
            <strong>{{ t('Tamu', 'Guests') }}:</strong>
            <span>{{ adults }} {{ t('dewasa', 'adults') }}, {{ children }} {{ t('anak', 'children') }}</span>
          </div>
        </div>
      </div>

      <div class="price-summary">
        <div class="price-row">
          <span>{{ formatCurrency(basePrice) }} x {{ nights }} {{ t('malam', 'nights') }}</span>
          <span>{{ formatCurrency(totalPrice) }}</span>
        </div>
        <div class="price-row">
          <span>{{ t('Pajak 10%', 'Tax 10%') }}</span>
          <span>{{ formatCurrency(taxAmount) }}</span>
        </div>
        <div class="price-row total">
          <strong>{{ t('Total', 'Total') }}</strong>
          <strong>{{ formatCurrency(grandTotal) }}</strong>
        </div>
      </div>

      <button class="btn-primary" @click="step = 2">
        {{ t('Lanjut ke Data Tamu', 'Next: Guest Data') }}
        <Icon :icon="mdiArrowRight" width="20" />
      </button>
    </div>

    <!-- Step 2: Guest Data -->
    <div v-if="step === 2" class="step-content">
      <div class="card">
        <h3>{{ t('Data Pemesan', 'Booker Info') }}</h3>
        <div class="form-group">
          <label>{{ t('Nama Lengkap', 'Full Name') }} *</label>
          <input v-model="guestForm.booker_name" type="text" class="input" :placeholder="t('Nama sesuai KTP', 'Name as on ID')" />
        </div>
        <div class="form-group">
          <label>{{ t('Nomor HP', 'Phone') }} *</label>
          <input v-model="guestForm.booker_phone" type="tel" class="input" placeholder="0812xxxx" inputmode="numeric" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>{{ t('Email', 'Email') }}</label>
            <input v-model="guestForm.booker_email" type="email" class="input" placeholder="email@example.com" />
          </div>
          <div class="form-group">
            <label>{{ t('NIK', 'NIK') }}</label>
            <input v-model="guestForm.booker_nik" type="text" class="input" placeholder="16 digit" inputmode="numeric" />
          </div>
        </div>
      </div>

      <div class="card">
        <label class="checkbox-row">
          <input v-model="guestForm.is_booking_for_other" type="checkbox" />
          <span>{{ t('Booking untuk orang lain', 'Booking for someone else') }}</span>
        </label>
        <div v-if="guestForm.is_booking_for_other" class="other-guest">
          <div class="form-group">
            <label>{{ t('Nama Tamu', 'Guest Name') }} *</label>
            <input v-model="guestForm.guest_name" type="text" class="input" :placeholder="t('Nama yang menginap', 'Guest staying')" />
          </div>
          <div class="form-group">
            <label>{{ t('No HP Tamu', 'Guest Phone') }}</label>
            <input v-model="guestForm.guest_phone" type="tel" class="input" placeholder="0812xxxx" />
          </div>
        </div>
      </div>

      <div class="card">
        <div class="form-group">
          <label>{{ t('Permintaan Khusus', 'Special Requests') }}</label>
          <textarea v-model="guestForm.special_requests" class="input textarea" rows="2" :placeholder="t('Misal: lantai tinggi, kamar dekat lift...', 'E.g. high floor, near elevator...')"></textarea>
        </div>
      </div>

      <p v-if="error" class="error-msg">{{ error }}</p>

      <div class="btn-group">
        <button class="btn-secondary" @click="step = 1">
          <Icon :icon="mdiArrowLeft" width="20" /> {{ t('Kembali', 'Back') }}
        </button>
        <button class="btn-primary" @click="step = 3">
          {{ t('Lanjut ke Review', 'Next: Review') }}
          <Icon :icon="mdiArrowRight" width="20" />
        </button>
      </div>
    </div>

    <!-- Step 3: Confirmation -->
    <div v-if="step === 3" class="step-content">
      <div class="card">
        <h3>{{ t('Review & Konfirmasi', 'Review & Confirm') }}</h3>
        <div class="confirm-detail">
          <div class="confirm-row">
            <span class="label">{{ t('Tipe Kamar', 'Room Type') }}</span>
            <span>{{ roomTypeCode }}</span>
          </div>
          <div class="confirm-row">
            <span class="label">{{ t('Check-in', 'Check-in') }}</span>
            <span>{{ formatDate(checkIn) }}</span>
          </div>
          <div class="confirm-row">
            <span class="label">{{ t('Check-out', 'Check-out') }}</span>
            <span>{{ formatDate(checkOut) }}</span>
          </div>
          <div class="confirm-row">
            <span class="label">{{ t('Tamu', 'Guests') }}</span>
            <span>{{ adults }} dewasa, {{ children }} anak</span>
          </div>
          <div class="confirm-row">
            <span class="label">{{ t('Pemesan', 'Booker') }}</span>
            <span>{{ guestForm.booker_name }}</span>
          </div>
          <div class="confirm-row">
            <span class="label">{{ t('No. HP', 'Phone') }}</span>
            <span>{{ guestForm.booker_phone }}</span>
          </div>
          <div class="confirm-divider"></div>
          <div class="confirm-row total">
            <span class="label">{{ t('Total Pembayaran', 'Total Payment') }}</span>
            <span class="total-price">{{ formatCurrency(grandTotal) }}</span>
          </div>
        </div>
      </div>

      <p v-if="error" class="error-msg">{{ error }}</p>

      <div class="btn-group">
        <button class="btn-secondary" @click="step = 2" :disabled="loading">
          <Icon :icon="mdiArrowLeft" width="20" /> {{ t('Kembali', 'Back') }}
        </button>
        <button class="btn-primary" @click="submitBooking" :disabled="loading">
          <Icon v-if="loading" icon="svg-spinners:clock" width="20" />
          <Icon v-else :icon="mdiCheckCircleOutline" width="20" />
          {{ loading ? t('Memproses...', 'Processing...') : t('Konfirmasi Booking', 'Confirm Booking') }}
        </button>
      </div>

      <div v-if="bookingResult" class="success-card">
        <Icon :icon="mdiCheckCircleOutline" width="48" class="success-icon" />
        <h3>{{ t('Booking Berhasil!', 'Booking Successful!') }}</h3>
        <p>{{ t('Kode booking Anda', 'Your booking code') }}:</p>
        <div class="booking-code">{{ bookingResult.booking_code }}</div>
        <button class="btn-primary" @click="goToConfirm">
          {{ t('Lihat E-Ticket', 'View E-Ticket') }}
          <Icon :icon="mdiArrowRight" width="20" />
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.booking-page {
  max-width: 700px; margin: 0 auto; padding: 16px;
  min-height: 100vh; background: var(--color-bg, #f8f9fa);
}
.booking-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.back-btn { background: none; border: none; cursor: pointer; padding: 4px; color: var(--color-primary, #2563eb); }
.step-indicator { display: flex; align-items: center; gap: 6px; font-size: 0.8rem; color: #aaa; }
.step-indicator .active { color: var(--color-primary, #2563eb); font-weight: 600; }
.step-divider { color: #ddd; }
.step-content { display: flex; flex-direction: column; gap: 16px; }
.card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
.card h3 { margin: 0 0 12px; font-size: 1rem; }
.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-size: 0.85rem; color: #666; margin-bottom: 4px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.input { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; }
.textarea { resize: vertical; }
.review-row { display: flex; gap: 12px; align-items: center; padding: 8px 0; color: #555; }
.review-row strong { display: block; font-size: 0.8rem; }
.review-row small { color: #888; font-size: 0.75rem; }
.price-summary { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
.price-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.9rem; color: #555; }
.price-row.total { border-top: 1px solid #eee; margin-top: 6px; padding-top: 12px; font-size: 1.1rem; color: var(--color-primary, #2563eb); }
.checkbox-row { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.other-guest { margin-top: 12px; padding-top: 12px; border-top: 1px solid #eee; }
.btn-group { display: flex; gap: 12px; }
.btn-primary, .btn-secondary {
  flex: 1; padding: 14px; border: none; border-radius: 10px; font-size: 0.95rem;
  display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer;
}
.btn-primary { background: var(--color-primary, #2563eb); color: #fff; }
.btn-secondary { background: #e5e7eb; color: #555; }
.btn-primary:disabled { opacity: 0.7; }
.error-msg { color: #e53e3e; font-size: 0.85rem; text-align: center; }
.success-card { text-align: center; padding: 30px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; }
.success-icon { color: #22c55e; }
.booking-code { font-size: 1.5rem; font-weight: 700; letter-spacing: 2px; color: var(--color-primary, #2563eb); margin: 12px 0 20px; }
.confirm-detail { font-size: 0.9rem; }
.confirm-row { display: flex; justify-content: space-between; padding: 6px 0; }
.confirm-row .label { color: #888; }
.confirm-divider { border-top: 1px solid #eee; margin: 8px 0; }
.confirm-row.total .label { font-weight: 600; color: #333; }
.total-price { font-size: 1.1rem; font-weight: 700; color: var(--color-primary, #2563eb); }
@media (max-width: 480px) { .form-row { grid-template-columns: 1fr; } }
</style>
