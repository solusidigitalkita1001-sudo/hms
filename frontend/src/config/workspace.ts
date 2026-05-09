import mdiAccountGroupOutline from '@iconify-icons/mdi/account-group-outline'
import mdiAccountTieOutline from '@iconify-icons/mdi/account-tie-outline'
import mdiAirplaneLanding from '@iconify-icons/mdi/airplane-landing'
import mdiAirplaneTakeoff from '@iconify-icons/mdi/airplane-takeoff'
import mdiBedOutline from '@iconify-icons/mdi/bed-outline'
import mdiBadgeAccountOutline from '@iconify-icons/mdi/badge-account-outline'
import mdiBroom from '@iconify-icons/mdi/broom'
import mdiCalendarCheckOutline from '@iconify-icons/mdi/calendar-check-outline'
import mdiCalendarPlus from '@iconify-icons/mdi/calendar-plus'
import mdiCalendarSearchOutline from '@iconify-icons/mdi/calendar-search-outline'
import mdiCalendarTextOutline from '@iconify-icons/mdi/calendar-text-outline'
import mdiCashMultiple from '@iconify-icons/mdi/cash-multiple'
import mdiChartBoxOutline from '@iconify-icons/mdi/chart-box-outline'
import mdiClipboardListOutline from '@iconify-icons/mdi/clipboard-list-outline'
import mdiCogOutline from '@iconify-icons/mdi/cog-outline'
import mdiCreditCardOutline from '@iconify-icons/mdi/credit-card-outline'
import mdiDoorClosed from '@iconify-icons/mdi/door-closed'
import mdiFloorPlan from '@iconify-icons/mdi/floor-plan'
import mdiFormatListBulletedSquare from '@iconify-icons/mdi/format-list-bulleted-square'
import mdiHomeAnalytics from '@iconify-icons/mdi/home-analytics'
import mdiHomeSwitchOutline from '@iconify-icons/mdi/home-switch-outline'
import mdiHumanGreetingVariant from '@iconify-icons/mdi/human-greeting-variant'
import mdiInvoiceOutline from '@iconify-icons/mdi/invoice-outline'
import mdiPaletteOutline from '@iconify-icons/mdi/palette-outline'
import mdiPackageVariantClosed from '@iconify-icons/mdi/package-variant-closed'
import mdiReceiptTextCheckOutline from '@iconify-icons/mdi/receipt-text-check-outline'
import mdiShieldCheckOutline from '@iconify-icons/mdi/shield-check-outline'
import mdiStorefrontOutline from '@iconify-icons/mdi/storefront-outline'
import mdiViewDashboardOutline from '@iconify-icons/mdi/view-dashboard-outline'
import type { IconifyIcon } from '@iconify/types'

type Metric = {
  label: string
  value: string
  tone?: 'primary' | 'success' | 'warning' | 'danger' | 'neutral'
}

type WorkspacePage = {
  key: string
  path: string
  parentLabel: string
  title: string
  summary: string
  eyebrow: string
  metrics: Metric[]
  primaryAction: string
  secondaryAction: string
  filters: string[]
  workflow: string[]
  tables: string[]
  endpoints: string[]
  states?: string[]
  spotlight: {
    title: string
    items: string[]
  }
  preview: {
    title: string
    columns: string[]
    rows: string[][]
  }
}

type NavigationChild = {
  label: string
  to: string
}

type NavigationItem = {
  label: string
  to: string
  icon: IconifyIcon
  description: string
  children?: NavigationChild[]
}

type NavigationSection = {
  label: string
  items: NavigationItem[]
}

const workspacePagesList: WorkspacePage[] = [
  {
    key: 'reservations-list',
    path: '/reservations/list',
    parentLabel: 'Reservasi',
    title: 'Reservasi List',
    summary: 'Pusat monitoring booking dengan filter status, source, tanggal, dan total tagihan agar pencarian cepat saat operasional padat.',
    eyebrow: 'Modul Reservasi',
    metrics: [
      { label: 'Pending', value: '4', tone: 'warning' },
      { label: 'Confirmed', value: '12', tone: 'success' },
      { label: 'Check-in Today', value: '9', tone: 'primary' },
    ],
    primaryAction: 'Buat reservasi baru',
    secondaryAction: 'Lihat timeline booking',
    filters: ['booking_code', 'reservation_status', 'source', 'check_in_date', 'check_out_date'],
    workflow: ['Cari booking', 'Cek status & tagihan', 'Confirm atau cancel', 'Buka detail reservasi'],
    tables: ['reservations', 'guests', 'reservation_rooms', 'invoices'],
    endpoints: ['GET /api/v1/reservations', 'PATCH /api/v1/reservations/{id}/confirm', 'PATCH /api/v1/reservations/{id}/cancel'],
    states: ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'],
    spotlight: {
      title: 'Kebutuhan UX utama',
      items: ['Search debounce', 'Filter cepat per status', 'Quick action confirm atau cancel', 'Status tagihan langsung terlihat'],
    },
    preview: {
      title: 'Kolom utama',
      columns: ['Booking', 'Guest', 'Stay Date', 'Status'],
      rows: [
        ['BK-240401', 'Alya Putri', '04 Apr - 06 Apr', 'Confirmed'],
        ['BK-240402', 'Arman Setiawan', '04 Apr - 05 Apr', 'Pending'],
      ],
    },
  },
  {
    key: 'reservations-new',
    path: '/reservations/new',
    parentLabel: 'Reservasi',
    title: 'Reservasi Baru',
    summary: 'Form step-based untuk memilih tanggal, cek availability, pilih tamu, serta menghitung biaya dan deposit secara real-time.',
    eyebrow: 'Modul Reservasi',
    metrics: [
      { label: 'Available Rooms', value: '12', tone: 'success' },
      { label: 'Average ADR', value: 'Rp 680rb', tone: 'primary' },
      { label: 'Deposit Policy', value: '30%', tone: 'neutral' },
    ],
    primaryAction: 'Mulai booking',
    secondaryAction: 'Buka form walk-in',
    filters: ['check_in_date', 'check_out_date', 'room_type_id', 'guests_count', 'property_id'],
    workflow: ['Pilih tanggal', 'Cek availability', 'Pilih kamar atau tipe kamar', 'Review biaya dan simpan booking'],
    tables: ['reservations', 'reservation_rooms', 'guests'],
    endpoints: ['POST /api/v1/availability/check', 'POST /api/v1/reservations'],
    states: ['pending', 'confirmed'],
    spotlight: {
      title: 'Ringkasan permanen',
      items: ['Biaya kamar', 'Deposit', 'Rule warning', 'Special request'],
    },
    preview: {
      title: 'Field penting',
      columns: ['Tanggal', 'Guest', 'Room Type', 'Estimate'],
      rows: [
        ['04 Apr - 06 Apr', 'Alya Putri', 'Deluxe', 'Rp 1.360.000'],
        ['05 Apr - 06 Apr', 'Walk-in Guest', 'Superior', 'Rp 620.000'],
      ],
    },
  },
  {
    key: 'reservations-availability',
    path: '/reservations/availability',
    parentLabel: 'Reservasi',
    title: 'Availability',
    summary: 'Layar cepat untuk mengecek ketersediaan kamar dan rate info sebelum staff lanjut membuat atau mengubah booking.',
    eyebrow: 'Modul Reservasi',
    metrics: [
      { label: 'Available', value: '12', tone: 'success' },
      { label: 'Reserved', value: '5', tone: 'warning' },
      { label: 'Occupied', value: '18', tone: 'primary' },
    ],
    primaryAction: 'Cek availability',
    secondaryAction: 'Lihat room board',
    filters: ['check_in_date', 'check_out_date', 'room_type_id', 'guests_count'],
    workflow: ['Input tanggal', 'Hitung kamar kosong', 'Tampilkan rate', 'Lanjutkan ke reservasi'],
    tables: ['rooms', 'room_types', 'reservation_rooms'],
    endpoints: ['GET /api/v1/availability', 'POST /api/v1/availability/check'],
    spotlight: {
      title: 'Output yang wajib cepat',
      items: ['Jumlah kamar tersedia', 'Rate info', 'Rule warning', 'Alternatif kamar'],
    },
    preview: {
      title: 'Result preview',
      columns: ['Room Type', 'Available', 'Rate', 'Warning'],
      rows: [
        ['Deluxe', '4', 'Rp 680.000', 'Weekend price'],
        ['Superior', '8', 'Rp 520.000', 'None'],
      ],
    },
  },
  {
    key: 'front-desk-arrivals',
    path: '/front-desk/arrivals',
    parentLabel: 'Front Desk',
    title: 'Arrivals',
    summary: 'Pantau tamu datang hari ini, verifikasi identitas, dan siapkan room assignment dengan klik minimal.',
    eyebrow: 'Front Desk',
    metrics: [
      { label: 'Arrivals', value: '9', tone: 'primary' },
      { label: 'Ready Rooms', value: '6', tone: 'success' },
      { label: 'Deposit Pending', value: '2', tone: 'warning' },
    ],
    primaryAction: 'Mulai check-in',
    secondaryAction: 'Cetak registrasi',
    filters: ['date_from', 'date_to', 'reservation_status', 'booking_code'],
    workflow: ['Cari tamu', 'Verifikasi reservasi', 'Assign room', 'Ubah status menjadi checked_in'],
    tables: ['reservations', 'stay_records', 'rooms', 'guests'],
    endpoints: ['GET /api/v1/front-desk/arrivals', 'POST /api/v1/front-desk/check-in'],
    states: ['confirmed', 'checked_in'],
    spotlight: {
      title: 'Data penting di arrival panel',
      items: ['Identitas tamu', 'Nomor kamar', 'Outstanding balance', 'Early check-in rule'],
    },
    preview: {
      title: 'Queue arrivals',
      columns: ['Guest', 'Booking', 'Room', 'Action'],
      rows: [
        ['Nadia Pramesti', 'BK-240401', 'Deluxe 302', 'Check-in'],
        ['Rizal Hadi', 'BK-240410', 'Superior 205', 'Verify ID'],
      ],
    },
  },
  {
    key: 'front-desk-departures',
    path: '/front-desk/departures',
    parentLabel: 'Front Desk',
    title: 'Departures',
    summary: 'Panel check-out untuk final invoice, verifikasi kondisi kamar, pembayaran akhir, dan trigger housekeeping.',
    eyebrow: 'Front Desk',
    metrics: [
      { label: 'Departures', value: '7', tone: 'primary' },
      { label: 'Pending Final Bill', value: '3', tone: 'warning' },
      { label: 'HK Auto Task', value: '5', tone: 'success' },
    ],
    primaryAction: 'Preview check-out',
    secondaryAction: 'Proses pembayaran akhir',
    filters: ['date_from', 'date_to', 'status', 'room_id'],
    workflow: ['Buka stay record', 'Hitung biaya final', 'Proses pembayaran', 'Set room dirty & create task'],
    tables: ['stay_records', 'invoices', 'payments', 'housekeeping_tasks'],
    endpoints: ['GET /api/v1/front-desk/check-out/preview', 'POST /api/v1/front-desk/check-out'],
    states: ['checked_in', 'checked_out', 'dirty'],
    spotlight: {
      title: 'Checklist check-out',
      items: ['Damage fee', 'Late check-out fee', 'Refund deposit', 'Housekeeping follow-up'],
    },
    preview: {
      title: 'Departure queue',
      columns: ['Room', 'Guest', 'Invoice', 'Status'],
      rows: [
        ['302', 'Nadia Pramesti', 'INV-0007', 'Ready to settle'],
        ['205', 'Rizal Hadi', 'INV-0008', 'Need room check'],
      ],
    },
  },
  {
    key: 'front-desk-in-house',
    path: '/front-desk/in-house',
    parentLabel: 'Front Desk',
    title: 'In-house Guests',
    summary: 'Monitoring tamu menginap aktif, status billing, extension stay, dan kebutuhan room change tanpa membuka banyak layar.',
    eyebrow: 'Front Desk',
    metrics: [
      { label: 'In House', value: '23', tone: 'primary' },
      { label: 'Balance Due', value: 'Rp 5,4 Jt', tone: 'warning' },
      { label: 'Extension Request', value: '2', tone: 'success' },
    ],
    primaryAction: 'Cari tamu aktif',
    secondaryAction: 'Change room',
    filters: ['search', 'room_id', 'date_from', 'date_to'],
    workflow: ['Buka tamu aktif', 'Lihat billing', 'Update stay', 'Proses room change bila perlu'],
    tables: ['stay_records', 'reservations', 'rooms', 'invoices'],
    endpoints: ['GET /api/v1/front-desk/in-house-guests', 'PATCH /api/v1/front-desk/change-room'],
    spotlight: {
      title: 'Kontrol operasional',
      items: ['Active stay summary', 'Room assignment', 'Outstanding payment', 'Special request'],
    },
    preview: {
      title: 'In-house board',
      columns: ['Guest', 'Room', 'Stay', 'Balance'],
      rows: [
        ['Mira Hasanah', '311', '2 nights', 'Rp 0'],
        ['Andi Putra', '208', '1 night', 'Rp 850.000'],
      ],
    },
  },
  {
    key: 'rooms-board',
    path: '/rooms/board',
    parentLabel: 'Kamar',
    title: 'Room Board',
    summary: 'Visual board untuk melihat status available, reserved, occupied, dirty, dan maintenance secara instan.',
    eyebrow: 'Modul Kamar',
    metrics: [
      { label: 'Available', value: '12', tone: 'success' },
      { label: 'Occupied', value: '18', tone: 'primary' },
      { label: 'Dirty', value: '3', tone: 'warning' },
    ],
    primaryAction: 'Buka board penuh',
    secondaryAction: 'Lihat status logs',
    filters: ['current_status', 'room_type_id', 'floor', 'is_active'],
    workflow: ['Filter status', 'Lihat room card', 'Buka detail kamar', 'Cek log perubahan status'],
    tables: ['rooms', 'room_status_logs', 'room_types'],
    endpoints: ['GET /api/v1/rooms', 'GET /api/v1/rooms/{id}/status-logs', 'PATCH /api/v1/rooms/{id}/status'],
    states: ['available', 'reserved', 'occupied', 'dirty', 'maintenance', 'out_of_service'],
    spotlight: {
      title: 'Visual state room',
      items: ['Badge konsisten', 'Warna tegas', 'Board responsif', 'Update status terkontrol'],
    },
    preview: {
      title: 'Board preview',
      columns: ['Room', 'Type', 'Floor', 'Status'],
      rows: [
        ['101', 'Superior', '1', 'Available'],
        ['302', 'Deluxe', '3', 'Occupied'],
      ],
    },
  },
  {
    key: 'rooms-list',
    path: '/rooms/list',
    parentLabel: 'Kamar',
    title: 'Master Kamar',
    summary: 'Kelola data kamar, floor, housekeeping status, dan catatan operasional dari satu list yang rapi.',
    eyebrow: 'Modul Kamar',
    metrics: [
      { label: 'Total Rooms', value: '35', tone: 'primary' },
      { label: 'Active', value: '33', tone: 'success' },
      { label: 'Maintenance', value: '2', tone: 'danger' },
    ],
    primaryAction: 'Tambah kamar',
    secondaryAction: 'Import master data',
    filters: ['room_type_id', 'floor', 'current_status', 'is_active'],
    workflow: ['Search room', 'Edit metadata', 'Patch status', 'Lihat histori status'],
    tables: ['rooms', 'room_types', 'room_status_logs'],
    endpoints: ['GET /api/v1/rooms', 'POST /api/v1/rooms', 'PUT /api/v1/rooms/{id}'],
    spotlight: {
      title: 'Data yang dijaga',
      items: ['Room number unik', 'Tipe kamar', 'Floor', 'Operational note'],
    },
    preview: {
      title: 'Kolom kamar',
      columns: ['Room', 'Type', 'Current Status', 'Housekeeping'],
      rows: [
        ['101', 'Superior', 'Available', 'Clean'],
        ['103', 'Superior', 'Dirty', 'Dirty'],
      ],
    },
  },
  {
    key: 'rooms-types',
    path: '/rooms/types',
    parentLabel: 'Kamar',
    title: 'Tipe Kamar',
    summary: 'Kelola kapasitas, base price, weekend price, dan deskripsi tipe kamar untuk pondasi availability dan billing.',
    eyebrow: 'Modul Kamar',
    metrics: [
      { label: 'Room Types', value: '4', tone: 'primary' },
      { label: 'Active Rate', value: '4', tone: 'success' },
      { label: 'Weekend Rules', value: '3', tone: 'neutral' },
    ],
    primaryAction: 'Tambah room type',
    secondaryAction: 'Patch status room type',
    filters: ['property', 'active_status', 'capacity'],
    workflow: ['Kelola master room type', 'Set pricing', 'Pastikan status aktif', 'Sinkron ke availability'],
    tables: ['room_types', 'rooms'],
    endpoints: ['GET /api/v1/room-types', 'POST /api/v1/room-types', 'PATCH /api/v1/room-types/{id}/status'],
    spotlight: {
      title: 'Atribut utama',
      items: ['Capacity', 'Base price', 'Weekend price', 'Extra bed price'],
    },
    preview: {
      title: 'Rate master',
      columns: ['Type', 'Capacity', 'Weekday', 'Weekend'],
      rows: [
        ['Superior', '2', 'Rp 520.000', 'Rp 580.000'],
        ['Deluxe', '2', 'Rp 680.000', 'Rp 760.000'],
      ],
    },
  },
  {
    key: 'housekeeping-board',
    path: '/housekeeping/board',
    parentLabel: 'Housekeeping',
    title: 'Task Board',
    summary: 'Task board berbasis status agar supervisor bisa assign, start, complete, dan verify pekerjaan secara cepat.',
    eyebrow: 'Housekeeping',
    metrics: [
      { label: 'Pending', value: '5', tone: 'warning' },
      { label: 'In Progress', value: '2', tone: 'primary' },
      { label: 'Verified', value: '14', tone: 'success' },
    ],
    primaryAction: 'Assign task',
    secondaryAction: 'Verifikasi kamar',
    filters: ['task_status', 'priority', 'assigned_employee_id', 'date_from', 'date_to'],
    workflow: ['Task dibuat', 'Assign petugas', 'Start dan complete', 'Verify lalu ubah room available'],
    tables: ['housekeeping_tasks', 'rooms', 'employees'],
    endpoints: ['GET /api/v1/housekeeping/tasks', 'PATCH /api/v1/housekeeping/tasks/{id}/assign', 'PATCH /api/v1/housekeeping/tasks/{id}/verify'],
    states: ['pending', 'assigned', 'in_progress', 'completed', 'verified'],
    spotlight: {
      title: 'Pattern UX penting',
      items: ['1 klik start', '1 klik complete', 'Board untuk tablet', 'Highlight priority tinggi'],
    },
    preview: {
      title: 'Status lanes',
      columns: ['Pending', 'Assigned', 'In Progress', 'Verified'],
      rows: [
        ['103 Checkout Cleaning', '205 Refresh', '311 Deep Clean', '101 Ready'],
        ['302 Linen Change', '102 Setup', '---', '104 Ready'],
      ],
    },
  },
  {
    key: 'housekeeping-dirty',
    path: '/housekeeping/dirty-rooms',
    parentLabel: 'Housekeeping',
    title: 'Dirty Rooms',
    summary: 'Daftar kamar dirty sebagai antrian kerja untuk housekeeping dan front desk setelah check-out.',
    eyebrow: 'Housekeeping',
    metrics: [
      { label: 'Dirty Rooms', value: '3', tone: 'warning' },
      { label: 'Urgent Turnover', value: '1', tone: 'danger' },
      { label: 'Waiting Verify', value: '2', tone: 'primary' },
    ],
    primaryAction: 'Buat task otomatis',
    secondaryAction: 'Cek room status log',
    filters: ['current_status', 'floor', 'priority'],
    workflow: ['Identifikasi dirty room', 'Buat task', 'Assign petugas', 'Verify saat selesai'],
    tables: ['rooms', 'housekeeping_tasks', 'room_status_logs'],
    endpoints: ['GET /api/v1/housekeeping/rooms-dirty', 'GET /api/v1/rooms/{id}/status-logs'],
    spotlight: {
      title: 'Output operasional',
      items: ['Turnover cepat', 'Urgent badge', 'Tautan ke reservation', 'Task ownership jelas'],
    },
    preview: {
      title: 'Dirty queue',
      columns: ['Room', 'Last Guest', 'Priority', 'Task'],
      rows: [
        ['103', 'BK-240402', 'High', 'Create cleaning'],
        ['302', 'BK-240407', 'Medium', 'Assigned'],
      ],
    },
  },
  {
    key: 'billing-invoices',
    path: '/billing/invoices',
    parentLabel: 'Billing',
    title: 'Invoices',
    summary: 'Workspace invoice untuk melihat charge room, minibar, laundry, diskon, dan outstanding balance secara akurat.',
    eyebrow: 'Billing',
    metrics: [
      { label: 'Outstanding', value: 'Rp 5,4 Jt', tone: 'warning' },
      { label: 'Paid Today', value: 'Rp 8,9 Jt', tone: 'success' },
      { label: 'Draft', value: '3', tone: 'neutral' },
    ],
    primaryAction: 'Buat invoice',
    secondaryAction: 'Recalculate charges',
    filters: ['invoice_status', 'date_from', 'date_to', 'reservation_id'],
    workflow: ['Buka invoice', 'Tambah item', 'Recalculate total', 'Pantau payment status'],
    tables: ['invoices', 'invoice_items', 'reservations'],
    endpoints: ['GET /api/v1/invoices', 'POST /api/v1/invoices', 'POST /api/v1/invoices/{id}/recalculate'],
    states: ['draft', 'unpaid', 'partial', 'paid', 'refunded', 'void'],
    spotlight: {
      title: 'Detail yang wajib terlihat',
      items: ['Line items', 'Invoice status', 'Remaining amount', 'Related reservation'],
    },
    preview: {
      title: 'Invoice table',
      columns: ['Invoice', 'Reservation', 'Grand Total', 'Status'],
      rows: [
        ['INV-0007', 'BK-240401', 'Rp 1.420.000', 'Partial'],
        ['INV-0008', 'BK-240410', 'Rp 760.000', 'Unpaid'],
      ],
    },
  },
  {
    key: 'billing-payments',
    path: '/billing/payments',
    parentLabel: 'Billing',
    title: 'Payments',
    summary: 'Catat split payment, refund, dan riwayat transaksi pembayaran dengan audit trail yang jelas.',
    eyebrow: 'Billing',
    metrics: [
      { label: 'Payments Today', value: '14', tone: 'success' },
      { label: 'Refund', value: '1', tone: 'danger' },
      { label: 'Cash Split', value: '4', tone: 'primary' },
    ],
    primaryAction: 'Catat pembayaran',
    secondaryAction: 'Proses refund',
    filters: ['payment_status', 'payment_method_code', 'date_from', 'date_to'],
    workflow: ['Pilih invoice', 'Input metode dan jumlah', 'Simpan payment', 'Hitung ulang status invoice'],
    tables: ['payments', 'payment_transactions', 'invoices'],
    endpoints: ['GET /api/v1/payments', 'POST /api/v1/payments', 'POST /api/v1/payments/{id}/refund'],
    spotlight: {
      title: 'Rule penting',
      items: ['Split payment', 'Audit refund', 'Sinkron status invoice', 'Riwayat per invoice'],
    },
    preview: {
      title: 'Payment log',
      columns: ['Payment', 'Method', 'Amount', 'Status'],
      rows: [
        ['PAY-0014', 'Cash', 'Rp 500.000', 'Paid'],
        ['PAY-0015', 'Transfer', 'Rp 350.000', 'Settled'],
      ],
    },
  },
  {
    key: 'guests-list',
    path: '/guests/list',
    parentLabel: 'Tamu',
    title: 'Guest Directory',
    summary: 'Profil tamu, preferensi, catatan khusus, dan repeat guest indicator dalam tampilan yang cepat dicari.',
    eyebrow: 'CRM Tamu',
    metrics: [
      { label: 'Total Guests', value: '1.284', tone: 'primary' },
      { label: 'Repeat Rate', value: '31%', tone: 'success' },
      { label: 'VIP Notes', value: '12', tone: 'warning' },
    ],
    primaryAction: 'Tambah tamu',
    secondaryAction: 'Lihat stay history',
    filters: ['search', 'phone', 'email', 'id_number'],
    workflow: ['Cari profil', 'Lihat reservasi', 'Buka riwayat inap', 'Update preferensi'],
    tables: ['guests', 'reservations', 'stay_records'],
    endpoints: ['GET /api/v1/guests', 'GET /api/v1/guests/{id}/reservations', 'GET /api/v1/guests/{id}/stay-history'],
    spotlight: {
      title: 'Informasi CRM inti',
      items: ['Identity info', 'Preferensi kamar', 'Special notes', 'Last stay'],
    },
    preview: {
      title: 'Guest list',
      columns: ['Guest', 'Phone', 'Total Stays', 'Indicator'],
      rows: [
        ['Alya Putri', '0812-9999-1111', '4', 'Repeat guest'],
        ['Rizal Hadi', '0812-8888-7777', '1', 'New guest'],
      ],
    },
  },
  {
    key: 'inventory-items',
    path: '/inventory/items',
    parentLabel: 'Inventori',
    title: 'Inventory Items',
    summary: 'Daftar barang dan kategori untuk amenities serta operasional, dengan alert stok minimum yang langsung terlihat.',
    eyebrow: 'Inventori',
    metrics: [
      { label: 'Items', value: '82', tone: 'primary' },
      { label: 'Low Stock', value: '6', tone: 'warning' },
      { label: 'Active SKU', value: '80', tone: 'success' },
    ],
    primaryAction: 'Tambah item',
    secondaryAction: 'Buka low stock',
    filters: ['category_id', 'item_name', 'movement_type', 'date_from', 'date_to'],
    workflow: ['Kelola master item', 'Catat stock-in', 'Catat usage', 'Pantau minimum stock'],
    tables: ['inventory_categories', 'inventory_items', 'inventory_movements'],
    endpoints: ['GET /api/v1/inventory/items', 'POST /api/v1/inventory/items', 'GET /api/v1/inventory/low-stock'],
    spotlight: {
      title: 'Kebutuhan harian',
      items: ['Stock summary', 'Low stock alerts', 'Movement history', 'Adjustment form'],
    },
    preview: {
      title: 'Inventory preview',
      columns: ['SKU', 'Item', 'Current', 'Min'],
      rows: [
        ['AM-0001', 'Toothbrush Kit', '12', '20'],
        ['LN-0002', 'Bath Towel', '56', '30'],
      ],
    },
  },
  {
    key: 'inventory-movements',
    path: '/inventory/movements',
    parentLabel: 'Inventori',
    title: 'Inventory Movements',
    summary: 'Riwayat stock in, stock out, adjustment, dan usage agar mutasi barang bisa ditelusuri dengan mudah.',
    eyebrow: 'Inventori',
    metrics: [
      { label: 'Usage Today', value: '47', tone: 'primary' },
      { label: 'Stock In', value: '9', tone: 'success' },
      { label: 'Adjustment', value: '2', tone: 'warning' },
    ],
    primaryAction: 'Catat stock in',
    secondaryAction: 'Catat usage',
    filters: ['category_id', 'movement_type', 'date_from', 'date_to', 'item_name'],
    workflow: ['Pilih item', 'Catat movement', 'Update saldo stok', 'Audit reference source'],
    tables: ['inventory_movements', 'inventory_items'],
    endpoints: ['GET /api/v1/inventory/movements', 'POST /api/v1/inventory/movements/stock-in', 'POST /api/v1/inventory/movements/usage'],
    spotlight: {
      title: 'Audit fields',
      items: ['stock_before', 'stock_after', 'reference_type', 'moved_by_user_id'],
    },
    preview: {
      title: 'Movement log',
      columns: ['Item', 'Type', 'Qty', 'After'],
      rows: [
        ['Toothbrush Kit', 'usage', '8', '12'],
        ['Bath Towel', 'stock_in', '20', '56'],
      ],
    },
  },
  {
    key: 'employees-list',
    path: '/employees/list',
    parentLabel: 'Karyawan',
    title: 'Employee Directory',
    summary: 'Kelola data pegawai, akun user, role, dan status aktif dari modul HR operasional.',
    eyebrow: 'Karyawan',
    metrics: [
      { label: 'Employees', value: '24', tone: 'primary' },
      { label: 'Active Users', value: '18', tone: 'success' },
      { label: 'Onboarding', value: '2', tone: 'warning' },
    ],
    primaryAction: 'Tambah pegawai',
    secondaryAction: 'Kelola user role',
    filters: ['department', 'employment_status', 'search'],
    workflow: ['Buat data pegawai', 'Hubungkan user', 'Tetapkan role', 'Patch active status'],
    tables: ['employees', 'users', 'roles'],
    endpoints: ['GET /api/v1/employees', 'POST /api/v1/employees', 'GET /api/v1/users'],
    spotlight: {
      title: 'Baseline akses',
      items: ['Employee profile', 'Role based access', 'User status', 'Attendance link'],
    },
    preview: {
      title: 'Employee list',
      columns: ['Employee', 'Department', 'Role', 'Status'],
      rows: [
        ['Sinta Maharani', 'Front Desk', 'Manager', 'Active'],
        ['Rifki Saputra', 'Housekeeping', 'Staff', 'Active'],
      ],
    },
  },
  {
    key: 'employees-shifts',
    path: '/employees/shifts',
    parentLabel: 'Karyawan',
    title: 'Shift Management',
    summary: 'Jadwal shift per pegawai untuk menjaga coverage operasional front desk, housekeeping, dan admin.',
    eyebrow: 'Karyawan',
    metrics: [
      { label: 'Shifts Today', value: '15', tone: 'primary' },
      { label: 'Coverage', value: '100%', tone: 'success' },
      { label: 'Swap Request', value: '1', tone: 'warning' },
    ],
    primaryAction: 'Buat shift',
    secondaryAction: 'Buka attendance summary',
    filters: ['shift_date', 'shift_type', 'employee_id'],
    workflow: ['Jadwalkan shift', 'Review coverage', 'Sinkron attendance', 'Masuk laporan'],
    tables: ['shifts', 'employees', 'attendances'],
    endpoints: ['GET /api/v1/shifts', 'POST /api/v1/shifts', 'GET /api/v1/attendances/summary'],
    spotlight: {
      title: 'Data shift minimum',
      items: ['shift_date', 'start_time', 'end_time', 'shift_type'],
    },
    preview: {
      title: 'Shift board',
      columns: ['Employee', 'Shift Date', 'Start', 'End'],
      rows: [
        ['Sinta Maharani', '04 Apr 2026', '07:00', '15:00'],
        ['Rifki Saputra', '04 Apr 2026', '08:00', '16:00'],
      ],
    },
  },
  {
    key: 'employees-attendance',
    path: '/employees/attendance',
    parentLabel: 'Karyawan',
    title: 'Attendance',
    summary: 'Pencatatan check-in dan check-out pegawai yang langsung terhubung ke summary kehadiran.',
    eyebrow: 'Karyawan',
    metrics: [
      { label: 'Checked In', value: '15', tone: 'success' },
      { label: 'Late', value: '1', tone: 'warning' },
      { label: 'Absent', value: '0', tone: 'neutral' },
    ],
    primaryAction: 'Check-in pegawai',
    secondaryAction: 'Check-out pegawai',
    filters: ['attendance_date', 'attendance_status', 'employee_id'],
    workflow: ['Pilih pegawai', 'Check-in', 'Check-out', 'Hitung summary kehadiran'],
    tables: ['attendances', 'shifts', 'employees'],
    endpoints: ['GET /api/v1/attendances', 'POST /api/v1/attendances/check-in', 'POST /api/v1/attendances/check-out'],
    spotlight: {
      title: 'State attendance',
      items: ['Present', 'Late', 'Absent', 'Shift linked'],
    },
    preview: {
      title: 'Attendance feed',
      columns: ['Employee', 'Shift', 'Check-in', 'Status'],
      rows: [
        ['Sinta Maharani', 'Morning', '06:58', 'Present'],
        ['Rifki Saputra', 'Morning', '08:14', 'Late'],
      ],
    },
  },
  {
    key: 'reports-daily',
    path: '/reports/daily',
    parentLabel: 'Laporan',
    title: 'Daily Operations Report',
    summary: 'Ringkasan harian untuk revenue, room status, arrivals, departures, housekeeping, dan in-house guests.',
    eyebrow: 'Laporan',
    metrics: [
      { label: 'Revenue', value: 'Rp 12,4 Jt', tone: 'success' },
      { label: 'Occupancy', value: '78%', tone: 'primary' },
      { label: 'Exports', value: '3', tone: 'neutral' },
    ],
    primaryAction: 'Preview laporan',
    secondaryAction: 'Export async',
    filters: ['date_from', 'date_to', 'property_id'],
    workflow: ['Pilih periode', 'Render preview', 'Review summary', 'Export via queue'],
    tables: ['reservations', 'invoices', 'housekeeping_tasks', 'inventory_movements'],
    endpoints: ['GET /api/v1/reports/daily-revenue', 'GET /api/v1/reports/room-status', 'POST /api/v1/reports/export'],
    spotlight: {
      title: 'Prinsip performa',
      items: ['Preview ringan dulu', 'Export async', 'Filter period jelas', 'Audit hasil export'],
    },
    preview: {
      title: 'Report modules',
      columns: ['Module', 'Metric', 'Status', 'Action'],
      rows: [
        ['Revenue', 'Rp 12,4 Jt', 'Ready', 'Preview'],
        ['Housekeeping', '5 Pending', 'Ready', 'Export'],
      ],
    },
  },
  {
    key: 'settings-general',
    path: '/settings/general',
    parentLabel: 'Settings',
    title: 'General Settings',
    summary: 'Pengaturan properti, business default, branding dasar, dan preferensi global aplikasi.',
    eyebrow: 'Settings',
    metrics: [
      { label: 'Property', value: 'Main Property', tone: 'primary' },
      { label: 'Timezone', value: 'Asia/Jakarta', tone: 'neutral' },
      { label: 'Currency', value: 'IDR', tone: 'success' },
    ],
    primaryAction: 'Simpan general settings',
    secondaryAction: 'Buka business rules',
    filters: ['property_id'],
    workflow: ['Load settings', 'Edit values', 'Validate request', 'Persist setting key'],
    tables: ['settings', 'properties'],
    endpoints: ['GET /api/v1/settings', 'PUT /api/v1/settings/general'],
    spotlight: {
      title: 'Settings penting',
      items: ['branding.app_name', 'business.check_in_time', 'business.check_out_time', 'billing.default_tax_percent'],
    },
    preview: {
      title: 'Setting groups',
      columns: ['Group', 'Key', 'Value', 'Scope'],
      rows: [
        ['branding', 'app_name', 'Booking WPA', 'property'],
        ['business', 'check_in_time', '14:00', 'property'],
      ],
    },
  },
]

type UiLanguage = 'id' | 'en'

const workspacePageTranslations: Record<string, {
  parentLabel?: string
  title: string
  summary: string
  eyebrow: string
  metrics: string[]
}> = {
  'reservations-list': {
    parentLabel: 'Reservations',
    title: 'Reservations List',
    summary: 'Booking monitoring center with status, source, date, and billing filters so searches stay fast during busy operations.',
    eyebrow: 'Reservations Module',
    metrics: ['Pending', 'Confirmed', 'Check-in Today'],
  },
  'reservations-new': {
    parentLabel: 'Reservations',
    title: 'New Reservation',
    summary: 'Step-based form to choose dates, check availability, select guests, and calculate costs and deposits in real time.',
    eyebrow: 'Reservations Module',
    metrics: ['Available Rooms', 'Average ADR', 'Deposit Policy'],
  },
  'reservations-availability': {
    parentLabel: 'Reservations',
    title: 'Availability',
    summary: 'Fast screen to check room availability and rate info before staff continue creating or updating a booking.',
    eyebrow: 'Reservations Module',
    metrics: ['Available', 'Reserved', 'Occupied'],
  },
  'front-desk-arrivals': {
    title: 'Arrivals',
    summary: 'Monitor today’s incoming guests, verify identity, and prepare room assignments with minimal clicks.',
    eyebrow: 'Front Desk',
    metrics: ['Arrivals', 'Ready Rooms', 'Deposit Pending'],
  },
  'front-desk-departures': {
    title: 'Departures',
    summary: 'Check-out panel for final invoices, room condition verification, final payments, and housekeeping triggers.',
    eyebrow: 'Front Desk',
    metrics: ['Departures', 'Pending Final Bill', 'HK Auto Task'],
  },
  'front-desk-in-house': {
    title: 'In-house Guests',
    summary: 'Monitor active stays, billing status, stay extensions, and room change requests without opening too many screens.',
    eyebrow: 'Front Desk',
    metrics: ['In House', 'Balance Due', 'Extension Request'],
  },
  'rooms-board': {
    parentLabel: 'Rooms',
    title: 'Room Board',
    summary: 'Visual board to instantly see available, reserved, occupied, dirty, and maintenance statuses.',
    eyebrow: 'Rooms Module',
    metrics: ['Available', 'Occupied', 'Dirty'],
  },
  'rooms-list': {
    parentLabel: 'Rooms',
    title: 'Room Master',
    summary: 'Manage room data, floors, housekeeping status, and operational notes from one clean list.',
    eyebrow: 'Rooms Module',
    metrics: ['Total Rooms', 'Active', 'Maintenance'],
  },
  'rooms-types': {
    parentLabel: 'Rooms',
    title: 'Room Types',
    summary: 'Manage capacity, base price, weekend price, and room type descriptions as the foundation for availability and billing.',
    eyebrow: 'Rooms Module',
    metrics: ['Room Types', 'Active Rate', 'Weekend Rules'],
  },
  'housekeeping-board': {
    title: 'Task Board',
    summary: 'Status-based task board so supervisors can assign, start, complete, and verify work quickly.',
    eyebrow: 'Housekeeping',
    metrics: ['Pending', 'In Progress', 'Verified'],
  },
  'housekeeping-dirty': {
    title: 'Dirty Rooms',
    summary: 'Dirty room queue for housekeeping and front desk after check-out.',
    eyebrow: 'Housekeeping',
    metrics: ['Dirty Rooms', 'Urgent Turnover', 'Waiting Verify'],
  },
  'billing-invoices': {
    title: 'Invoices',
    summary: 'Invoice workspace to review room charges, minibar, laundry, discounts, and outstanding balances accurately.',
    eyebrow: 'Billing',
    metrics: ['Outstanding', 'Paid Today', 'Draft'],
  },
  'billing-payments': {
    title: 'Payments',
    summary: 'Record split payments, refunds, and transaction history with a clear audit trail.',
    eyebrow: 'Billing',
    metrics: ['Payments Today', 'Refund', 'Cash Split'],
  },
  'guests-list': {
    parentLabel: 'Guests',
    title: 'Guest Directory',
    summary: 'Guest profiles, preferences, special notes, and repeat guest indicators in a searchable view.',
    eyebrow: 'Guest CRM',
    metrics: ['Total Guests', 'Repeat Rate', 'VIP Notes'],
  },
  'inventory-items': {
    parentLabel: 'Inventory',
    title: 'Inventory Items',
    summary: 'Item and category list for amenities and operations, with minimum stock alerts visible right away.',
    eyebrow: 'Inventory',
    metrics: ['Items', 'Low Stock', 'Active SKU'],
  },
  'inventory-movements': {
    parentLabel: 'Inventory',
    title: 'Inventory Movements',
    summary: 'Track stock-in, stock-out, adjustments, and usage so item movement stays easy to trace.',
    eyebrow: 'Inventory',
    metrics: ['Usage Today', 'Stock In', 'Adjustment'],
  },
  'employees-list': {
    parentLabel: 'Employees',
    title: 'Employee Directory',
    summary: 'Manage employee data, user accounts, roles, and active status from the operational HR module.',
    eyebrow: 'Employees',
    metrics: ['Employees', 'Active Users', 'Onboarding'],
  },
  'employees-shifts': {
    parentLabel: 'Employees',
    title: 'Shift Management',
    summary: 'Employee shift schedules to keep front desk, housekeeping, and admin operations fully covered.',
    eyebrow: 'Employees',
    metrics: ['Shifts Today', 'Coverage', 'Swap Request'],
  },
  'employees-attendance': {
    parentLabel: 'Employees',
    title: 'Attendance',
    summary: 'Employee check-in and check-out records linked directly to attendance summaries.',
    eyebrow: 'Employees',
    metrics: ['Checked In', 'Late', 'Absent'],
  },
  'reports-daily': {
    parentLabel: 'Reports',
    title: 'Daily Operations Report',
    summary: 'Daily summary for revenue, room status, arrivals, departures, housekeeping, and in-house guests.',
    eyebrow: 'Reports',
    metrics: ['Revenue', 'Occupancy', 'Exports'],
  },
  'settings-general': {
    title: 'General Settings',
    summary: 'Property settings, business defaults, core branding, and global application preferences.',
    eyebrow: 'Settings',
    metrics: ['Property', 'Timezone', 'Currency'],
  },
}

const workspaceTextTranslations: Record<string, string> = {
  Overview: 'Overview',
  Dashboard: 'Dashboard',
  'Summary operasional harian': 'Daily operational summary',
  'Reservation & Stay': 'Reservations & Stay',
  Reservasi: 'Reservations',
  'Booking, availability, dan timeline': 'Bookings, availability, and timeline',
  'Daftar Booking': 'Booking List',
  'Reservasi Baru': 'New Reservation',
  'Front Desk': 'Front Desk',
  'Check-in, departures, in-house': 'Check-in, departures, in-house',
  Arrivals: 'Arrivals',
  Departures: 'Departures',
  'In-house Guests': 'In-house Guests',
  Operations: 'Operations',
  Kamar: 'Rooms',
  'Board, master, dan tipe kamar': 'Board, master, and room types',
  'Room Board': 'Room Board',
  'Master Kamar': 'Room Master',
  'Tipe Kamar': 'Room Types',
  Housekeeping: 'Housekeeping',
  'Task board dan dirty rooms': 'Task board and dirty rooms',
  'Task Board': 'Task Board',
  'Dirty Rooms': 'Dirty Rooms',
  Billing: 'Billing',
  'Invoice dan pembayaran': 'Invoices and payments',
  Invoices: 'Invoices',
  Payments: 'Payments',
  'Master Data': 'Master Data',
  Tamu: 'Guests',
  'Profil, riwayat, preferensi': 'Profile, history, preferences',
  'Guest Directory': 'Guest Directory',
  Inventori: 'Inventory',
  'Item, movement, low stock': 'Items, movements, low stock',
  'Inventory Items': 'Inventory Items',
  Movements: 'Movements',
  Karyawan: 'Employees',
  'Employee, shift, attendance': 'Employees, shifts, attendance',
  'Employee Directory': 'Employee Directory',
  'Shift Management': 'Shift Management',
  Attendance: 'Attendance',
  'Insights & System': 'Insights & System',
  Laporan: 'Reports',
  'Daily report dan export': 'Daily reports and export',
  'Daily Operations': 'Daily Operations',
  Settings: 'Settings',
  'Branding, business rules, UI': 'Branding, business rules, UI',
  'General Settings': 'General Settings',
  'Portal CMS': 'Portal CMS',
  'Buat Booking': 'Create Booking',
  'Cek Availability': 'Check Availability',
  'Proses Arrival': 'Process Arrival',
  'Proses Departure': 'Process Departure',
  'Low Stock': 'Low Stock',
  'Ready Check-in': 'Ready Check-in',
  'Need ID Verify': 'Need ID Verification',
  'Ready Check-out': 'Ready Check-out',
  'Pending Final Bill': 'Pending Final Bill',
  Pending: 'Pending',
  'In Progress': 'In Progress',
  Verified: 'Verified',
  'Low stock': 'Low stock',
  'Reorder soon': 'Reorder soon',
  'Reservasi → Check-in': 'Reservations → Check-in',
  'Check-out → Housekeeping': 'Check-out → Housekeeping',
  'Operasional → Inventory': 'Operations → Inventory',
  'Admin → Settings': 'Admin → Settings',
  General: 'General',
  Branding: 'Branding',
  'UI Preferences': 'UI Preferences',
  'Business Rules': 'Business Rules',
  'Access & Audit': 'Access & Audit',
  'Data Tables': 'Data Tables',
  'Billing Defaults': 'Billing Defaults',
  'Payment Methods': 'Payment Methods',
  Safety: 'Safety',
  Property: 'Property',
}

const translateWorkspaceText = (value: string, language: UiLanguage) =>
  language === 'en' ? workspaceTextTranslations[value] ?? value : value

const localizeWorkspacePage = (page: WorkspacePage, language: UiLanguage): WorkspacePage => {
  if (language === 'id') {
    return page
  }

  const translation = workspacePageTranslations[page.key]

  return {
    ...page,
    parentLabel: translation?.parentLabel ?? translateWorkspaceText(page.parentLabel, language),
    title: translation?.title ?? page.title,
    summary: translation?.summary ?? page.summary,
    eyebrow: translation?.eyebrow ?? page.eyebrow,
    metrics: page.metrics.map((metric, index) => ({
      ...metric,
      label: translation?.metrics[index] ?? metric.label,
    })),
  }
}

export const getWorkspacePages = (language: UiLanguage = 'id') => workspacePagesList.reduce<Record<string, WorkspacePage>>((accumulator, page) => {
  accumulator[page.key] = localizeWorkspacePage(page, language)

  return accumulator
}, {})

export const getWorkspacePageByPath = (language: UiLanguage = 'id') => workspacePagesList.reduce<Record<string, WorkspacePage>>((accumulator, page) => {
  accumulator[page.path] = localizeWorkspacePage(page, language)

  return accumulator
}, {})

export const workspaceRoutes = workspacePagesList.map((page) => ({
  path: page.path,
  name: page.key,
  meta: {
    title: page.title,
    pageKey: page.key,
  },
}))

const navigationSectionsBase: NavigationSection[] = [
  {
    label: 'Overview',
    items: [
      {
        label: 'Dashboard',
        to: '/',
        icon: mdiViewDashboardOutline,
        description: 'Summary operasional harian',
      },
    ],
  },
  {
    label: 'Reservation & Stay',
    items: [
      {
        label: 'Reservasi',
        to: '/reservations/list',
        icon: mdiCalendarTextOutline,
        description: 'Booking, availability, dan timeline',
        children: [
          { label: 'Daftar Booking', to: '/reservations/list' },
          { label: 'Reservasi Baru', to: '/reservations/new' },
          { label: 'Availability', to: '/reservations/availability' },
          { label: 'Booking Inquiries', to: '/reservations/inquiries' },
        ],
      },
      {
        label: 'Front Desk',
        to: '/front-desk/arrivals',
        icon: mdiHumanGreetingVariant,
        description: 'Check-in, departures, in-house',
        children: [
          { label: 'Arrivals', to: '/front-desk/arrivals' },
          { label: 'Departures', to: '/front-desk/departures' },
          { label: 'In-house Guests', to: '/front-desk/in-house' },
        ],
      },
    ],
  },
  {
    label: 'Operations',
    items: [
      {
        label: 'Kamar',
        to: '/rooms/board',
        icon: mdiDoorClosed,
        description: 'Board, master, dan tipe kamar',
        children: [
          { label: 'Room Board', to: '/rooms/board' },
          { label: 'Master Kamar', to: '/rooms/list' },
          { label: 'Tipe Kamar', to: '/rooms/types' },
        ],
      },
      {
        label: 'Housekeeping',
        to: '/housekeeping/board',
        icon: mdiBroom,
        description: 'Task board dan dirty rooms',
        children: [
          { label: 'Task Board', to: '/housekeeping/board' },
          { label: 'Dirty Rooms', to: '/housekeeping/dirty-rooms' },
        ],
      },
      {
        label: 'Billing',
        to: '/billing/invoices',
        icon: mdiCashMultiple,
        description: 'Invoice dan pembayaran',
        children: [
          { label: 'Invoices', to: '/billing/invoices' },
          { label: 'Payments', to: '/billing/payments' },
        ],
      },
    ],
  },
  {
    label: 'Master Data',
    items: [
      {
        label: 'Tamu',
        to: '/guests/list',
        icon: mdiAccountGroupOutline,
        description: 'Profil, riwayat, preferensi',
        children: [{ label: 'Guest Directory', to: '/guests/list' }],
      },
      {
        label: 'Inventori',
        to: '/inventory/items',
        icon: mdiPackageVariantClosed,
        description: 'Item, movement, low stock',
        children: [
          { label: 'Inventory Items', to: '/inventory/items' },
          { label: 'Movements', to: '/inventory/movements' },
        ],
      },
      {
        label: 'Karyawan',
        to: '/employees/list',
        icon: mdiBadgeAccountOutline,
        description: 'Employee, shift, attendance',
        children: [
          { label: 'Employee Directory', to: '/employees/list' },
          { label: 'Shift Management', to: '/employees/shifts' },
          { label: 'Attendance', to: '/employees/attendance' },
        ],
      },
    ],
  },
  {
    label: 'Insights & System',
    items: [
      {
        label: 'Laporan',
        to: '/reports/daily',
        icon: mdiChartBoxOutline,
        description: 'Daily report dan export',
        children: [{ label: 'Daily Operations', to: '/reports/daily' }],
      },
      {
        label: 'Settings',
        to: '/settings',
        icon: mdiCogOutline,
        description: 'Branding, business rules, UI',
        children: [
          { label: 'General Settings', to: '/settings/general' },
          { label: 'Portal CMS', to: '/settings/portal-cms' },
        ],
      },
    ],
  },
]

export const getNavigationSections = (language: UiLanguage = 'id'): NavigationSection[] =>
  navigationSectionsBase.map((section) => ({
    ...section,
    label: translateWorkspaceText(section.label, language),
    items: section.items.map((item) => ({
      ...item,
      label: translateWorkspaceText(item.label, language),
      description: translateWorkspaceText(item.description, language),
      children: item.children?.map((child) => ({
        ...child,
        label: translateWorkspaceText(child.label, language),
      })),
    })),
  }))

const dashboardQuickLinksBase = [
  { label: 'Buat Booking', to: '/reservations/new', icon: mdiCalendarPlus },
  { label: 'Cek Availability', to: '/reservations/availability', icon: mdiCalendarSearchOutline },
  { label: 'Proses Arrival', to: '/front-desk/arrivals', icon: mdiAirplaneLanding },
  { label: 'Proses Departure', to: '/front-desk/departures', icon: mdiAirplaneTakeoff },
  { label: 'Room Board', to: '/rooms/board', icon: mdiFloorPlan },
  { label: 'Low Stock', to: '/inventory/items', icon: mdiStorefrontOutline },
]

export const getDashboardQuickLinks = (language: UiLanguage = 'id') =>
  dashboardQuickLinksBase.map((item) => ({
    ...item,
    label: translateWorkspaceText(item.label, language),
  }))

const dashboardPanelsBase = {
  roomStatus: [
    { label: 'Available', value: '12', tone: 'success' as const },
    { label: 'Reserved', value: '5', tone: 'warning' as const },
    { label: 'Occupied', value: '18', tone: 'primary' as const },
    { label: 'Dirty', value: '3', tone: 'danger' as const },
  ],
  arrivals: [
    { guest: 'Nadia Pramesti', code: 'BK-240401', room: 'Deluxe 302', status: 'Ready Check-in' },
    { guest: 'Rizal Hadi', code: 'BK-240410', room: 'Superior 205', status: 'Need ID Verify' },
  ],
  departures: [
    { guest: 'Mira Hasanah', room: '311', balance: 'Rp 0', status: 'Ready Check-out' },
    { guest: 'Andi Putra', room: '208', balance: 'Rp 850.000', status: 'Pending Final Bill' },
  ],
  housekeeping: [
    { task: '103 Checkout Cleaning', assignee: 'Rifki', status: 'Pending' },
    { task: '205 Refresh', assignee: 'Sari', status: 'In Progress' },
    { task: '101 Verify', assignee: 'Supervisor', status: 'Verified' },
  ],
  lowStock: [
    { item: 'Toothbrush Kit', stock: '12 / 20', level: 'Low stock' },
    { item: 'Laundry Bag', stock: '8 / 15', level: 'Reorder soon' },
  ],
  businessFlow: [
    {
      title: 'Reservasi → Check-in',
      summary: 'reservations, reservation_rooms, stay_records, invoices',
      icon: mdiCalendarCheckOutline,
    },
    {
      title: 'Check-out → Housekeeping',
      summary: 'stay_records, payments, housekeeping_tasks, room_status_logs',
      icon: mdiClipboardListOutline,
    },
    {
      title: 'Operasional → Inventory',
      summary: 'inventory_items, inventory_movements, low stock alerts',
      icon: mdiPackageVariantClosed,
    },
    {
      title: 'Admin → Settings',
      summary: 'settings, roles, users, activity_logs',
      icon: mdiShieldCheckOutline,
    },
  ],
}

export const getDashboardPanels = (language: UiLanguage = 'id') => ({
  roomStatus: dashboardPanelsBase.roomStatus.map((item) => ({
    ...item,
    label: translateWorkspaceText(item.label, language),
  })),
  arrivals: dashboardPanelsBase.arrivals.map((item) => ({
    ...item,
    status: translateWorkspaceText(item.status, language),
  })),
  departures: dashboardPanelsBase.departures.map((item) => ({
    ...item,
    status: translateWorkspaceText(item.status, language),
  })),
  housekeeping: dashboardPanelsBase.housekeeping.map((item) => ({
    ...item,
    status: translateWorkspaceText(item.status, language),
  })),
  lowStock: dashboardPanelsBase.lowStock.map((item) => ({
    ...item,
    level: translateWorkspaceText(item.level, language),
  })),
  businessFlow: dashboardPanelsBase.businessFlow.map((item) => ({
    ...item,
    title: translateWorkspaceText(item.title, language),
    summary: translateWorkspaceText(item.summary, language),
  })),
})

const settingsTabsBase = [
  { label: 'General', icon: mdiHomeAnalytics },
  { label: 'Branding', icon: mdiPaletteOutline },
  { label: 'UI Preferences', icon: mdiHomeSwitchOutline },
  { label: 'Business Rules', icon: mdiReceiptTextCheckOutline },
  { label: 'Access & Audit', icon: mdiAccountTieOutline },
  { label: 'Data Tables', icon: mdiFormatListBulletedSquare },
  { label: 'Billing Defaults', icon: mdiInvoiceOutline },
  { label: 'Payment Methods', icon: mdiCreditCardOutline },
  { label: 'Safety', icon: mdiShieldCheckOutline },
  { label: 'Property', icon: mdiBedOutline },
] as const

export const getSettingsTabs = (language: UiLanguage = 'id') =>
  settingsTabsBase.map((item) => ({
    ...item,
    label: translateWorkspaceText(item.label, language),
  }))
