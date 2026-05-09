<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiClose from '@iconify-icons/mdi/close'
import mdiInformationOutline from '@iconify-icons/mdi/information-outline'
import mdiRefresh from '@iconify-icons/mdi/refresh'
import mdiTableLarge from '@iconify-icons/mdi/table-large'
import mdiTimelineTextOutline from '@iconify-icons/mdi/timeline-text-outline'
import mdiTrayArrowDown from '@iconify-icons/mdi/tray-arrow-down'
import mdiViewGridOutline from '@iconify-icons/mdi/view-grid-outline'
import mdiViewListOutline from '@iconify-icons/mdi/view-list-outline'
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'

import AppShell from '../components/AppShell.vue'
import SearchSelect from '../components/SearchSelect.vue'
import ServerDataTable from '../components/ServerDataTable.vue'
import { useAppLocale } from '../composables/useAppLocale'
import { useAuthSession } from '../composables/useAuthSession'
import { buildApiUrl } from '../lib/api'

type InquiryStatus = 'new' | 'contacted' | 'qualified' | 'converted' | 'cancelled'

type ReservationInquiry = {
  id: number
  full_name: string
  phone: string
  email: string | null
  guest_count: number
  check_in_date: string
  check_out_date: string
  notes: string | null
  source: string
  status: InquiryStatus
  property: {
    code: string | null
    name: string | null
  }
  room_type: {
    code: string | null
    name: string | null
  }
  created_at: string
}

type InquirySummaryItem = {
  status: InquiryStatus
  count: number
}

type InquiryPayload = {
  summary: InquirySummaryItem[]
  items: ReservationInquiry[]
  filters: {
    status: string
    search: string
  }
  available_statuses: InquiryStatus[]
  sort: {
    by: string
    direction: 'asc' | 'desc'
  }
}

type InquiryMeta = {
  total: number
  current_page: number
  per_page: number
  last_page: number
  from: number | null
  to: number | null
}

type InquiryViewMode = 'table' | 'list' | 'grid'

type InsightTab = 'summary' | 'workflow'

const { state: authState } = useAuthSession()
const { isEnglish, language } = useAppLocale()

const loading = ref(true)
const refreshing = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const inquiries = ref<ReservationInquiry[]>([])
const summary = ref<InquirySummaryItem[]>([])
const availableStatuses = ref<InquiryStatus[]>([])
const updatingInquiryId = ref<number | null>(null)
const currentView = ref<InquiryViewMode>('table')
const selectedInquiry = ref<ReservationInquiry | null>(null)
const insightModalOpen = ref(false)
const insightTab = ref<InsightTab>('summary')
const meta = ref<InquiryMeta>({
  total: 0,
  current_page: 1,
  per_page: 10,
  last_page: 1,
  from: null,
  to: null,
})

const filters = reactive({
  status: 'all',
  search: '',
})

const tableQuery = reactive({
  page: 1,
  perPage: 10,
  sortBy: 'created_at',
  sortDirection: 'desc' as 'asc' | 'desc',
})

const copy = computed(() => {
  if (isEnglish.value) {
    return {
      title: 'Booking Inquiries',
      eyebrow: 'Reservation Intake',
      summary: 'Operational inbox for inquiries coming from the public portal so the reservations team can follow up, qualify, and continue to the reservation stage.',
      totalInquiry: 'Total Inquiries',
      newInquiry: 'New',
      qualified: 'Qualified',
      converted: 'Converted',
      columns: ['Guest', 'Contact', 'Stay', 'Status', 'Submitted'],
      viewOptions: ['Table', 'List', 'Grid'],
      workflowSteps: [
        'Review new inquiries coming from the public portal.',
        'Contact the guest to confirm stay needs and current availability.',
        'Change the status to qualified once the inquiry is valid.',
        'Continue to reservation conversion in the next batch.',
      ],
      allStatuses: 'All statuses',
      allStatusesDesc: 'Show all incoming inquiries',
      inquiryCount: 'inquiries',
      invalidInquiryResponse: 'Invalid inquiry server response.',
      loadFailed: 'Booking inquiries failed to load.',
      invalidStatusResponse: 'Invalid inquiry status update response.',
      statusUpdateFailed: 'Inquiry status failed to update.',
      inbox: 'Inquiry inbox',
      inboxTitle: 'List of inquiries coming from the portal',
      inboxGuide: 'Inbox guide',
      refreshData: 'Refresh data',
      refreshing: 'Refreshing...',
      filterStatus: 'Filter status',
      selectStatus: 'Select status',
      searchStatus: 'Search inquiry status...',
      searchContact: 'Search name / contact',
      searchContactPlaceholder: 'Search guest, phone, email...',
      loadingInquiry: 'Loading inquiries...',
      viewMode: 'View mode',
      viewModeTitle: 'Choose the most comfortable working view',
      liveList: 'Live list',
      liveListTitle: 'Inquiries ready for follow-up',
      noInquiry: 'No inquiries for this filter yet',
      noInquiryHint: 'Try changing the status filter or submit a new inquiry from the public portal.',
      emailMissing: 'Email not provided',
      guests: 'guests',
      contact: 'Contact',
      stay: 'Stay',
      submitted: 'Submitted',
      source: 'Source',
      guestNotes: 'Guest notes',
      noNotes: 'No additional notes from the guest yet.',
      viewDetail: 'View detail',
      totalInquiries: 'total inquiries',
      prev: 'Prev',
      next: 'Next',
      inboxGuideTitle: 'Quick guide for Booking Inquiries',
      statusPulse: 'Status pulse',
      workflow: 'Workflow',
      inquiryDetail: 'Inquiry detail',
      actionStatus: 'Status action',
      saving: 'Saving...',
    }
  }

  return {
    title: 'Booking Inquiries',
    eyebrow: 'Reservation Intake',
    summary: 'Inbox operasional untuk inquiry yang masuk dari portal publik agar tim reservasi bisa follow up, kualifikasi, dan lanjutkan ke tahap reservasi.',
    totalInquiry: 'Total Inquiry',
    newInquiry: 'Baru masuk',
    qualified: 'Siap diproses',
    converted: 'Sudah dikonversi',
    columns: ['Tamu', 'Kontak', 'Stay', 'Status', 'Masuk'],
    viewOptions: ['Table', 'List', 'Grid'],
    workflowSteps: [
      'Review inquiry yang baru masuk dari portal publik.',
      'Hubungi tamu untuk cek kebutuhan stay dan availability aktual.',
      'Ubah status ke siap diproses kalau inquiry sudah valid.',
      'Lanjutkan ke reservation conversion di batch berikutnya.',
    ],
    allStatuses: 'Semua status',
    allStatusesDesc: 'Tampilkan seluruh inquiry yang masuk',
    inquiryCount: 'inquiry',
    invalidInquiryResponse: 'Respons server inquiry tidak valid.',
    loadFailed: 'Booking inquiries gagal dimuat.',
    invalidStatusResponse: 'Respons update status inquiry tidak valid.',
    statusUpdateFailed: 'Status inquiry gagal diperbarui.',
    inbox: 'Inquiry inbox',
    inboxTitle: 'Daftar inquiry yang masuk dari portal',
    inboxGuide: 'Panduan inbox',
    refreshData: 'Refresh data',
    refreshing: 'Merefresh...',
    filterStatus: 'Filter status',
    selectStatus: 'Pilih status',
    searchStatus: 'Cari status inquiry...',
    searchContact: 'Cari nama / kontak',
    searchContactPlaceholder: 'Cari tamu, phone, email...',
    loadingInquiry: 'Loading inquiry...',
    viewMode: 'View mode',
    viewModeTitle: 'Pilih tampilan kerja yang paling nyaman',
    liveList: 'Live list',
    liveListTitle: 'Inquiry yang siap difollow up',
    noInquiry: 'Belum ada inquiry untuk filter ini',
    noInquiryHint: 'Coba ubah filter status atau kirim inquiry baru dari portal publik.',
    emailMissing: 'Email belum diisi',
    guests: 'tamu',
    contact: 'Kontak',
    stay: 'Stay',
    submitted: 'Masuk',
    source: 'Source',
    guestNotes: 'Catatan tamu',
    noNotes: 'Belum ada catatan tambahan dari tamu.',
    viewDetail: 'Lihat detail',
    totalInquiries: 'total inquiry',
    prev: 'Prev',
    next: 'Next',
    inboxGuideTitle: 'Panduan singkat Booking Inquiries',
    statusPulse: 'Status pulse',
    workflow: 'Workflow',
    inquiryDetail: 'Inquiry detail',
    actionStatus: 'Action status',
    saving: 'Menyimpan...',
  }
})

const statusLabelMap = computed<Record<InquiryStatus, string>>(() => ({
  new: copy.value.newInquiry,
  contacted: isEnglish.value ? 'Contacted' : 'Sudah dihubungi',
  qualified: copy.value.qualified,
  converted: copy.value.converted,
  cancelled: isEnglish.value ? 'Cancelled' : 'Dibatalkan',
}))

const statusBadgeClassMap: Record<InquiryStatus, string> = {
  new: 'status-badge status-badge--soft',
  contacted: 'status-badge',
  qualified: 'status-badge status-badge--success',
  converted: 'status-badge status-badge--success',
  cancelled: 'status-badge status-badge--warning',
}

const authHeaders = (includeJson = false) => ({
  ...(includeJson ? { 'Content-Type': 'application/json' } : {}),
  Authorization: `Bearer ${authState.token}`,
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

const formatDate = (value: string) => new Intl.DateTimeFormat(language.value === 'en' ? 'en-US' : 'id-ID', {
  day: '2-digit',
  month: 'short',
  year: 'numeric',
}).format(new Date(value))

const formatDateTime = (value: string) => new Intl.DateTimeFormat(language.value === 'en' ? 'en-US' : 'id-ID', {
  day: '2-digit',
  month: 'short',
  hour: '2-digit',
  minute: '2-digit',
}).format(new Date(value))

const summaryCount = (status: InquiryStatus) => summary.value.find((item) => item.status === status)?.count ?? 0

const metrics = computed(() => [
  { label: copy.value.totalInquiry, value: String(summary.value.reduce((total, item) => total + item.count, 0)), tone: 'primary' as const },
  { label: copy.value.newInquiry, value: String(summaryCount('new')), tone: 'warning' as const },
  { label: copy.value.qualified, value: String(summaryCount('qualified')), tone: 'success' as const },
  { label: copy.value.converted, value: String(summaryCount('converted')), tone: 'neutral' as const },
])

const inquiryColumns = computed(() => [
  { key: 'full_name', label: copy.value.columns[0], sortable: true },
  { key: 'phone', label: copy.value.columns[1], sortable: true },
  { key: 'check_in_date', label: copy.value.columns[2], sortable: true },
  { key: 'status', label: copy.value.columns[3], sortable: true, align: 'center' as const },
  { key: 'created_at', label: copy.value.columns[4], sortable: true },
])

const viewOptions = computed(() => ([
  { value: 'table', label: copy.value.viewOptions[0], icon: mdiTableLarge },
  { value: 'list', label: copy.value.viewOptions[1], icon: mdiViewListOutline },
  { value: 'grid', label: copy.value.viewOptions[2], icon: mdiViewGridOutline },
]) as const)

const workflowSteps = computed(() => copy.value.workflowSteps)

const tableRows = computed(() => inquiries.value as unknown as Record<string, unknown>[])

const asInquiry = (row: unknown) => row as ReservationInquiry

const pageNumbers = computed(() => {
  const start = Math.max(1, meta.value.current_page - 2)
  const end = Math.min(meta.value.last_page, start + 4)
  const adjustedStart = Math.max(1, end - 4)

  return Array.from({ length: Math.max(0, end - adjustedStart + 1) }, (_, index) => adjustedStart + index)
})

const statusFilterOptions = computed(() => [
  {
    value: 'all',
    label: copy.value.allStatuses,
    description: copy.value.allStatusesDesc,
  },
  ...availableStatuses.value.map((status) => ({
    value: status,
    label: statusLabelMap.value[status],
    description: `${summaryCount(status)} ${copy.value.inquiryCount}`,
  })),
])

const loadInquiries = async (mode: 'initial' | 'refresh' = 'initial') => {
  if (mode === 'initial') {
    loading.value = true
  } else {
    refreshing.value = true
  }

  errorMessage.value = ''

  try {
    const query = new URLSearchParams()

    if (filters.status !== 'all') {
      query.set('status', filters.status)
    }

    if (filters.search.trim()) {
      query.set('search', filters.search.trim())
    }

    query.set('page', String(tableQuery.page))
    query.set('per_page', String(tableQuery.perPage))
    query.set('sort_by', tableQuery.sortBy)
    query.set('sort_direction', tableQuery.sortDirection)

    const response = await fetch(buildApiUrl(`/api/v1/reservation-inquiries?${query.toString()}`), {
      headers: authHeaders(),
    })

    const payload = await parseJsonResponse<{
      success: boolean
      message: string
      data: InquiryPayload
      meta: InquiryMeta
    }>(response, copy.value.invalidInquiryResponse)

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || copy.value.loadFailed)
    }

    inquiries.value = payload.data.items
    summary.value = payload.data.summary
    availableStatuses.value = payload.data.available_statuses
    meta.value = payload.meta
    tableQuery.page = payload.meta.current_page
    tableQuery.perPage = payload.meta.per_page
    tableQuery.sortBy = payload.data.sort.by
    tableQuery.sortDirection = payload.data.sort.direction
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : copy.value.loadFailed
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

const openInquiryDetail = (inquiry: ReservationInquiry) => {
  selectedInquiry.value = inquiry
}

const closeInquiryDetail = () => {
  selectedInquiry.value = null
}

const openInsightModal = (tab: InsightTab = 'summary') => {
  insightTab.value = tab
  insightModalOpen.value = true
}

const closeInsightModal = () => {
  insightModalOpen.value = false
}

const handleSortChange = ({ sortBy, sortDirection }: { sortBy: string; sortDirection: 'asc' | 'desc' }) => {
  tableQuery.sortBy = sortBy
  tableQuery.sortDirection = sortDirection
  tableQuery.page = 1
  loadInquiries('refresh')
}

const handlePageChange = (page: number) => {
  tableQuery.page = page
  loadInquiries('refresh')
}

const handlePerPageChange = (perPage: number) => {
  tableQuery.perPage = perPage
  tableQuery.page = 1
  loadInquiries('refresh')
}

const handleRowClick = (row: Record<string, unknown>) => {
  openInquiryDetail(row as ReservationInquiry)
}

const updateInquiryStatus = async (inquiryId: number, status: InquiryStatus) => {
  updatingInquiryId.value = inquiryId
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch(buildApiUrl(`/api/v1/reservation-inquiries/${inquiryId}/status`), {
      method: 'PATCH',
      headers: authHeaders(true),
      body: JSON.stringify({ status }),
    })

    const payload = await parseJsonResponse<{
      success: boolean
      message: string
      errors?: Record<string, string[]>
    }>(response, copy.value.invalidStatusResponse)

    if (!response.ok || !payload.success) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || copy.value.statusUpdateFailed)
    }

    successMessage.value = payload.message
    await loadInquiries('refresh')

    if (selectedInquiry.value) {
      selectedInquiry.value = inquiries.value.find((item) => item.id === selectedInquiry.value?.id) ?? null
    }
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : copy.value.statusUpdateFailed
  } finally {
    updatingInquiryId.value = null
  }
}

let searchDebounce: number | undefined

watch(() => filters.status, () => {
  tableQuery.page = 1
  loadInquiries('refresh')
})

watch(() => filters.search, () => {
  if (searchDebounce) {
    window.clearTimeout(searchDebounce)
  }

  searchDebounce = window.setTimeout(() => {
    tableQuery.page = 1
    loadInquiries('refresh')
  }, 250)
})

onMounted(() => {
  loadInquiries()
})

onUnmounted(() => {
  if (searchDebounce) {
    window.clearTimeout(searchDebounce)
  }
})
</script>

<template>
  <AppShell
    :title="copy.title"
    :eyebrow="copy.eyebrow"
    :summary="copy.summary"
    :metrics="metrics"
    hero-variant="plain"
  >
    <section class="content-grid">
      <article class="surface-card surface-card--wide">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.inbox }}</span>
            <h2>{{ copy.inboxTitle }}</h2>
          </div>

          <div class="action-pair">
            <button type="button" class="ghost-button" @click="openInsightModal()">
              <Icon :icon="mdiInformationOutline" />
              {{ copy.inboxGuide }}
            </button>
            <button type="button" class="secondary-button" :disabled="refreshing" @click="loadInquiries('refresh')">
              <Icon :icon="mdiRefresh" />
              {{ refreshing ? copy.refreshing : copy.refreshData }}
            </button>
          </div>
        </div>

        <div class="inquiry-toolbar">
          <label class="field">
            <span>{{ copy.filterStatus }}</span>
            <SearchSelect
              v-model="filters.status"
              :options="statusFilterOptions"
              :placeholder="copy.selectStatus"
              :search-placeholder="copy.searchStatus"
            />
          </label>

          <label class="field">
            <span>{{ copy.searchContact }}</span>
            <input v-model="filters.search" type="text" class="text-input" :placeholder="copy.searchContactPlaceholder" />
          </label>
        </div>

        <div class="cms-status-row">
          <div v-if="loading" class="cms-status-pill">
            <Icon :icon="mdiRefresh" class="cms-spin" />
            {{ copy.loadingInquiry }}
          </div>
          <div v-else-if="successMessage" class="cms-status-pill cms-status-pill--success">{{ successMessage }}</div>
          <div v-if="errorMessage" class="cms-status-pill cms-status-pill--error">{{ errorMessage }}</div>
        </div>
      </article>
    </section>

    <section class="content-grid">
      <article class="surface-card surface-card--wide">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.viewMode }}</span>
            <h2>{{ copy.viewModeTitle }}</h2>
          </div>
          <div class="segmented">
            <button
              v-for="option in viewOptions"
              :key="option.value"
              type="button"
              class="segmented__button"
              :class="{ 'segmented__button--active': currentView === option.value }"
              @click="currentView = option.value"
            >
              <Icon :icon="option.icon" />
              {{ option.label }}
            </button>
          </div>
        </div>

        <div class="chip-grid">
          <div v-for="item in summary" :key="item.status" class="info-chip inquiry-chip">
            <strong>{{ statusLabelMap[item.status] }}</strong>
            <span>{{ item.count }} {{ copy.inquiryCount }}</span>
          </div>
        </div>
      </article>
    </section>

    <section class="content-grid">
      <article class="surface-card surface-card--wide">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.liveList }}</span>
            <h2>{{ copy.liveListTitle }}</h2>
          </div>
        </div>

        <div v-if="!loading && inquiries.length === 0" class="portal-state-card">
          <Icon :icon="mdiTrayArrowDown" />
          <strong>{{ copy.noInquiry }}</strong>
          <span>{{ copy.noInquiryHint }}</span>
        </div>

        <ServerDataTable
          v-else-if="currentView === 'table'"
          row-key="id"
          :columns="inquiryColumns"
          :rows="tableRows"
          :meta="meta"
          :loading="loading"
          :sort-by="tableQuery.sortBy"
          :sort-direction="tableQuery.sortDirection"
          :empty-message="copy.noInquiry"
          clickable-rows
          @sort-change="handleSortChange"
          @page-change="handlePageChange"
          @per-page-change="handlePerPageChange"
          @row-click="handleRowClick"
        >
          <template #cell-full_name="{ row }">
            <div class="inquiry-table-cell">
              <strong>{{ asInquiry(row).full_name }}</strong>
              <span>#{{ asInquiry(row).id }} · {{ asInquiry(row).property.code }}</span>
            </div>
          </template>

          <template #cell-phone="{ row }">
            <div class="inquiry-table-cell">
              <strong>{{ asInquiry(row).phone }}</strong>
              <span>{{ asInquiry(row).email || copy.emailMissing }}</span>
            </div>
          </template>

          <template #cell-check_in_date="{ row }">
            <div class="inquiry-table-cell">
              <strong>{{ formatDate(asInquiry(row).check_in_date) }}</strong>
              <span>{{ asInquiry(row).guest_count }} {{ copy.guests }} · {{ asInquiry(row).room_type.name }}</span>
            </div>
          </template>

          <template #cell-status="{ row }">
            <span :class="statusBadgeClassMap[asInquiry(row).status]">{{ statusLabelMap[asInquiry(row).status] }}</span>
          </template>

          <template #cell-created_at="{ row }">
            <div class="inquiry-table-cell">
              <strong>{{ formatDateTime(asInquiry(row).created_at) }}</strong>
              <span>{{ asInquiry(row).source }}</span>
            </div>
          </template>
        </ServerDataTable>

        <div v-else-if="currentView === 'list'" class="inquiry-list">
          <article v-for="inquiry in inquiries" :key="inquiry.id" class="inquiry-card">
            <div class="inquiry-card__top">
              <div>
                <span class="section-kicker">#{{ inquiry.id }} · {{ inquiry.property.code }}</span>
                <h3>{{ inquiry.full_name }}</h3>
              </div>
              <span :class="statusBadgeClassMap[inquiry.status]">{{ statusLabelMap[inquiry.status] }}</span>
            </div>

            <div class="inquiry-card__grid">
              <div class="inquiry-card__block">
                <span>{{ copy.contact }}</span>
                <strong>{{ inquiry.phone }}</strong>
                <small>{{ inquiry.email || copy.emailMissing }}</small>
              </div>

              <div class="inquiry-card__block">
                <span>{{ copy.stay }}</span>
                <strong>{{ formatDate(inquiry.check_in_date) }} → {{ formatDate(inquiry.check_out_date) }}</strong>
                <small>{{ inquiry.guest_count }} {{ copy.guests }} · {{ inquiry.room_type.name }}</small>
              </div>

              <div class="inquiry-card__block">
                <span>{{ copy.submitted }}</span>
                <strong>{{ formatDateTime(inquiry.created_at) }}</strong>
                <small>{{ copy.source }}: {{ inquiry.source }}</small>
              </div>
            </div>

            <div class="inquiry-card__notes">
              <span>{{ copy.guestNotes }}</span>
              <p>{{ inquiry.notes || copy.noNotes }}</p>
            </div>

            <div class="inquiry-card__actions">
              <button type="button" class="ghost-button" @click="openInquiryDetail(inquiry)">
                {{ copy.viewDetail }}
              </button>
            </div>
          </article>
        </div>

        <div v-else class="inquiry-card-grid">
          <button
            v-for="inquiry in inquiries"
            :key="inquiry.id"
            type="button"
            class="inquiry-grid-card"
            @click="openInquiryDetail(inquiry)"
          >
            <div class="inquiry-grid-card__top">
              <span class="section-kicker">#{{ inquiry.id }} · {{ inquiry.property.code }}</span>
              <span :class="statusBadgeClassMap[inquiry.status]">{{ statusLabelMap[inquiry.status] }}</span>
            </div>
            <div class="inquiry-grid-card__body">
              <strong>{{ inquiry.full_name }}</strong>
              <span>{{ inquiry.phone }}</span>
              <span>{{ formatDate(inquiry.check_in_date) }} → {{ formatDate(inquiry.check_out_date) }}</span>
              <span>{{ inquiry.guest_count }} {{ copy.guests }} · {{ inquiry.room_type.name }}</span>
            </div>
          </button>
        </div>

        <div v-if="currentView !== 'table' && meta.total > 0" class="inquiry-pagination">
          <div class="inquiry-pagination__summary">
            <strong>{{ meta.total }}</strong>
            <span>{{ copy.totalInquiries }}</span>
            <span v-if="meta.from !== null && meta.to !== null">· {{ meta.from }}-{{ meta.to }}</span>
          </div>

          <div class="inquiry-pagination__controls">
            <button type="button" class="ghost-button" :disabled="meta.current_page <= 1" @click="handlePageChange(meta.current_page - 1)">
              {{ copy.prev }}
            </button>
            <button
              v-for="page in pageNumbers"
              :key="`list-page-${page}`"
              type="button"
              class="segmented__button"
              :class="{ 'segmented__button--active': page === meta.current_page }"
              @click="handlePageChange(page)"
            >
              {{ page }}
            </button>
            <button type="button" class="ghost-button" :disabled="meta.current_page >= meta.last_page" @click="handlePageChange(meta.current_page + 1)">
              {{ copy.next }}
            </button>
          </div>
        </div>
      </article>
    </section>

    <div v-if="insightModalOpen || selectedInquiry" class="inquiry-modal-backdrop" @click="closeInsightModal(); closeInquiryDetail()"></div>

    <section v-if="insightModalOpen" class="inquiry-modal-shell">
      <article class="inquiry-modal-card" @click.stop>
        <div class="inquiry-modal-card__header">
          <div>
            <span class="section-kicker">{{ copy.inboxGuide }}</span>
            <h2>{{ copy.inboxGuideTitle }}</h2>
          </div>
          <button type="button" class="icon-button" @click="closeInsightModal">
            <Icon :icon="mdiClose" />
          </button>
        </div>

        <div class="segmented">
          <button
            type="button"
            class="segmented__button"
            :class="{ 'segmented__button--active': insightTab === 'summary' }"
            @click="insightTab = 'summary'"
          >
            <Icon :icon="mdiInformationOutline" />
            {{ copy.statusPulse }}
          </button>
          <button
            type="button"
            class="segmented__button"
            :class="{ 'segmented__button--active': insightTab === 'workflow' }"
            @click="insightTab = 'workflow'"
          >
            <Icon :icon="mdiTimelineTextOutline" />
            {{ copy.workflow }}
          </button>
        </div>

        <div v-if="insightTab === 'summary'" class="chip-grid">
          <div v-for="item in summary" :key="`modal-${item.status}`" class="info-chip inquiry-chip">
            <strong>{{ statusLabelMap[item.status] }}</strong>
            <span>{{ item.count }} {{ copy.inquiryCount }}</span>
          </div>
        </div>

        <ol v-else class="ordered-list">
          <li v-for="step in workflowSteps" :key="step">{{ step }}</li>
        </ol>
      </article>
    </section>

    <section v-if="selectedInquiry" class="inquiry-modal-shell">
      <article class="inquiry-modal-card inquiry-modal-card--detail" @click.stop>
        <div class="inquiry-modal-card__header">
          <div>
            <span class="section-kicker">{{ copy.inquiryDetail }}</span>
            <h2>{{ selectedInquiry.full_name }}</h2>
            <p>#{{ selectedInquiry.id }} · {{ selectedInquiry.property.name }} · {{ selectedInquiry.room_type.name }}</p>
          </div>
          <button type="button" class="icon-button" @click="closeInquiryDetail">
            <Icon :icon="mdiClose" />
          </button>
        </div>

        <div class="inquiry-detail-grid">
          <div class="inquiry-card__block">
            <span>{{ copy.contact }}</span>
            <strong>{{ selectedInquiry.phone }}</strong>
            <small>{{ selectedInquiry.email || copy.emailMissing }}</small>
          </div>
          <div class="inquiry-card__block">
            <span>{{ copy.stay }}</span>
            <strong>{{ formatDate(selectedInquiry.check_in_date) }} → {{ formatDate(selectedInquiry.check_out_date) }}</strong>
            <small>{{ selectedInquiry.guest_count }} {{ copy.guests }}</small>
          </div>
          <div class="inquiry-card__block">
            <span>Status</span>
            <strong>{{ statusLabelMap[selectedInquiry.status] }}</strong>
            <small>{{ formatDateTime(selectedInquiry.created_at) }}</small>
          </div>
        </div>

        <div class="inquiry-card__notes">
          <span>{{ copy.guestNotes }}</span>
          <p>{{ selectedInquiry.notes || copy.noNotes }}</p>
        </div>

        <div class="inquiry-modal-card__footer">
          <span class="section-kicker">{{ copy.actionStatus }}</span>
          <div class="inquiry-card__actions">
            <button
              v-for="status in availableStatuses"
              :key="`detail-${selectedInquiry.id}-${status}`"
              type="button"
              class="secondary-button inquiry-status-button"
              :class="{ 'inquiry-status-button--active': selectedInquiry.status === status }"
              :disabled="updatingInquiryId === selectedInquiry.id || selectedInquiry.status === status"
              @click="updateInquiryStatus(selectedInquiry.id, status)"
            >
              {{ updatingInquiryId === selectedInquiry.id && selectedInquiry.status !== status ? copy.saving : statusLabelMap[status] }}
            </button>
          </div>
        </div>
      </article>
    </section>
  </AppShell>
</template>
