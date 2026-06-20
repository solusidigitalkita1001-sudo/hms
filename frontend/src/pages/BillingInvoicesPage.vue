<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiArrowLeft from '@iconify-icons/mdi/arrow-left'
import mdiClose from '@iconify-icons/mdi/close'
import mdiDeleteOutline from '@iconify-icons/mdi/delete-outline'
import mdiPlus from '@iconify-icons/mdi/plus'
import mdiPencilOutline from '@iconify-icons/mdi/pencil-outline'
import mdiRefresh from '@iconify-icons/mdi/refresh'
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import AppShell from '../components/AppShell.vue'
import { useAppLocale } from '../composables/useAppLocale'
import { useAuthSession } from '../composables/useAuthSession'
import { buildApiUrl } from '../lib/api'

type InvoiceDetail = {
  invoice: {
    id: number
    invoice_number: string
    invoice_status: string
    issued_at: string | null
    due_at: string | null
    subtotal_amount: number
    tax_amount: number
    service_amount: number
    discount_amount: number
    grand_total: number
    paid_amount: number
    remaining_amount: number
    voided_at: string | null
    notes: string | null
    created_at: string | null
  }
  reservation: {
    id: number
    booking_code: string
    guest_name: string | null
  } | null
  items: InvoiceItemRow[]
  payments: InvoicePaymentRow[]
  status_logs: InvoiceStatusLogRow[]
}

type InvoiceItemRow = {
  id: number
  item_type: string
  item_code: string | null
  item_name: string
  description: string | null
  unit_price: number
  quantity: number
  discount_amount: number
  tax_amount: number
  line_total: number
  item_date: string | null
}

type InvoicePaymentRow = {
  id: number
  payment_code: string
  payment_type: string
  payment_status: string
  payment_method_code: string
  amount: number
  paid_at: string | null
  notes: string | null
}

type InvoiceStatusLogRow = {
  id: number
  from_status: string | null
  to_status: string
  reason: string | null
  changed_by: string | null
  changed_at: string | null
}

const route = useRoute()
const router = useRouter()
const { state: authState } = useAuthSession()
const { isEnglish } = useAppLocale()

const loading = ref(true)
const recalculating = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const invoiceData = ref<InvoiceDetail | null>(null)

const itemModalOpen = ref(false)
const editMode = ref(false)
const editingItemId = ref<number | null>(null)
const itemSubmitting = ref(false)

const voidModalOpen = ref(false)
const voidSubmitting = ref(false)

const itemForm = reactive({
  item_type: 'room_charge',
  item_code: '',
  item_name: '',
  description: '',
  unit_price: 0,
  quantity: 1,
  discount_amount: 0,
  tax_amount: 0,
  item_date: new Date().toISOString().split('T')[0],
  notes: '',
})

const voidForm = reactive({
  void_reason: '',
  approval_reference: '',
})

const itemTypes = [
  { value: 'room_charge', label: 'Room Charge' },
  { value: 'amenity', label: 'Amenity' },
  { value: 'food', label: 'Food & Beverage' },
  { value: 'service', label: 'Service' },
  { value: 'damage_fee', label: 'Damage Fee' },
  { value: 'late_checkout_fee', label: 'Late Checkout Fee' },
  { value: 'lost_keycard_fee', label: 'Lost Keycard Fee' },
  { value: 'adjustment', label: 'Adjustment' },
  { value: 'refund', label: 'Refund' },
]

const copy = computed(() => {
  if (isEnglish.value) {
    return {
      title: 'Invoice Detail',
      back: 'Back',
      invalidResponse: 'Invalid invoice response.',
      loadFailed: 'Failed to load invoice.',
      invoiceNumber: 'Invoice Number',
      status: 'Status',
      issuedAt: 'Issued at',
      totals: 'Totals',
      paid: 'Paid',
      remaining: 'Remaining',
      discount: 'Discount',
      service: 'Service charge',
      subtotal: 'Subtotal',
      tax: 'Tax',
      reservation: 'Reservation',
      lineItems: 'Line items',
      invoiceItems: 'Invoice Items',
      addItem: 'Add item',
      editItem: 'Edit item',
      deleteItem: 'Delete item',
      colType: 'Type',
      colName: 'Name',
      colDescription: 'Description',
      colPrice: 'Price',
      colQty: 'Qty',
      colTotal: 'Total',
      colActions: 'Actions',
      noItems: 'No items on this invoice.',
      paymentHistory: 'Payment history',
      payments: 'Payments',
      noPayments: 'No payments recorded.',
      colPaymentCode: 'Code',
      colMethod: 'Method',
      colAmount: 'Amount',
      colPaidAt: 'Date',
      colPaymentStatus: 'Status',
      auditTrail: 'Audit trail',
      statusLogs: 'Status Logs',
      noLogs: 'No status logs.',
      colPrevStatus: 'From',
      colNewStatus: 'To',
      colChangedBy: 'Changed by',
      colChangedAt: 'Date',
      recalculate: 'Recalculate',
      recalculating: 'Recalculating...',
      voidInvoice: 'Void Invoice',
      voidTitle: 'Void Invoice',
      voidDescription: 'This action cannot be undone. Please provide a reason.',
      voidReason: 'Void reason',
      voidReasonPlaceholder: 'Reason for voiding this invoice (min 10 characters)',
      approvalReference: 'Approval reference',
      approvalReferencePlaceholder: 'Optional approval reference',
      voidSubmit: 'Void Invoice',
      voiding: 'Voiding...',
      itemName: 'Item name',
      itemNamePlaceholder: 'Item name',
      description: 'Description',
      descriptionPlaceholder: 'Optional description',
      unitPrice: 'Unit price',
      quantity: 'Quantity',
      discountAmount: 'Discount',
      taxAmount: 'Tax',
      itemDate: 'Item date',
      itemCode: 'Item code',
      itemCodePlaceholder: 'Optional item code',
      notes: 'Notes',
      notesPlaceholder: 'Optional notes',
      save: 'Save',
      saving: 'Saving...',
      cancel: 'Cancel',
      confirmDelete: 'Delete this item?',
    }
  }

  return {
    title: 'Detail Invoice',
    back: 'Kembali',
    invalidResponse: 'Respons invoice tidak valid.',
    loadFailed: 'Gagal memuat invoice.',
    invoiceNumber: 'Nomor Invoice',
    status: 'Status',
    issuedAt: 'Diterbitkan',
    totals: 'Total',
    paid: 'Dibayar',
    remaining: 'Sisa',
    discount: 'Diskon',
    service: 'Biaya layanan',
    reservation: 'Reservasi',
    lineItems: 'Daftar item',
    invoiceItems: 'Item Invoice',
    addItem: 'Tambah item',
    editItem: 'Edit item',
    deleteItem: 'Hapus item',
    colType: 'Tipe',
    colName: 'Nama',
    colDescription: 'Deskripsi',
    colPrice: 'Harga',
    colQty: 'Qty',
    colTotal: 'Total',
    colActions: 'Aksi',
    noItems: 'Belum ada item di invoice ini.',
    paymentHistory: 'Riwayat pembayaran',
    payments: 'Pembayaran',
    noPayments: 'Belum ada pembayaran.',
    colPaymentCode: 'Kode',
    colMethod: 'Metode',
    colAmount: 'Jumlah',
    colPaidAt: 'Tanggal',
    colPaymentStatus: 'Status',
    auditTrail: 'Audit trail',
    statusLogs: 'Log Status',
    noLogs: 'Belum ada log status.',
    colPrevStatus: 'Dari',
    colNewStatus: 'Ke',
    colChangedBy: 'Diubah oleh',
    colChangedAt: 'Tanggal',
    recalculate: 'Recalculate',
    recalculating: 'Menghitung ulang...',
    voidInvoice: 'Void Invoice',
    voidTitle: 'Void Invoice',
    voidDescription: 'Tindakan ini tidak bisa dibatalkan. Berikan alasan.',
    voidReason: 'Alasan void',
    voidReasonPlaceholder: 'Alasan void invoice ini (min 10 karakter)',
    approvalReference: 'Referensi approval',
    approvalReferencePlaceholder: 'Referensi approval opsional',
    voidSubmit: 'Void Invoice',
    voiding: 'Memproses void...',
    itemName: 'Nama item',
    itemNamePlaceholder: 'Nama item',
    description: 'Deskripsi',
    descriptionPlaceholder: 'Deskripsi opsional',
    unitPrice: 'Harga satuan',
    quantity: 'Jumlah',
    discountAmount: 'Diskon',
    taxAmount: 'Pajak',
    itemDate: 'Tanggal item',
    itemCode: 'Kode item',
    itemCodePlaceholder: 'Kode item opsional',
    notes: 'Catatan',
    notesPlaceholder: 'Catatan opsional',
    save: 'Simpan',
    saving: 'Menyimpan...',
    cancel: 'Batal',
    confirmDelete: 'Hapus item ini?',
  }
})

const invoiceId = computed(() => Number(route.params.id))

const invoiceStatusBadgeClass = computed(() => {
  const status = invoiceData.value?.invoice.invoice_status
  if (status === 'paid') return 'status-badge status-badge--success'
  if (status === 'partial') return 'status-badge status-badge--soft'
  if (status === 'void' || status === 'refunded') return 'status-badge status-badge--warning'
  if (status === 'unpaid') return 'status-badge status-badge--soft'
  return 'status-badge'
})

const formatCurrency = (value: number | null | undefined) => {
  if (value == null) return '-'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}

const formatDate = (value: string | null) => {
  if (!value) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
}

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

const resetItemForm = () => {
  itemForm.item_type = 'room_charge'
  itemForm.item_code = ''
  itemForm.item_name = ''
  itemForm.description = ''
  itemForm.unit_price = 0
  itemForm.quantity = 1
  itemForm.discount_amount = 0
  itemForm.tax_amount = 0
  itemForm.item_date = new Date().toISOString().split('T')[0]
  itemForm.notes = ''
}

const loadInvoice = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await fetch(buildApiUrl(`/api/v1/invoices/${invoiceId.value}`), {
      headers: authHeaders(),
    })

    const payload = await parseJsonResponse<{
      success: boolean
      message: string
      data: InvoiceDetail
    }>(response, copy.value.invalidResponse)

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || copy.value.loadFailed)
    }

    invoiceData.value = payload.data
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : copy.value.loadFailed
  } finally {
    loading.value = false
  }
}

const openAddItemModal = () => {
  editMode.value = false
  editingItemId.value = null
  resetItemForm()
  itemModalOpen.value = true
}

const openEditItemModal = (item: InvoiceItemRow) => {
  editMode.value = true
  editingItemId.value = item.id
  itemForm.item_type = item.item_type
  itemForm.item_code = item.item_code ?? ''
  itemForm.item_name = item.item_name
  itemForm.description = item.description ?? ''
  itemForm.unit_price = item.unit_price
  itemForm.quantity = item.quantity
  itemForm.discount_amount = item.discount_amount
  itemForm.tax_amount = item.tax_amount
  itemForm.item_date = item.item_date ?? new Date().toISOString().split('T')[0]
  itemForm.notes = ''
  itemModalOpen.value = true
}

const closeItemModal = () => {
  itemModalOpen.value = false
  resetItemForm()
}

const submitItem = async () => {
  if (!invoiceData.value) return
  if (!itemForm.item_name.trim()) return

  itemSubmitting.value = true
  errorMessage.value = ''
  successMessage.value = ''

  const body = {
    item_type: itemForm.item_type,
    item_code: itemForm.item_code || null,
    item_name: itemForm.item_name,
    description: itemForm.description || null,
    unit_price: itemForm.unit_price,
    quantity: itemForm.quantity,
    discount_amount: itemForm.discount_amount || null,
    tax_amount: itemForm.tax_amount || null,
    item_date: itemForm.item_date || null,
    notes: itemForm.notes || null,
  }

  try {
    let response: Response

    if (editMode.value && editingItemId.value) {
      response = await fetch(buildApiUrl(`/api/v1/invoices/${invoiceId.value}/items/${editingItemId.value}`), {
        method: 'PUT',
        headers: authHeaders(true),
        body: JSON.stringify(body),
      })
    } else {
      response = await fetch(buildApiUrl(`/api/v1/invoices/${invoiceId.value}/items`), {
        method: 'POST',
        headers: authHeaders(true),
        body: JSON.stringify(body),
      })
    }

    const payload = await parseJsonResponse<{
      success?: boolean
      message?: string
      errors?: Record<string, string[]>
    }>(response, editMode.value ? 'Invalid update response.' : 'Invalid create response.')

    if (!response.ok || !payload.success) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || (editMode.value ? 'Update failed.' : 'Create failed.'))
    }

    successMessage.value = payload.message || (editMode.value ? 'Item updated.' : 'Item added.')
    closeItemModal()
    await loadInvoice()
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Operation failed.'
  } finally {
    itemSubmitting.value = false
  }
}

const deleteItem = async (itemId: number) => {
  if (!confirm(copy.value.confirmDelete)) return

  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch(buildApiUrl(`/api/v1/invoices/${invoiceId.value}/items/${itemId}`), {
      method: 'DELETE',
      headers: authHeaders(),
    })

    const payload = await parseJsonResponse<{
      success?: boolean
      message?: string
    }>(response, 'Invalid delete response.')

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Delete failed.')
    }

    successMessage.value = payload.message || 'Item deleted.'
    await loadInvoice()
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Delete failed.'
  }
}

const recalculateInvoice = async () => {
  recalculating.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch(buildApiUrl(`/api/v1/invoices/${invoiceId.value}/recalculate`), {
      method: 'POST',
      headers: authHeaders(true),
    })

    const payload = await parseJsonResponse<{
      success?: boolean
      message?: string
    }>(response, 'Invalid recalculate response.')

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Recalculate failed.')
    }

    successMessage.value = payload.message || 'Invoice recalculated.'
    await loadInvoice()
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Recalculate failed.'
  } finally {
    recalculating.value = false
  }
}

const openVoidModal = () => {
  voidForm.void_reason = ''
  voidForm.approval_reference = ''
  voidModalOpen.value = true
}

const closeVoidModal = () => {
  voidModalOpen.value = false
}

const submitVoid = async () => {
  if (voidForm.void_reason.trim().length < 10) return

  voidSubmitting.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch(buildApiUrl(`/api/v1/invoices/${invoiceId.value}/void`), {
      method: 'POST',
      headers: authHeaders(true),
      body: JSON.stringify({
        void_reason: voidForm.void_reason,
        approval_reference: voidForm.approval_reference || null,
      }),
    })

    const payload = await parseJsonResponse<{
      success?: boolean
      message?: string
      errors?: Record<string, string[]>
    }>(response, 'Invalid void response.')

    if (!response.ok || !payload.success) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || 'Void failed.')
    }

    successMessage.value = payload.message || 'Invoice voided.'
    closeVoidModal()
    await loadInvoice()
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Void failed.'
  } finally {
    voidSubmitting.value = false
  }
}

const goBack = () => {
  router.back()
}

onMounted(() => {
  loadInvoice()
})
</script>

<template>
  <AppShell :title="copy.title" hero-variant="plain">
    <section class="content-grid">
      <article class="surface-card surface-card--wide">
        <div class="section-heading">
          <div class="section-heading--left">
            <button type="button" class="ghost-button" @click="goBack">
              <Icon :icon="mdiArrowLeft" />
              {{ copy.back }}
            </button>
          </div>

          <div v-if="invoiceData" class="action-pair">
            <button type="button" class="secondary-button" :disabled="recalculating" @click="recalculateInvoice">
              <Icon :icon="mdiRefresh" />
              {{ recalculating ? copy.recalculating : copy.recalculate }}
            </button>
            <button
              v-if="invoiceData.invoice.invoice_status !== 'void'"
              type="button"
              class="secondary-button"
              @click="openVoidModal"
            >
              {{ copy.voidInvoice }}
            </button>
          </div>
        </div>

        <div v-if="invoiceData" class="billing-header-grid">
          <div class="billing-header-card">
            <span class="section-kicker">{{ copy.invoiceNumber }}</span>
            <strong>{{ invoiceData.invoice.invoice_number }}</strong>
            <span :class="invoiceStatusBadgeClass">{{ invoiceData.invoice.invoice_status }}</span>
          </div>

          <div class="billing-header-card">
            <span class="section-kicker">{{ copy.totals }}</span>
            <strong>{{ formatCurrency(invoiceData.invoice.grand_total) }}</strong>
            <small>
              {{ copy.paid }}: {{ formatCurrency(invoiceData.invoice.paid_amount) }}
              · {{ copy.remaining }}: {{ formatCurrency(invoiceData.invoice.remaining_amount) }}
            </small>
          </div>

          <div class="billing-header-card">
            <span class="section-kicker">{{ copy.reservation }}</span>
            <strong v-if="invoiceData.reservation">{{ invoiceData.reservation.booking_code }}</strong>
            <small v-if="invoiceData.reservation">{{ invoiceData.reservation.guest_name }}</small>
          </div>
        </div>

        <div v-if="invoiceData" class="billing-totals-detail">
          <span>{{ copy.subtotal || 'Subtotal' }}: {{ formatCurrency(invoiceData.invoice.subtotal_amount) }}</span>
          <span>{{ copy.discount }}: {{ formatCurrency(invoiceData.invoice.discount_amount) }}</span>
          <span>{{ copy.service }}: {{ formatCurrency(invoiceData.invoice.service_amount) }}</span>
          <span>{{ copy.tax || 'Tax' }}: {{ formatCurrency(invoiceData.invoice.tax_amount) }}</span>
        </div>

        <div class="cms-status-row">
          <div v-if="loading" class="cms-status-pill">
            <Icon :icon="mdiRefresh" class="cms-spin" />
            {{ copy.loadFailed }}
          </div>
          <div v-else-if="successMessage" class="cms-status-pill cms-status-pill--success">{{ successMessage }}</div>
          <div v-if="errorMessage" class="cms-status-pill cms-status-pill--error">{{ errorMessage }}</div>
        </div>
      </article>
    </section>

    <!-- Line Items -->
    <section v-if="invoiceData" class="content-grid">
      <article class="surface-card surface-card--wide">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.lineItems }}</span>
            <h2>{{ copy.invoiceItems }}</h2>
          </div>
          <button type="button" class="primary-button" :disabled="invoiceData.invoice.invoice_status === 'void'" @click="openAddItemModal">
            <Icon :icon="mdiPlus" />
            {{ copy.addItem }}
          </button>
        </div>

        <div v-if="invoiceData.items.length === 0" class="portal-state-card">
          <strong>{{ copy.noItems }}</strong>
        </div>

        <div v-else class="billing-items-table">
          <div class="billing-items-header">
            <span>{{ copy.colType }}</span>
            <span>{{ copy.colName }}</span>
            <span>{{ copy.colPrice }}</span>
            <span>{{ copy.colQty }}</span>
            <span>{{ copy.colTotal }}</span>
            <span></span>
          </div>
          <div v-for="item in invoiceData.items" :key="item.id" class="billing-items-row">
            <span class="status-badge">{{ item.item_type }}</span>
            <div class="billing-item-name">
              <strong>{{ item.item_name }}</strong>
              <small v-if="item.description">{{ item.description }}</small>
            </div>
            <span>{{ formatCurrency(item.unit_price) }}</span>
            <span>{{ item.quantity }}</span>
            <strong>{{ formatCurrency(item.line_total) }}</strong>
            <div class="billing-item-actions">
              <button type="button" class="icon-button" :disabled="invoiceData.invoice.invoice_status === 'void'" @click="openEditItemModal(item)">
                <Icon :icon="mdiPencilOutline" />
              </button>
              <button type="button" class="icon-button" :disabled="invoiceData.invoice.invoice_status === 'void'" @click="deleteItem(item.id)">
                <Icon :icon="mdiDeleteOutline" />
              </button>
            </div>
          </div>
        </div>
      </article>
    </section>

    <!-- Payment History -->
    <section v-if="invoiceData" class="content-grid">
      <article class="surface-card surface-card--wide">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.paymentHistory }}</span>
            <h2>{{ copy.payments }}</h2>
          </div>
        </div>

        <div v-if="invoiceData.payments.length === 0" class="portal-state-card">
          <strong>{{ copy.noPayments }}</strong>
        </div>

        <div v-else class="billing-items-table">
          <div class="billing-items-header">
            <span>{{ copy.colPaymentCode }}</span>
            <span>{{ copy.colMethod }}</span>
            <span>{{ copy.colAmount }}</span>
            <span>{{ copy.colPaidAt }}</span>
            <span>{{ copy.colPaymentStatus }}</span>
          </div>
          <div v-for="payment in invoiceData.payments" :key="payment.id" class="billing-items-row">
            <strong>{{ payment.payment_code }}</strong>
            <span>{{ payment.payment_method_code }}</span>
            <strong>{{ formatCurrency(payment.amount) }}</strong>
            <span>{{ formatDate(payment.paid_at) }}</span>
            <span class="status-badge">{{ payment.payment_status }}</span>
          </div>
        </div>
      </article>
    </section>

    <!-- Status Logs -->
    <section v-if="invoiceData" class="content-grid">
      <article class="surface-card surface-card--wide">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.auditTrail }}</span>
            <h2>{{ copy.statusLogs }}</h2>
          </div>
        </div>

        <div v-if="invoiceData.status_logs.length === 0" class="portal-state-card">
          <strong>{{ copy.noLogs }}</strong>
        </div>

        <div v-else class="billing-items-table">
          <div class="billing-items-header">
            <span>{{ copy.colPrevStatus }}</span>
            <span>{{ copy.colNewStatus }}</span>
            <span>{{ copy.colChangedBy }}</span>
            <span>{{ copy.colChangedAt }}</span>
          </div>
          <div v-for="log in invoiceData.status_logs" :key="log.id" class="billing-items-row">
            <span>{{ log.from_status || '-' }}</span>
            <span class="status-badge">{{ log.to_status }}</span>
            <span>{{ log.changed_by || '-' }}</span>
            <span>{{ formatDate(log.changed_at) }}</span>
          </div>
        </div>
      </article>
    </section>

    <!-- Add/Edit Item Modal -->
    <div v-if="itemModalOpen" class="billing-modal-backdrop" @click="closeItemModal"></div>
    <section v-if="itemModalOpen" class="billing-modal-shell">
      <article class="billing-modal-card" @click.stop>
        <div class="billing-modal-card__header">
          <div>
            <span class="section-kicker">{{ editMode ? copy.editItem : copy.addItem }}</span>
            <h2>{{ editMode ? copy.editItem : copy.addItem }}</h2>
          </div>
          <button type="button" class="icon-button" @click="closeItemModal">
            <Icon :icon="mdiClose" />
          </button>
        </div>

        <div class="billing-form-grid">
          <label class="field">
            <span>{{ copy.colType }}</span>
            <select v-model="itemForm.item_type" class="text-input">
              <option v-for="t in itemTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
          </label>

          <label class="field">
            <span>{{ copy.itemCode }}</span>
            <input v-model="itemForm.item_code" type="text" class="text-input" :placeholder="copy.itemCodePlaceholder" />
          </label>

          <label class="field">
            <span>{{ copy.itemName }}</span>
            <input v-model="itemForm.item_name" type="text" class="text-input" :placeholder="copy.itemNamePlaceholder" />
          </label>

          <label class="field">
            <span>{{ copy.itemDate }}</span>
            <input v-model="itemForm.item_date" type="date" class="text-input" />
          </label>

          <label class="field billing-form-wide">
            <span>{{ copy.description }}</span>
            <input v-model="itemForm.description" type="text" class="text-input" :placeholder="copy.descriptionPlaceholder" />
          </label>

          <label class="field">
            <span>{{ copy.unitPrice }}</span>
            <input v-model.number="itemForm.unit_price" type="number" min="0" class="text-input" />
          </label>

          <label class="field">
            <span>{{ copy.quantity }}</span>
            <input v-model.number="itemForm.quantity" type="number" min="0.01" step="0.01" class="text-input" />
          </label>

          <label class="field">
            <span>{{ copy.discountAmount }}</span>
            <input v-model.number="itemForm.discount_amount" type="number" min="0" class="text-input" />
          </label>

          <label class="field">
            <span>{{ copy.taxAmount }}</span>
            <input v-model.number="itemForm.tax_amount" type="number" min="0" class="text-input" />
          </label>

          <label class="field billing-form-wide">
            <span>{{ copy.notes }}</span>
            <input v-model="itemForm.notes" type="text" class="text-input" :placeholder="copy.notesPlaceholder" />
          </label>
        </div>

        <div class="action-pair">
          <button type="button" class="secondary-button" @click="closeItemModal">{{ copy.cancel }}</button>
          <button type="button" class="primary-button" :disabled="itemSubmitting || !itemForm.item_name.trim()" @click="submitItem">
            {{ itemSubmitting ? copy.saving : copy.save }}
          </button>
        </div>
      </article>
    </section>

    <!-- Void Modal -->
    <div v-if="voidModalOpen" class="billing-modal-backdrop" @click="closeVoidModal"></div>
    <section v-if="voidModalOpen" class="billing-modal-shell">
      <article class="billing-modal-card" @click.stop>
        <div class="billing-modal-card__header">
          <div>
            <span class="section-kicker">{{ copy.voidTitle }}</span>
            <h2>{{ copy.voidTitle }}</h2>
            <p>{{ copy.voidDescription }}</p>
          </div>
          <button type="button" class="icon-button" @click="closeVoidModal">
            <Icon :icon="mdiClose" />
          </button>
        </div>

        <div class="billing-form-grid billing-form-grid--single">
          <label class="field billing-form-wide">
            <span>{{ copy.voidReason }}</span>
            <textarea v-model="voidForm.void_reason" class="text-input textarea-input" :placeholder="copy.voidReasonPlaceholder" rows="3" />
            <small v-if="voidForm.void_reason.length > 0 && voidForm.void_reason.length < 10" class="field-hint">
              {{ voidForm.void_reason.length }}/10 min
            </small>
          </label>

          <label class="field billing-form-wide">
            <span>{{ copy.approvalReference }}</span>
            <input v-model="voidForm.approval_reference" type="text" class="text-input" :placeholder="copy.approvalReferencePlaceholder" />
          </label>
        </div>

        <div class="action-pair">
          <button type="button" class="secondary-button" @click="closeVoidModal">{{ copy.cancel }}</button>
          <button type="button" class="primary-button primary-button--danger" :disabled="voidSubmitting || voidForm.void_reason.trim().length < 10" @click="submitVoid">
            {{ voidSubmitting ? copy.voiding : copy.voidSubmit }}
          </button>
        </div>
      </article>
    </section>
  </AppShell>
</template>

<style scoped>
.section-heading--left {
  display: flex;
  gap: 12px;
  align-items: center;
}

.billing-header-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.billing-header-card {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.billing-header-card strong {
  color: var(--color-text);
}

.billing-header-card small {
  color: var(--color-text-soft);
  font-size: 0.82rem;
}

.billing-totals-detail {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  padding: 12px 0;
  border-top: 1px solid var(--color-border);
  font-size: 0.88rem;
  color: var(--color-text-soft);
}

/* Items table */
.billing-items-table {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.billing-items-header,
.billing-items-row {
  display: grid;
  grid-template-columns: 100px 1fr 100px 60px 100px 80px;
  gap: 12px;
  padding: 10px 14px;
  align-items: center;
}

.billing-items-header {
  border-bottom: 1px solid var(--color-border);
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-soft);
}

.billing-items-row {
  border-bottom: 1px solid var(--color-border);
  font-size: 0.9rem;
}

.billing-items-row:last-child {
  border-bottom: none;
}

.billing-item-name {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.billing-item-name small {
  color: var(--color-text-soft);
  font-size: 0.78rem;
}

.billing-item-actions {
  display: flex;
  gap: 4px;
}

/* Modal */
.billing-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(26, 20, 18, 0.4);
  z-index: 160;
}

.billing-modal-shell {
  position: fixed;
  inset: 0;
  z-index: 170;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 20px;
}

.billing-modal-card {
  width: min(780px, 100%);
  max-height: calc(100vh - 64px);
  overflow: auto;
  border-radius: var(--radius);
  padding: 20px;
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.billing-modal-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.billing-modal-card__header p {
  color: var(--color-text-soft);
  margin-top: 6px;
}

.billing-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.billing-form-grid--single {
  grid-template-columns: 1fr;
}

.billing-form-wide {
  grid-column: 1 / -1;
}

.textarea-input {
  resize: vertical;
  min-height: 72px;
}

.field-hint {
  color: var(--color-warning);
  font-size: 0.78rem;
}

.primary-button--danger {
  background: var(--color-danger, #dc2626);
  color: #fff;
}

.primary-button--danger:hover {
  opacity: 0.9;
}

@media (max-width: 1024px) {
  .billing-header-grid,
  .billing-form-grid {
    grid-template-columns: 1fr;
  }

  .billing-form-wide {
    grid-column: auto;
  }

  .billing-items-header,
  .billing-items-row {
    grid-template-columns: 80px 1fr 80px 50px 80px 60px;
    font-size: 0.82rem;
    gap: 8px;
    padding: 8px 10px;
  }
}
</style>
