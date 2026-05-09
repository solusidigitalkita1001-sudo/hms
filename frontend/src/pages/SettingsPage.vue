<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue'

import AppShell from '../components/AppShell.vue'
import { getSettingsTabs } from '../config/workspace'
import { useAppLocale } from '../composables/useAppLocale'
import { useUiPreferences } from '../composables/useUiPreferences'

const { state, updateLayoutMode, updatePrimaryColor, updateDarkPrimaryColor, updateTableDensity, updateLanguage } = useUiPreferences()
const { language, isEnglish } = useAppLocale()
const settingsTabs = computed(() => getSettingsTabs(language.value))
const copy = computed(() => {
  if (isEnglish.value) {
    return {
      title: 'Settings',
      eyebrow: 'Workspace Personalization',
      summary: 'Adjust branding, UI preferences, layout mode, bilingual options, and core business defaults with consistent tokens.',
      lightPrimary: 'Light Primary',
      darkPrimary: 'Dark Primary',
      layoutMode: 'Layout Mode',
      tableDensity: 'Table Density',
      settingGroups: 'Setting groups',
      settingsArchitecture: 'Settings architecture for WPA',
      branding: 'Branding',
      brandingTitle: 'Primary colors and app feel',
      primaryLightLabel: 'Primary color for light mode',
      primaryDarkLabel: 'Primary color for dark mode',
      layout: 'Layout',
      layoutTitle: 'Sidebar or navbar',
      sidebar: 'Sidebar',
      navbar: 'Navbar',
      comfortable: 'Comfortable',
      compact: 'Compact',
      language: 'Language',
      languageTitle: 'Bilingual interface preference',
      languageHint: 'Choose the primary admin language for the shell, account settings, and key controls.',
      alignment: 'Business alignment',
      alignmentTitle: 'Settings that must stay aligned with requirements and DB',
      alignmentPoints: [
        'ui.primary_color, ui.layout_mode, ui.sidebar_collapsed, ui.table_density, ui.language',
        'branding.app_name and branding.logo_path define the property identity',
        'business.check_in_time and business.check_out_time support operations',
        'Every important change should be ready for audit trail recording',
      ],
    }
  }

  return {
    title: 'Settings',
    eyebrow: 'Workspace Personalization',
    summary: 'Atur branding, UI preferences, layout mode, bilingual options, dan business rules dasar dengan token yang konsisten.',
    lightPrimary: 'Light Primary',
    darkPrimary: 'Dark Primary',
    layoutMode: 'Layout Mode',
    tableDensity: 'Table Density',
    settingGroups: 'Setting groups',
    settingsArchitecture: 'Arsitektur settings untuk WPA',
    branding: 'Branding',
    brandingTitle: 'Primary color dan feel aplikasi',
    primaryLightLabel: 'Primary color light mode',
    primaryDarkLabel: 'Primary color dark mode',
    layout: 'Layout',
    layoutTitle: 'Sidebar atau navbar',
    sidebar: 'Sidebar',
    navbar: 'Navbar',
    comfortable: 'Comfortable',
    compact: 'Compact',
    language: 'Language',
    languageTitle: 'Preferensi bilingual interface',
    languageHint: 'Pilih bahasa utama admin untuk shell, account settings, dan kontrol utama.',
    alignment: 'Business alignment',
    alignmentTitle: 'Settings yang harus sinkron dengan requirement dan DB',
    alignmentPoints: [
      'ui.primary_color, ui.layout_mode, ui.sidebar_collapsed, ui.table_density, ui.language',
      'branding.app_name dan branding.logo_path untuk identitas properti',
      'business.check_in_time dan business.check_out_time untuk operasional',
      'Semua perubahan penting harus siap dicatat ke audit trail',
    ],
  }
})

const metrics = computed(() => [
  { label: copy.value.lightPrimary, value: state.primaryColor, tone: 'primary' as const },
  { label: copy.value.darkPrimary, value: state.darkPrimaryColor, tone: 'warning' as const },
  { label: copy.value.layoutMode, value: state.layoutMode, tone: 'success' as const },
  { label: copy.value.tableDensity, value: state.tableDensity, tone: 'neutral' as const },
])
</script>

<template>
  <AppShell
    :title="copy.title"
    :eyebrow="copy.eyebrow"
    :summary="copy.summary"
    :metrics="metrics"
  >
    <section class="content-grid">
      <article class="surface-card surface-card--wide">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.settingGroups }}</span>
            <h2>{{ copy.settingsArchitecture }}</h2>
          </div>
        </div>

        <div class="settings-tab-grid">
          <div v-for="tab in settingsTabs" :key="tab.label" class="settings-tab-card">
            <span class="settings-tab-card__icon">
              <Icon :icon="tab.icon" />
            </span>
            <strong>{{ tab.label }}</strong>
          </div>
        </div>
      </article>
    </section>

    <section class="content-grid">
      <article class="surface-card">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.branding }}</span>
            <h2>{{ copy.brandingTitle }}</h2>
          </div>
        </div>

        <div class="settings-stack">
          <label class="field">
            <span>{{ copy.primaryLightLabel }}</span>
            <div class="color-row">
              <input
                :value="state.primaryColor"
                type="color"
                class="color-input"
                @input="updatePrimaryColor(($event.target as HTMLInputElement).value)"
              />
              <input
                :value="state.primaryColor"
                type="text"
                class="text-input"
                @change="updatePrimaryColor(($event.target as HTMLInputElement).value)"
              />
            </div>
          </label>

          <label class="field">
            <span>{{ copy.primaryDarkLabel }}</span>
            <div class="color-row">
              <input
                :value="state.darkPrimaryColor"
                type="color"
                class="color-input"
                @input="updateDarkPrimaryColor(($event.target as HTMLInputElement).value)"
              />
              <input
                :value="state.darkPrimaryColor"
                type="text"
                class="text-input"
                @change="updateDarkPrimaryColor(($event.target as HTMLInputElement).value)"
              />
            </div>
          </label>
        </div>
      </article>

      <article class="surface-card">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.layout }}</span>
            <h2>{{ copy.layoutTitle }}</h2>
          </div>
        </div>

        <div class="segmented">
          <button
            type="button"
            class="segmented__button"
            :class="{ 'segmented__button--active': state.layoutMode === 'sidebar' }"
            @click="updateLayoutMode('sidebar')"
          >
            {{ copy.sidebar }}
          </button>
          <button
            type="button"
            class="segmented__button"
            :class="{ 'segmented__button--active': state.layoutMode === 'navbar' }"
            @click="updateLayoutMode('navbar')"
          >
            {{ copy.navbar }}
          </button>
        </div>

        <div class="segmented">
          <button
            type="button"
            class="segmented__button"
            :class="{ 'segmented__button--active': state.tableDensity === 'comfortable' }"
            @click="updateTableDensity('comfortable')"
          >
            {{ copy.comfortable }}
          </button>
          <button
            type="button"
            class="segmented__button"
            :class="{ 'segmented__button--active': state.tableDensity === 'compact' }"
            @click="updateTableDensity('compact')"
          >
            {{ copy.compact }}
          </button>
        </div>
      </article>

      <article class="surface-card">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.language }}</span>
            <h2>{{ copy.languageTitle }}</h2>
          </div>
        </div>

        <p class="section-support">{{ copy.languageHint }}</p>

        <div class="segmented">
          <button
            type="button"
            class="segmented__button"
            :class="{ 'segmented__button--active': state.language === 'id' }"
            @click="updateLanguage('id')"
          >
            Bahasa Indonesia
          </button>
          <button
            type="button"
            class="segmented__button"
            :class="{ 'segmented__button--active': state.language === 'en' }"
            @click="updateLanguage('en')"
          >
            English
          </button>
        </div>
      </article>
    </section>

    <section class="content-grid">
      <article class="surface-card surface-card--wide">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.alignment }}</span>
            <h2>{{ copy.alignmentTitle }}</h2>
          </div>
        </div>

        <div class="chip-grid">
          <span class="info-chip info-chip--database">settings</span>
          <span class="info-chip info-chip--database">properties</span>
          <span class="info-chip info-chip--database">users</span>
          <span class="info-chip info-chip--database">activity_logs</span>
        </div>

        <ul class="bullet-list">
          <li v-for="point in copy.alignmentPoints" :key="point">{{ point }}</li>
        </ul>
      </article>
    </section>
  </AppShell>
</template>
