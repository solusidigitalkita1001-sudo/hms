<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiAccountBadgeOutline from '@iconify-icons/mdi/account-badge-outline'
import mdiAccountPlusOutline from '@iconify-icons/mdi/account-plus-outline'
import mdiBedOutline from '@iconify-icons/mdi/bed-outline'
import mdiCheckCircleOutline from '@iconify-icons/mdi/check-circle-outline'
import mdiClose from '@iconify-icons/mdi/close'
import mdiRefresh from '@iconify-icons/mdi/refresh'
import mdiTrayArrowDown from '@iconify-icons/mdi/tray-arrow-down'
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'

import AppShell from '../components/AppShell.vue'
import SearchSelect from '../components/SearchSelect.vue'
import ServerDataTable from '../components/ServerDataTable.vue'
import { useAppLocale } from '../composables/useAppLocale'
import { useAuthSession } from '../composables/useAuthSession'
import { buildApiUrl } from '../lib/api'

type ReservationStatus =
  | 'reserved'
  | 'arrival_due'
  | 'arrived'
  | 'id_pending'
  | 'registration_pending'
  | 'ready_for_checkin'
  | 'checked_in'
  | 'no_show'
  | 'cancelled'

type ArrivalRow = {
  id: number
  booking_code: string
  source: string
  reservation_status: ReservationStatus
  check_in_date: string
  check_out_date: string
  adult_count: number
  child_count: number
  payment_status: string
  guarantee_status: string
  property: {
    id: number | null
    code: string | null
    name: string | null
  }
  primary_guest: {
    id: number | null
    full_name: string | null
    phone: string | null
    email: string | null
    identity_verified: boolean | null
    identity_verification_status: string | null
  }
  room_type: {
    id: number | null
    code: string | null
    name: string | null
  }
  assigned_room: {
    id: number | null
    room_number: string | null
    current_status: string | null
  }
}

type ArrivalMeta = {
  total: number
  current_page: number
  per_page: number
  last_page: number
  from: number | null
  to: number | null
}

type ArrivalPayload = {
  items: ArrivalRow[]
  filters: {
    search: string
    status: string
    property_id: string | number | null
    date_from: string | null
    date_to: string | null
    source: string
    sort_by: string
    sort_direction: 'asc' | 'desc'
  }
}

type AssignableRoom = {
  id: number
  room_number: string
  floor: number
  current_status: string
  room_type: {
    id: number | null
    code: string | null
    name: string | null
  }
}

const { state: authState } = useAuthSession()
const { isEnglish } = useAppLocale()

const loading = ref(true)
const refreshing = ref(false)
const loadingAssignableRooms = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const arrivals = ref<ArrivalRow[]>([])
const assignableRooms = ref<AssignableRoom[]>([])
const selectedArrival = ref<ArrivalRow | null>(null)
const assigningRoomId = ref<number | null>(null)
const verifyingReservationId = ref<number | null>(null)
const completingReservationId = ref<number | null>(null)
const meta = ref<ArrivalMeta>({
  total: 0,
  current_page: 1,
  per_page: 10,
  last_page: 1,
  from: null,
  to: null,
})

const filters = reactive({
  status: 'all',
  source: 'all',
  search: '',
})

const tableQuery = reactive({
  page: 1,
  perPage: 10,
  sortBy: 'check_in_date',
  sortDirection: 'asc' as 'asc' | 'desc',
})

const roomAssignmentForm = reactive({
  roomId: '',
  notes: '',
})

const identityForm = reactive({
  full_name: '',
  id_type: 'ktp',
  id_number: '',
  phone: '',
  email: '',
  nationality: 'ID',
  address: '',
})

const completeForm = reactive({
  confirmIdentityVerified: true,
  confirmRegistrationSigned: true,
  confirmTermsAccepted: true,
  issueKeycard: true,
  notes: '',
})

// Walk-in state
const walkInModalOpen = ref(false)
const walkInSubmitting = ref(false)
const walkInForm = reactive({
  guest_full_name: '',
  guest_phone: '',
  guest_email: '',
  guest_id_type: 'ktp',
  guest_id_number: '',
  room_type_id: '',
  room_id: '',
  check_in_date: new Date().toISOString().split('T')[0],
  check_out_date: '',
  adult_count: 1,
  child_count: 0,
  rate_per_night: 0,
  deposit_amount: 0,
  payment_method_code: 'cash',
  payment_amount: 0,
  special_requests: '',
  internal_notes: '',
  auto_check_in: false,
  create_invoice: true,
})

const copy = computed(() => {
  if (isEnglish.value) {
    return {
      title: 'Arrivals',
      metrics: ['Total queue', 'ID verified', 'Room assigned', 'Ready check-in'],
      allStatuses: 'All statuses',
      allStatusesDesc: 'Show all active arrivals',
      allSources: 'All sources',
      allSourcesDesc: 'Direct, portal, OTA, corporate',
      status: 'Status',
      source: 'Source',
      searchBookingGuest: 'Search booking / guest',
      searchBookingGuestPlaceholder: 'Search booking code, guest name, phone...',
      arrivalQueue: 'Arrival queue',
      arrivalQueueTitle: 'Internal check-in control without mixing with portal/CMS',
      refreshQueue: 'Refresh queue',
      refreshing: 'Refreshing...',
      searchArrivalStatus: 'Search arrival status...',
      searchSource: 'Search source...',
      loadingQueue: 'Loading arrival queue...',
      liveArrivals: 'Live arrivals',
      liveArrivalsTitle: 'Check-in queue ready to process',
      noArrival: 'No arrivals for this filter yet',
      noArrivalHint: 'Try changing the status or source filter to see another queue.',
      invalidAssignableRooms: 'Invalid assignable rooms response.',
      assignableRoomsFailed: 'Failed to load room assignment list.',
      invalidArrivalResponse: 'Invalid arrival queue response.',
      arrivalLoadFailed: 'Failed to load arrival queue.',
      roomIdRequired: 'Room ID is required before assigning a room.',
      invalidAssignRoom: 'Invalid assign room response.',
      assignRoomFailed: 'Failed to update room assignment.',
      assignRoomSuccess: 'Room assignment updated successfully.',
      invalidVerifyIdentity: 'Invalid identity verification response.',
      verifyIdentityFailed: 'Identity verification failed.',
      verifyIdentitySuccess: 'Guest identity verified successfully.',
      invalidCompleteCheckin: 'Invalid complete check-in response.',
      completeCheckinFailed: 'Complete check-in failed.',
      completeCheckinSuccess: 'Check-in completed successfully.',
      columns: ['Booking', 'Guest', 'Stay', 'Room', 'Status'],
      statuses: {
        reserved: 'Reserved',
        arrival_due: 'Arrival due',
        arrived: 'Arrived',
        id_pending: 'ID pending',
        registration_pending: 'Registration pending',
        ready_for_checkin: 'Ready check-in',
        checked_in: 'Checked in',
        no_show: 'No show',
        cancelled: 'Cancelled',
      },
      sourceOptions: [
        { label: 'Direct', description: 'Reservation created internally' },
        { label: 'Portal', description: 'Generated from the public portal' },
      ],
      propertyMissing: 'Property not selected yet',
      primaryGuestMissing: 'No primary guest yet',
      phoneMissing: 'Phone not provided',
      adults: 'adults',
      children: 'children',
      roomTypeMissing: 'Room type not filled yet',
      notAssigned: 'Not assigned yet',
      waitingAssignment: 'Waiting for room assignment',
      checkinWorkspace: 'Check-in workspace',
      propertyFallback: 'Property',
      reservationStatus: 'Reservation status',
      guestIdentity: 'Guest identity',
      verified: 'Verified',
      notVerified: 'Not verified yet',
      roomAssignment: 'Room assignment',
      step1: 'Step 1',
      step2: 'Step 2',
      step3: 'Step 3',
      assignRoom: 'Assign room',
      saving: 'Saving...',
      internalRoomId: 'Internal room ID',
      selectArrivalRoom: 'Choose room for this arrival',
      searchRoomNumber: 'Search room number...',
      assignmentNotes: 'Assignment notes',
      assignmentNotesPlaceholder: 'Example: near elevator / high floor',
      verifyIdentity: 'Verify identity',
      verifying: 'Verifying...',
      primaryGuestName: 'Primary guest name',
      primaryGuestNamePlaceholder: 'Name as shown on ID',
      idType: 'Identity type',
      idTypePlaceholder: 'ktp / passport',
      idNumber: 'Identity number',
      idNumberPlaceholder: 'KTP / passport number',
      guestPhonePlaceholder: 'Guest phone number',
      guestEmailPlaceholder: 'Guest email',
      nationality: 'Nationality',
      address: 'Address',
      addressPlaceholder: 'Short guest address',
      completeCheckin: 'Complete check-in',
      confirmIdentity: 'Identity verified',
      confirmRegistration: 'Registration signed',
      confirmTerms: 'Terms accepted',
      issueKeycard: 'Issue keycard',
      checkinNotes: 'Check-in notes',
      checkinNotesPlaceholder: 'Operational notes during check-in',
      completing: 'Completing...',
      walkIn: 'Walk-in',
      walkInTitle: 'Create Walk-in Reservation',
      guestInformation: 'Guest Information',
      roomSelection: 'Room & Dates',
      rateAndPayment: 'Rate & Payment',
      walkinGuestName: 'Guest name',
      walkinGuestNamePlaceholder: 'Guest full name',
      walkinGuestPhone: 'Phone',
      walkinGuestPhonePlaceholder: 'Guest phone number',
      walkinGuestEmail: 'Email',
      walkinGuestEmailPlaceholder: 'Guest email',
      walkinIdType: 'ID type',
      walkinIdNumber: 'ID number',
      walkinRoomType: 'Room type ID',
      walkinRoomTypePlaceholder: 'Room type ID',
      walkinRoomId: 'Room ID',
      walkinRoomPlaceholder: 'Room ID (optional)',
      walkinCheckInDate: 'Check-in date',
      walkinCheckOutDate: 'Check-out date',
      walkinAdults: 'adults',
      walkinChildren: 'children',
      walkinRatePerNight: 'Rate per night',
      walkinDepositAmount: 'Deposit amount',
      walkinPaymentMethod: 'Payment method',
      walkinPaymentAmount: 'Payment amount',
      walkinAutoCheckIn: 'Auto check-in',
      walkinCreateInvoice: 'Create invoice',
      walkinSpecialRequests: 'Special requests',
      walkinSpecialRequestsPlaceholder: 'Special requests...',
      walkinInternalNotes: 'Internal notes',
      walkinInternalNotesPlaceholder: 'Internal notes...',
      submitting: 'Submitting...',
      walkInSuccess: 'Walk-in reservation created successfully.',
      walkInFailed: 'Failed to create walk-in reservation.',
    }
  }

  return {
    title: 'Arrivals',
    metrics: ['Total queue', 'ID verified', 'Room assigned', 'Ready check-in'],
    allStatuses: 'Semua status',
    allStatusesDesc: 'Tampilkan semua arrival aktif',
    allSources: 'Semua source',
    allSourcesDesc: 'Direct, portal, OTA, corporate',
    status: 'Status',
    source: 'Source',
    searchBookingGuest: 'Cari booking / tamu',
    searchBookingGuestPlaceholder: 'Cari booking code, nama, phone...',
    arrivalQueue: 'Arrival queue',
    arrivalQueueTitle: 'Kontrol check-in internal tanpa nyampur ke portal/CMS',
    refreshQueue: 'Refresh queue',
    refreshing: 'Merefresh...',
    searchArrivalStatus: 'Cari status arrival...',
    searchSource: 'Cari source...',
    loadingQueue: 'Loading arrival queue...',
    liveArrivals: 'Live arrivals',
    liveArrivalsTitle: 'Queue check-in yang siap diproses',
    noArrival: 'Belum ada arrival untuk filter ini',
    noArrivalHint: 'Coba ubah filter status atau source untuk melihat queue yang lain.',
    invalidAssignableRooms: 'Respons assignable rooms tidak valid.',
    assignableRoomsFailed: 'Daftar room assignment gagal dimuat.',
    invalidArrivalResponse: 'Respons arrival queue tidak valid.',
    arrivalLoadFailed: 'Arrival queue gagal dimuat.',
    roomIdRequired: 'Room ID wajib diisi sebelum assign kamar.',
    invalidAssignRoom: 'Respons assign room tidak valid.',
    assignRoomFailed: 'Room assignment gagal diperbarui.',
    assignRoomSuccess: 'Room assignment berhasil diperbarui.',
    invalidVerifyIdentity: 'Respons verifikasi identitas tidak valid.',
    verifyIdentityFailed: 'Verifikasi identitas gagal.',
    verifyIdentitySuccess: 'Identitas tamu berhasil diverifikasi.',
    invalidCompleteCheckin: 'Respons complete check-in tidak valid.',
    completeCheckinFailed: 'Complete check-in gagal.',
    completeCheckinSuccess: 'Check-in berhasil diselesaikan.',
    columns: ['Booking', 'Tamu', 'Stay', 'Room', 'Status'],
    statuses: {
      reserved: 'Reserved',
      arrival_due: 'Arrival due',
      arrived: 'Arrived',
      id_pending: 'ID pending',
      registration_pending: 'Registrasi pending',
      ready_for_checkin: 'Siap check-in',
      checked_in: 'Checked in',
      no_show: 'No show',
      cancelled: 'Cancelled',
    },
    sourceOptions: [
      { label: 'Direct', description: 'Reservasi dibuat internal' },
      { label: 'Portal', description: 'Hasil dari portal publik' },
    ],
    propertyMissing: 'Property belum dipilih',
    primaryGuestMissing: 'Belum ada tamu utama',
    phoneMissing: 'Phone belum diisi',
    adults: 'dewasa',
    children: 'anak',
    roomTypeMissing: 'Room type belum diisi',
    notAssigned: 'Belum assigned',
    waitingAssignment: 'Waiting room assignment',
    checkinWorkspace: 'Check-in workspace',
    propertyFallback: 'Property',
    reservationStatus: 'Status reservasi',
    guestIdentity: 'Identitas tamu',
    verified: 'Verified',
    notVerified: 'Belum verified',
    roomAssignment: 'Room assignment',
    step1: 'Step 1',
    step2: 'Step 2',
    step3: 'Step 3',
    assignRoom: 'Assign room',
    saving: 'Menyimpan...',
    internalRoomId: 'Room ID internal',
    selectArrivalRoom: 'Pilih kamar untuk arrival',
    searchRoomNumber: 'Cari nomor kamar...',
    assignmentNotes: 'Catatan assignment',
    assignmentNotesPlaceholder: 'Contoh: dekat lift / high floor',
    verifyIdentity: 'Verify identity',
    verifying: 'Memverifikasi...',
    primaryGuestName: 'Nama tamu utama',
    primaryGuestNamePlaceholder: 'Nama sesuai KTP',
    idType: 'Jenis identitas',
    idTypePlaceholder: 'ktp / passport',
    idNumber: 'Nomor identitas',
    idNumberPlaceholder: 'Nomor KTP / passport',
    guestPhonePlaceholder: 'Nomor HP tamu',
    guestEmailPlaceholder: 'Email tamu',
    nationality: 'Nationality',
    address: 'Alamat',
    addressPlaceholder: 'Alamat singkat tamu',
    completeCheckin: 'Complete check-in',
    confirmIdentity: 'Identity verified',
    confirmRegistration: 'Registration signed',
    confirmTerms: 'Terms accepted',
    issueKeycard: 'Issue keycard',
    checkinNotes: 'Catatan check-in',
    checkinNotesPlaceholder: 'Catatan operasional saat check-in',
    completing: 'Memproses...',
    walkIn: 'Walk-in',
    walkInTitle: 'Buat Reservasi Walk-in',
    guestInformation: 'Informasi Tamu',
    roomSelection: 'Kamar & Tanggal',
    rateAndPayment: 'Rate & Pembayaran',
    walkinGuestName: 'Nama tamu',
    walkinGuestNamePlaceholder: 'Nama lengkap tamu',
    walkinGuestPhone: 'Phone',
    walkinGuestPhonePlaceholder: 'Nomor HP tamu',
    walkinGuestEmail: 'Email',
    walkinGuestEmailPlaceholder: 'Email tamu',
    walkinIdType: 'Jenis identitas',
    walkinIdNumber: 'Nomor identitas',
    walkinRoomType: 'Room type ID',
    walkinRoomTypePlaceholder: 'Room type ID',
    walkinRoomId: 'Room ID',
    walkinRoomPlaceholder: 'Room ID (opsional)',
    walkinCheckInDate: 'Tanggal check-in',
    walkinCheckOutDate: 'Tanggal check-out',
    walkinAdults: 'dewasa',
    walkinChildren: 'anak',
    walkinRatePerNight: 'Rate per malam',
    walkinDepositAmount: 'Deposit',
    walkinPaymentMethod: 'Metode pembayaran',
    walkinPaymentAmount: 'Jumlah pembayaran',
    walkinAutoCheckIn: 'Auto check-in',
    walkinCreateInvoice: 'Buat invoice',
    walkinSpecialRequests: 'Permintaan khusus',
    walkinSpecialRequestsPlaceholder: 'Permintaan khusus...',
    walkinInternalNotes: 'Catatan internal',
    walkinInternalNotesPlaceholder: 'Catatan internal...',
    submitting: 'Menyimpan...',
    walkInSuccess: 'Reservasi walk-in berhasil dibuat.',
    walkInFailed: 'Gagal membuat reservasi walk-in.',
  }
})

const statusLabelMap = computed<Record<ReservationStatus, string>>(() => copy.value.statuses)

const statusBadgeClassMap: Record<ReservationStatus, string> = {
  reserved: 'status-badge',
  arrival_due: 'status-badge status-badge--soft',
  arrived: 'status-badge status-badge--warning',
  id_pending: 'status-badge status-badge--warning',
  registration_pending: 'status-badge',
  ready_for_checkin: 'status-badge status-badge--success',
  checked_in: 'status-badge status-badge--success',
  no_show: 'status-badge status-badge--warning',
  cancelled: 'status-badge status-badge--warning',
}

const arrivalColumns = computed(() => [
  { key: 'booking_code', label: copy.value.columns[0], sortable: true },
  { key: 'primary_guest', label: copy.value.columns[1], sortable: false },
  { key: 'check_in_date', label: copy.value.columns[2], sortable: true },
  { key: 'assigned_room', label: copy.value.columns[3], sortable: false },
  { key: 'reservation_status', label: copy.value.columns[4], sortable: true, align: 'center' as const },
])

const metrics = computed(() => [
  { label: copy.value.metrics[0], value: String(meta.value.total), tone: 'primary' as const },
  {
    label: copy.value.metrics[1],
    value: String(arrivals.value.filter((item) => item.primary_guest.identity_verified).length),
    tone: 'success' as const,
  },
  {
    label: copy.value.metrics[2],
    value: String(arrivals.value.filter((item) => item.assigned_room.id !== null).length),
    tone: 'neutral' as const,
  },
  {
    label: copy.value.metrics[3],
    value: String(arrivals.value.filter((item) => item.reservation_status === 'registration_pending' || item.reservation_status === 'ready_for_checkin').length),
    tone: 'warning' as const,
  },
])

const statusOptions = computed(() => [
  { value: 'all', label: copy.value.allStatuses, description: copy.value.allStatusesDesc },
  { value: 'arrival_due', label: statusLabelMap.value.arrival_due, description: isEnglish.value ? 'Guests scheduled to arrive today' : 'Tamu dijadwalkan datang hari ini' },
  { value: 'arrived', label: statusLabelMap.value.arrived, description: isEnglish.value ? 'Guest has arrived at the front desk' : 'Tamu sudah datang ke front desk' },
  { value: 'registration_pending', label: statusLabelMap.value.registration_pending, description: isEnglish.value ? 'Identity already verified' : 'Identitas sudah diverifikasi' },
  { value: 'checked_in', label: statusLabelMap.value.checked_in, description: isEnglish.value ? 'Guest is already in the room' : 'Tamu sudah masuk kamar' },
])

const sourceOptions = computed(() => [
  { value: 'all', label: copy.value.allSources, description: copy.value.allSourcesDesc },
  { value: 'direct', label: copy.value.sourceOptions[0].label, description: copy.value.sourceOptions[0].description },
  { value: 'portal', label: copy.value.sourceOptions[1].label, description: copy.value.sourceOptions[1].description },
])

const roomOptions = computed(() => assignableRooms.value.map((room) => ({
  value: String(room.id),
  label: `${room.room_number} · ${room.room_type.name || room.room_type.code || 'Room'}`,
  description: `Floor ${room.floor} · ${room.current_status}`,
})))

const tableRows = computed(() => arrivals.value as unknown as Record<string, unknown>[])
const asArrival = (row: unknown) => row as ArrivalRow

let searchDebounce: number | undefined

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

const formatDate = (value: string) => new Intl.DateTimeFormat('id-ID', {
  day: '2-digit',
  month: 'short',
  year: 'numeric',
}).format(new Date(value))

const resetForms = () => {
  roomAssignmentForm.roomId = ''
  roomAssignmentForm.notes = ''
  identityForm.full_name = ''
  identityForm.id_type = 'ktp'
  identityForm.id_number = ''
  identityForm.phone = ''
  identityForm.email = ''
  identityForm.nationality = 'ID'
  identityForm.address = ''
  completeForm.confirmIdentityVerified = true
  completeForm.confirmRegistrationSigned = true
  completeForm.confirmTermsAccepted = true
  completeForm.issueKeycard = true
  completeForm.notes = ''
}

const syncDetailForms = (arrival: ArrivalRow | null) => {
  resetForms()

  if (!arrival) {
    return
  }

  roomAssignmentForm.roomId = arrival.assigned_room.id ? String(arrival.assigned_room.id) : ''
  identityForm.full_name = arrival.primary_guest.full_name ?? ''
  identityForm.phone = arrival.primary_guest.phone ?? ''
  identityForm.email = arrival.primary_guest.email ?? ''
  completeForm.confirmRegistrationSigned = arrival.primary_guest.identity_verified ?? true
}

const applyFiltersToQuery = (query: URLSearchParams) => {
  if (filters.status !== 'all') {
    query.set('status', filters.status)
  }

  if (filters.source !== 'all') {
    query.set('source', filters.source)
  }

  if (filters.search.trim()) {
    query.set('search', filters.search.trim())
  }

  query.set('page', String(tableQuery.page))
  query.set('per_page', String(tableQuery.perPage))
  query.set('sort_by', tableQuery.sortBy)
  query.set('sort_direction', tableQuery.sortDirection)
}

const refreshSelectedArrival = (reservationId: number) => {
  const fresh = arrivals.value.find((item) => item.id === reservationId) ?? null
  selectedArrival.value = fresh
  syncDetailForms(fresh)
}

const loadAssignableRooms = async (reservationId: number) => {
  loadingAssignableRooms.value = true

  try {
    const response = await fetch(buildApiUrl(`/api/v1/front-desk/arrivals/${reservationId}/assignable-rooms`), {
      headers: authHeaders(),
    })

    const payload = await parseJsonResponse<{
      success: boolean
      message: string
      data: { items: AssignableRoom[] }
    }>(response, copy.value.invalidAssignableRooms)

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || copy.value.assignableRoomsFailed)
    }

    assignableRooms.value = payload.data.items
  } catch (error) {
    assignableRooms.value = []
    errorMessage.value = error instanceof Error ? error.message : copy.value.assignableRoomsFailed
  } finally {
    loadingAssignableRooms.value = false
  }
}

const loadArrivals = async (mode: 'initial' | 'refresh' = 'initial', keepDetail = true) => {
  if (mode === 'initial') {
    loading.value = true
  } else {
    refreshing.value = true
  }

  errorMessage.value = ''

  const selectedId = selectedArrival.value?.id ?? null

  try {
    const query = new URLSearchParams()
    applyFiltersToQuery(query)

    const response = await fetch(buildApiUrl(`/api/v1/front-desk/arrivals?${query.toString()}`), {
      headers: authHeaders(),
    })

    const payload = await parseJsonResponse<{
      success: boolean
      message: string
      data: ArrivalPayload
      meta: ArrivalMeta
    }>(response, copy.value.invalidArrivalResponse)

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || copy.value.arrivalLoadFailed)
    }

    arrivals.value = payload.data.items
    meta.value = payload.meta
    tableQuery.page = payload.meta.current_page
    tableQuery.perPage = payload.meta.per_page
    tableQuery.sortBy = payload.data.filters.sort_by
    tableQuery.sortDirection = payload.data.filters.sort_direction

    if (keepDetail && selectedId !== null) {
      refreshSelectedArrival(selectedId)
    }
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : copy.value.arrivalLoadFailed
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

const openArrivalDetail = (row: Record<string, unknown>) => {
  selectedArrival.value = row as ArrivalRow
  syncDetailForms(selectedArrival.value)
  loadAssignableRooms(selectedArrival.value.id)
}

const closeArrivalDetail = () => {
  selectedArrival.value = null
  assignableRooms.value = []
  resetForms()
}

const handleSortChange = ({ sortBy, sortDirection }: { sortBy: string; sortDirection: 'asc' | 'desc' }) => {
  tableQuery.sortBy = sortBy
  tableQuery.sortDirection = sortDirection
  tableQuery.page = 1
  loadArrivals('refresh')
}

const handlePageChange = (page: number) => {
  tableQuery.page = page
  loadArrivals('refresh')
}

const handlePerPageChange = (perPage: number) => {
  tableQuery.perPage = perPage
  tableQuery.page = 1
  loadArrivals('refresh')
}

const assignRoom = async () => {
  if (!selectedArrival.value || !roomAssignmentForm.roomId.trim()) {
    errorMessage.value = copy.value.roomIdRequired
    return
  }

  assigningRoomId.value = selectedArrival.value.id
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch(buildApiUrl(`/api/v1/front-desk/arrivals/${selectedArrival.value.id}/assign-room`), {
      method: 'PATCH',
      headers: authHeaders(true),
      body: JSON.stringify({
        room_id: Number(roomAssignmentForm.roomId),
        notes: roomAssignmentForm.notes || null,
      }),
    })

    const payload = await parseJsonResponse<{
      success?: boolean
      message?: string
      errors?: Record<string, string[]>
    }>(response, copy.value.invalidAssignRoom)

    if (!response.ok || !payload.success) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || copy.value.assignRoomFailed)
    }

    successMessage.value = payload.message || copy.value.assignRoomSuccess
    await loadArrivals('refresh')
    await loadAssignableRooms(selectedArrival.value.id)
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : copy.value.assignRoomFailed
  } finally {
    assigningRoomId.value = null
  }
}

const verifyIdentity = async () => {
  if (!selectedArrival.value) {
    return
  }

  verifyingReservationId.value = selectedArrival.value.id
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch(buildApiUrl(`/api/v1/front-desk/arrivals/${selectedArrival.value.id}/verify-identity`), {
      method: 'PATCH',
      headers: authHeaders(true),
      body: JSON.stringify({
        guest: {
          full_name: identityForm.full_name,
          id_type: identityForm.id_type,
          id_number: identityForm.id_number,
          phone: identityForm.phone || null,
          email: identityForm.email || null,
          nationality: identityForm.nationality || null,
          address: identityForm.address || null,
        },
      }),
    })

    const payload = await parseJsonResponse<{
      success?: boolean
      message?: string
      errors?: Record<string, string[]>
    }>(response, copy.value.invalidVerifyIdentity)

    if (!response.ok || !payload.success) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || copy.value.verifyIdentityFailed)
    }

    successMessage.value = payload.message || copy.value.verifyIdentitySuccess
    await loadArrivals('refresh')
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : copy.value.verifyIdentityFailed
  } finally {
    verifyingReservationId.value = null
  }
}

const completeCheckin = async () => {
  if (!selectedArrival.value) {
    return
  }

  completingReservationId.value = selectedArrival.value.id
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch(buildApiUrl(`/api/v1/front-desk/arrivals/${selectedArrival.value.id}/complete-check-in`), {
      method: 'POST',
      headers: authHeaders(true),
      body: JSON.stringify({
        confirm_identity_verified: completeForm.confirmIdentityVerified,
        confirm_registration_signed: completeForm.confirmRegistrationSigned,
        confirm_terms_accepted: completeForm.confirmTermsAccepted,
        issue_keycard: completeForm.issueKeycard,
        notes: completeForm.notes || null,
      }),
    })

    const payload = await parseJsonResponse<{
      success?: boolean
      message?: string
      errors?: Record<string, string[]>
    }>(response, copy.value.invalidCompleteCheckin)

    if (!response.ok || !payload.success) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || copy.value.completeCheckinFailed)
    }

    successMessage.value = payload.message || copy.value.completeCheckinSuccess
    await loadArrivals('refresh')
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : copy.value.completeCheckinFailed
  } finally {
    completingReservationId.value = null
  }
}

// Walk-in functions
const resetWalkInForm = () => {
  walkInForm.guest_full_name = ''
  walkInForm.guest_phone = ''
  walkInForm.guest_email = ''
  walkInForm.guest_id_type = 'ktp'
  walkInForm.guest_id_number = ''
  walkInForm.room_type_id = ''
  walkInForm.room_id = ''
  walkInForm.check_in_date = new Date().toISOString().split('T')[0]
  walkInForm.check_out_date = ''
  walkInForm.adult_count = 1
  walkInForm.child_count = 0
  walkInForm.rate_per_night = 0
  walkInForm.deposit_amount = 0
  walkInForm.payment_method_code = 'cash'
  walkInForm.payment_amount = 0
  walkInForm.special_requests = ''
  walkInForm.internal_notes = ''
  walkInForm.auto_check_in = false
  walkInForm.create_invoice = true
}

const openWalkInModal = () => {
  resetWalkInForm()
  walkInModalOpen.value = true
}

const closeWalkInModal = () => {
  walkInModalOpen.value = false
}

const submitWalkIn = async () => {
  if (!walkInForm.guest_full_name.trim() || !walkInForm.guest_phone.trim() || !walkInForm.check_out_date) return

  walkInSubmitting.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch(buildApiUrl('/api/v1/front-desk/walk-in?property_id=1'), {
      method: 'POST',
      headers: authHeaders(true),
      body: JSON.stringify({
        guest_full_name: walkInForm.guest_full_name,
        guest_phone: walkInForm.guest_phone,
        guest_email: walkInForm.guest_email || null,
        guest_id_type: walkInForm.guest_id_type || null,
        guest_id_number: walkInForm.guest_id_number || null,
        room_type_id: walkInForm.room_type_id ? Number(walkInForm.room_type_id) : null,
        room_id: walkInForm.room_id ? Number(walkInForm.room_id) : null,
        check_in_date: walkInForm.check_in_date,
        check_out_date: walkInForm.check_out_date,
        adult_count: walkInForm.adult_count,
        child_count: walkInForm.child_count,
        rate_per_night: walkInForm.rate_per_night > 0 ? walkInForm.rate_per_night : null,
        deposit_amount: walkInForm.deposit_amount > 0 ? walkInForm.deposit_amount : null,
        payment_method_code: walkInForm.payment_method_code || null,
        payment_amount: walkInForm.payment_amount > 0 ? walkInForm.payment_amount : null,
        special_requests: walkInForm.special_requests || null,
        internal_notes: walkInForm.internal_notes || null,
        auto_check_in: walkInForm.auto_check_in,
        create_invoice: walkInForm.create_invoice,
      }),
    })

    const payload = await parseJsonResponse<{
      success?: boolean
      message?: string
      errors?: Record<string, string[]>
    }>(response, 'Invalid walk-in response.')

    if (!response.ok || !payload.success) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || copy.value.walkInFailed)
    }

    successMessage.value = payload.message || copy.value.walkInSuccess
    closeWalkInModal()
    await loadArrivals('refresh')
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : copy.value.walkInFailed
  } finally {
    walkInSubmitting.value = false
  }
}

watch(() => filters.status, () => {
  tableQuery.page = 1
  loadArrivals('refresh', false)
})

watch(() => filters.source, () => {
  tableQuery.page = 1
  loadArrivals('refresh', false)
})

watch(() => filters.search, () => {
  if (searchDebounce) {
    window.clearTimeout(searchDebounce)
  }

  searchDebounce = window.setTimeout(() => {
    tableQuery.page = 1
    loadArrivals('refresh', false)
  }, 320)
})

onMounted(() => {
  loadArrivals()
})

onUnmounted(() => {
  if (searchDebounce) {
    window.clearTimeout(searchDebounce)
  }
})
</script>

<template>
  <AppShell :title="copy.title" :metrics="metrics" hero-variant="plain">
    <section class="content-grid">
      <article class="surface-card surface-card--wide">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.arrivalQueue }}</span>
            <h2>{{ copy.arrivalQueueTitle }}</h2>
          </div>

          <div class="action-pair">
            <button type="button" class="primary-button" @click="openWalkInModal">
              <Icon :icon="mdiAccountPlusOutline" />
              {{ copy.walkIn }}
            </button>
            <button type="button" class="secondary-button" :disabled="refreshing" @click="loadArrivals('refresh')">
              <Icon :icon="mdiRefresh" />
              {{ refreshing ? copy.refreshing : copy.refreshQueue }}
            </button>
          </div>
        </div>

        <div class="arrival-toolbar">
          <label class="field">
            <span>{{ copy.status }}</span>
            <SearchSelect
              v-model="filters.status"
              :options="statusOptions"
              :placeholder="copy.allStatuses"
              :search-placeholder="copy.searchArrivalStatus"
            />
          </label>

          <label class="field">
            <span>{{ copy.source }}</span>
            <SearchSelect
              v-model="filters.source"
              :options="sourceOptions"
              :placeholder="copy.allSources"
              :search-placeholder="copy.searchSource"
            />
          </label>

          <label class="field">
            <span>{{ copy.searchBookingGuest }}</span>
            <input v-model="filters.search" type="text" class="text-input" :placeholder="copy.searchBookingGuestPlaceholder" />
          </label>
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
            <span class="section-kicker">{{ copy.liveArrivals }}</span>
            <h2>{{ copy.liveArrivalsTitle }}</h2>
          </div>
        </div>

        <div v-if="!loading && arrivals.length === 0" class="portal-state-card">
          <Icon :icon="mdiTrayArrowDown" />
          <strong>{{ copy.noArrival }}</strong>
          <span>{{ copy.noArrivalHint }}</span>
        </div>

        <ServerDataTable
          v-else
          row-key="id"
          :columns="arrivalColumns"
          :rows="tableRows"
          :meta="meta"
          :loading="loading"
          :sort-by="tableQuery.sortBy"
          :sort-direction="tableQuery.sortDirection"
          :empty-message="copy.noArrival"
          clickable-rows
          @sort-change="handleSortChange"
          @page-change="handlePageChange"
          @per-page-change="handlePerPageChange"
          @row-click="openArrivalDetail"
        >
          <template #cell-booking_code="{ row }">
            <div class="arrival-table-cell">
              <strong>{{ asArrival(row).booking_code }}</strong>
              <span>{{ asArrival(row).property.code || copy.propertyMissing }} · {{ asArrival(row).source }}</span>
            </div>
          </template>

          <template #cell-primary_guest="{ row }">
            <div class="arrival-table-cell">
              <strong>{{ asArrival(row).primary_guest.full_name || copy.primaryGuestMissing }}</strong>
              <span>{{ asArrival(row).primary_guest.phone || copy.phoneMissing }}</span>
            </div>
          </template>

          <template #cell-check_in_date="{ row }">
            <div class="arrival-table-cell">
              <strong>{{ formatDate(asArrival(row).check_in_date) }}</strong>
              <span>
                {{ asArrival(row).adult_count }} {{ copy.adults }} ·
                {{ asArrival(row).child_count }} {{ copy.children }} ·
                {{ asArrival(row).room_type.name || copy.roomTypeMissing }}
              </span>
            </div>
          </template>

          <template #cell-assigned_room="{ row }">
            <div class="arrival-table-cell">
              <strong>{{ asArrival(row).assigned_room.room_number || copy.notAssigned }}</strong>
              <span>{{ asArrival(row).assigned_room.current_status || copy.waitingAssignment }}</span>
            </div>
          </template>

          <template #cell-reservation_status="{ row }">
            <span :class="statusBadgeClassMap[asArrival(row).reservation_status]">
              {{ statusLabelMap[asArrival(row).reservation_status] }}
            </span>
          </template>
        </ServerDataTable>
      </article>
    </section>

    <div v-if="selectedArrival" class="arrival-modal-backdrop" @click="closeArrivalDetail"></div>

    <section v-if="selectedArrival" class="arrival-modal-shell">
      <article class="arrival-modal-card" @click.stop>
        <div class="arrival-modal-card__header">
          <div>
            <span class="section-kicker">{{ copy.checkinWorkspace }}</span>
            <h2>{{ selectedArrival.primary_guest.full_name || selectedArrival.booking_code }}</h2>
            <p>
              {{ selectedArrival.booking_code }} ·
              {{ selectedArrival.property.name || selectedArrival.property.code || copy.propertyFallback }} ·
              {{ selectedArrival.room_type.name || copy.roomTypeMissing }}
            </p>
          </div>

          <button type="button" class="icon-button" @click="closeArrivalDetail">
            <Icon :icon="mdiClose" />
          </button>
        </div>

        <div class="arrival-detail-grid">
          <div class="arrival-summary-card">
            <span class="section-kicker">{{ copy.reservationStatus }}</span>
            <strong>{{ statusLabelMap[selectedArrival.reservation_status] }}</strong>
            <small>{{ formatDate(selectedArrival.check_in_date) }} → {{ formatDate(selectedArrival.check_out_date) }}</small>
          </div>

          <div class="arrival-summary-card">
            <span class="section-kicker">{{ copy.guestIdentity }}</span>
            <strong>{{ selectedArrival.primary_guest.identity_verified ? copy.verified : copy.notVerified }}</strong>
            <small>{{ selectedArrival.primary_guest.email || copy.phoneMissing }}</small>
          </div>

          <div class="arrival-summary-card">
            <span class="section-kicker">{{ copy.roomAssignment }}</span>
            <strong>{{ selectedArrival.assigned_room.room_number || copy.notAssigned }}</strong>
            <small>{{ selectedArrival.payment_status }} · {{ selectedArrival.guarantee_status }}</small>
          </div>
        </div>

        <div class="arrival-action-grid">
          <section class="arrival-workspace-block">
            <div class="arrival-workspace-block__header">
              <div>
                <span class="section-kicker">{{ copy.step1 }}</span>
                <h3>{{ copy.assignRoom }}</h3>
              </div>
              <Icon :icon="mdiBedOutline" />
            </div>

            <label class="field">
              <span>{{ copy.internalRoomId }}</span>
              <SearchSelect
                v-model="roomAssignmentForm.roomId"
                :options="roomOptions"
                :disabled="loadingAssignableRooms || assigningRoomId === selectedArrival.id"
                :placeholder="copy.selectArrivalRoom"
                :search-placeholder="copy.searchRoomNumber"
              />
            </label>

            <label class="field">
              <span>{{ copy.assignmentNotes }}</span>
              <input v-model="roomAssignmentForm.notes" type="text" class="text-input" :placeholder="copy.assignmentNotesPlaceholder" />
            </label>

            <button
              type="button"
              class="primary-button"
              :disabled="assigningRoomId === selectedArrival.id"
              @click="assignRoom"
            >
              {{ assigningRoomId === selectedArrival.id ? copy.saving : copy.assignRoom }}
            </button>
          </section>

          <section class="arrival-workspace-block">
            <div class="arrival-workspace-block__header">
              <div>
                <span class="section-kicker">{{ copy.step2 }}</span>
                <h3>{{ copy.verifyIdentity }}</h3>
              </div>
              <Icon :icon="mdiAccountBadgeOutline" />
            </div>

            <div class="arrival-form-grid">
              <label class="field">
                <span>{{ copy.primaryGuestName }}</span>
                <input v-model="identityForm.full_name" type="text" class="text-input" :placeholder="copy.primaryGuestNamePlaceholder" />
              </label>

              <label class="field">
                <span>{{ copy.idType }}</span>
                <input v-model="identityForm.id_type" type="text" class="text-input" :placeholder="copy.idTypePlaceholder" />
              </label>

              <label class="field">
                <span>{{ copy.idNumber }}</span>
                <input v-model="identityForm.id_number" type="text" class="text-input" :placeholder="copy.idNumberPlaceholder" />
              </label>

              <label class="field">
                <span>Phone</span>
                <input v-model="identityForm.phone" type="text" class="text-input" :placeholder="copy.guestPhonePlaceholder" />
              </label>

              <label class="field">
                <span>Email</span>
                <input v-model="identityForm.email" type="email" class="text-input" :placeholder="copy.guestEmailPlaceholder" />
              </label>

              <label class="field">
                <span>{{ copy.nationality }}</span>
                <input v-model="identityForm.nationality" type="text" class="text-input" placeholder="ID" />
              </label>
            </div>

            <label class="field">
              <span>{{ copy.address }}</span>
              <input v-model="identityForm.address" type="text" class="text-input" :placeholder="copy.addressPlaceholder" />
            </label>

            <button
              type="button"
              class="primary-button"
              :disabled="verifyingReservationId === selectedArrival.id"
              @click="verifyIdentity"
            >
              {{ verifyingReservationId === selectedArrival.id ? copy.verifying : copy.verifyIdentity }}
            </button>
          </section>
        </div>

        <section class="arrival-workspace-block arrival-workspace-block--complete">
          <div class="arrival-workspace-block__header">
            <div>
              <span class="section-kicker">{{ copy.step3 }}</span>
              <h3>{{ copy.completeCheckin }}</h3>
            </div>
            <Icon :icon="mdiCheckCircleOutline" />
          </div>

          <div class="arrival-confirm-grid">
            <label class="arrival-check">
              <input v-model="completeForm.confirmIdentityVerified" type="checkbox" />
              <span>{{ copy.confirmIdentity }}</span>
            </label>

            <label class="arrival-check">
              <input v-model="completeForm.confirmRegistrationSigned" type="checkbox" />
              <span>{{ copy.confirmRegistration }}</span>
            </label>

            <label class="arrival-check">
              <input v-model="completeForm.confirmTermsAccepted" type="checkbox" />
              <span>{{ copy.confirmTerms }}</span>
            </label>

            <label class="arrival-check">
              <input v-model="completeForm.issueKeycard" type="checkbox" />
              <span>{{ copy.issueKeycard }}</span>
            </label>
          </div>

          <label class="field">
            <span>{{ copy.checkinNotes }}</span>
            <input v-model="completeForm.notes" type="text" class="text-input" :placeholder="copy.checkinNotesPlaceholder" />
          </label>

          <button
            type="button"
            class="primary-button"
            :disabled="completingReservationId === selectedArrival.id"
            @click="completeCheckin"
          >
            {{ completingReservationId === selectedArrival.id ? copy.completing : copy.completeCheckin }}
          </button>
        </section>
      </article>
    </section>

    <!-- Walk-in Modal -->
    <div v-if="walkInModalOpen" class="walkin-modal-backdrop" @click="closeWalkInModal"></div>

    <section v-if="walkInModalOpen" class="walkin-modal-shell">
      <article class="walkin-modal-card" @click.stop>
        <div class="walkin-modal-card__header">
          <div>
            <span class="section-kicker">{{ copy.walkIn }}</span>
            <h2>{{ copy.walkInTitle }}</h2>
          </div>

          <button type="button" class="icon-button" @click="closeWalkInModal">
            <Icon :icon="mdiClose" />
          </button>
        </div>

        <!-- Step 1: Guest Info -->
        <section class="arrival-workspace-block">
          <div class="arrival-workspace-block__header">
            <div>
              <span class="section-kicker">{{ copy.step1 }}</span>
              <h3>{{ copy.guestInformation }}</h3>
            </div>
            <Icon :icon="mdiAccountPlusOutline" />
          </div>

          <div class="arrival-form-grid">
            <label class="field">
              <span>{{ copy.walkinGuestName }}</span>
              <input v-model="walkInForm.guest_full_name" type="text" class="text-input" :placeholder="copy.walkinGuestNamePlaceholder" />
            </label>
            <label class="field">
              <span>{{ copy.walkinGuestPhone }}</span>
              <input v-model="walkInForm.guest_phone" type="text" class="text-input" :placeholder="copy.walkinGuestPhonePlaceholder" />
            </label>
            <label class="field">
              <span>{{ copy.walkinGuestEmail }}</span>
              <input v-model="walkInForm.guest_email" type="email" class="text-input" :placeholder="copy.walkinGuestEmailPlaceholder" />
            </label>
            <label class="field">
              <span>{{ copy.walkinIdType }}</span>
              <input v-model="walkInForm.guest_id_type" type="text" class="text-input" placeholder="ktp" />
            </label>
            <label class="field">
              <span>{{ copy.walkinIdNumber }}</span>
              <input v-model="walkInForm.guest_id_number" type="text" class="text-input" />
            </label>
          </div>
        </section>

        <!-- Step 2: Room & Dates -->
        <section class="arrival-workspace-block">
          <div class="arrival-workspace-block__header">
            <div>
              <span class="section-kicker">{{ copy.step2 }}</span>
              <h3>{{ copy.roomSelection }}</h3>
            </div>
            <Icon :icon="mdiBedOutline" />
          </div>

          <div class="arrival-form-grid">
            <label class="field">
              <span>{{ copy.walkinRoomType }}</span>
              <input v-model="walkInForm.room_type_id" type="number" class="text-input" :placeholder="copy.walkinRoomTypePlaceholder" />
            </label>
            <label class="field">
              <span>{{ copy.walkinRoomId }}</span>
              <input v-model="walkInForm.room_id" type="number" class="text-input" :placeholder="copy.walkinRoomPlaceholder" />
            </label>
            <label class="field">
              <span>{{ copy.walkinCheckInDate }}</span>
              <input v-model="walkInForm.check_in_date" type="date" class="text-input" />
            </label>
            <label class="field">
              <span>{{ copy.walkinCheckOutDate }}</span>
              <input v-model="walkInForm.check_out_date" type="date" class="text-input" />
            </label>
            <label class="field">
              <span>{{ copy.walkinAdults }}</span>
              <input v-model.number="walkInForm.adult_count" type="number" min="1" class="text-input" />
            </label>
            <label class="field">
              <span>{{ copy.walkinChildren }}</span>
              <input v-model.number="walkInForm.child_count" type="number" min="0" class="text-input" />
            </label>
          </div>
        </section>

        <!-- Step 3: Rate & Payment -->
        <section class="arrival-workspace-block">
          <div class="arrival-workspace-block__header">
            <div>
              <span class="section-kicker">{{ copy.step3 }}</span>
              <h3>{{ copy.rateAndPayment }}</h3>
            </div>
            <Icon :icon="mdiAccountBadgeOutline" />
          </div>

          <div class="arrival-form-grid">
            <label class="field">
              <span>{{ copy.walkinRatePerNight }}</span>
              <input v-model.number="walkInForm.rate_per_night" type="number" min="0" class="text-input" />
            </label>
            <label class="field">
              <span>{{ copy.walkinDepositAmount }}</span>
              <input v-model.number="walkInForm.deposit_amount" type="number" min="0" class="text-input" />
            </label>
            <label class="field">
              <span>{{ copy.walkinPaymentMethod }}</span>
              <select v-model="walkInForm.payment_method_code" class="text-input">
                <option value="cash">Cash</option>
                <option value="transfer">Transfer</option>
                <option value="card">Card</option>
                <option value="qris">QRIS</option>
              </select>
            </label>
            <label class="field">
              <span>{{ copy.walkinPaymentAmount }}</span>
              <input v-model.number="walkInForm.payment_amount" type="number" min="0" class="text-input" />
            </label>
          </div>

          <div class="arrival-confirm-grid">
            <label class="arrival-check">
              <input v-model="walkInForm.auto_check_in" type="checkbox" />
              <span>{{ copy.walkinAutoCheckIn }}</span>
            </label>
            <label class="arrival-check">
              <input v-model="walkInForm.create_invoice" type="checkbox" />
              <span>{{ copy.walkinCreateInvoice }}</span>
            </label>
          </div>
        </section>

        <!-- Notes -->
        <section class="arrival-workspace-block">
          <div class="arrival-form-grid">
            <label class="field">
              <span>{{ copy.walkinSpecialRequests }}</span>
              <input v-model="walkInForm.special_requests" type="text" class="text-input" :placeholder="copy.walkinSpecialRequestsPlaceholder" />
            </label>
            <label class="field">
              <span>{{ copy.walkinInternalNotes }}</span>
              <input v-model="walkInForm.internal_notes" type="text" class="text-input" :placeholder="copy.walkinInternalNotesPlaceholder" />
            </label>
          </div>
        </section>

        <!-- Submit -->
        <div class="action-pair">
          <button type="button" class="secondary-button" @click="closeWalkInModal">{{ isEnglish ? 'Cancel' : 'Batal' }}</button>
          <button
            type="button"
            class="primary-button"
            :disabled="walkInSubmitting || !walkInForm.guest_full_name.trim() || !walkInForm.guest_phone.trim() || !walkInForm.check_out_date"
            @click="submitWalkIn"
          >
            {{ walkInSubmitting ? copy.submitting : copy.walkIn }}
          </button>
        </div>
      </article>
    </section>
  </AppShell>
</template>

<style scoped>
.arrival-toolbar,
.arrival-form-grid,
.arrival-confirm-grid,
.arrival-detail-grid,
.arrival-action-grid {
  display: grid;
  gap: 16px;
}

.arrival-toolbar,
.arrival-action-grid {
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.arrival-detail-grid {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.arrival-form-grid,
.arrival-confirm-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.arrival-table-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.arrival-table-cell span {
  color: var(--color-text-soft);
  font-size: 0.82rem;
  line-height: 1.4;
}

.arrival-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(26, 20, 18, 0.4);
  z-index: 160;
}

.arrival-modal-shell {
  position: fixed;
  inset: 0;
  z-index: 170;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 20px;
}

.arrival-modal-card {
  width: min(1180px, 100%);
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

.arrival-modal-card__header,
.arrival-workspace-block__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.arrival-modal-card__header p {
  color: var(--color-text-soft);
  margin-top: 6px;
}

.arrival-summary-card,
.arrival-workspace-block {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.arrival-summary-card strong,
.arrival-workspace-block h3 {
  color: var(--color-text);
}

.arrival-summary-card small,
.arrival-workspace-block__header svg {
  color: var(--color-text-soft);
}

.arrival-workspace-block--complete {
  gap: 18px;
}

.arrival-check {
  min-height: 52px;
  padding: 14px 16px;
  border-radius: 18px;
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--color-text);
  font-weight: 600;
}

.arrival-check input {
  width: 18px;
  height: 18px;
  accent-color: var(--color-primary);
}

@media (max-width: 1024px) {
  .arrival-detail-grid,
  .arrival-form-grid,
  .arrival-confirm-grid,
  .arrival-action-grid {
    grid-template-columns: 1fr;
  }
}

.walkin-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(26, 20, 18, 0.4);
  z-index: 160;
}

.walkin-modal-shell {
  position: fixed;
  inset: 0;
  z-index: 170;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 20px;
}

.walkin-modal-card {
  width: min(860px, 100%);
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

.walkin-modal-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}
</style>
