<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiAlertCircleOutline from '@iconify-icons/mdi/alert-circle-outline'
import mdiArrowRight from '@iconify-icons/mdi/arrow-right'
import mdiGoogle from '@iconify-icons/mdi/google'
import mdiLockOutline from '@iconify-icons/mdi/lock-outline'
import mdiShieldCheckOutline from '@iconify-icons/mdi/shield-check-outline'
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAppLocale } from '../composables/useAppLocale'
import { useAuthSession } from '../composables/useAuthSession'
import { buildApiUrl } from '../lib/api'

const route = useRoute()
const router = useRouter()
const { persist } = useAuthSession()
const { isEnglish } = useAppLocale()

const form = reactive({
  identifier: '',
  password: '',
})

const loading = ref(false)
const errorMessage = ref(String(route.query.error ?? ''))

const redirectTarget = computed(() => String(route.query.redirect ?? '/'))
const copy = computed(() => {
  if (isEnglish.value) {
    return {
      loginFailed: 'Login failed.',
      kicker: 'Booking WPA',
      title: 'Secure access for hotel teams, with manual login and Google sign-in.',
      summary: 'A refined login surface built for modern hotel operations instead of a stiff generic form.',
      secureMode: 'Internal secure mode',
      secureModeDesc: 'Access dashboard, reservations, room board, and hotel operations from one entry point.',
      googleSignIn: 'Google sign-in',
      googleSignInDesc: 'Quick access for teams using Google Workspace or regular Google accounts.',
      signInWorkspace: 'Sign in to workspace',
      staffLogin: 'Staff login',
      loginChoice: 'Choose manual login or continue with Google.',
      loginWithGoogle: 'Continue with Google',
      orManual: 'or manual login',
      emailOrUsername: 'Email or username',
      emailOrUsernamePlaceholder: 'admin@local.test or admin',
      password: 'Password',
      passwordPlaceholder: 'Enter password',
      processing: 'Processing login...',
      manualLogin: 'Manual login',
      demoAccount: 'Demo account',
    }
  }

  return {
    loginFailed: 'Login gagal.',
    kicker: 'Booking WPA',
    title: 'Secure access untuk tim hotel, dengan login manual dan Google.',
    summary: 'Gue bikinin tampilan login yang lebih proper, lebih premium, dan lebih cocok buat operasional hotel modern daripada form generik yang kaku.',
    secureMode: 'Internal secure mode',
    secureModeDesc: 'Akses dashboard, reservasi, room board, dan operasional hotel dari satu entry point.',
    googleSignIn: 'Google sign-in',
    googleSignInDesc: 'Masuk cepat untuk tim yang pakai akun Google Workspace atau akun Google biasa.',
    signInWorkspace: 'Masuk ke workspace',
    staffLogin: 'Login staf',
    loginChoice: 'Pilih login manual atau lanjut dengan Google.',
    loginWithGoogle: 'Login dengan Google',
    orManual: 'atau login manual',
    emailOrUsername: 'Email atau username',
    emailOrUsernamePlaceholder: 'admin@local.test atau admin',
    password: 'Password',
    passwordPlaceholder: 'Masukkan password',
    processing: 'Memproses login...',
    manualLogin: 'Login manual',
    demoAccount: 'Demo account',
  }
})

const submitManualLogin = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await fetch(buildApiUrl('/api/v1/auth/login'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(form),
    })

    const payload = (await response.json()) as {
      success: boolean
      message: string
      errors?: Record<string, string[]>
      data?: {
        access_token: string
        user: {
          id: number
          name: string
          email: string
          username?: string | null
          avatar_url?: string | null
        }
      }
    }

    if (!response.ok || !payload.success || !payload.data) {
      throw new Error(payload.errors?.identifier?.[0] || payload.message || copy.value.loginFailed)
    }

    persist(payload.data.access_token, payload.data.user)
    await router.replace(redirectTarget.value)
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : copy.value.loginFailed
  } finally {
    loading.value = false
  }
}

const loginWithGoogle = () => {
  window.location.href = buildApiUrl('/api/v1/auth/google/redirect')
}
</script>

<template>
  <div class="login-page">
    <section class="login-shell">
      <article class="login-showcase">
        <span class="login-kicker">{{ copy.kicker }}</span>
        <h1>{{ copy.title }}</h1>
        <p>{{ copy.summary }}</p>

        <div class="login-showcase__grid">
          <div class="login-showcase__card">
            <Icon :icon="mdiShieldCheckOutline" />
            <strong>{{ copy.secureMode }}</strong>
            <span>{{ copy.secureModeDesc }}</span>
          </div>
          <div class="login-showcase__card">
            <Icon :icon="mdiGoogle" />
            <strong>{{ copy.googleSignIn }}</strong>
            <span>{{ copy.googleSignInDesc }}</span>
          </div>
        </div>
      </article>

      <article class="login-card">
        <div class="login-card__header">
          <span class="login-kicker">{{ copy.signInWorkspace }}</span>
          <h2>{{ copy.staffLogin }}</h2>
          <p>{{ copy.loginChoice }}</p>
        </div>

        <button type="button" class="login-google-button" @click="loginWithGoogle">
          <Icon :icon="mdiGoogle" />
          {{ copy.loginWithGoogle }}
          <Icon :icon="mdiArrowRight" />
        </button>

        <div class="login-divider">
          <span>{{ copy.orManual }}</span>
        </div>

        <form class="login-form" @submit.prevent="submitManualLogin">
          <label class="login-field">
            <span>{{ copy.emailOrUsername }}</span>
            <input v-model="form.identifier" type="text" :placeholder="copy.emailOrUsernamePlaceholder" />
          </label>

          <label class="login-field">
            <span>{{ copy.password }}</span>
            <input v-model="form.password" type="password" :placeholder="copy.passwordPlaceholder" />
          </label>

          <div v-if="errorMessage" class="login-error">
            <Icon :icon="mdiAlertCircleOutline" />
            <span>{{ errorMessage }}</span>
          </div>

          <button type="submit" class="login-submit-button" :disabled="loading">
            <Icon :icon="mdiLockOutline" />
            {{ loading ? copy.processing : copy.manualLogin }}
          </button>
        </form>

        <div class="login-helper">
          <strong>{{ copy.demoAccount }}</strong>
          <span>Email: admin@local.test</span>
          <span>Password: password</span>
        </div>
      </article>
    </section>
  </div>
</template>
