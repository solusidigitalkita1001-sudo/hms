<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiAccountCogOutline from '@iconify-icons/mdi/account-cog-outline'
import mdiAccountEditOutline from '@iconify-icons/mdi/account-edit-outline'
import mdiArrowTopRight from '@iconify-icons/mdi/arrow-top-right'
import mdiCameraOutline from '@iconify-icons/mdi/camera-outline'
import mdiChevronDown from '@iconify-icons/mdi/chevron-down'
import mdiChevronRight from '@iconify-icons/mdi/chevron-right'
import mdiClose from '@iconify-icons/mdi/close'
import mdiFormTextboxPassword from '@iconify-icons/mdi/form-textbox-password'
import mdiInformationOutline from '@iconify-icons/mdi/information-outline'
import mdiLightningBoltOutline from '@iconify-icons/mdi/lightning-bolt-outline'
import mdiLogoutVariant from '@iconify-icons/mdi/logout-variant'
import mdiMagnify from '@iconify-icons/mdi/magnify'
import mdiMenu from '@iconify-icons/mdi/menu'
import mdiMoonWaningCrescent from '@iconify-icons/mdi/moon-waning-crescent'
import mdiShieldKeyOutline from '@iconify-icons/mdi/shield-key-outline'
import mdiStar from '@iconify-icons/mdi/star'
import mdiStarOutline from '@iconify-icons/mdi/star-outline'
import mdiViewGridOutline from '@iconify-icons/mdi/view-grid-outline'
import mdiWhiteBalanceSunny from '@iconify-icons/mdi/white-balance-sunny'
import type { IconifyIcon } from '@iconify/types'
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { getNavigationSections } from '../config/workspace'
import { useAppLocale } from '../composables/useAppLocale'
import { useAuthSession } from '../composables/useAuthSession'
import { useUiPreferences } from '../composables/useUiPreferences'
import { buildApiUrl } from '../lib/api'

type Metric = {
  label: string
  value: string
  tone?: 'primary' | 'success' | 'warning' | 'danger' | 'neutral'
}

type NavigationLinkEntry = {
  id: string
  kind: 'navigation'
  label: string
  description: string
  to: string
  parentLabel: string
  icon: IconifyIcon | string
  keywords: string[]
}

type PaletteActionEntry = {
  id: string
  kind: 'action'
  label: string
  description: string
  icon: IconifyIcon | string
  keywords: string[]
  execute: () => void | Promise<void>
}

type PaletteEntry = NavigationLinkEntry | PaletteActionEntry

type StoredAdminNavigationState = {
  favorites: string[]
}

type AccountTab = 'information' | 'password'

const props = withDefaults(defineProps<{
  title: string
  summary?: string
  metrics?: Metric[]
  eyebrow?: string
  heroVariant?: 'card' | 'plain'
  showHeaderInfo?: boolean
}>(), {
  eyebrow: 'WPA / Hotel Management System',
  metrics: () => [],
  summary: '',
  heroVariant: 'plain',
  showHeaderInfo: false,
})

const route = useRoute()
const router = useRouter()
const mobileMenuOpen = ref(false)
const expandedGroupLabels = ref<string[]>([])
const commandPaletteOpen = ref(false)
const profileMenuOpen = ref(false)
const accountModalOpen = ref(false)
const accountTab = ref<AccountTab>('information')
const commandQuery = ref('')
const commandInput = ref<HTMLInputElement | null>(null)
const avatarInput = ref<HTMLInputElement | null>(null)
const profileMenuRoot = ref<HTMLElement | null>(null)
const navigationStorageKey = 'booking.wpa.admin-navigation'
const { state, isSidebar, toggleSidebar, toggleThemeMode, updateLanguage } = useUiPreferences()
const { language } = useAppLocale()
const authSession = useAuthSession()
const isDarkMode = computed(() => state.themeMode === 'dark')
const isEnglish = computed(() => state.language === 'en')
const accountSaving = ref(false)
const passwordSaving = ref(false)
const avatarUploading = ref(false)
const accountErrorMessage = ref('')
const accountSuccessMessage = ref('')

const informationForm = reactive({
  name: '',
  username: '',
  email: '',
  avatarUrl: '',
})

const passwordForm = reactive({
  currentPassword: '',
  password: '',
  passwordConfirmation: '',
})

const storedNavigationState = (() => {
  const raw = globalThis.localStorage?.getItem(navigationStorageKey)

  if (!raw) {
    return {
      favorites: [],
    } satisfies StoredAdminNavigationState
  }

  try {
    const parsed = JSON.parse(raw) as Partial<StoredAdminNavigationState>

    return {
      favorites: Array.isArray(parsed.favorites) ? parsed.favorites.filter((item): item is string => typeof item === 'string') : [],
    } satisfies StoredAdminNavigationState
  } catch {
    return {
      favorites: [],
    } satisfies StoredAdminNavigationState
  }
})()

const favoritePaths = ref<string[]>(storedNavigationState.favorites)
const navigationSections = computed(() => getNavigationSections(language.value))

const topNavigationItems = computed(() =>
  navigationSections.value.flatMap((section) => section.items.map((item) => item)),
)

const navigationLinkEntries = computed<NavigationLinkEntry[]>(() =>
  topNavigationItems.value.flatMap((item) => {
    const parentKeywords = [item.label, item.description]
    const childEntries = item.children?.map((child) => ({
      id: child.to,
      kind: 'navigation' as const,
      label: child.label,
      description: item.description,
      to: child.to,
      parentLabel: item.label,
      icon: item.icon,
      keywords: [child.label, item.label, item.description],
    })) ?? []

    if (childEntries.length > 0) {
      return childEntries
    }

    return [{
      id: item.to,
      kind: 'navigation' as const,
      label: item.label,
      description: item.description,
      to: item.to,
      parentLabel: item.label,
      icon: item.icon,
      keywords: parentKeywords,
    }]
  }),
)

const activeNavigationItem = computed(() =>
  topNavigationItems.value.find((item) =>
    item.children?.some((child) => route.path.startsWith(child.to)) ?? route.path === item.to,
  ),
)

const activeBreadcrumb = computed(() => {
  const activeChild = activeNavigationItem.value?.children?.find((child) => route.path === child.to)

  return {
    parent: activeNavigationItem.value?.label ?? 'Dashboard',
    child: activeChild?.label ?? props.title,
  }
})

const activeChildren = computed(() => activeNavigationItem.value?.children ?? [])

const activeSectionLabel = computed(() => activeBreadcrumb.value.parent)
const currentAvatarUrl = computed(() => authSession.state.user?.avatar_url?.trim() || '')
const topbarAvatarStyle = computed(() => (
  currentAvatarUrl.value
    ? { backgroundImage: `url(${currentAvatarUrl.value})` }
    : undefined
))
const copy = computed(() => {
  if (isEnglish.value) {
    return {
      adminConsole: 'Admin Console',
      internalHotelOps: 'Internal hotel operations',
      expandSidebar: 'Expand sidebar',
      collapseSidebar: 'Collapse sidebar',
      currentWorkspace: 'Current workspace',
      compactNavigation: 'Compact navigation enabled',
      fullSidebar: 'Full sidebar for fast admin flow',
      favorites: 'Favorites',
      favorite: 'Favorite',
      unfavorite: 'Unfavorite',
      expand: 'Expand',
      collapse: 'Collapse',
      guestUser: 'Guest User',
      compactMode: 'Compact mode',
      adminNavigationReady: 'Admin navigation ready',
      searchPlaceholder: 'Search bookings, guests, rooms, invoices, or admin pages...',
      darkMode: 'Dark mode',
      lightMode: 'Light mode',
      information: 'Information',
      informationDesc: 'Update name, email, username, and avatar',
      changePassword: 'Change password',
      changePasswordDesc: 'Keep the admin account secure',
      logout: 'Log out',
      logoutDesc: 'Sign out from the internal dashboard',
      commandPalette: 'Admin command palette',
      commandPaletteTitle: 'Search admin pages, actions, and shortcuts',
      commandPalettePlaceholder: 'Search menu, favorite, or quick action...',
      noResults: 'No results for this search',
      noResultsHint: 'Try keywords like arrival, settings, inventory, or theme.',
      adminAccount: 'Admin account',
      manageAccount: 'Manage profile and account security',
      manageAccountHint: 'This menu is separated from the main navbar so the account area feels focused and tidy.',
      profileInformation: 'Profile information',
      updateIdentity: 'Update admin identity',
      profilePhoto: 'Admin profile photo',
      profilePhotoHint: 'Use a clear square photo so the account identity is easy to recognize.',
      uploadPhoto: 'Upload photo',
      uploading: 'Uploading...',
      fullName: 'Full name',
      fullNamePlaceholder: 'Admin full name',
      username: 'Username',
      usernamePlaceholder: 'Login username',
      email: 'Email',
      emailPlaceholder: 'Active email',
      interfaceLanguage: 'Interface language',
      interfaceLanguageHint: 'Choose the primary language for admin shell and account settings.',
      bahasaIndonesia: 'Bahasa Indonesia',
      english: 'English',
      reset: 'Reset',
      saving: 'Saving...',
      saveInformation: 'Save information',
      passwordSecurity: 'Password security',
      changeAdminPassword: 'Change admin account password',
      currentPassword: 'Current password',
      currentPasswordPlaceholder: 'Enter current password',
      newPassword: 'New password',
      newPasswordPlaceholder: 'Minimum 8 characters',
      confirmNewPassword: 'Confirm new password',
      confirmNewPasswordPlaceholder: 'Repeat the new password',
      recommendation: 'Recommendation',
      recommendationHint: 'Use a unique and long password, and avoid reusing passwords from other accounts.',
      updating: 'Updating...',
      updatePassword: 'Update password',
      openProfileMenu: 'Open profile menu',
      switchToEnglish: 'Switch to English',
      switchToIndonesian: 'Ganti ke Bahasa Indonesia',
    }
  }

  return {
    adminConsole: 'Admin Console',
    internalHotelOps: 'Operasional internal hotel',
    expandSidebar: 'Expand sidebar',
    collapseSidebar: 'Collapse sidebar',
    currentWorkspace: 'Current workspace',
    compactNavigation: 'Compact navigation aktif',
    fullSidebar: 'Sidebar penuh untuk flow admin yang cepat',
    favorites: 'Favorites',
    favorite: 'Favorite',
    unfavorite: 'Unfavorite',
    expand: 'Expand',
    collapse: 'Collapse',
    guestUser: 'Guest User',
    compactMode: 'Compact mode',
    adminNavigationReady: 'Admin navigation ready',
    searchPlaceholder: 'Cari booking, tamu, kamar, invoice, atau halaman admin...',
    darkMode: 'Dark mode',
    lightMode: 'Light mode',
    information: 'Information',
    informationDesc: 'Ubah nama, email, username, dan avatar',
    changePassword: 'Change password',
    changePasswordDesc: 'Jaga akun admin tetap aman',
    logout: 'Log Out',
    logoutDesc: 'Keluar dari dashboard internal',
    commandPalette: 'Admin command palette',
    commandPaletteTitle: 'Cari halaman, action, dan shortcut admin',
    commandPalettePlaceholder: 'Cari menu, favorite, atau action cepat...',
    noResults: 'Tidak ada hasil untuk pencarian ini',
    noResultsHint: 'Coba kata kunci lain seperti arrival, settings, inventory, atau theme.',
    adminAccount: 'Admin account',
    manageAccount: 'Kelola profil dan keamanan akun',
    manageAccountHint: 'Menu ini terpisah dari navbar utama supaya area akun terasa fokus dan rapi.',
    profileInformation: 'Profile information',
    updateIdentity: 'Update identitas admin',
    profilePhoto: 'Foto profile admin',
    profilePhotoHint: 'Gunakan foto square yang jelas supaya identitas akun gampang dikenali.',
    uploadPhoto: 'Ganti foto',
    uploading: 'Mengupload...',
    fullName: 'Nama lengkap',
    fullNamePlaceholder: 'Nama admin',
    username: 'Username',
    usernamePlaceholder: 'Username login',
    email: 'Email',
    emailPlaceholder: 'Email aktif',
    interfaceLanguage: 'Bahasa interface',
    interfaceLanguageHint: 'Pilih bahasa utama untuk shell admin dan area account settings.',
    bahasaIndonesia: 'Bahasa Indonesia',
    english: 'English',
    reset: 'Reset',
    saving: 'Menyimpan...',
    saveInformation: 'Simpan informasi',
    passwordSecurity: 'Password security',
    changeAdminPassword: 'Ganti password akun admin',
    currentPassword: 'Password saat ini',
    currentPasswordPlaceholder: 'Masukkan password saat ini',
    newPassword: 'Password baru',
    newPasswordPlaceholder: 'Minimal 8 karakter',
    confirmNewPassword: 'Konfirmasi password baru',
    confirmNewPasswordPlaceholder: 'Ulangi password baru',
    recommendation: 'Rekomendasi',
    recommendationHint: 'Pakai password unik, panjang, dan hindari pengulangan password akun lain untuk akses dashboard admin.',
    updating: 'Memperbarui...',
    updatePassword: 'Perbarui password',
    openProfileMenu: 'Buka menu profile',
    switchToEnglish: 'Switch to English',
    switchToIndonesian: 'Ganti ke Bahasa Indonesia',
  }
})
const favoriteEntries = computed(() =>
  favoritePaths.value
    .map((path) => navigationLinkEntries.value.find((entry) => entry.to === path))
    .filter((entry): entry is NavigationLinkEntry => Boolean(entry)),
)

const quickActionEntries = computed<PaletteActionEntry[]>(() => [
  {
    id: 'action-theme',
    kind: 'action',
    label: isDarkMode.value ? (isEnglish.value ? 'Switch to light mode' : 'Switch ke light mode') : (isEnglish.value ? 'Switch to dark mode' : 'Switch ke dark mode'),
    description: isEnglish.value ? 'Change the internal dashboard theme' : 'Ubah theme dashboard internal',
    icon: isDarkMode.value ? mdiWhiteBalanceSunny : mdiMoonWaningCrescent,
    keywords: ['theme', 'dark', 'light', 'appearance'],
    execute: () => toggleThemeMode(),
  },
  {
    id: 'action-sidebar',
    kind: 'action',
    label: state.sidebarCollapsed ? copy.value.expandSidebar : copy.value.collapseSidebar,
    description: isEnglish.value ? 'Change the width of the admin sidebar rail' : 'Ubah lebar rail sidebar admin',
    icon: mdiViewGridOutline,
    keywords: ['sidebar', 'collapse', 'expand', 'menu'],
    execute: () => toggleSidebar(),
  },
  {
    id: 'action-logout',
    kind: 'action',
    label: isEnglish.value ? 'Admin logout' : 'Logout admin',
    description: copy.value.logoutDesc,
    icon: mdiLogoutVariant,
    keywords: ['logout', 'sign out', 'keluar'],
    execute: () => handleLogout(),
  },
])

const filteredPaletteEntries = computed<PaletteEntry[]>(() => {
  const query = commandQuery.value.trim().toLowerCase()
  const entries: PaletteEntry[] = [...quickActionEntries.value, ...navigationLinkEntries.value]

  if (!query) {
    return entries
  }

  return entries.filter((entry) =>
    [entry.label, entry.description, ...entry.keywords]
      .join(' ')
      .toLowerCase()
      .includes(query),
  )
})

const isItemActive = (target: string) => route.path === target || route.path.startsWith(`${target}/`)

const isFavoritePath = (path: string) => favoritePaths.value.includes(path)

const authHeaders = (includeJson = false) => ({
  ...(includeJson ? { 'Content-Type': 'application/json' } : {}),
  Authorization: `Bearer ${authSession.state.token}`,
})

const parseJsonResponse = async <T>(response: Response, fallbackMessage: string): Promise<T> => {
  const raw = await response.text()

  if (!raw.trim()) {
    throw new Error(fallbackMessage)
  }

  try {
    return JSON.parse(raw) as T
  } catch {
    throw new Error(fallbackMessage)
  }
}

const persistNavigationState = () => {
  globalThis.localStorage?.setItem(navigationStorageKey, JSON.stringify({
    favorites: favoritePaths.value,
  } satisfies StoredAdminNavigationState))
}

const isGroupExpanded = (label: string) => {
  if (state.sidebarCollapsed && isSidebar.value) {
    return false
  }

  return expandedGroupLabels.value.includes(label)
}

const syncExpandedGroups = () => {
  const currentLabels = topNavigationItems.value
    .filter((item) => item.children?.some((child) => route.path === child.to || route.path.startsWith(`${child.to}/`)))
    .map((item) => item.label)

  expandedGroupLabels.value = Array.from(new Set([
    ...expandedGroupLabels.value.filter((label) => topNavigationItems.value.some((item) => item.label === label)),
    ...currentLabels,
  ]))
}

const toggleGroup = (label: string) => {
  if (expandedGroupLabels.value.includes(label)) {
    expandedGroupLabels.value = expandedGroupLabels.value.filter((item) => item !== label)
    return
  }

  expandedGroupLabels.value = [...expandedGroupLabels.value, label]
}

const toggleFavorite = (path: string) => {
  favoritePaths.value = isFavoritePath(path)
    ? favoritePaths.value.filter((item) => item !== path)
    : [path, ...favoritePaths.value.filter((item) => item !== path)].slice(0, 6)

  persistNavigationState()
}

const syncAccountForms = () => {
  informationForm.name = authSession.state.user?.name ?? ''
  informationForm.username = authSession.state.user?.username ?? ''
  informationForm.email = authSession.state.user?.email ?? ''
  informationForm.avatarUrl = authSession.state.user?.avatar_url ?? ''
  passwordForm.currentPassword = ''
  passwordForm.password = ''
  passwordForm.passwordConfirmation = ''
}

const openAccountModal = (tab: AccountTab = 'information') => {
  profileMenuOpen.value = false
  accountTab.value = tab
  accountErrorMessage.value = ''
  accountSuccessMessage.value = ''
  syncAccountForms()
  accountModalOpen.value = true
}

const closeAccountModal = () => {
  accountModalOpen.value = false
  accountErrorMessage.value = ''
  accountSuccessMessage.value = ''
}

const toggleProfileMenu = () => {
  profileMenuOpen.value = !profileMenuOpen.value
}

const openCommandPalette = async () => {
  commandPaletteOpen.value = true
  mobileMenuOpen.value = false
  profileMenuOpen.value = false
  await nextTick()
  commandInput.value?.focus()
}

const closeCommandPalette = () => {
  commandPaletteOpen.value = false
  commandQuery.value = ''
}

const runPaletteEntry = async (entry: PaletteEntry) => {
  if (entry.kind === 'action') {
    await entry.execute()
    closeCommandPalette()
    return
  }

  closeCommandPalette()
  await router.push(entry.to)
}

const saveInformation = async () => {
  accountSaving.value = true
  accountErrorMessage.value = ''
  accountSuccessMessage.value = ''

  try {
    const response = await fetch(buildApiUrl('/api/v1/auth/profile'), {
      method: 'PATCH',
      headers: authHeaders(true),
      body: JSON.stringify({
        name: informationForm.name,
        username: informationForm.username,
        email: informationForm.email,
        avatar_url: informationForm.avatarUrl || null,
      }),
    })

    const payload = await parseJsonResponse<{
      success?: boolean
      message?: string
      data?: {
        id?: number
        name: string
        email: string
        username?: string | null
        avatar_url?: string | null
      }
      errors?: Record<string, string[]>
    }>(response, 'Respons update profil tidak valid.')

    if (!response.ok || !payload.success || !payload.data) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || 'Informasi akun gagal diperbarui.')
    }

    authSession.updateUser(payload.data)
    accountSuccessMessage.value = payload.message || 'Informasi akun berhasil diperbarui.'
  } catch (error) {
    accountErrorMessage.value = error instanceof Error ? error.message : 'Informasi akun gagal diperbarui.'
  } finally {
    accountSaving.value = false
  }
}

const triggerAvatarPicker = () => {
  avatarInput.value?.click()
}

const uploadAvatar = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (!file) {
    return
  }

  avatarUploading.value = true
  accountErrorMessage.value = ''
  accountSuccessMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('avatar', file)

    const response = await fetch(buildApiUrl('/api/v1/auth/profile/avatar'), {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authSession.state.token}`,
      },
      body: formData,
    })

    const payload = await parseJsonResponse<{
      success?: boolean
      message?: string
      data?: {
        id?: number
        name: string
        email: string
        username?: string | null
        avatar_url?: string | null
      }
      errors?: Record<string, string[]>
    }>(response, 'Respons upload avatar tidak valid.')

    if (!response.ok || !payload.success || !payload.data) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || 'Foto profil gagal diperbarui.')
    }

    informationForm.avatarUrl = payload.data.avatar_url ?? ''
    authSession.updateUser(payload.data)
    accountSuccessMessage.value = payload.message || 'Foto profil berhasil diperbarui.'
  } catch (error) {
    accountErrorMessage.value = error instanceof Error ? error.message : 'Foto profil gagal diperbarui.'
  } finally {
    avatarUploading.value = false
    input.value = ''
  }
}

const updatePassword = async () => {
  passwordSaving.value = true
  accountErrorMessage.value = ''
  accountSuccessMessage.value = ''

  try {
    const response = await fetch(buildApiUrl('/api/v1/auth/password'), {
      method: 'PUT',
      headers: authHeaders(true),
      body: JSON.stringify({
        current_password: passwordForm.currentPassword,
        password: passwordForm.password,
        password_confirmation: passwordForm.passwordConfirmation,
      }),
    })

    const payload = await parseJsonResponse<{
      success?: boolean
      message?: string
      errors?: Record<string, string[]>
    }>(response, 'Respons update password tidak valid.')

    if (!response.ok || !payload.success) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || 'Password gagal diperbarui.')
    }

    passwordForm.currentPassword = ''
    passwordForm.password = ''
    passwordForm.passwordConfirmation = ''
    accountSuccessMessage.value = payload.message || 'Password berhasil diperbarui.'
  } catch (error) {
    accountErrorMessage.value = error instanceof Error ? error.message : 'Password gagal diperbarui.'
  } finally {
    passwordSaving.value = false
  }
}

const handleDocumentClick = (event: MouseEvent) => {
  if (!profileMenuRoot.value?.contains(event.target as Node)) {
    profileMenuOpen.value = false
  }
}

const handleKeyboardShortcut = (event: KeyboardEvent) => {
  if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault()
    void openCommandPalette()
    return
  }

  if (event.key === 'Escape' && commandPaletteOpen.value) {
    closeCommandPalette()
    return
  }

  if (event.key === 'Escape' && accountModalOpen.value) {
    closeAccountModal()
    return
  }

  if (event.key === 'Escape' && profileMenuOpen.value) {
    profileMenuOpen.value = false
  }
}

const handleLogout = async () => {
  if (authSession.state.token) {
    try {
      await fetch(buildApiUrl('/api/v1/auth/logout'), {
        method: 'DELETE',
        headers: {
          Authorization: `Bearer ${authSession.state.token}`,
        },
      })
    } catch {
      //
    }
  }

  authSession.clear()
  await router.replace('/login')
}

watch(
  () => route.fullPath,
  () => {
    mobileMenuOpen.value = false
    closeCommandPalette()
    profileMenuOpen.value = false
    syncExpandedGroups()
  },
  { immediate: true },
)

watch(
  () => state.sidebarCollapsed,
  (collapsed) => {
    if (!collapsed) {
      syncExpandedGroups()
    }
  },
)

onMounted(() => {
  document.addEventListener('keydown', handleKeyboardShortcut)
  document.addEventListener('mousedown', handleDocumentClick)
  syncAccountForms()
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeyboardShortcut)
  document.removeEventListener('mousedown', handleDocumentClick)
})
</script>

<template>
  <div
    class="shell-layout"
    :class="{
      'shell-layout--navbar': !isSidebar,
      'shell-layout--compact': state.sidebarCollapsed && isSidebar,
      'shell-layout--dark': isDarkMode,
    }"
  >
    <div v-if="mobileMenuOpen" class="shell-overlay" @click="mobileMenuOpen = false"></div>

    <aside
      class="sidebar-panel"
      :class="{
        'sidebar-panel--open': mobileMenuOpen,
        'sidebar-panel--compact': state.sidebarCollapsed && isSidebar,
        'sidebar-panel--hidden': !isSidebar,
      }"
    >
      <div class="sidebar-panel__header">
        <div class="brand-card">
          <div class="brand-mark">
            BW
          </div>
          <div class="brand-copy">
            <span class="brand-copy__eyebrow">{{ copy.adminConsole }}</span>
            <strong>Booking WPA</strong>
            <small>{{ copy.internalHotelOps }}</small>
          </div>
        </div>

        <div class="sidebar-panel__header-actions">
          <button v-if="isSidebar" type="button" class="icon-button desktop-only" :title="state.sidebarCollapsed ? copy.expandSidebar : copy.collapseSidebar" @click="toggleSidebar">
            <Icon :icon="mdiViewGridOutline" />
          </button>
          <button type="button" class="icon-button mobile-only" @click="mobileMenuOpen = false">
            <Icon :icon="mdiClose" />
          </button>
        </div>
      </div>

      <div class="sidebar-panel__context">
        <div class="sidebar-context-card">
          <span class="sidebar-context-card__eyebrow">{{ copy.currentWorkspace }}</span>
          <strong>{{ activeSectionLabel }}</strong>
          <small>{{ state.sidebarCollapsed ? copy.compactNavigation : copy.fullSidebar }}</small>
        </div>
      </div>

      <div class="sidebar-panel__body">
        <section v-if="favoriteEntries.length" class="nav-section nav-section--utility">
          <span class="nav-section__label">{{ copy.favorites }}</span>

          <div class="shortcut-stack">
            <RouterLink v-for="entry in favoriteEntries" :key="`favorite-${entry.to}`" :to="entry.to" class="utility-link-card">
              <span class="utility-link-card__icon">
                <Icon :icon="entry.icon" />
              </span>
              <span class="utility-link-card__copy">
                <strong>{{ entry.label }}</strong>
                <small>{{ entry.parentLabel }}</small>
              </span>
              <Icon :icon="mdiStar" class="utility-link-card__accent" />
            </RouterLink>
          </div>
        </section>

        <section v-for="section in navigationSections" :key="section.label" class="nav-section">
          <span class="nav-section__label">{{ section.label }}</span>

          <div class="nav-group-list">
            <article
              v-for="item in section.items"
              :key="item.label"
              class="nav-group"
              :class="{
                'nav-group--active': isItemActive(item.to),
                'nav-group--expanded': isGroupExpanded(item.label),
              }"
            >
              <div class="nav-group__frame">
                <RouterLink :to="item.to" class="nav-group__header" :title="state.sidebarCollapsed ? item.label : undefined">
                  <span class="nav-group__icon">
                    <Icon :icon="item.icon" />
                  </span>

                  <span class="nav-group__copy">
                    <strong>{{ item.label }}</strong>
                    <small>{{ item.description }}</small>
                  </span>
                </RouterLink>

                <button
                  v-if="!state.sidebarCollapsed"
                  type="button"
                  class="nav-group__favorite"
                  :aria-label="isFavoritePath(item.children?.[0]?.to ?? item.to) ? `${copy.unfavorite} ${item.label}` : `${copy.favorite} ${item.label}`"
                  @click="toggleFavorite(item.children?.[0]?.to ?? item.to)"
                >
                  <Icon :icon="isFavoritePath(item.children?.[0]?.to ?? item.to) ? mdiStar : mdiStarOutline" />
                </button>

                <button
                  v-if="item.children?.length && !state.sidebarCollapsed"
                  type="button"
                  class="nav-group__toggle"
                  :aria-label="isGroupExpanded(item.label) ? `${copy.collapse} ${item.label}` : `${copy.expand} ${item.label}`"
                  @click="toggleGroup(item.label)"
                >
                  <Icon :icon="mdiChevronDown" class="nav-group__chevron" :class="{ 'nav-group__chevron--expanded': isGroupExpanded(item.label) }" />
                </button>
              </div>

              <div
                v-if="item.children?.length"
                class="submenu-list"
                :class="{ 'submenu-list--expanded': isGroupExpanded(item.label) }"
              >
                <RouterLink
                  v-for="child in item.children"
                  :key="child.to"
                  :to="child.to"
                  class="submenu-item"
                  :class="{ 'submenu-item--active': route.path === child.to }"
                >
                  <span class="submenu-item__dot"></span>
                  <span>{{ child.label }}</span>
                </RouterLink>
              </div>
            </article>
          </div>
        </section>
      </div>

      <div class="sidebar-panel__footer">
        <div class="sidebar-footer-card">
          <div class="sidebar-footer-card__avatar">{{ authSession.initials }}</div>
          <div class="sidebar-footer-card__copy">
            <strong>{{ authSession.state.user?.name ?? copy.guestUser }}</strong>
            <span>{{ state.sidebarCollapsed ? copy.compactMode : copy.adminNavigationReady }}</span>
          </div>
        </div>
      </div>
    </aside>

    <div class="shell-main">
      <header class="topbar-panel">
        <div class="topbar-panel__left">
          <button type="button" class="icon-button mobile-only" @click="mobileMenuOpen = true">
            <Icon :icon="mdiMenu" />
          </button>

          <div class="breadcrumb-trail">
            <span>{{ activeBreadcrumb.parent }}</span>
            <Icon :icon="mdiChevronRight" />
            <strong>{{ activeBreadcrumb.child }}</strong>
          </div>

          <button type="button" class="search-panel" @click="openCommandPalette">
            <span class="search-panel__icon">
              <Icon :icon="mdiMagnify" />
            </span>
            <span class="search-panel__copy">{{ copy.searchPlaceholder }}</span>
            <span class="search-panel__shortcut">⌘K</span>
          </button>
        </div>

        <div class="topbar-panel__right">
          <button type="button" class="theme-toggle-button" @click="toggleThemeMode">
            <span class="theme-toggle-button__track">
              <span class="theme-toggle-button__thumb">
                <Icon :icon="isDarkMode ? mdiMoonWaningCrescent : mdiWhiteBalanceSunny" />
              </span>
            </span>
            <span>{{ isDarkMode ? copy.darkMode : copy.lightMode }}</span>
          </button>

          <div ref="profileMenuRoot" class="profile-menu">
            <button type="button" class="profile-menu__trigger" :class="{ 'profile-menu__trigger--open': profileMenuOpen }" :aria-label="copy.openProfileMenu" @click="toggleProfileMenu">
              <span class="avatar-chip avatar-chip--button" :style="topbarAvatarStyle">
                <span v-if="!currentAvatarUrl">{{ authSession.initials }}</span>
              </span>
            </button>

            <div v-if="profileMenuOpen" class="profile-menu__dropdown">
              <div class="profile-menu__dropdown-head">
                <div class="avatar-chip avatar-chip--large" :style="topbarAvatarStyle">
                  <span v-if="!currentAvatarUrl">{{ authSession.initials }}</span>
                </div>
                <div class="profile-menu__dropdown-copy">
                  <strong>{{ authSession.state.user?.name ?? 'Guest User' }}</strong>
                  <span>{{ authSession.state.user?.email ?? 'No email' }}</span>
                  <small>@{{ authSession.state.user?.username ?? 'admin' }}</small>
                </div>
              </div>

              <div class="profile-menu__dropdown-actions">
                <button type="button" class="profile-menu__action" @click="openAccountModal('information')">
                  <span class="profile-menu__action-icon">
                    <Icon :icon="mdiAccountEditOutline" />
                  </span>
                  <span class="profile-menu__action-copy">
                    <strong>{{ copy.information }}</strong>
                    <small>{{ copy.informationDesc }}</small>
                  </span>
                </button>

                <button type="button" class="profile-menu__action" @click="openAccountModal('password')">
                  <span class="profile-menu__action-icon">
                    <Icon :icon="mdiShieldKeyOutline" />
                  </span>
                  <span class="profile-menu__action-copy">
                    <strong>{{ copy.changePassword }}</strong>
                    <small>{{ copy.changePasswordDesc }}</small>
                  </span>
                </button>

                <button type="button" class="profile-menu__action profile-menu__action--danger" @click="handleLogout">
                  <span class="profile-menu__action-icon">
                    <Icon :icon="mdiLogoutVariant" />
                  </span>
                  <span class="profile-menu__action-copy">
                    <strong>{{ copy.logout }}</strong>
                    <small>{{ copy.logoutDesc }}</small>
                  </span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </header>

      <nav v-if="!isSidebar" class="top-nav-panel">
        <RouterLink
          v-for="item in topNavigationItems"
          :key="item.to"
          :to="item.to"
          class="top-nav-panel__item"
          :class="{ 'top-nav-panel__item--active': isItemActive(item.to) }"
        >
          <Icon :icon="item.icon" />
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>

      <nav v-if="activeChildren.length" class="subnav-strip">
        <RouterLink
          v-for="child in activeChildren"
          :key="child.to"
          :to="child.to"
          class="subnav-strip__item"
          :class="{ 'subnav-strip__item--active': route.path === child.to }"
        >
          {{ child.label }}
        </RouterLink>
      </nav>

      <main class="page-shell">
        <section v-if="heroVariant === 'card'" class="hero-banner">
          <div class="hero-banner__content">
            <span v-if="showHeaderInfo && eyebrow" class="hero-banner__eyebrow">{{ eyebrow }}</span>
            <h1>{{ title }}</h1>
            <p v-if="showHeaderInfo && summary">{{ summary }}</p>
          </div>
        </section>

        <section v-else class="page-heading">
          <div class="page-heading__copy">
            <span v-if="showHeaderInfo && eyebrow" class="hero-banner__eyebrow">{{ eyebrow }}</span>
            <h1>{{ title }}</h1>
            <p v-if="showHeaderInfo && summary">{{ summary }}</p>
          </div>
        </section>

        <section v-if="metrics.length" class="metric-grid">
          <article
            v-for="metric in metrics"
            :key="metric.label"
            class="metric-card"
            :data-tone="metric.tone ?? 'neutral'"
          >
            <span>{{ metric.label }}</span>
            <strong>{{ metric.value }}</strong>
          </article>
        </section>

        <slot />
      </main>
    </div>

    <div v-if="commandPaletteOpen" class="command-palette-overlay" @click="closeCommandPalette"></div>

    <section v-if="commandPaletteOpen" class="command-palette-shell">
      <article class="command-palette-card" @click.stop>
        <div class="command-palette-card__header">
          <div>
            <span class="section-kicker">{{ copy.commandPalette }}</span>
            <h2>{{ copy.commandPaletteTitle }}</h2>
          </div>
          <button type="button" class="icon-button" @click="closeCommandPalette">
            <Icon :icon="mdiClose" />
          </button>
        </div>

        <label class="command-palette-search">
          <Icon :icon="mdiMagnify" />
          <input
            ref="commandInput"
            v-model="commandQuery"
            type="text"
            :placeholder="copy.commandPalettePlaceholder"
          />
          <span>ESC</span>
        </label>

        <div v-if="favoriteEntries.length" class="command-palette-meta">
          <div class="command-palette-meta__group">
            <span class="section-kicker">{{ copy.favorites }}</span>
            <div class="chip-grid">
              <button
                v-for="entry in favoriteEntries.slice(0, 4)"
                :key="`palette-favorite-${entry.to}`"
                type="button"
                class="info-chip"
                @click="runPaletteEntry(entry)"
              >
                {{ entry.label }}
              </button>
            </div>
          </div>
        </div>

        <div class="command-palette-list">
          <button
            v-for="entry in filteredPaletteEntries"
            :key="entry.id"
            type="button"
            class="command-palette-item"
            @click="runPaletteEntry(entry)"
          >
            <span class="command-palette-item__icon">
              <Icon :icon="entry.icon" />
            </span>
            <span class="command-palette-item__copy">
              <strong>{{ entry.label }}</strong>
              <small>
                {{ entry.kind === 'navigation' ? `${entry.parentLabel} · ${entry.description}` : entry.description }}
              </small>
            </span>
            <span class="command-palette-item__meta">
              <span v-if="entry.kind === 'navigation'">{{ entry.to }}</span>
              <Icon v-else :icon="mdiLightningBoltOutline" />
              <Icon :icon="mdiArrowTopRight" />
            </span>
          </button>

          <div v-if="filteredPaletteEntries.length === 0" class="command-palette-empty">
            <strong>{{ copy.noResults }}</strong>
            <span>{{ copy.noResultsHint }}</span>
          </div>
        </div>
      </article>
    </section>

    <div v-if="accountModalOpen" class="account-modal-overlay" @click="closeAccountModal"></div>

    <section v-if="accountModalOpen" class="account-modal-shell">
      <article class="account-modal-card" @click.stop>
        <div class="account-modal-card__header">
          <div>
            <span class="section-kicker">{{ copy.adminAccount }}</span>
            <h2>{{ copy.manageAccount }}</h2>
            <p>{{ copy.manageAccountHint }}</p>
          </div>

          <button type="button" class="icon-button" @click="closeAccountModal">
            <Icon :icon="mdiClose" />
          </button>
        </div>

        <div class="account-modal-card__summary">
          <div class="account-summary-card">
            <div class="avatar-chip avatar-chip--large" :style="topbarAvatarStyle">
              <span v-if="!currentAvatarUrl">{{ authSession.initials }}</span>
            </div>
            <div class="account-summary-card__copy">
              <strong>{{ authSession.state.user?.name ?? 'Guest User' }}</strong>
              <span>{{ authSession.state.user?.email ?? 'No email' }}</span>
              <small>@{{ authSession.state.user?.username ?? 'admin' }}</small>
            </div>
          </div>

          <div class="segmented">
            <button
              type="button"
              class="segmented__button"
              :class="{ 'segmented__button--active': accountTab === 'information' }"
              @click="accountTab = 'information'"
            >
              <Icon :icon="mdiInformationOutline" />
              {{ copy.information }}
            </button>
            <button
              type="button"
              class="segmented__button"
              :class="{ 'segmented__button--active': accountTab === 'password' }"
              @click="accountTab = 'password'"
            >
              <Icon :icon="mdiFormTextboxPassword" />
              {{ copy.changePassword }}
            </button>
          </div>
        </div>

        <div class="cms-status-row">
          <div v-if="accountSuccessMessage" class="cms-status-pill cms-status-pill--success">{{ accountSuccessMessage }}</div>
          <div v-if="accountErrorMessage" class="cms-status-pill cms-status-pill--error">{{ accountErrorMessage }}</div>
        </div>

        <section v-if="accountTab === 'information'" class="account-panel-grid">
          <article class="account-panel-block">
            <div class="section-heading">
              <div>
                <span class="section-kicker">{{ copy.profileInformation }}</span>
                <h3>{{ copy.updateIdentity }}</h3>
              </div>
              <Icon :icon="mdiAccountCogOutline" />
            </div>

            <div class="account-avatar-panel">
              <div class="avatar-chip avatar-chip--profile-preview" :style="topbarAvatarStyle">
                <span v-if="!currentAvatarUrl">{{ authSession.initials }}</span>
              </div>

              <div class="account-avatar-panel__copy">
                <strong>{{ copy.profilePhoto }}</strong>
                <span>{{ copy.profilePhotoHint }}</span>
              </div>

              <input
                ref="avatarInput"
                type="file"
                accept="image/png,image/jpeg,image/webp"
                class="account-avatar-panel__input"
                @change="uploadAvatar"
              />

              <button type="button" class="secondary-button" :disabled="avatarUploading" @click="triggerAvatarPicker">
                <Icon :icon="mdiCameraOutline" />
                {{ avatarUploading ? copy.uploading : copy.uploadPhoto }}
              </button>
            </div>

            <div class="account-form-grid">
              <label class="field">
                <span>{{ copy.fullName }}</span>
                <input v-model="informationForm.name" type="text" class="text-input" :placeholder="copy.fullNamePlaceholder" />
              </label>

              <label class="field">
                <span>{{ copy.username }}</span>
                <input v-model="informationForm.username" type="text" class="text-input" :placeholder="copy.usernamePlaceholder" />
              </label>

              <label class="field">
                <span>{{ copy.email }}</span>
                <input v-model="informationForm.email" type="email" class="text-input" :placeholder="copy.emailPlaceholder" />
              </label>
            </div>

            <div class="account-language-card">
              <div class="account-language-card__copy">
                <span class="section-kicker">{{ copy.interfaceLanguage }}</span>
                <strong>{{ copy.interfaceLanguage }}</strong>
                <small>{{ copy.interfaceLanguageHint }}</small>
              </div>

              <div class="segmented">
                <button
                  type="button"
                  class="segmented__button"
                  :class="{ 'segmented__button--active': state.language === 'id' }"
                  @click="updateLanguage('id')"
                >
                  {{ copy.bahasaIndonesia }}
                </button>
                <button
                  type="button"
                  class="segmented__button"
                  :class="{ 'segmented__button--active': state.language === 'en' }"
                  @click="updateLanguage('en')"
                >
                  {{ copy.english }}
                </button>
              </div>
            </div>

            <div class="account-panel-block__actions">
              <button type="button" class="secondary-button" @click="syncAccountForms">{{ copy.reset }}</button>
              <button type="button" class="primary-button" :disabled="accountSaving" @click="saveInformation">
                {{ accountSaving ? copy.saving : copy.saveInformation }}
              </button>
            </div>
          </article>
        </section>

        <section v-else class="account-panel-grid">
          <article class="account-panel-block">
            <div class="section-heading">
              <div>
                <span class="section-kicker">{{ copy.passwordSecurity }}</span>
                <h3>{{ copy.changeAdminPassword }}</h3>
              </div>
              <Icon :icon="mdiShieldKeyOutline" />
            </div>

            <div class="account-form-grid">
              <label class="field">
                <span>{{ copy.currentPassword }}</span>
                <input v-model="passwordForm.currentPassword" type="password" class="text-input" :placeholder="copy.currentPasswordPlaceholder" />
              </label>

              <label class="field">
                <span>{{ copy.newPassword }}</span>
                <input v-model="passwordForm.password" type="password" class="text-input" :placeholder="copy.newPasswordPlaceholder" />
              </label>

              <label class="field">
                <span>{{ copy.confirmNewPassword }}</span>
                <input v-model="passwordForm.passwordConfirmation" type="password" class="text-input" :placeholder="copy.confirmNewPasswordPlaceholder" />
              </label>
            </div>

            <div class="account-note-card">
              <span class="section-kicker">{{ copy.recommendation }}</span>
              <p>{{ copy.recommendationHint }}</p>
            </div>

            <div class="account-panel-block__actions">
              <button type="button" class="secondary-button" @click="syncAccountForms">{{ copy.reset }}</button>
              <button type="button" class="primary-button" :disabled="passwordSaving" @click="updatePassword">
                {{ passwordSaving ? copy.updating : copy.updatePassword }}
              </button>
            </div>
          </article>
        </section>
      </article>
    </section>
  </div>
</template>
