<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import AppShell from '../components/AppShell.vue'
import { getWorkspacePages } from '../config/workspace'
import { useAppLocale } from '../composables/useAppLocale'

const route = useRoute()
const { language } = useAppLocale()
const page = computed(() => getWorkspacePages(language.value)[String(route.meta.pageKey)] ?? null)
</script>

<template>
  <AppShell
    v-if="page"
    :title="page.title"
    :eyebrow="page.eyebrow"
    :summary="page.summary"
    :metrics="page.metrics"
  />
</template>
