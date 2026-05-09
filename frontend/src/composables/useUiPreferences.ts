import { computed, reactive, watch } from 'vue'

type LayoutMode = 'sidebar' | 'navbar'
type TableDensity = 'comfortable' | 'compact'
type ThemeMode = 'light' | 'dark'
type UiLanguage = 'id' | 'en'

type UiPreferences = {
  primaryColor: string
  darkPrimaryColor: string
  layoutMode: LayoutMode
  sidebarCollapsed: boolean
  tableDensity: TableDensity
  themeMode: ThemeMode
  language: UiLanguage
}

const storageKey = 'booking.wpa.ui-preferences'

const defaults: UiPreferences = {
  primaryColor: '#2563eb',
  darkPrimaryColor: '#F8DE22',
  layoutMode: 'sidebar',
  sidebarCollapsed: false,
  tableDensity: 'comfortable',
  themeMode: 'light',
  language: 'id',
}

const isHexColor = (value: string) => /^#([0-9a-fA-F]{6})$/.test(value)

const normalizeHexColor = (value: string, fallback: string) => {
  const normalized = value.trim()

  return isHexColor(normalized) ? normalized : fallback
}

const getContrastColor = (hexColor: string) => {
  const red = parseInt(hexColor.slice(1, 3), 16)
  const green = parseInt(hexColor.slice(3, 5), 16)
  const blue = parseInt(hexColor.slice(5, 7), 16)
  const luminance = (0.299 * red + 0.587 * green + 0.114 * blue) / 255

  return luminance > 0.62 ? '#0f172a' : '#ffffff'
}

const storedPreferences = (() => {
  const raw = globalThis.localStorage?.getItem(storageKey)

  if (!raw) {
    return defaults
  }

  try {
    return {
      ...defaults,
      ...JSON.parse(raw),
    } as UiPreferences
  } catch {
    return defaults
  }
})()

const state = reactive<UiPreferences>({
  ...storedPreferences,
  primaryColor: normalizeHexColor(storedPreferences.primaryColor, defaults.primaryColor),
  darkPrimaryColor: normalizeHexColor(storedPreferences.darkPrimaryColor, defaults.darkPrimaryColor),
})

watch(
  state,
  (value) => {
    globalThis.localStorage?.setItem(storageKey, JSON.stringify(value))

    const root = document.documentElement
    const activePrimaryColor = normalizeHexColor(
      value.themeMode === 'dark' ? value.darkPrimaryColor : value.primaryColor,
      value.themeMode === 'dark' ? defaults.darkPrimaryColor : defaults.primaryColor,
    )

    root.style.setProperty('--color-primary', activePrimaryColor)
    root.style.setProperty('--color-primary-contrast', getContrastColor(activePrimaryColor))
    root.style.setProperty('--layout-max-width', value.layoutMode === 'navbar' ? '1480px' : '1600px')
    root.dataset.layoutMode = value.layoutMode
    root.dataset.tableDensity = value.tableDensity
    root.dataset.themeMode = value.themeMode
    root.dataset.uiLanguage = value.language
  },
  { immediate: true, deep: true },
)

export function useUiPreferences() {
  const layoutMode = computed(() => state.layoutMode)
  const isSidebar = computed(() => state.layoutMode === 'sidebar')

  const updatePrimaryColor = (primaryColor: string) => {
    state.primaryColor = normalizeHexColor(primaryColor, defaults.primaryColor)
  }

  const updateDarkPrimaryColor = (darkPrimaryColor: string) => {
    state.darkPrimaryColor = normalizeHexColor(darkPrimaryColor, defaults.darkPrimaryColor)
  }

  const updateLayoutMode = (layoutMode: LayoutMode) => {
    state.layoutMode = layoutMode
  }

  const toggleSidebar = () => {
    state.sidebarCollapsed = !state.sidebarCollapsed
  }

  const updateTableDensity = (tableDensity: TableDensity) => {
    state.tableDensity = tableDensity
  }

  const updateThemeMode = (themeMode: ThemeMode) => {
    state.themeMode = themeMode
  }

  const updateLanguage = (language: UiLanguage) => {
    state.language = language
  }

  const toggleThemeMode = () => {
    state.themeMode = state.themeMode === 'dark' ? 'light' : 'dark'
  }

  return {
    state,
    layoutMode,
    isSidebar,
    updatePrimaryColor,
    updateDarkPrimaryColor,
    updateLayoutMode,
    toggleSidebar,
    updateTableDensity,
    updateThemeMode,
    updateLanguage,
    toggleThemeMode,
  }
}
