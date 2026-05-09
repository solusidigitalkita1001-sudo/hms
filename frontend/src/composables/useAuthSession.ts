import { computed, reactive } from 'vue'

type AuthUser = {
  id?: number
  name: string
  email: string
  username?: string | null
  avatar_url?: string | null
}

const storageKey = 'booking-wpa-auth'

const state = reactive<{
  token: string
  user: AuthUser | null
  initialized: boolean
}>({
  token: '',
  user: null,
  initialized: false,
})

function initialize() {
  if (state.initialized) {
    return
  }

  const raw = window.localStorage.getItem(storageKey)

  if (raw) {
    try {
      const parsed = JSON.parse(raw) as { token?: string; user?: AuthUser | null }
      state.token = parsed.token ?? ''
      state.user = parsed.user ?? null
    } catch {
      window.localStorage.removeItem(storageKey)
    }
  }

  state.initialized = true
}

function persist(token: string, user: AuthUser) {
  state.token = token
  state.user = user
  state.initialized = true

  window.localStorage.setItem(
    storageKey,
    JSON.stringify({
      token,
      user,
    }),
  )
}

function updateUser(user: AuthUser) {
  if (!state.token) {
    return
  }

  state.user = user
  state.initialized = true

  window.localStorage.setItem(
    storageKey,
    JSON.stringify({
      token: state.token,
      user,
    }),
  )
}

function clear() {
  state.token = ''
  state.user = null
  state.initialized = true
  window.localStorage.removeItem(storageKey)
}

export function useAuthSession() {
  return {
    state,
    initialize,
    persist,
    updateUser,
    clear,
    isAuthenticated: computed(() => Boolean(state.token && state.user)),
    initials: computed(() => {
      const name = state.user?.name?.trim()

      if (!name) {
        return 'GU'
      }

      return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('')
    }),
  }
}
