<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiCashMultiple from '@iconify-icons/mdi/cash-multiple'
import mdiCheckCircleOutline from '@iconify-icons/mdi/check-circle-outline'
import mdiClose from '@iconify-icons/mdi/close'
import mdiRefresh from '@iconify-icons/mdi/refresh'
import mdiTrayArrowDown from '@iconify-icons/mdi/tray-arrow-down'
import { computed, onMounted, reactive, ref, watch } from 'vue'

import AppShell from '../components/AppShell.vue'
import ServerDataTable from '../components/ServerDataTable.vue'
import { useAppLocale } from '../composables/useAppLocale'
import { useAuthSession } from '../composables/useAuthSession'
import { buildApiUrl } from '../lib/api'

type DepartureStatus = 'checked_in' | 'checked_out' | 'cancelled'

type DepartureRow = {
  id: number
  booking_code: string
  reservation_status: DepartureStatus
  check_out_date: string
  actual_check_out: string | null
  is_departing_today: boolean
  primary_guest: {
    id: number | null
    full_name: string | null
    phone: string | null
  }
  assigned_room: {
    id: number | null
    room_number: string | null
    current_status: string | null
    housekeeping_status: string | null
  }
  room_type: {
    id: number | null
    name: string | null
    code: string | null
  }
  invoice: {
    id: number | null
    invoice_number: string | null
    invoice_status: string | null
    grand_total: number | null
    remaining_amount: number | null
  } | null
}

type DepartureMeta = {
  total: number
  current_page: number
  per_page: number
  last_page: number
  from: number | null
  to: number | null
}

type BillPreviewLine = {
  item_type: string
  item_name: string
  quantity: number
  unit_price: number
  line_total: number
}

type BillPreview = {
  room_charges: number
  extra_charges: number
  damage_fee: number
  late_checkout_fee: number
  lost_keycard_fee: number
  subtotal: number
  tax_amount: number
  grand_total: number
  paid_amount: number
  remaining_amount: number
  lines: BillPreviewLine[]
}

const { state: authState } = useAuthSession()
const { isEnglish } = useAppLocale()

const loading = ref(true)
const refreshing = ref(false)
const loadingPreview = ref(false)
const completingCheckout = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const departures = ref<DepartureRow[]>([])
const selectedDeparture = ref<DepartureRow | null>(null)
const billPreview = ref<BillPreview | null>(null)
const meta = ref<DepartureMeta>({
  total: 0,
  current_page: 1,
  per_page: 20,
  last_page: 1,
  from: null,
  to: null,
})

const tableQuery = reactive({
  page: 1,
  perPage: 20,
  sortBy: 'check_out_date',
  sortDirection: 'asc' as 'asc' | 'desc',
})

const filterDate = ref(new Date().toISOString().split('T')[0])

const checkoutForm = reactive({
  damage_fee_amount: 0,
  damage_fee_notes: '',
  late_checkout_hours: 0,
  late_checkout_fee_amount: 0,
  lost_keycard_fee: 0,
  room_inspected: true,
  keycard_returned: true,
  room_condition_notes: '',
  payment_method_code: 'cash',
  payment_amount: 0,
  payment_reference: '',
  notes: '',
})

const copy = computed(() => {
  if (isEnglish.value) {
    return {
      title: 'Departures',
      metrics: ['Departures', 'Pending Bill', 'Ready checkout'],
      departureQueue: 'Departure Queue',
      departureQueueTitle: 'Checkout control panel for today\'s departing guests',
      refreshQueue: 'Refresh queue',
      refreshing: 'Refreshing...',
      loadingQueue: 'Loading departure queue...',
      liveDepartures: 'Live departures',
      liveDeparturesTitle: 'Checkout queue ready to process',
      noDeparture: 'No departures for this filter',
      noDepartureHint: 'Try changing the date filter.',
      filterDate: 'Date',
      invalidResponse: 'Invalid departure response.',
      loadFailed: 'Failed to load departure queue.',
      invalidPreview: 'Invalid preview response.',
      previewFailed: 'Failed to load bill preview.',
      invalidCheckout: 'Invalid checkout response.',
      checkoutFailed: 'Checkout failed.',
      checkoutSuccess: 'Checkout completed successfully.',
      columns: ['Room', 'Guest', 'Booking', 'Check-out', 'Balance', 'Status'],
      statuses: {
        checked_in: 'In House',
        checked_out: 'Checked Out',
        cancelled: 'Cancelled',
      },
      noGuest: 'No guest',
      noPhone: 'No phone',
      noRoom: 'No room',
      noInvoice: 'No invoice',
      notDeparting: 'Not today',
      checkoutWorkspace: 'Checkout workspace',
      propertyFallback: 'Property',
      guestSummary: 'Guest summary',
      billPreview: 'Bill preview',
      roomInspection: 'Room inspection',
      payment: 'Payment',
      completeCheckout: 'Complete checkout',
      completing: 'Processing...',
      loadPreview: 'Load preview',
      loadingPreview: 'Loading preview...',
      damageFee: 'Damage fee',
      damageNotes: 'Damage notes',
      lateCheckoutHours: 'Late checkout (hours)',
      lateCheckoutFee: 'Late checkout fee',
      lostKeycardFee: 'Lost keycard fee',
      roomInspected: 'Room inspected',
      keycardReturned: 'Keycard returned',
      roomCondition: 'Room condition notes',
      roomConditionPlaceholder: 'Room condition after inspection',
      paymentMethod: 'Payment method',
      paymentAmount: 'Payment amount',
      paymentReference: 'Payment reference',
      checkoutNotes: 'Checkout notes',
      checkoutNotesPlaceholder: 'Operational notes during checkout',
      roomCharges: 'Room charges',
      extraCharges: 'Extra charges',
      subtotal: 'Subtotal',
      tax: 'Tax',
      grandTotal: 'Grand total',
      paid: 'Paid',
      remaining: 'Remaining',
      stay: 'Stay',
      invoice: 'Invoice',
      balance: 'Balance',
    }
  }

  return {
    title: 'Departures',
    metrics: ['Departures', 'Tagihan pending', 'Siap checkout'],
    departureQueue: 'Departure Queue',
    departureQueueTitle: 'Panel kontrol checkout untuk tamu yang akan checkout hari ini',
    refreshQueue: 'Refresh queue',
    refreshing: 'Merefresh...',
    loadingQueue: 'Loading departure queue...',
    liveDepartures: 'Live departures',
    liveDeparturesTitle: 'Queue checkout yang siap diproses',
    noDeparture: 'Belum ada departure untuk filter ini',
    noDepartureHint: 'Coba ubah filter tanggal.',
    filterDate: 'Tanggal',
    invalidResponse: 'Respons departure tidak valid.',
    loadFailed: 'Departure queue gagal dimuat.',
    invalidPreview: 'Respons preview tidak valid.',
    previewFailed: 'Bill preview gagal dimuat.',
    invalidCheckout: 'Respons checkout tidak valid.',
    checkoutFailed: 'Checkout gagal.',
    checkoutSuccess: 'Checkout berhasil diselesaikan.',
    columns: ['Room', 'Tamu', 'Booking', 'Check-out', 'Saldo', 'Status'],
    statuses: {
      checked_in: 'In House',
      checked_out: 'Checked Out',
      cancelled: 'Cancelled',
    },
    noGuest: 'Tidak ada tamu',
    noPhone: 'Tidak ada phone',
    noRoom: 'Tidak ada room',
    noInvoice: 'Tidak ada invoice',
    notDeparting: 'Bukan hari ini',
    checkoutWorkspace: 'Checkout workspace',
    propertyFallback: 'Property',
    guestSummary: 'Ringkasan tamu',
    billPreview: 'Bill preview',
    roomInspection: 'Inspeksi kamar',
    payment: 'Pembayaran',
    completeCheckout: 'Complete checkout',
    completing: 'Memproses...',
    loadPreview: 'Load preview',
    loadingPreview: 'Loading preview...',
    damageFee: 'Biaya kerusakan',
    damageNotes: 'Catatan kerusakan',
    lateCheckoutHours: 'Late checkout (jam)',
    lateCheckoutFee: 'Biaya late checkout',
    lostKeycardFee: 'Biaya keycard hilang',
    roomInspected: 'Kamar sudah diperiksa',
    keycardReturned: 'Keycard dikembalikan',
    roomCondition: 'Kondisi kamar',
    roomConditionPlaceholder: 'Kondisi kamar setelah inspeksi',
    paymentMethod: 'Metode pembayaran',
    paymentAmount: 'Jumlah pembayaran',
    paymentReference: 'Referensi pembayaran',
    checkoutNotes: 'Catatan checkout',
    checkoutNotesPlaceholder: 'Catatan operasional saat checkout',
    roomCharges: 'Biaya kamar',
    extraCharges: 'Biaya tambahan',
    subtotal: 'Subtotal',
    tax: 'Pajak',
    grandTotal: 'Grand total',
    paid: 'Dibayar',
    remaining: 'Sisa',
    stay: 'Stay',
    invoice: 'Invoice',
    balance: 'Saldo',
  }
})

const statusLabelMap = computed<Record<DepartureStatus, string>>(() => copy.value.statuses)

const statusBadgeClassMap: Record<DepartureStatus, string> = {
  checked_in: 'status-badge status-badge--success',
  checked_out: 'status-badge',
  cancelled: 'status-badge status-badge--warning',
}

const departureColumns = computed(() => [
  { key: 'assigned_room', label: copy.value.columns[0], sortable: false },
  { key: 'primary_guest', label: copy.value.columns[1], sortable: false },
  { key: 'booking_code', label: copy.value.columns[2], sortable: true },
  { key: 'check_out_date', label: copy.value.columns[3], sortable: true },
  { key: 'balance', label: copy.value.columns[4], sortable: false, align: 'right' as const },
  { key: 'reservation_status', label: copy.value.columns[5], sortable: true, align: 'center' as const },
])

const metrics = computed(() => [
  { label: copy.value.metrics[0], value: String(meta.value.total), tone: 'primary' as const },
  {
    label: copy.value.metrics[1],
    value: String(departures.value.filter((d) => d.invoice && (d.invoice.remaining_amount ?? 0) > 0).length),
    tone: 'warning' as const,
  },
  {
    label: copy.value.metrics[2],
    value: String(departures.value.filter((d) => d.reservation_status === 'checked_in').length),
    tone: 'success' as const,
  },
])

const tableRows = computed(() => departures.value as unknown as Record<string, unknown>[])
const asDeparture = (row: unknown) => row as DepartureRow

const formatCurrency = (value: number | null | undefined) => {
  if (value == null) return '-'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}

const formatDate = (value: string) => new Intl.DateTimeFormat('id-ID', {
  day: '2-digit',
  month: 'short',
  year: 'numeric',
}).format(new Date(value))

const authHeaders = (includeJson = false) => ({
  ...(includeJson ? { 'Content-Type': 'application/json' } : {}),
  Authorization: `Bearer ${authState.token}`,
})

const parseJsonResponse = async <T>(response: Response, fallbackMessage: string): Promise<T> => {
  const raw = await response.text()
  if (!raw.trim()) throw new Error(fallbackMessage)
  try {
    return JSON.parse(raw) as T
  } catch {
    throw new Error(fallbackMessage)
  }
}

const resetCheckoutForm = () => {
  checkoutForm.damage_fee_amount = 0
  checkoutForm.damage_fee_notes = ''
  checkoutForm.late_checkout_hours = 0
  checkoutForm.late_checkout_fee_amount = 0
  checkoutForm.lost_keycard_fee = 0
  checkoutForm.room_inspected = true
  checkoutForm.keycard_returned = true
  checkoutForm.room_condition_notes = ''
  checkoutForm.payment_method_code = 'cash'
  checkoutForm.payment_amount = 0
  checkoutForm.payment_reference = ''
  checkoutForm.notes = ''
  billPreview.value = null
}

const loadDepartures = async (mode: 'initial' | 'refresh' = 'initial') => {
  if (mode === 'initial') {
    loading.value = true
  } else {
    refreshing.value = true
  }
  errorMessage.value = ''

  try {
    const query = new URLSearchParams()
    query.set('date', filterDate.value)
    query.set('page', String(tableQuery.page))
    query.set('per_page', String(tableQuery.perPage))

    const response = await fetch(buildApiUrl(`/api/v1/front-desk/departures?${query.toString()}`), {
      headers: authHeaders(),
    })

    const payload = await parseJsonResponse<{
      success: boolean
      message: string
      data: { items: DepartureRow[] }
      meta: DepartureMeta
    }>(response, copy.value.invalidResponse)

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || copy.value.loadFailed)
    }

    departures.value = payload.data.items
    meta.value = payload.meta
    tableQuery.page = payload.meta.current_page
    tableQuery.perPage = payload.meta.per_page
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : copy.value.loadFailed
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

const loadBillPreview = async () => {
  if (!selectedDeparture.value) return
  loadingPreview.value = true
  errorMessage.value = ''

  try {
    const params = new URLSearchParams()
    if (checkoutForm.damage_fee_amount > 0) params.set('damage_fee_amount', String(checkoutForm.damage_fee_amount))
    if (checkoutForm.damage_fee_notes) params.set('damage_fee_notes', checkoutForm.damage_fee_notes)
    if (checkoutForm.late_checkout_hours > 0) params.set('late_checkout_hours', String(checkoutForm.late_checkout_hours))
    if (checkoutForm.lost_keycard_fee > 0) params.set('lost_keycard_fee', String(checkoutForm.lost_keycard_fee))

    const qs = params.toString()
    const url = `/api/v1/front-desk/departures/${selectedDeparture.value.id}/preview${qs ? `?${qs}` : ''}`

    const response = await fetch(buildApiUrl(url), {
      headers: authHeaders(),
    })

    const payload = await parseJsonResponse<{
      success: boolean
      message: string
      data: {
        reservation: Record<string, unknown>
        room: Record<string, unknown>
        invoice: {
          id: number
          items: BillPreviewLine[]
          subtotal_amount: number
          tax_amount: number
          discount_amount: number
          grand_total: number
          paid_amount: number
          remaining_amount: number
        }
        payments: Array<Record<string, unknown>>
        additional_charges: {
          damage_fee: number
          late_checkout_fee: number
          lost_keycard_fee: number
        }
        business_date: string
      }
    }>(response, copy.value.invalidPreview)

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || copy.value.previewFailed)
    }

    // Map API nested response to flat BillPreview format used by template
    const inv = payload.data.invoice
    billPreview.value = {
      room_charges: inv.items.filter(i => i.item_type === 'room_charge').reduce((s, i) => s + i.line_total, 0),
      extra_charges: inv.items.filter(i => i.item_type !== 'room_charge').reduce((s, i) => s + i.line_total, 0),
      damage_fee: payload.data.additional_charges.damage_fee,
      late_checkout_fee: payload.data.additional_charges.late_checkout_fee,
      lost_keycard_fee: payload.data.additional_charges.lost_keycard_fee,
      subtotal: inv.subtotal_amount,
      tax_amount: inv.tax_amount,
      grand_total: inv.grand_total,
      paid_amount: inv.paid_amount,
      remaining_amount: inv.remaining_amount,
      lines: inv.items.map(i => ({
        item_type: i.item_type,
        item_name: i.item_name,
        quantity: i.quantity,
        unit_price: i.unit_price,
        line_total: i.line_total,
      })),
    }
    checkoutForm.payment_amount = inv.remaining_amount
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : copy.value.previewFailed
  } finally {
    loadingPreview.value = false
  }
}

const openDepartureDetail = (row: Record<string, unknown>) => {
  selectedDeparture.value = row as DepartureRow
  resetCheckoutForm()
}

const closeDepartureDetail = () => {
  selectedDeparture.value = null
  resetCheckoutForm()
}

const completeCheckout = async () => {
  if (!selectedDeparture.value) return

  completingCheckout.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch(buildApiUrl(`/api/v1/front-desk/departures/${selectedDeparture.value.id}/complete-checkout`), {
      method: 'POST',
      headers: authHeaders(true),
      body: JSON.stringify({
        damage_fee_amount: checkoutForm.damage_fee_amount > 0 ? checkoutForm.damage_fee_amount : null,
        damage_fee_notes: checkoutForm.damage_fee_notes || null,
        late_checkout_hours: checkoutForm.late_checkout_hours > 0 ? checkoutForm.late_checkout_hours : null,
        late_checkout_fee_amount: checkoutForm.late_checkout_fee_amount > 0 ? checkoutForm.late_checkout_fee_amount : null,
        lost_keycard_fee: checkoutForm.lost_keycard_fee > 0 ? checkoutForm.lost_keycard_fee : null,
        room_inspected: checkoutForm.room_inspected,
        room_condition_notes: checkoutForm.room_condition_notes || null,
        keycard_returned: checkoutForm.keycard_returned,
        payment_method_code: checkoutForm.payment_method_code || null,
        payment_amount: checkoutForm.payment_amount > 0 ? checkoutForm.payment_amount : null,
        payment_reference: checkoutForm.payment_reference || null,
        notes: checkoutForm.notes || null,
      }),
    })

    const payload = await parseJsonResponse<{
      success?: boolean
      message?: string
      errors?: Record<string, string[]>
    }>(response, copy.value.invalidCheckout)

    if (!response.ok || !payload.success) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || copy.value.checkoutFailed)
    }

    successMessage.value = payload.message || copy.value.checkoutSuccess
    closeDepartureDetail()
    await loadDepartures('refresh')
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : copy.value.checkoutFailed
  } finally {
    completingCheckout.value = false
  }
}

const handleSortChange = ({ sortBy, sortDirection }: { sortBy: string; sortDirection: 'asc' | 'desc' }) => {
  tableQuery.sortBy = sortBy
  tableQuery.sortDirection = sortDirection
  tableQuery.page = 1
  loadDepartures('refresh')
}

const handlePageChange = (page: number) => {
  tableQuery.page = page
  loadDepartures('refresh')
}

const handlePerPageChange = (perPage: number) => {
  tableQuery.perPage = perPage
  tableQuery.page = 1
  loadDepartures('refresh')
}

watch(() => filterDate.value, () => {
  tableQuery.page = 1
  loadDepartures('refresh')
})

onMounted(() => {
  loadDepartures()
})
</script>

<template>
  <AppShell :title="copy.title" :metrics="metrics" hero-variant="plain">
    <section class="content-grid">
      <article class="surface-card surface-card--wide">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.departureQueue }}</span>
            <h2>{{ copy.departureQueueTitle }}</h2>
          </div>

          <div class="action-pair">
            <label class="field departure-date-field">
              <span>{{ copy.filterDate }}</span>
              <input v-model="filterDate" type="date" class="text-input" />
            </label>
            <button type="button" class="secondary-button" :disabled="refreshing" @click="loadDepartures('refresh')">
              <Icon :icon="mdiRefresh" />
              {{ refreshing ? copy.refreshing : copy.refreshQueue }}
            </button>
          </div>
        </div>

        <div class="cms-status-row">
          <div v-if="loading" class="cms-status-pill">
            <Icon :icon="mdiRefresh" class="cms-spin" />
            {{ copy.loadingQueue }}
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
            <span class="section-kicker">{{ copy.liveDepartures }}</span>
            <h2>{{ copy.liveDeparturesTitle }}</h2>
          </div>
        </div>

        <div v-if="!loading && departures.length === 0" class="portal-state-card">
          <Icon :icon="mdiTrayArrowDown" />
          <strong>{{ copy.noDeparture }}</strong>
          <span>{{ copy.noDepartureHint }}</span>
        </div>

        <ServerDataTable
          v-else
          row-key="id"
          :columns="departureColumns"
          :rows="tableRows"
          :meta="meta"
          :loading="loading"
          :sort-by="tableQuery.sortBy"
          :sort-direction="tableQuery.sortDirection"
          :empty-message="copy.noDeparture"
          clickable-rows
          @sort-change="handleSortChange"
          @page-change="handlePageChange"
          @per-page-change="handlePerPageChange"
          @row-click="openDepartureDetail"
        >
          <template #cell-assigned_room="{ row }">
            <div class="departure-table-cell">
              <strong>{{ asDeparture(row).assigned_room.room_number || copy.noRoom }}</strong>
              <span>{{ asDeparture(row).room_type.name || '' }}</span>
            </div>
          </template>

          <template #cell-primary_guest="{ row }">
            <div class="departure-table-cell">
              <strong>{{ asDeparture(row).primary_guest.full_name || copy.noGuest }}</strong>
              <span>{{ asDeparture(row).primary_guest.phone || copy.noPhone }}</span>
            </div>
          </template>

          <template #cell-booking_code="{ row }">
            <div class="departure-table-cell">
              <strong>{{ asDeparture(row).booking_code }}</strong>
              <span>{{ asDeparture(row).is_departing_today ? '' : copy.notDeparting }}</span>
            </div>
          </template>

          <template #cell-check_out_date="{ row }">
            <div class="departure-table-cell">
              <strong>{{ formatDate(asDeparture(row).check_out_date) }}</strong>
            </div>
          </template>

          <template #cell-balance="{ row }">
            <div class="departure-table-cell departure-table-cell--right">
              <strong>{{ formatCurrency(asDeparture(row).invoice?.remaining_amount) }}</strong>
              <span>{{ asDeparture(row).invoice?.invoice_status || copy.noInvoice }}</span>
            </div>
          </template>

          <template #cell-reservation_status="{ row }">
            <span :class="statusBadgeClassMap[asDeparture(row).reservation_status]">
              {{ statusLabelMap[asDeparture(row).reservation_status] }}
            </span>
          </template>
        </ServerDataTable>
      </article>
    </section>

    <!-- Checkout Detail Modal -->
    <div v-if="selectedDeparture" class="departure-modal-backdrop" @click="closeDepartureDetail"></div>

    <section v-if="selectedDeparture" class="departure-modal-shell">
      <article class="departure-modal-card" @click.stop>
        <div class="departure-modal-card__header">
          <div>
            <span class="section-kicker">{{ copy.checkoutWorkspace }}</span>
            <h2>{{ selectedDeparture.primary_guest.full_name || selectedDeparture.booking_code }}</h2>
            <p>
              {{ selectedDeparture.booking_code }} ·
              Room {{ selectedDeparture.assigned_room.room_number || copy.noRoom }} ·
              {{ selectedDeparture.room_type.name || '' }}
            </p>
          </div>

          <button type="button" class="icon-button" @click="closeDepartureDetail">
            <Icon :icon="mdiClose" />
          </button>
        </div>

        <!-- Guest Summary -->
        <div class="departure-detail-grid">
          <div class="departure-summary-card">
            <span class="section-kicker">{{ copy.guestSummary }}</span>
            <strong>{{ selectedDeparture.primary_guest.full_name || copy.noGuest }}</strong>
            <small>{{ selectedDeparture.primary_guest.phone || copy.noPhone }}</small>
          </div>

          <div class="departure-summary-card">
            <span class="section-kicker">{{ copy.stay }}</span>
            <strong>Room {{ selectedDeparture.assigned_room.room_number || copy.noRoom }}</strong>
            <small>{{ statusLabelMap[selectedDeparture.reservation_status] }}</small>
          </div>

          <div class="departure-summary-card">
            <span class="section-kicker">{{ copy.invoice }}</span>
            <strong>{{ formatCurrency(selectedDeparture.invoice?.remaining_amount) }}</strong>
            <small>{{ selectedDeparture.invoice?.invoice_status || copy.noInvoice }}</small>
          </div>
        </div>

        <!-- Bill Preview -->
        <section class="departure-workspace-block">
          <div class="departure-workspace-block__header">
            <div>
              <span class="section-kicker">{{ copy.billPreview }}</span>
              <h3>{{ copy.billPreview }}</h3>
            </div>
            <div class="action-pair">
              <button type="button" class="secondary-button" :disabled="loadingPreview" @click="loadBillPreview">
                <Icon :icon="mdiCashMultiple" />
                {{ loadingPreview ? copy.loadingPreview : copy.loadPreview }}
              </button>
            </div>
          </div>

          <div v-if="billPreview" class="departure-bill-grid">
            <div v-for="line in billPreview.lines" :key="line.item_name" class="departure-bill-row">
              <span>{{ line.item_name }}</span>
              <span>{{ line.quantity }}x {{ formatCurrency(line.unit_price) }}</span>
              <strong>{{ formatCurrency(line.line_total) }}</strong>
            </div>

            <div class="departure-bill-divider"></div>

            <div class="departure-bill-row departure-bill-row--summary">
              <span>{{ copy.subtotal }}</span>
              <strong>{{ formatCurrency(billPreview.subtotal) }}</strong>
            </div>
            <div class="departure-bill-row departure-bill-row--summary">
              <span>{{ copy.tax }}</span>
              <strong>{{ formatCurrency(billPreview.tax_amount) }}</strong>
            </div>
            <div class="departure-bill-row departure-bill-row--total">
              <span>{{ copy.grandTotal }}</span>
              <strong>{{ formatCurrency(billPreview.grand_total) }}</strong>
            </div>
            <div class="departure-bill-row departure-bill-row--summary">
              <span>{{ copy.paid }}</span>
              <strong>{{ formatCurrency(billPreview.paid_amount) }}</strong>
            </div>
            <div class="departure-bill-row departure-bill-row--remaining">
              <span>{{ copy.remaining }}</span>
              <strong>{{ formatCurrency(billPreview.remaining_amount) }}</strong>
            </div>
          </div>

          <div v-else class="departure-bill-empty">
            {{ copy.loadPreview }}
          </div>
        </section>

        <!-- Room Inspection + Extra Fees + Payment -->
        <div class="departure-action-grid">
          <section class="departure-workspace-block">
            <div class="departure-workspace-block__header">
              <div>
                <span class="section-kicker">{{ copy.roomInspection }}</span>
                <h3>{{ copy.roomInspection }}</h3>
              </div>
              <Icon :icon="mdiCheckCircleOutline" />
            </div>

            <div class="departure-form-grid">
              <label class="field">
                <span>{{ copy.damageFee }}</span>
                <input v-model.number="checkoutForm.damage_fee_amount" type="number" min="0" class="text-input" />
              </label>

              <label class="field">
                <span>{{ copy.lateCheckoutHours }}</span>
                <input v-model.number="checkoutForm.late_checkout_hours" type="number" min="0" class="text-input" />
              </label>

              <label class="field">
                <span>{{ copy.lateCheckoutFee }}</span>
                <input v-model.number="checkoutForm.late_checkout_fee_amount" type="number" min="0" class="text-input" />
              </label>

              <label class="field">
                <span>{{ copy.lostKeycardFee }}</span>
                <input v-model.number="checkoutForm.lost_keycard_fee" type="number" min="0" class="text-input" />
              </label>
            </div>

            <label class="field">
              <span>{{ copy.damageNotes }}</span>
              <input v-model="checkoutForm.damage_fee_notes" type="text" class="text-input" />
            </label>

            <label class="field">
              <span>{{ copy.roomCondition }}</span>
              <input v-model="checkoutForm.room_condition_notes" type="text" class="text-input" :placeholder="copy.roomConditionPlaceholder" />
            </label>

            <div class="departure-confirm-grid">
              <label class="departure-check">
                <input v-model="checkoutForm.room_inspected" type="checkbox" />
                <span>{{ copy.roomInspected }}</span>
              </label>

              <label class="departure-check">
                <input v-model="checkoutForm.keycard_returned" type="checkbox" />
                <span>{{ copy.keycardReturned }}</span>
              </label>
            </div>
          </section>

          <section class="departure-workspace-block">
            <div class="departure-workspace-block__header">
              <div>
                <span class="section-kicker">{{ copy.payment }}</span>
                <h3>{{ copy.payment }}</h3>
              </div>
              <Icon :icon="mdiCashMultiple" />
            </div>

            <div class="departure-form-grid">
              <label class="field">
                <span>{{ copy.paymentMethod }}</span>
                <select v-model="checkoutForm.payment_method_code" class="text-input">
                  <option value="cash">Cash</option>
                  <option value="transfer">Transfer</option>
                  <option value="card">Card</option>
                  <option value="qris">QRIS</option>
                </select>
              </label>

              <label class="field">
                <span>{{ copy.paymentAmount }}</span>
                <input v-model.number="checkoutForm.payment_amount" type="number" min="0" class="text-input" />
              </label>
            </div>

            <label class="field">
              <span>{{ copy.paymentReference }}</span>
              <input v-model="checkoutForm.payment_reference" type="text" class="text-input" />
            </label>

            <label class="field">
              <span>{{ copy.checkoutNotes }}</span>
              <input v-model="checkoutForm.notes" type="text" class="text-input" :placeholder="copy.checkoutNotesPlaceholder" />
            </label>
          </section>
        </div>

        <!-- Complete Checkout -->
        <button
          type="button"
          class="primary-button primary-button--full"
          :disabled="completingCheckout || !checkoutForm.room_inspected"
          @click="completeCheckout"
        >
          {{ completingCheckout ? copy.completing : copy.completeCheckout }}
        </button>
      </article>
    </section>
  </AppShell>
</template>

<style scoped>
.departure-date-field {
  min-width: 180px;
}

.departure-toolbar,
.departure-form-grid,
.departure-confirm-grid,
.departure-detail-grid,
.departure-action-grid {
  display: grid;
  gap: 16px;
}

.departure-action-grid {
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
}

.departure-detail-grid {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.departure-form-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.departure-confirm-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.departure-table-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.departure-table-cell span {
  color: var(--color-text-soft);
  font-size: 0.82rem;
  line-height: 1.4;
}

.departure-table-cell--right {
  align-items: flex-end;
}

.departure-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(26, 20, 18, 0.4);
  z-index: 160;
}

.departure-modal-shell {
  position: fixed;
  inset: 0;
  z-index: 170;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 20px;
}

.departure-modal-card {
  width: min(1180px, 100%);
  max-height: calc(100vh - 64px);
  overflow: auto;
  border-radius: var(--radius);
  padding: 24px;
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.departure-modal-card__header,
.departure-workspace-block__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.departure-modal-card__header p {
  color: var(--color-text-soft);
  margin-top: 6px;
}

.departure-summary-card,
.departure-workspace-block {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.departure-summary-card strong,
.departure-workspace-block h3 {
  color: var(--color-text);
}

.departure-summary-card small,
.departure-workspace-block__header svg {
  color: var(--color-text-soft);
}

.departure-check {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--color-text);
  font-size: 0.9rem;
}

.departure-check input {
  width: 18px;
  height: 18px;
  accent-color: var(--color-primary);
}

/* Bill preview */
.departure-bill-grid {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.departure-bill-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  font-size: 0.9rem;
}

.departure-bill-row span:first-child {
  flex: 1;
  color: var(--color-text-soft);
}

.departure-bill-row span:nth-child(2) {
  color: var(--color-text-soft);
  font-size: 0.82rem;
}

.departure-bill-row--summary span {
  color: var(--color-text) !important;
}

.departure-bill-divider {
  border-top: 1px dashed var(--color-border);
  margin: 4px 0;
}

.departure-bill-row--total {
  font-size: 1.05rem;
}

.departure-bill-row--total strong {
  color: var(--color-text);
}

.departure-bill-row--remaining strong {
  color: var(--color-warning);
}

.departure-bill-empty {
  color: var(--color-text-soft);
  font-size: 0.9rem;
  text-align: center;
  padding: 12px;
}

.primary-button--full {
  width: 100%;
}

@media (max-width: 1024px) {
  .departure-detail-grid,
  .departure-form-grid,
  .departure-confirm-grid,
  .departure-action-grid {
    grid-template-columns: 1fr;
  }
}
</style>
