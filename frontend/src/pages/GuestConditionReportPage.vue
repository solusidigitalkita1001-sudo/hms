<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiChevronLeft from '@iconify-icons/mdi/chevron-left'
import mdiCameraOutline from '@iconify-icons/mdi/camera-outline'
import mdiAlertCircleOutline from '@iconify-icons/mdi/alert-circle-outline'
import mdiCheckCircleOutline from '@iconify-icons/mdi/check-circle-outline'
import mdiClose from '@iconify-icons/mdi/close'
import mdiInformationOutline from '@iconify-icons/mdi/information-outline'
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAppLocale } from '../composables/useAppLocale'
import { buildApiUrl } from '../lib/api'

const route = useRoute()
const router = useRouter()
const { text: t } = useAppLocale()

const propertyCode = route.params.propertyCode as string
const bookingId = parseInt(route.params.bookingId as string) || 0

const categories = [
  'Kebersihan', 'Furniture', 'Elektronik', 'Plumbing', 'Lainnya',
]
const categoryEn = [
  'Cleanliness', 'Furniture', 'Electronics', 'Plumbing', 'Other',
]

const reports = reactive<{ category: string; description: string }[]>([])
const submitting = ref(false)
const submitted = ref(false)
const error = ref('')
const timerRemaining = ref(1800) // 30 minutes in seconds
const timerActive = ref(true)

let timerInterval: number | null = null

const timerDisplay = computed(() => {
  const mins = Math.floor(timerRemaining.value / 60)
  const secs = timerRemaining.value % 60
  return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
})

function addReport() {
  if (reports.length >= 10) return
  reports.push({ category: categories[0], description: '' })
}

function removeReport(index: number) {
  reports.splice(index, 1)
}

async function submitReports() {
  if (reports.length === 0) {
    error.value = 'Tambahkan minimal 1 laporan.'
    return
  }

  const token = localStorage.getItem('guest-token')
  if (!token) {
    error.value = 'Silakan login terlebih dahulu.'
    return
  }

  submitting.value = true
  error.value = ''

  try {
    const items = reports.map(r => ({
      category: r.category,
      description: r.description || '-',
    }))

    const res = await fetch(buildApiUrl(`/api/v1/guest/bookings/${bookingId}/condition-reports`), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        items,
        reporter_type: 'guest',
        guest_name: '',
      }),
    })
    const json = await res.json()
    if (json.success) {
      submitted.value = true
    } else {
      error.value = json.message || 'Gagal mengirim laporan.'
    }
  } catch {
    error.value = 'Gagal terhubung ke server.'
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.push({
    name: 'guest-booking-detail',
    params: { propertyCode, bookingId },
  })
}

// Initialize with one empty report
onMounted(() => {
  addReport()

  // Timer countdown
  timerInterval = window.setInterval(() => {
    if (timerRemaining.value > 0) {
      timerRemaining.value--
    } else {
      timerActive.value = false
      if (timerInterval) clearInterval(timerInterval)
    }
  }, 1000)
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
})
</script>

<template>
  <div class="report-page">
    <header class="page-header">
      <button class="back-btn" @click="goBack">
        <Icon :icon="mdiChevronLeft" width="24" />
      </button>
      <h1>{{ t('Laporan Kondisi Kamar', 'Room Condition Report') }}</h1>
    </header>

    <!-- Timer -->
    <div v-if="timerActive" class="timer-bar">
      <Icon :icon="mdiInformationOutline" width="16" />
      <span>{{ t('Sisa waktu laporan', 'Report window') }}: {{ timerDisplay }}</span>
    </div>

    <!-- Submitted state -->
    <div v-if="submitted" class="success-card">
      <Icon :icon="mdiCheckCircleOutline" width="48" class="success-icon" />
      <h3>{{ t('Laporan Terkirim!', 'Report Submitted!') }}</h3>
      <p>{{ t('Terima kasih, laporan kondisi kamar Anda telah disimpan.', 'Thank you, your room condition report has been saved.') }}</p>
      <p class="note">{{ t('Laporan ini akan melindungi Anda dari charge kerusakan yang tidak adil saat check-out.', 'This report protects you from unfair damage charges at check-out.') }}</p>
      <button class="btn-primary" @click="goBack">
        {{ t('Kembali', 'Back') }}
      </button>
    </div>

    <!-- Report Form -->
    <template v-else>
      <div class="info-card">
        <Icon :icon="mdiAlertCircleOutline" width="20" />
        <p>{{ t('Laporkan kondisi kamar sebelum batas waktu. Ini melindungi Anda dari charge kerusakan yang tidak Anda lakukan.', 'Report room conditions before the deadline. This protects you from damage charges for pre-existing issues.') }}</p>
      </div>

      <div v-for="(report, index) in reports" :key="index" class="report-item">
        <div class="report-header">
          <span>{{ t('Laporan', 'Report') }} #{{ index + 1 }}</span>
          <button v-if="reports.length > 1" class="remove-btn" @click="removeReport(index)">
            <Icon :icon="mdiClose" width="18" />
          </button>
        </div>
        <div class="form-group">
          <label>{{ t('Kategori', 'Category') }}</label>
          <select v-model="report.category" class="input">
            <option v-for="(cat, i) in categories" :key="cat" :value="cat">
              {{ t(cat, categoryEn[i]) }}
            </option>
          </select>
        </div>
        <div class="form-group">
          <label>{{ t('Deskripsi', 'Description') }}</label>
          <textarea v-model="report.description" class="input textarea" rows="2" :placeholder="t('Jelaskan kondisi...', 'Describe the condition...')"></textarea>
        </div>
        <div class="photo-placeholder">
          <Icon :icon="mdiCameraOutline" width="20" />
          <span>{{ t('Foto (opsional)', 'Photo (optional)') }}</span>
        </div>
      </div>

      <button v-if="reports.length < 10" class="add-btn" @click="addReport">
        + {{ t('Tambah Laporan', 'Add Report') }}
      </button>

      <p v-if="error" class="error-msg">{{ error }}</p>

      <button class="submit-btn" :disabled="submitting" @click="submitReports">
        <Icon :icon="submitting ? 'svg-spinners:clock' : mdiCheckCircleOutline" width="20" />
        {{ submitting ? t('Mengirim...', 'Submitting...') : t('Kirim Laporan', 'Submit Report') }}
      </button>
    </template>
  </div>
</template>

<style scoped>
.report-page {
  max-width: 600px; margin: 0 auto; padding: 16px;
  min-height: 100vh; background: var(--color-bg, #f8f9fa);
}
.page-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.page-header h1 { margin: 0; font-size: 1.2rem; }
.back-btn { background: none; border: none; cursor: pointer; padding: 4px; color: var(--color-primary, #2563eb); }
.timer-bar { display: flex; align-items: center; gap: 8px; background: #fef3c7; padding: 10px 14px; border-radius: 10px; font-size: 0.85rem; color: #d97706; margin-bottom: 16px; }
.info-card { display: flex; gap: 10px; background: #eff6ff; padding: 14px; border-radius: 10px; font-size: 0.85rem; color: #1d4ed8; margin-bottom: 16px; }
.info-card p { margin: 0; }
.report-item { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 12px; }
.report-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-weight: 600; font-size: 0.9rem; }
.remove-btn { background: none; border: none; cursor: pointer; color: #e53e3e; padding: 2px; }
.form-group { margin-bottom: 10px; }
.form-group label { display: block; font-size: 0.85rem; color: #666; margin-bottom: 4px; }
.input { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; }
.textarea { resize: vertical; }
.photo-placeholder { display: flex; align-items: center; gap: 6px; padding: 10px; border: 1px dashed #ddd; border-radius: 8px; color: #888; font-size: 0.85rem; cursor: pointer; }
.add-btn { width: 100%; padding: 12px; border: 1px dashed var(--color-primary, #2563eb); border-radius: 10px; background: none; color: var(--color-primary, #2563eb); font-size: 0.9rem; cursor: pointer; margin-bottom: 12px; }
.error-msg { color: #e53e3e; font-size: 0.85rem; text-align: center; margin: 8px 0; }
.submit-btn { width: 100%; padding: 14px; background: var(--color-primary, #2563eb); color: #fff; border: none; border-radius: 10px; font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; }
.submit-btn:disabled { opacity: 0.7; }
.success-card { text-align: center; padding: 40px 20px; }
.success-icon { color: #22c55e; }
.success-card h3 { margin: 12px 0 8px; }
.success-card p { font-size: 0.9rem; color: #555; }
.note { font-size: 0.8rem; color: #888; margin: 12px 0 24px; }
.btn-primary { padding: 14px 24px; background: var(--color-primary, #2563eb); color: #fff; border: none; border-radius: 10px; cursor: pointer; }
</style>
