<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiAlertCircleOutline from '@iconify-icons/mdi/alert-circle-outline'
import mdiArrowRight from '@iconify-icons/mdi/arrow-right'
import mdiBedOutline from '@iconify-icons/mdi/bed-outline'
import mdiClose from '@iconify-icons/mdi/close'
import mdiClockOutline from '@iconify-icons/mdi/clock-outline'
import mdiMapMarkerOutline from '@iconify-icons/mdi/map-marker-outline'
import mdiMenu from '@iconify-icons/mdi/menu'
import mdiMoonWaningCrescent from '@iconify-icons/mdi/moon-waning-crescent'
import mdiPhoneOutline from '@iconify-icons/mdi/phone-outline'
import mdiRefresh from '@iconify-icons/mdi/refresh'
import mdiStarFourPointsOutline from '@iconify-icons/mdi/star-four-points-outline'
import mdiWhiteBalanceSunny from '@iconify-icons/mdi/white-balance-sunny'
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import SearchSelect from '../components/SearchSelect.vue'
import { useAppLocale } from '../composables/useAppLocale'
import { buildApiUrl } from '../lib/api'

type PortalFacility = {
  name: string
  icon: string | null
  description: string | null
  is_featured: boolean
}

type PortalRoomType = {
  code: string
  name: string
  description: string | null
  capacity: number
  base_price: number
  weekend_price: number
  available_rooms: number
  starting_from: number
  is_available: boolean
}

type PortalRecommendation = {
  title: string
  description: string
  tag: string
}

type PortalCmsNavItem = {
  label: string
  url: string
}

type PortalCmsDestination = {
  title: string
  subtitle: string
  image_url: string
}

type PortalCmsFilter = {
  title: string
  items: string[]
}

type PortalCmsFeaturedHotel = {
  brand: string
  name: string
  description: string
  image_url: string
  rating: string
  location: string
}

type PortalCms = {
  announcement_badge: string
  announcement_text: string
  announcement_link_label: string
  announcement_link_url: string
  nav_items: PortalCmsNavItem[]
  hero_title: string
  hero_subtitle: string
  hero_image_url: string
  hero_search_destination_label: string
  hero_search_destination_value: string
  hero_search_date_label: string
  hero_search_date_value: string
  hero_search_room_label: string
  hero_search_room_value: string
  hero_search_button_label: string
  destinations_title: string
  destinations: PortalCmsDestination[]
  explore_title: string
  explore_filters: PortalCmsFilter[]
  featured_hotels: PortalCmsFeaturedHotel[]
  cta_title: string
  cta_description: string
  cta_primary_label: string
}

type PortalResponse = {
  property: {
    code: string
    name: string
    address: string | null
    phone: string | null
    email: string | null
    timezone: string
    currency: string
  }
  branding: {
    app_name: string
    tagline: string
    hero_title: string
    hero_description: string
    check_in_time: string
    check_out_time: string
    primary_color: string
  }
  cms: PortalCms
  facilities: PortalFacility[]
  available_room_types: PortalRoomType[]
  recommendations: PortalRecommendation[]
  summary: {
    available_rooms: number
    occupied_rooms: number
    featured_facilities: number
  }
}

type PortalInquiryForm = {
  room_type_code: string
  full_name: string
  phone: string
  email: string
  check_in_date: string
  check_out_date: string
  guest_count: number
  notes: string
}

const route = useRoute()
const { isEnglish, language } = useAppLocale()
const loading = ref(true)
const errorMessage = ref('')
const portal = ref<PortalResponse | null>(null)
const clockTick = ref(Date.now())
const isInquiryModalOpen = ref(false)
const inquirySubmitting = ref(false)
const inquiryErrorMessage = ref('')
const inquirySuccessMessage = ref('')
const selectedRoomTypeName = ref('')
const isMobileMenuOpen = ref(false)
const themePreference = ref<'light' | 'dark' | null>(null)
let clockInterval: number | undefined

const propertyCode = computed(() => String(route.params.propertyCode ?? 'main'))
const portalThemeStorageKey = computed(() => `booking-portal-theme:${propertyCode.value}`)
const copy = computed(() => {
  if (isEnglish.value) {
    return {
      loadingPortal: 'Loading portal...',
      loadingPortalHint: 'Preparing stay data, destinations, and landing page content.',
      portalUnavailable: 'The portal cannot be displayed yet',
      retry: 'Try again',
      darkMode: 'Dark mode',
      lightMode: 'Light mode',
      signInRegister: 'Sign in / Register',
      quickNavigation: 'Quick navigation',
      switchToLight: 'Switch to light mode',
      switchToDark: 'Switch to dark mode',
      nextDestination: 'Your next destination',
      searchBy: 'Browse by',
      contentFilters: 'Content filters',
      exploreStay: 'Explore stay',
      facilities: 'Facilities',
      facilitiesTitle: 'What guests look for before booking',
      facilityFallback: 'Hotel facilities are available to support the guest stay.',
      roomsAvailable: 'Available rooms',
      roomsAvailableTitle: 'Room types ready to book now',
      roomsAvailableCount: 'rooms available',
      soldOut: 'Currently full',
      upToGuests: 'Up to',
      guests: 'guests',
      weekday: 'Weekday',
      weekend: 'Weekend',
      inquiryThisRoom: 'Inquiry this room',
      askAlternative: 'Ask for alternatives',
      recommendations: 'Recommendations',
      recommendationsTitle: 'Quick highlights from active hotel data',
      contactBooking: 'Contact & booking',
      contactUnavailable: 'Contact not available yet',
      addressUnavailable: 'Property address not configured yet',
      backToTop: 'Back to top',
      bookingInquiry: 'Booking inquiry',
      sendStayRequest: 'Send a stay request',
      selectRoomTypePrompt: 'Choose the room type you want to ask about.',
      inquirySuccess: 'Inquiry sent successfully',
      close: 'Close',
      inquiryFailed: 'Inquiry not sent yet',
      roomType: 'Room type',
      selectRoomType: 'Choose a room type',
      searchRoomType: 'Search room type...',
      guestCount: 'Guest count',
      fullName: 'Full name',
      fullNamePlaceholder: 'Guest name',
      phone: 'WhatsApp / phone number',
      phonePlaceholder: '08xxxxxxxxxx',
      email: 'Email',
      emailPlaceholder: 'name@email.com',
      quickFollowUp: 'Quick follow-up',
      quickFollowUpHint: 'The property team will contact you to confirm rates, availability, and stay details.',
      checkinDate: 'Check-in date',
      checkoutDate: 'Check-out date',
      additionalNotes: 'Additional notes',
      additionalNotesPlaceholder: 'Example: non-smoking, high floor, late arrival, or other preferences.',
      cancel: 'Cancel',
      sending: 'Sending...',
      sendInquiry: 'Send inquiry',
      roomAvailableDesc: 'rooms available',
      roomFullDesc: 'Currently full',
      greetingMorning: 'Good morning',
      greetingNoon: 'Good afternoon',
      greetingEvening: 'Good evening',
      greetingNight: 'Good evening',
      quickStats: ['Available rooms', 'Featured facilities', 'Check-in / out'],
      loadFailed: 'Failed to load portal.',
      inquiryFailedMessage: 'Inquiry submission failed.',
    }
  }

  return {
    loadingPortal: 'Loading portal...',
    loadingPortalHint: 'Menyiapkan data stay, destinasi, dan konten landing page.',
    portalUnavailable: 'Portal belum bisa ditampilkan',
    retry: 'Coba lagi',
    darkMode: 'Dark mode',
    lightMode: 'Light mode',
    signInRegister: 'Masuk / Registrasi',
    quickNavigation: 'Quick navigation',
    switchToLight: 'Ubah ke light mode',
    switchToDark: 'Ubah ke dark mode',
    nextDestination: 'Destinasi Anda berikutnya',
    searchBy: 'Cari berdasarkan',
    contentFilters: 'Filter konten',
    exploreStay: 'Explore stay',
    facilities: 'Fasilitas',
    facilitiesTitle: 'Yang paling dicari tamu sebelum booking',
    facilityFallback: 'Fasilitas hotel tersedia untuk menunjang stay tamu.',
    roomsAvailable: 'Kamar tersedia',
    roomsAvailableTitle: 'Pilihan room type yang siap dipesan sekarang',
    roomsAvailableCount: 'kamar tersedia',
    soldOut: 'Sedang penuh',
    upToGuests: 'Hingga',
    guests: 'tamu',
    weekday: 'Weekday',
    weekend: 'Weekend',
    inquiryThisRoom: 'Inquiry kamar ini',
    askAlternative: 'Tanya alternatif',
    recommendations: 'Rekomendasi',
    recommendationsTitle: 'Highlight cepat dari data hotel yang aktif',
    contactBooking: 'Contact & booking',
    contactUnavailable: 'Kontak belum tersedia',
    addressUnavailable: 'Alamat properti belum diatur',
    backToTop: 'Kembali ke atas',
    bookingInquiry: 'Booking inquiry',
    sendStayRequest: 'Kirim permintaan stay',
    selectRoomTypePrompt: 'Pilih room type yang ingin Anda tanyakan.',
    inquirySuccess: 'Inquiry berhasil dikirim',
    close: 'Tutup',
    inquiryFailed: 'Inquiry belum terkirim',
    roomType: 'Room type',
    selectRoomType: 'Pilih room type',
    searchRoomType: 'Cari room type...',
    guestCount: 'Jumlah tamu',
    fullName: 'Nama lengkap',
    fullNamePlaceholder: 'Nama tamu',
    phone: 'No. WhatsApp / telepon',
    phonePlaceholder: '08xxxxxxxxxx',
    email: 'Email',
    emailPlaceholder: 'nama@email.com',
    quickFollowUp: 'Follow up cepat',
    quickFollowUpHint: 'Tim properti akan menghubungi Anda untuk konfirmasi rate, availability, dan detail stay.',
    checkinDate: 'Tanggal check-in',
    checkoutDate: 'Tanggal check-out',
    additionalNotes: 'Catatan tambahan',
    additionalNotesPlaceholder: 'Contoh: non-smoking, high floor, late arrival, atau kebutuhan lainnya.',
    cancel: 'Batal',
    sending: 'Mengirim...',
    sendInquiry: 'Kirim inquiry',
    roomAvailableDesc: 'kamar tersedia',
    roomFullDesc: 'Sedang penuh',
    greetingMorning: 'Selamat pagi',
    greetingNoon: 'Selamat siang',
    greetingEvening: 'Selamat sore',
    greetingNight: 'Selamat malam',
    quickStats: ['Kamar tersedia', 'Fasilitas unggulan', 'Check-in / out'],
    loadFailed: 'Portal gagal dimuat.',
    inquiryFailedMessage: 'Inquiry gagal dikirim.',
  }
})

const formatDateInput = (date: Date) => date.toISOString().slice(0, 10)

const createDefaultInquiryForm = (): PortalInquiryForm => ({
  room_type_code: '',
  full_name: '',
  phone: '',
  email: '',
  check_in_date: formatDateInput(new Date(Date.now() + 86_400_000)),
  check_out_date: formatDateInput(new Date(Date.now() + 172_800_000)),
  guest_count: 2,
  notes: '',
})

const inquiryForm = reactive<PortalInquiryForm>(createDefaultInquiryForm())

const currencyFormatter = computed(() =>
  new Intl.NumberFormat(language.value === 'en' ? 'en-US' : 'id-ID', {
    style: 'currency',
    currency: portal.value?.property.currency ?? 'IDR',
    maximumFractionDigits: 0,
  }),
)

const formatPrice = (amount: number) => currencyFormatter.value.format(amount)

const formatterWithTimezone = (options: Intl.DateTimeFormatOptions) =>
  new Intl.DateTimeFormat(language.value === 'en' ? 'en-US' : 'id-ID', {
    timeZone: portal.value?.property.timezone ?? 'Asia/Jakarta',
    ...options,
  }).format(new Date(clockTick.value))

const currentHour = computed(() => Number(
  new Intl.DateTimeFormat('en-GB', {
    hour: '2-digit',
    hour12: false,
    timeZone: portal.value?.property.timezone ?? 'Asia/Jakarta',
  }).format(new Date(clockTick.value)),
))

const isNightTheme = computed(() => currentHour.value >= 18 || currentHour.value < 6)
const currentTheme = computed(() => themePreference.value ?? (isNightTheme.value ? 'dark' : 'light'))
const isDarkMode = computed(() => currentTheme.value === 'dark')

const localTimeLabel = computed(() =>
  formatterWithTimezone({
    weekday: 'long',
    hour: '2-digit',
    minute: '2-digit',
  }),
)

const greeting = computed(() => {
  if (currentHour.value < 11) {
    return copy.value.greetingMorning
  }

  if (currentHour.value < 15) {
    return copy.value.greetingNoon
  }

  if (currentHour.value < 18) {
    return copy.value.greetingEvening
  }

  return copy.value.greetingNight
})

const portalStyle = computed(() => ({
  '--portal-accent': isDarkMode.value ? '#F8DE22' : (portal.value?.branding.primary_color ?? '#2563eb'),
}))

const heroMediaStyle = computed(() => {
  const imageUrl = portal.value?.cms.hero_image_url?.trim()

  if (imageUrl) {
    return {
      backgroundImage: `linear-gradient(115deg, rgba(11, 17, 32, 0.72), rgba(11, 17, 32, 0.18)), url(${imageUrl})`,
    }
  }

  return {
    backgroundImage:
      'radial-gradient(circle at top left, rgba(255, 255, 255, 0.18), transparent 22%), linear-gradient(135deg, rgba(54, 87, 170, 0.92), rgba(29, 49, 102, 0.94) 52%, rgba(15, 23, 42, 0.96))',
  }
})

const searchHighlights = computed(() => {
  if (!portal.value) {
    return []
  }

  return [
    {
      label: portal.value.cms.hero_search_destination_label,
      value: portal.value.cms.hero_search_destination_value,
    },
    {
      label: portal.value.cms.hero_search_date_label,
      value: portal.value.cms.hero_search_date_value,
    },
    {
      label: portal.value.cms.hero_search_room_label,
      value: portal.value.cms.hero_search_room_value,
    },
  ]
})

const quickStats = computed(() => {
  if (!portal.value) {
    return []
  }

  return [
    {
      label: copy.value.quickStats[0],
      value: String(portal.value.summary.available_rooms),
    },
    {
      label: copy.value.quickStats[1],
      value: String(portal.value.summary.featured_facilities),
    },
    {
      label: copy.value.quickStats[2],
      value: `${portal.value.branding.check_in_time} / ${portal.value.branding.check_out_time}`,
    },
  ]
})

const contactHref = computed(() => {
  if (!portal.value?.property.phone) {
    return '#contact'
  }

  return `tel:${portal.value.property.phone}`
})

const inquiryRoomType = computed(() =>
  portal.value?.available_room_types.find((roomType) => roomType.code === inquiryForm.room_type_code) ?? null,
)

const inquiryRoomTypeOptions = computed(() =>
  (portal.value?.available_room_types ?? []).map((roomType) => ({
    value: roomType.code,
    label: `${roomType.name} · ${formatPrice(roomType.starting_from)}`,
    description: roomType.is_available
      ? `${roomType.available_rooms} ${copy.value.roomAvailableDesc} · kapasitas ${roomType.capacity} ${copy.value.guests}`
      : `${copy.value.roomFullDesc} · kapasitas ${roomType.capacity} ${copy.value.guests}`,
  })),
)

const resetInquiryState = () => {
  inquiryErrorMessage.value = ''
  inquirySuccessMessage.value = ''
}

const openInquiryModal = (roomType?: PortalRoomType) => {
  const preferredRoomType = roomType
    ?? portal.value?.available_room_types.find((item) => item.is_available)
    ?? portal.value?.available_room_types[0]

  if (!preferredRoomType) {
    return
  }

  inquiryForm.room_type_code = preferredRoomType.code
  inquiryForm.check_in_date = formatDateInput(new Date(Date.now() + 86_400_000))
  inquiryForm.check_out_date = formatDateInput(new Date(Date.now() + 172_800_000))
  inquiryForm.guest_count = Math.min(Math.max(inquiryForm.guest_count || 2, 1), Math.max(preferredRoomType.capacity, 1))
  inquiryForm.notes = ''
  selectedRoomTypeName.value = preferredRoomType.name
  resetInquiryState()
  isInquiryModalOpen.value = true
}

const closeInquiryModal = () => {
  if (inquirySubmitting.value) {
    return
  }

  isInquiryModalOpen.value = false
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
}

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const toggleTheme = () => {
  themePreference.value = isDarkMode.value ? 'light' : 'dark'

  if (typeof window !== 'undefined') {
    window.localStorage.setItem(portalThemeStorageKey.value, themePreference.value)
  }
}

const submitInquiry = async () => {
  if (!portal.value) {
    return
  }

  inquirySubmitting.value = true
  inquiryErrorMessage.value = ''
  inquirySuccessMessage.value = ''

  try {
    const response = await fetch(buildApiUrl(`/api/v1/portal/${propertyCode.value}/inquiries`), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(inquiryForm),
    })

    const payload = (await response.json()) as {
      success: boolean
      message: string
      errors?: Record<string, string[]>
    }

    if (!response.ok || !payload.success) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || copy.value.inquiryFailedMessage)
    }

    inquirySuccessMessage.value = payload.message
    inquiryForm.full_name = ''
    inquiryForm.phone = ''
    inquiryForm.email = ''
    inquiryForm.notes = ''
  } catch (error) {
    inquiryErrorMessage.value = error instanceof Error ? error.message : copy.value.inquiryFailedMessage
  } finally {
    inquirySubmitting.value = false
  }
}

const loadPortal = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await fetch(buildApiUrl(`/api/v1/portal/${propertyCode.value}`))
    const payload = (await response.json()) as { success: boolean; data: PortalResponse; message: string }

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || copy.value.loadFailed)
    }

    portal.value = payload.data

    if (!inquiryForm.room_type_code) {
      const defaultRoomType = payload.data.available_room_types.find((roomType) => roomType.is_available) ?? payload.data.available_room_types[0]

      if (defaultRoomType) {
        inquiryForm.room_type_code = defaultRoomType.code
        selectedRoomTypeName.value = defaultRoomType.name
      }
    }
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : copy.value.loadFailed
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  const storedTheme = window.localStorage.getItem(portalThemeStorageKey.value)

  if (storedTheme === 'light' || storedTheme === 'dark') {
    themePreference.value = storedTheme
  }

  loadPortal()
  clockInterval = window.setInterval(() => {
    clockTick.value = Date.now()
  }, 60_000)
})

onUnmounted(() => {
  if (clockInterval) {
    window.clearInterval(clockInterval)
  }
})

watch(propertyCode, () => {
  isInquiryModalOpen.value = false
  closeMobileMenu()
  const storedTheme = window.localStorage.getItem(portalThemeStorageKey.value)
  themePreference.value = storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : null
  loadPortal()
})

watch(inquiryRoomType, (roomType) => {
  selectedRoomTypeName.value = roomType?.name ?? ''
})
</script>

<template>
  <div id="top" class="portal-page portal-page--booking" :data-theme="isDarkMode ? 'night' : 'day'" :style="portalStyle">
    <div v-if="loading" class="portal-state-card">
      <strong>{{ copy.loadingPortal }}</strong>
      <span>{{ copy.loadingPortalHint }}</span>
    </div>

    <div v-else-if="errorMessage" class="portal-state-card portal-state-card--error">
      <Icon :icon="mdiAlertCircleOutline" />
      <strong>{{ copy.portalUnavailable }}</strong>
      <span>{{ errorMessage }}</span>
      <button type="button" class="secondary-button" @click="loadPortal">
        <Icon :icon="mdiRefresh" />
        {{ copy.retry }}
      </button>
    </div>

    <template v-else-if="portal">
      <!-- <div class="portal-announcement">
        <div class="portal-announcement__copy">
          <strong>{{ portal.cms.announcement_badge }}</strong>
          <span>{{ portal.cms.announcement_text }}</span>
        </div>
        <a :href="portal.cms.announcement_link_url || '#explore'">{{ portal.cms.announcement_link_label }}</a>
      </div> -->

      <header class="portal-header">
        <div class="portal-brand">
          <div class="portal-brand__mark">{{ portal.branding.app_name.slice(0, 2).toUpperCase() }}</div>
          <div>
            <strong>{{ portal.branding.app_name }}</strong>
            <span>{{ greeting }} · {{ localTimeLabel }}</span>
          </div>
        </div>

        <nav class="portal-nav">
          <a v-for="item in portal.cms.nav_items" :key="`${item.label}-${item.url}`" :href="item.url" @click="closeMobileMenu">{{ item.label }}</a>
        </nav>

        <div class="portal-header__actions">
          <button type="button" class="portal-theme-switch" @click="toggleTheme">
            <span class="portal-theme-switch__track">
              <span class="portal-theme-switch__thumb">
                <Icon :icon="isDarkMode ? mdiMoonWaningCrescent : mdiWhiteBalanceSunny" />
              </span>
            </span>
            <span class="portal-theme-switch__label">{{ isDarkMode ? copy.darkMode : copy.lightMode }}</span>
          </button>
          <span class="portal-header__currency">{{ portal.property.currency }}</span>
          <a href="/login" class="portal-login-button">{{ copy.signInRegister }}</a>
          <button type="button" class="portal-menu-button" :aria-expanded="isMobileMenuOpen" @click="toggleMobileMenu">
            <Icon :icon="isMobileMenuOpen ? mdiClose : mdiMenu" />
          </button>
        </div>
      </header>

      <div v-if="isMobileMenuOpen" class="portal-mobile-nav-backdrop" @click="closeMobileMenu"></div>

      <section v-if="isMobileMenuOpen" class="portal-mobile-nav-card">
        <div class="portal-mobile-nav-card__top">
          <div>
            <span class="portal-kicker">{{ copy.quickNavigation }}</span>
            <h2>{{ portal.property.name }}</h2>
          </div>
          <span class="portal-header__currency">{{ portal.property.currency }}</span>
        </div>

        <nav class="portal-mobile-nav-list">
          <a
            v-for="item in portal.cms.nav_items"
            :key="`mobile-${item.label}-${item.url}`"
            :href="item.url"
            class="portal-mobile-nav-link"
            @click="closeMobileMenu"
          >
            <span>{{ item.label }}</span>
            <Icon :icon="mdiArrowRight" />
          </a>
        </nav>

        <div class="portal-mobile-nav-card__footer">
          <button type="button" class="portal-theme-switch portal-theme-switch--panel" @click="toggleTheme">
            <span class="portal-theme-switch__track">
              <span class="portal-theme-switch__thumb">
                <Icon :icon="isDarkMode ? mdiMoonWaningCrescent : mdiWhiteBalanceSunny" />
              </span>
            </span>
            <span class="portal-theme-switch__label">{{ isDarkMode ? copy.switchToLight : copy.switchToDark }}</span>
          </button>
          <a href="/login" class="portal-login-button portal-login-button--wide">{{ copy.signInRegister }}</a>
        </div>
      </section>

      <section class="portal-landing">
        <div class="portal-landing__hero" :style="heroMediaStyle">
          <div class="portal-landing__copy">
            <span class="portal-kicker">{{ copy.nextDestination }}</span>
            <h1>{{ portal.cms.hero_title }}</h1>
            <p>{{ portal.cms.hero_subtitle }}</p>
          </div>

          <div class="portal-landing__stats">
            <div v-for="stat in quickStats" :key="stat.label" class="portal-landing__stat">
              <span>{{ stat.label }}</span>
              <strong>{{ stat.value }}</strong>
            </div>
          </div>
        </div>

        <div class="portal-search-panel">
          <div class="portal-search-panel__grid">
            <div v-for="item in searchHighlights" :key="item.label" class="portal-search-field">
              <span>{{ item.label }}</span>
              <strong>{{ item.value }}</strong>
            </div>
            <button type="button" class="portal-search-button" @click="openInquiryModal()">
              {{ portal.cms.hero_search_button_label }}
              <Icon :icon="mdiArrowRight" />
            </button>
          </div>
        </div>
      </section>

      <section id="destinations" class="portal-section-block">
        <div class="portal-section-block__header">
          <div>
            <span class="portal-kicker">Destinasi</span>
            <h2>{{ portal.cms.destinations_title }}</h2>
          </div>
        </div>

        <div class="portal-destination-grid">
          <article v-for="destination in portal.cms.destinations" :key="destination.title" class="portal-destination-card">
            <div
              class="portal-destination-card__visual"
              :style="destination.image_url ? { backgroundImage: `linear-gradient(180deg, rgba(15, 23, 42, 0.06), rgba(15, 23, 42, 0.52)), url(${destination.image_url})` } : undefined"
            >
              <span>{{ destination.title }}</span>
            </div>
            <div class="portal-destination-card__body">
              <strong>{{ destination.title }}</strong>
              <p>{{ destination.subtitle }}</p>
            </div>
          </article>
        </div>
      </section>

      <section id="explore" class="portal-explore-layout">
        <aside class="portal-explore-sidebar">
          <div class="portal-explore-sidebar__header">
            <span class="portal-kicker">{{ copy.searchBy }}</span>
            <h2>{{ copy.contentFilters }}</h2>
          </div>

          <div class="portal-filter-group" v-for="filter in portal.cms.explore_filters" :key="filter.title">
            <strong>{{ filter.title }}</strong>
            <div class="portal-filter-group__items">
              <span v-for="item in filter.items" :key="item" class="portal-filter-chip">{{ item }}</span>
            </div>
          </div>
        </aside>

        <div class="portal-explore-main">
          <div class="portal-section-block__header">
            <div>
              <span class="portal-kicker">{{ copy.exploreStay }}</span>
              <h2>{{ portal.cms.explore_title }}</h2>
            </div>
          </div>

          <div class="portal-hotel-grid">
            <article v-for="item in portal.cms.featured_hotels" :key="`${item.brand}-${item.name}`" class="portal-hotel-card">
              <div
                class="portal-hotel-card__visual"
                :style="item.image_url ? { backgroundImage: `linear-gradient(180deg, rgba(15, 23, 42, 0.08), rgba(15, 23, 42, 0.58)), url(${item.image_url})` } : undefined"
              >
                <span class="portal-hotel-card__brand">{{ item.brand }}</span>
              </div>
              <div class="portal-hotel-card__body">
                <div>
                  <h3>{{ item.name }}</h3>
                  <p>{{ item.description }}</p>
                </div>
                <div class="portal-hotel-card__meta">
                  <span>{{ item.location }}</span>
                  <strong>{{ item.rating }}</strong>
                </div>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section id="facilities" class="portal-section-block">
        <div class="portal-section-block__header">
          <div>
            <span class="portal-kicker">{{ copy.facilities }}</span>
            <h2>{{ copy.facilitiesTitle }}</h2>
          </div>
        </div>

        <div class="portal-facility-grid portal-facility-grid--compact">
          <article v-for="facility in portal.facilities" :key="facility.name" class="portal-facility-card portal-facility-card--modern">
            <span class="portal-facility-card__icon">
              <Icon :icon="facility.icon || mdiStarFourPointsOutline" />
            </span>
            <strong>{{ facility.name }}</strong>
            <p>{{ facility.description || copy.facilityFallback }}</p>
          </article>
        </div>
      </section>

      <section id="rooms" class="portal-section-block">
        <div class="portal-section-block__header">
          <div>
            <span class="portal-kicker">{{ copy.roomsAvailable }}</span>
            <h2>{{ copy.roomsAvailableTitle }}</h2>
          </div>
        </div>

        <div class="portal-room-grid">
          <article v-for="roomType in portal.available_room_types" :key="roomType.code" class="portal-room-card portal-room-card--listing">
            <div class="portal-room-card__top">
              <span class="portal-room-card__badge" :class="{ 'portal-room-card__badge--muted': !roomType.is_available }">
                {{ roomType.is_available ? `${roomType.available_rooms} ${copy.roomsAvailableCount}` : copy.soldOut }}
              </span>
              <span class="portal-room-card__code">{{ roomType.code }}</span>
            </div>

            <div class="portal-room-card__body">
              <div>
                <h3>{{ roomType.name }}</h3>
                <p>{{ roomType.description }}</p>
              </div>

              <div class="portal-room-card__meta">
                <span>
                  <Icon :icon="mdiBedOutline" />
                  {{ copy.upToGuests }} {{ roomType.capacity }} {{ copy.guests }}
                </span>
                <strong>{{ formatPrice(roomType.starting_from) }}</strong>
              </div>
            </div>

            <div class="portal-room-card__footer">
              <span>{{ copy.weekday }} {{ formatPrice(roomType.base_price) }}</span>
              <span>{{ copy.weekend }} {{ formatPrice(roomType.weekend_price) }}</span>
            </div>

            <div class="portal-room-card__actions">
              <button type="button" class="primary-button portal-room-card__button" @click="openInquiryModal(roomType)">
                {{ roomType.is_available ? copy.inquiryThisRoom : copy.askAlternative }}
              </button>
            </div>
          </article>
        </div>
      </section>

      <section class="portal-section-block">
        <div class="portal-section-block__header">
          <div>
            <span class="portal-kicker">{{ copy.recommendations }}</span>
            <h2>{{ copy.recommendationsTitle }}</h2>
          </div>
        </div>

        <div class="portal-recommendation-list portal-recommendation-list--modern">
          <article v-for="recommendation in portal.recommendations" :key="recommendation.title" class="portal-recommendation portal-recommendation--modern">
            <span class="portal-recommendation__tag">{{ recommendation.tag }}</span>
            <strong>{{ recommendation.title }}</strong>
            <p>{{ recommendation.description }}</p>
          </article>
        </div>
      </section>

      <section id="contact" class="portal-footer-cta">
        <div class="portal-footer-cta__copy">
          <span class="portal-kicker">{{ copy.contactBooking }}</span>
          <h2>{{ portal.cms.cta_title }}</h2>
          <p>{{ portal.cms.cta_description }}</p>

          <div class="portal-contact-strip">
            <span>
              <Icon :icon="mdiPhoneOutline" />
              {{ portal.property.phone || copy.contactUnavailable }}
            </span>
            <span>
              <Icon :icon="mdiMapMarkerOutline" />
              {{ portal.property.address || copy.addressUnavailable }}
            </span>
            <span>
              <Icon :icon="mdiClockOutline" />
              {{ portal.branding.check_in_time }} / {{ portal.branding.check_out_time }}
            </span>
          </div>
        </div>

        <div class="portal-footer-cta__actions">
          <a :href="contactHref" class="primary-button">{{ portal.cms.cta_primary_label }}</a>
          <a href="#top" class="secondary-button">
            {{ copy.backToTop }}
            <Icon :icon="mdiArrowRight" />
          </a>
        </div>
      </section>

      <div v-if="isInquiryModalOpen" class="portal-modal-backdrop" @click="closeInquiryModal"></div>

      <section v-if="isInquiryModalOpen" class="portal-modal-shell">
        <article class="portal-modal" @click.stop>
          <div class="portal-modal__header">
            <div>
              <span class="portal-kicker">{{ copy.bookingInquiry }}</span>
              <h2>{{ copy.sendStayRequest }}</h2>
              <p>{{ selectedRoomTypeName || inquiryRoomType?.name || copy.selectRoomTypePrompt }}</p>
            </div>

            <button type="button" class="icon-button" :disabled="inquirySubmitting" @click="closeInquiryModal">
              <Icon :icon="mdiClose" />
            </button>
          </div>

          <div v-if="inquirySuccessMessage" class="portal-feedback portal-feedback--success">
            <strong>{{ copy.inquirySuccess }}</strong>
            <p>{{ inquirySuccessMessage }}</p>
            <button type="button" class="primary-button" @click="closeInquiryModal">{{ copy.close }}</button>
          </div>

          <form v-else class="portal-modal__body" @submit.prevent="submitInquiry">
            <div v-if="inquiryErrorMessage" class="portal-feedback portal-feedback--error">
              <strong>{{ copy.inquiryFailed }}</strong>
              <p>{{ inquiryErrorMessage }}</p>
            </div>

            <div class="portal-form-grid portal-form-grid--two">
              <label class="field">
                <span>{{ copy.roomType }}</span>
                <SearchSelect
                  v-model="inquiryForm.room_type_code"
                  :options="inquiryRoomTypeOptions"
                  :placeholder="copy.selectRoomType"
                  :search-placeholder="copy.searchRoomType"
                />
              </label>

              <label class="field">
                <span>{{ copy.guestCount }}</span>
                <input v-model.number="inquiryForm.guest_count" min="1" max="8" type="number" class="portal-form-input" />
              </label>
            </div>

            <div class="portal-form-grid portal-form-grid--two">
              <label class="field">
                <span>{{ copy.fullName }}</span>
                <input v-model="inquiryForm.full_name" type="text" class="portal-form-input" :placeholder="copy.fullNamePlaceholder" />
              </label>

              <label class="field">
                <span>{{ copy.phone }}</span>
                <input v-model="inquiryForm.phone" type="text" class="portal-form-input" :placeholder="copy.phonePlaceholder" />
              </label>
            </div>

            <div class="portal-form-grid portal-form-grid--two">
              <label class="field">
                <span>{{ copy.email }}</span>
                <input v-model="inquiryForm.email" type="email" class="portal-form-input" :placeholder="copy.emailPlaceholder" />
              </label>

              <div class="portal-inline-note">
                <strong>{{ copy.quickFollowUp }}</strong>
                <p>{{ copy.quickFollowUpHint }}</p>
              </div>
            </div>

            <div class="portal-form-grid portal-form-grid--two">
              <label class="field">
                <span>{{ copy.checkinDate }}</span>
                <input v-model="inquiryForm.check_in_date" type="date" class="portal-form-input" />
              </label>

              <label class="field">
                <span>{{ copy.checkoutDate }}</span>
                <input v-model="inquiryForm.check_out_date" type="date" class="portal-form-input" />
              </label>
            </div>

            <label class="field">
              <span>{{ copy.additionalNotes }}</span>
              <textarea
                v-model="inquiryForm.notes"
                class="portal-form-textarea"
                :placeholder="copy.additionalNotesPlaceholder"
              ></textarea>
            </label>

            <div class="portal-modal__actions">
              <button type="button" class="secondary-button" :disabled="inquirySubmitting" @click="closeInquiryModal">
                {{ copy.cancel }}
              </button>
              <button type="submit" class="primary-button" :disabled="inquirySubmitting">
                {{ inquirySubmitting ? copy.sending : copy.sendInquiry }}
              </button>
            </div>
          </form>
        </article>
      </section>
    </template>
  </div>
</template>
