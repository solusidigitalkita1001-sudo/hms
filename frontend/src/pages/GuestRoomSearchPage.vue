<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiBedOutline from '@iconify-icons/mdi/bed-outline'
import mdiCalendarRangeOutline from '@iconify-icons/mdi/calendar-range-outline'
import mdiAccountOutline from '@iconify-icons/mdi/account-outline'
import mdiMagnify from '@iconify-icons/mdi/magnify'
import mdiArrowRight from '@iconify-icons/mdi/arrow-right'
import mdiWifi from '@iconify-icons/mdi/wifi'
import mdiAirConditioner from '@iconify-icons/mdi/air-conditioner'
import mdiTelevision from '@iconify-icons/mdi/television'
import mdiShower from '@iconify-icons/mdi/shower'
import mdiSmokingOff from '@iconify-icons/mdi/smoking-off'
import mdiSmoking from '@iconify-icons/mdi/smoking'
import mdiChevronLeft from '@iconify-icons/mdi/chevron-left'
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAppLocale } from '../composables/useAppLocale'
import { buildApiUrl } from '../lib/api'

const route = useRoute()
const router = useRouter()
const { text: t } = useAppLocale()

const propertyCode = route.params.propertyCode as string

const searchForm = reactive({
  check_in_date: '',
  check_out_date: '',
  adult_count: 1,
  child_count: 0,
})

const roomTypes = ref<any[]>([])
const loading = ref(false)
const searched = ref(false)
const error = ref('')

function formatCurrency(amount: number): string {
  return `Rp ${amount.toLocaleString('id-ID')}`
}

async function doSearch() {
  if (!searchForm.check_in_date || !searchForm.check_out_date) {
    error.value = 'Silakan pilih tanggal check-in dan check-out.'
    return
  }
  if (searchForm.check_in_date >= searchForm.check_out_date) {
    error.value = 'Check-out harus setelah check-in.'
    return
  }

  loading.value = true
  error.value = ''

  try {
    const params = new URLSearchParams({
      property_code: propertyCode,
      check_in_date: searchForm.check_in_date,
      check_out_date: searchForm.check_out_date,
      adult_count: String(searchForm.adult_count),
      child_count: String(searchForm.child_count),
    })
    const res = await fetch(buildApiUrl(`/api/v1/public/rooms/search?${params}`))
    const json = await res.json()
    if (json.success) {
      roomTypes.value = json.data.room_types.filter((r: any) => r.is_available)
      searched.value = true
    } else {
      error.value = json.message || 'Gagal mencari kamar.'
    }
  } catch {
    error.value = 'Gagal terhubung ke server.'
  } finally {
    loading.value = false
  }
}

function selectRoom(roomType: any) {
  router.push({
    name: 'guest-booking',
    params: { propertyCode },
    query: {
      room_type: roomType.code,
      check_in: searchForm.check_in_date,
      check_out: searchForm.check_out_date,
      adults: String(searchForm.adult_count),
      children: String(searchForm.child_count),
      price: String(roomType.base_price),
    },
  })
}

function goBack() {
  router.push({ name: 'guest-portal', params: { propertyCode } })
}

const totalNights = computed(() => {
  if (!searchForm.check_in_date || !searchForm.check_out_date) return 0
  const ci = new Date(searchForm.check_in_date + 'T00:00:00')
  const co = new Date(searchForm.check_out_date + 'T00:00:00')
  return Math.max(1, Math.round((co.getTime() - ci.getTime()) / 86400000))
})

onMounted(() => {
  const today = new Date()
  const tomorrow = new Date(today)
  tomorrow.setDate(tomorrow.getDate() + 1)
  searchForm.check_in_date = today.toISOString().slice(0, 10)
  searchForm.check_out_date = tomorrow.toISOString().slice(0, 10)
})
</script>

<template>
  <div class="search-page">
    <header class="search-header">
      <button class="back-btn" @click="goBack">
        <Icon :icon="mdiChevronLeft" width="24" />
      </button>
      <h1>{{ t('Cari Kamar', 'Search Rooms') }}</h1>
    </header>

    <!-- Search Form -->
    <div class="search-form">
      <div class="form-row">
        <div class="form-group">
          <label>
            <Icon :icon="mdiCalendarRangeOutline" width="16" />
            {{ t('Check-in', 'Check-in') }}
          </label>
          <input v-model="searchForm.check_in_date" type="date" class="form-input" />
        </div>
        <div class="form-group">
          <label>
            <Icon :icon="mdiCalendarRangeOutline" width="16" />
            {{ t('Check-out', 'Check-out') }}
          </label>
          <input v-model="searchForm.check_out_date" type="date" class="form-input" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>
            <Icon :icon="mdiAccountOutline" width="16" />
            {{ t('Dewasa', 'Adults') }}
          </label>
          <select v-model.number="searchForm.adult_count" class="form-input">
            <option v-for="n in 6" :key="n" :value="n">{{ n }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>
            <Icon :icon="mdiAccountOutline" width="16" />
            {{ t('Anak', 'Children') }}
          </label>
          <select v-model.number="searchForm.child_count" class="form-input">
            <option v-for="n in 4" :key="n" :value="n">{{ n }}</option>
          </select>
        </div>
      </div>
      <p v-if="error" class="error-msg">{{ error }}</p>
      <button class="search-btn" :disabled="loading" @click="doSearch">
        <Icon :icon="mdiMagnify" width="20" />
        {{ loading ? t('Mencari...', 'Searching...') : t('Cari Kamar', 'Search Rooms') }}
      </button>
    </div>

    <!-- Results -->
    <div v-if="loading" class="loading-state">
      <p>{{ t('Mencari kamar tersedia...', 'Finding available rooms...') }}</p>
    </div>

    <div v-else-if="searched && roomTypes.length === 0" class="empty-state">
      <Icon :icon="mdiBedOutline" width="48" />
      <h3>{{ t('Tidak ada kamar tersedia', 'No rooms available') }}</h3>
      <p>{{ t('Coba ubah tanggal atau jumlah tamu.', 'Try changing dates or guest count.') }}</p>
    </div>

    <div v-else-if="roomTypes.length > 0" class="room-list">
      <div
        v-for="rt in roomTypes"
        :key="rt.code"
        class="room-card"
        @click="selectRoom(rt)"
      >
        <div class="room-image">
          <Icon :icon="mdiBedOutline" width="40" />
          <span class="room-badge">{{ rt.available_rooms }} {{ t('tersisa', 'left') }}</span>
        </div>
        <div class="room-info">
          <h3>{{ rt.name }}</h3>
          <p class="room-desc">{{ rt.description }}</p>
          <div class="room-meta">
            <span><Icon :icon="mdiAccountOutline" width="14" /> {{ rt.capacity }} {{ t('tamu', 'guests') }}</span>
            <span v-if="rt.bed_type">{{ rt.bed_type }}</span>
            <span v-if="rt.size_sqm">{{ rt.size_sqm }} m²</span>
          </div>
          <div class="room-amenities">
            <span v-if="rt.amenities?.includes('wifi')"><Icon :icon="mdiWifi" width="14" /> WiFi</span>
            <span v-if="rt.amenities?.includes('ac') || rt.amenities?.includes('AC')"><Icon :icon="mdiAirConditioner" width="14" /> AC</span>
            <span v-if="rt.amenities?.includes('tv') || rt.amenities?.includes('TV')"><Icon :icon="mdiTelevision" width="14" /> TV</span>
            <span v-if="rt.amenities?.includes('shower')"><Icon :icon="mdiShower" width="14" /> {{ t('Kamar Mandi', 'Bathroom') }}</span>
            <span v-if="rt.smoking_allowed"><Icon :icon="mdiSmoking" width="14" /> {{ t('Boleh Merokok', 'Smoking') }}</span>
            <span v-else><Icon :icon="mdiSmokingOff" width="14" /> {{ t('No Smoking', 'No Smoking') }}</span>
          </div>
          <div class="room-price">
            <span class="price">{{ formatCurrency(rt.base_price) }} <small>/ {{ t('malam', 'night') }}</small></span>
            <span class="total">{{ formatCurrency(rt.base_price * totalNights) }} {{ t('total', 'total') }}</span>
          </div>
        </div>
        <div class="room-action">
          <Icon :icon="mdiArrowRight" width="24" />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.search-page {
  max-width: 800px;
  margin: 0 auto;
  padding: 16px;
  min-height: 100vh;
  background: var(--color-bg, #f8f9fa);
}
.search-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}
.search-header h1 { font-size: 1.3rem; margin: 0; }
.back-btn {
  background: none; border: none; cursor: pointer; padding: 4px;
  color: var(--color-primary, #2563eb);
}
.search-form {
  background: #fff; border-radius: 12px; padding: 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 20px;
}
.form-row {
  display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
  margin-bottom: 12px;
}
.form-group label {
  display: flex; align-items: center; gap: 6px;
  font-size: 0.85rem; color: #666; margin-bottom: 4px;
}
.form-input {
  width: 100%; padding: 10px 12px; border: 1px solid #ddd;
  border-radius: 8px; font-size: 0.95rem;
  box-sizing: border-box; background: #fff;
}
.search-btn {
  width: 100%; padding: 14px; background: var(--color-primary, #2563eb);
  color: #fff; border: none; border-radius: 10px; font-size: 1rem;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  cursor: pointer; margin-top: 8px;
}
.search-btn:disabled { opacity: 0.7; }
.error-msg { color: #e53e3e; font-size: 0.85rem; margin: 4px 0; }
.loading-state, .empty-state {
  text-align: center; padding: 60px 20px; color: #888;
}
.room-list { display: flex; flex-direction: column; gap: 12px; }
.room-card {
  display: flex; gap: 16px; background: #fff; border-radius: 12px;
  padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  cursor: pointer; transition: transform 0.15s;
  align-items: center;
}
.room-card:active { transform: scale(0.99); }
.room-image {
  width: 100px; height: 100px; border-radius: 10px;
  background: var(--color-primary, #2563eb)10; display: flex;
  align-items: center; justify-content: center; flex-shrink: 0;
  position: relative; color: var(--color-primary, #2563eb);
}
.room-badge {
  position: absolute; bottom: 6px; left: 6px; right: 6px;
  background: #e53e3e; color: #fff; font-size: 0.7rem;
  text-align: center; border-radius: 4px; padding: 2px;
}
.room-info { flex: 1; min-width: 0; }
.room-info h3 { margin: 0 0 4px; font-size: 1rem; }
.room-desc { font-size: 0.8rem; color: #888; margin: 0 0 8px; }
.room-meta { display: flex; gap: 12px; font-size: 0.78rem; color: #666; margin-bottom: 6px; }
.room-amenities { display: flex; flex-wrap: wrap; gap: 6px; font-size: 0.75rem; color: #555; }
.room-amenities span { display: flex; align-items: center; gap: 3px; }
.room-price { margin-top: 8px; }
.price { font-size: 1.1rem; font-weight: 700; color: var(--color-primary, #2563eb); }
.price small { font-size: 0.75rem; font-weight: 400; color: #888; }
.total { font-size: 0.8rem; color: #888; margin-left: 8px; }
.room-action { color: #ccc; flex-shrink: 0; }
@media (max-width: 480px) {
  .form-row { grid-template-columns: 1fr; }
  .room-card { flex-wrap: wrap; }
  .room-image { width: 80px; height: 80px; }
}
</style>
