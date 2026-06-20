<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiClose from '@iconify-icons/mdi/close'
import mdiMoonWaningCrescent from '@iconify-icons/mdi/moon-waning-crescent'
import mdiRefresh from '@iconify-icons/mdi/refresh'
import { computed, onMounted, ref } from 'vue'

import AppShell from '../components/AppShell.vue'
import { getSettingsTabs } from '../config/workspace'
import { useAppLocale } from '../composables/useAppLocale'
import { useAuthSession } from '../composables/useAuthSession'
import { useUiPreferences } from '../composables/useUiPreferences'
import { buildApiUrl } from '../lib/api'

const { state, updateLayoutMode, updatePrimaryColor, updateDarkPrimaryColor, updateTableDensity, updateLanguage } = useUiPreferences()
const { language, isEnglish } = useAppLocale()
const { state: authState } = useAuthSession()
const settingsTabs = computed(() => getSettingsTabs(language.value))

// --- Business Date & Night Audit ---
type BusinessDateData = {
  current_business_date: string | null
  property_code: string | null
  latest_night_audit: {
    business_date: string | null
    next_business_date: string | null
    status: string | null
    completed_at: string | null
    closed_by_user_id: number | null
  } | null
}

const businessDateData = ref<BusinessDateData | null>(null)
const businessDateLoading = ref(false)
const nightAuditRunning = ref(false)
const nightAuditModalOpen = ref(false)
const nightAuditNotes = ref('')
const businessDateError = ref('')
const businessDateSuccess = ref('')

const authHeaders = (includeJson = false) => ({
  ...(includeJson ? { 'Content-Type': 'application/json' } : {}),
  Authorization: `Bearer ${authState.token}`,
})

const loadBusinessDate = async () => {
  businessDateLoading.value = true
  businessDateError.value = ''

  try {
    const response = await fetch(buildApiUrl('/api/v1/settings/business-date'), {
      headers: authHeaders(),
    })
    const raw = await response.text()
    if (!raw.trim()) throw new Error('Empty response')
    const payload = JSON.parse(raw) as {
      success: boolean
      message: string
      data: BusinessDateData
    }
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Failed to load business date')
    }
    businessDateData.value = payload.data
  } catch (error) {
    businessDateError.value = error instanceof Error ? error.message : 'Failed to load business date'
  } finally {
    businessDateLoading.value = false
  }
}

const openNightAuditModal = () => {
  nightAuditNotes.value = ''
  nightAuditModalOpen.value = true
}

const closeNightAuditModal = () => {
  nightAuditModalOpen.value = false
}

const runNightAudit = async () => {
  nightAuditRunning.value = true
  businessDateError.value = ''
  businessDateSuccess.value = ''

  try {
    const response = await fetch(buildApiUrl('/api/v1/settings/night-audit'), {
      method: 'POST',
      headers: authHeaders(true),
      body: JSON.stringify({
        property_code: 'MAIN',
        notes: nightAuditNotes.value || null,
      }),
    })
    const raw = await response.text()
    if (!raw.trim()) throw new Error('Empty response')
    const payload = JSON.parse(raw) as {
      success?: boolean
      message?: string
      errors?: Record<string, string[]>
    }
    if (!response.ok || !payload.success) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || 'Night audit failed')
    }
    businessDateSuccess.value = payload.message || 'Night audit completed successfully.'
    closeNightAuditModal()
    await loadBusinessDate()
  } catch (error) {
    businessDateError.value = error instanceof Error ? error.message : 'Night audit failed'
  } finally {
    nightAuditRunning.value = false
  }
}

const formatDateTime = (value: string | null) => {
  if (!value) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
}

onMounted(() => {
  loadBusinessDate()
})
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
      nightAudit: 'Night Audit',
      nightAuditTitle: 'Business date and night audit controls',
      currentBusinessDate: 'Current business date',
      latestAudit: 'Latest night audit',
      auditStatus: 'Status',
      auditCompletedAt: 'Completed at',
      runNightAudit: 'Run Night Audit',
      auditRunning: 'Running...',
      auditNotes: 'Notes',
      auditNotesPlaceholder: 'Optional notes for this night audit',
      auditConfirmTitle: 'Run Night Audit',
      auditConfirmDescription: 'This will close the current business day and advance to the next date. All end-of-day processing will be triggered.',
      auditSubmit: 'Run Night Audit',
      auditCancel: 'Cancel',
      loadBusinessDateFailed: 'Failed to load business date.',
      noAuditYet: 'No audit recorded yet.',
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
    nightAudit: 'Night Audit',
    nightAuditTitle: 'Business date dan kontrol night audit',
    currentBusinessDate: 'Business date saat ini',
    latestAudit: 'Night audit terakhir',
    auditStatus: 'Status',
    auditCompletedAt: 'Selesai pada',
    runNightAudit: 'Jalankan Night Audit',
    auditRunning: 'Memproses...',
    auditNotes: 'Catatan',
    auditNotesPlaceholder: 'Catatan opsional untuk night audit ini',
    auditConfirmTitle: 'Jalankan Night Audit',
    auditConfirmDescription: 'Ini akan menutup hari bisnis saat ini dan maju ke tanggal berikutnya. Semua proses end-of-day akan dijalankan.',
    auditSubmit: 'Jalankan Night Audit',
    auditCancel: 'Batal',
    loadBusinessDateFailed: 'Gagal memuat business date.',
    noAuditYet: 'Belum ada audit tercatat.',
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

    <!-- Night Audit & Business Date Section -->
    <section class="content-grid">
      <article class="surface-card surface-card--wide">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.nightAudit }}</span>
            <h2>{{ copy.nightAuditTitle }}</h2>
          </div>
          <div class="action-pair">
            <button type="button" class="secondary-button" :disabled="businessDateLoading" @click="loadBusinessDate">
              <Icon :icon="mdiRefresh" />
            </button>
            <button type="button" class="primary-button" @click="openNightAuditModal">
              <Icon :icon="mdiMoonWaningCrescent" />
              {{ copy.runNightAudit }}
            </button>
          </div>
        </div>

        <div class="cms-status-row">
          <div v-if="businessDateLoading" class="cms-status-pill">
            <Icon :icon="mdiRefresh" class="cms-spin" />
            Loading...
          </div>
          <div v-else-if="businessDateSuccess" class="cms-status-pill cms-status-pill--success">{{ businessDateSuccess }}</div>
          <div v-if="businessDateError" class="cms-status-pill cms-status-pill--error">{{ businessDateError }}</div>
        </div>

        <div v-if="businessDateData" class="audit-grid">
          <div class="audit-card">
            <span class="section-kicker">{{ copy.currentBusinessDate }}</span>
            <strong class="audit-date-value">{{ businessDateData.current_business_date || '-' }}</strong>
            <small>{{ businessDateData.property_code || '-' }}</small>
          </div>

          <div class="audit-card">
            <span class="section-kicker">{{ copy.latestAudit }}</span>
            <template v-if="businessDateData.latest_night_audit">
              <strong>
                <span class="status-badge status-badge--success">{{ businessDateData.latest_night_audit.status }}</span>
              </strong>
              <small>
                {{ copy.auditCompletedAt }}: {{ formatDateTime(businessDateData.latest_night_audit.completed_at) }}
              </small>
            </template>
            <template v-else>
              <strong>{{ copy.noAuditYet }}</strong>
            </template>
          </div>
        </div>
      </article>
    </section>

    <!-- Night Audit Confirmation Modal -->
    <div v-if="nightAuditModalOpen" class="audit-modal-backdrop" @click="closeNightAuditModal"></div>
    <section v-if="nightAuditModalOpen" class="audit-modal-shell">
      <article class="audit-modal-card" @click.stop>
        <div class="audit-modal-card__header">
          <div>
            <span class="section-kicker">{{ copy.nightAudit }}</span>
            <h2>{{ copy.auditConfirmTitle }}</h2>
            <p>{{ copy.auditConfirmDescription }}</p>
          </div>
          <button type="button" class="icon-button" @click="closeNightAuditModal">
            <Icon :icon="mdiClose" />
          </button>
        </div>

        <label class="field">
          <span>{{ copy.auditNotes }}</span>
          <textarea v-model="nightAuditNotes" class="text-input" :placeholder="copy.auditNotesPlaceholder" rows="3" />
        </label>

        <div class="action-pair">
          <button type="button" class="secondary-button" :disabled="nightAuditRunning" @click="closeNightAuditModal">
            {{ copy.auditCancel }}
          </button>
          <button type="button" class="primary-button" :disabled="nightAuditRunning" @click="runNightAudit">
            <Icon :icon="mdiMoonWaningCrescent" />
            {{ nightAuditRunning ? copy.auditRunning : copy.auditSubmit }}
          </button>
        </div>
      </article>
    </section>
  </AppShell>
</template>

<style scoped>
.audit-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.audit-card {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.audit-card strong {
  color: var(--color-text);
}

.audit-card small {
  color: var(--color-text-soft);
  font-size: 0.82rem;
}

.audit-date-value {
  font-size: 1.25rem;
}

.audit-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(26, 20, 18, 0.4);
  z-index: 160;
}

.audit-modal-shell {
  position: fixed;
  inset: 0;
  z-index: 170;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 20px;
}

.audit-modal-card {
  width: min(580px, 100%);
  max-height: calc(100vh - 64px);
  overflow: auto;
  border-radius: 28px;
  padding: 24px;
  background: var(--color-surface-strong);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-lg);
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.audit-modal-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.audit-modal-card__header p {
  color: var(--color-text-soft);
  margin-top: 6px;
  font-size: 0.9rem;
}

.textarea-input {
  resize: vertical;
  min-height: 72px;
}

@media (max-width: 1024px) {
  .audit-grid {
    grid-template-columns: 1fr;
  }
}
</style>
