<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import { useAppLocale } from '../composables/useAppLocale'
import { useAuthSession } from '../composables/useAuthSession'

const router = useRouter()
const { persist } = useAuthSession()
const { isEnglish } = useAppLocale()
const copy = computed(() => ({
  googleAuth: isEnglish.value ? 'Google authentication' : 'Google authentication',
  finishingLogin: isEnglish.value ? 'Finishing login...' : 'Menyelesaikan login...',
  waitingMessage: isEnglish.value
    ? 'Please wait while the system connects your Google account to the workspace.'
    : 'Mohon tunggu, sistem sedang menghubungkan akun Google Anda ke workspace.',
  failedMessage: isEnglish.value ? 'Google login failed' : 'Login Google gagal',
}))

onMounted(async () => {
  const params = new URLSearchParams(window.location.search)
  const token = params.get('token')
  const name = params.get('name')
  const email = params.get('email')

  if (!token || !name || !email) {
    await router.replace(`/login?error=${encodeURIComponent(copy.value.failedMessage)}`)
    return
  }

  persist(token, {
    name,
    email,
    username: params.get('username'),
    avatar_url: params.get('avatar_url'),
  })

  await router.replace('/')
})
</script>

<template>
  <div class="login-page">
    <section class="login-shell login-shell--compact">
      <article class="login-card">
        <div class="login-card__header">
          <span class="login-kicker">{{ copy.googleAuth }}</span>
          <h2>{{ copy.finishingLogin }}</h2>
          <p>{{ copy.waitingMessage }}</p>
        </div>
      </article>
    </section>
  </div>
</template>
