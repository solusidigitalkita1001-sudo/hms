# API Blueprint dan UI Page Map Sistem Manajemen Hotel / Homestay

## 1. Tujuan Dokumen

Dokumen ini menjadi jembatan antara requirement final, ERD final, dan implementasi Laravel + frontend modern. Fokusnya adalah menentukan struktur endpoint utama, pola request-response, daftar halaman, layout aplikasi, dan pengalaman pengguna per modul.

## 2. Prinsip API

- API dipakai untuk frontend operasional internal
- Semua endpoint utama berada di bawah prefix `/api`
- Endpoint harus cepat, konsisten, dan mudah dipahami tim
- Resource besar wajib mendukung pagination, filtering, sorting, dan search
- Endpoint transaksi penting harus aman, tervalidasi, dan dapat diaudit
- Response error harus seragam
- Tidak ada dependency frontend pada CDN

## 3. Konvensi Umum API

### 3.1 Prefix dan Versioning

- Prefix dasar: `/api`
- Versi awal yang direkomendasikan: `/api/v1`

Contoh:

- `/api/v1/auth/login`
- `/api/v1/reservations`
- `/api/v1/dashboard/summary`

### 3.2 Format Response Sukses

Format umum:

```json
{
  "success": true,
  "message": "Data berhasil diambil",
  "data": {},
  "meta": {}
}
```

### 3.3 Format Response Error

Format umum:

```json
{
  "success": false,
  "message": "Validasi gagal",
  "errors": {
    "field_name": [
      "Pesan error"
    ]
  }
}
```

### 3.4 Query Parameter Standar

- `page`
- `per_page`
- `search`
- `sort_by`
- `sort_direction`
- `status`
- `date_from`
- `date_to`
- `property_id`

### 3.5 Pola Resource Action

- `GET /resources`
- `POST /resources`
- `GET /resources/{id}`
- `PUT /resources/{id}`
- `PATCH /resources/{id}`
- `DELETE /resources/{id}`

Catatan:

- Delete fisik sebaiknya dibatasi
- Gunakan `is_active`, `cancelled`, `void`, atau soft delete bila lebih aman

## 4. Blueprint Modul Auth dan Session

### 4.1 Endpoint

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/refresh` jika model token dipakai
- `PUT /api/v1/auth/change-password`

### 4.2 Kebutuhan Data

- user profile
- role
- permissions
- property default
- UI preferences milik user bila ada

### 4.3 Kebutuhan UI

- halaman login yang ringan
- remember session opsional
- feedback error yang jelas

## 5. Blueprint Dashboard

### 5.1 Endpoint

- `GET /api/v1/dashboard/summary`
- `GET /api/v1/dashboard/room-status`
- `GET /api/v1/dashboard/arrivals`
- `GET /api/v1/dashboard/departures`
- `GET /api/v1/dashboard/in-house-guests`
- `GET /api/v1/dashboard/housekeeping-overview`
- `GET /api/v1/dashboard/revenue-overview`

### 5.2 Data yang Ditampilkan

- occupancy hari ini
- kamar available, reserved, occupied, dirty, maintenance
- total arrival dan departure
- revenue hari ini
- outstanding payment
- task housekeeping pending
- stok minimum alert

### 5.3 Aturan Performa

- summary cards dimuat lebih dulu
- chart atau widget berat dimuat belakangan
- dashboard tidak mengembalikan data detail berlebihan

## 6. Blueprint Settings dan Branding

### 6.1 Endpoint

- `GET /api/v1/settings`
- `PUT /api/v1/settings/general`
- `PUT /api/v1/settings/ui`
- `PUT /api/v1/settings/branding`
- `PUT /api/v1/settings/business-rules`

### 6.2 Setting Penting

- `ui.primary_color`
- `ui.layout_mode`
- `ui.sidebar_collapsed`
- `ui.table_density`
- `branding.logo_path`
- `branding.app_name`
- `business.check_in_time`
- `business.check_out_time`
- `billing.default_tax_percent`

### 6.3 Implikasi UI

- primary color berubah tanpa reload penuh bila memungkinkan
- layout bisa switch sidebar atau navbar
- setting disimpan per property atau per user sesuai kebutuhan

## 7. Blueprint Master Data

### 7.1 Properties

Endpoint:

- `GET /api/v1/properties`
- `POST /api/v1/properties`
- `GET /api/v1/properties/{id}`
- `PUT /api/v1/properties/{id}`

### 7.2 Room Types

Endpoint:

- `GET /api/v1/room-types`
- `POST /api/v1/room-types`
- `GET /api/v1/room-types/{id}`
- `PUT /api/v1/room-types/{id}`
- `PATCH /api/v1/room-types/{id}/status`

Filter penting:

- property
- active status
- capacity

### 7.3 Rooms

Endpoint:

- `GET /api/v1/rooms`
- `POST /api/v1/rooms`
- `GET /api/v1/rooms/{id}`
- `PUT /api/v1/rooms/{id}`
- `PATCH /api/v1/rooms/{id}/status`
- `GET /api/v1/rooms/{id}/status-logs`

Filter penting:

- current_status
- room_type_id
- floor
- is_active

### 7.4 Guests

Endpoint:

- `GET /api/v1/guests`
- `POST /api/v1/guests`
- `GET /api/v1/guests/{id}`
- `PUT /api/v1/guests/{id}`
- `GET /api/v1/guests/{id}/reservations`
- `GET /api/v1/guests/{id}/stay-history`

Search penting:

- nama
- nomor identitas
- phone
- email

### 7.5 Employees

Endpoint:

- `GET /api/v1/employees`
- `POST /api/v1/employees`
- `GET /api/v1/employees/{id}`
- `PUT /api/v1/employees/{id}`
- `PATCH /api/v1/employees/{id}/status`
- `GET /api/v1/employees/{id}/shifts`
- `GET /api/v1/employees/{id}/attendances`

### 7.6 Roles dan Users

Endpoint:

- `GET /api/v1/roles`
- `GET /api/v1/users`
- `POST /api/v1/users`
- `GET /api/v1/users/{id}`
- `PUT /api/v1/users/{id}`
- `PATCH /api/v1/users/{id}/status`
- `PUT /api/v1/users/{id}/permissions` jika granular permission dipakai

## 8. Blueprint Reservasi

### 8.1 Endpoint Inti

- `GET /api/v1/reservations`
- `POST /api/v1/reservations`
- `GET /api/v1/reservations/{id}`
- `PUT /api/v1/reservations/{id}`
- `PATCH /api/v1/reservations/{id}/confirm`
- `PATCH /api/v1/reservations/{id}/cancel`
- `PATCH /api/v1/reservations/{id}/mark-no-show`
- `GET /api/v1/reservations/{id}/invoice`
- `GET /api/v1/reservations/{id}/timeline`

### 8.2 Endpoint Availability

- `GET /api/v1/availability`
- `POST /api/v1/availability/check`

Input utama:

- check_in_date
- check_out_date
- room_type_id
- guests_count
- property_id

Output utama:

- available rooms
- rate info
- rule warning

### 8.3 Filter Penting

- reservation_status
- source
- check_in_date
- check_out_date
- guest_id
- booking_code

### 8.4 Aksi Khusus

- ubah tanggal reservasi
- ubah kamar
- tambah deposit
- kirim ulang konfirmasi

## 9. Blueprint Check-in dan Check-out

### 9.1 Check-in

Endpoint:

- `POST /api/v1/front-desk/check-in`
- `GET /api/v1/front-desk/check-in/lookup`

Input utama:

- reservation_id atau booking_code
- room_id
- actual_guest_count
- identity_verified
- checked_in_at

Output:

- stay record
- updated room status
- updated reservation status

### 9.2 Check-out

Endpoint:

- `POST /api/v1/front-desk/check-out`
- `GET /api/v1/front-desk/check-out/preview`

Input utama:

- stay_record_id atau reservation_id
- damage_fee
- late_check_out_fee
- final_notes

Output:

- final invoice summary
- payment status
- housekeeping task created

### 9.3 Endpoint Pendukung Front Desk

- `GET /api/v1/front-desk/arrivals`
- `GET /api/v1/front-desk/departures`
- `GET /api/v1/front-desk/in-house-guests`
- `POST /api/v1/front-desk/walk-in`
- `PATCH /api/v1/front-desk/change-room`

## 10. Blueprint Invoice dan Payment

### 10.1 Invoice

Endpoint:

- `GET /api/v1/invoices`
- `POST /api/v1/invoices`
- `GET /api/v1/invoices/{id}`
- `PUT /api/v1/invoices/{id}`
- `POST /api/v1/invoices/{id}/items`
- `PUT /api/v1/invoices/{id}/items/{itemId}`
- `DELETE /api/v1/invoices/{id}/items/{itemId}`
- `POST /api/v1/invoices/{id}/recalculate`
- `POST /api/v1/invoices/{id}/void`

### 10.2 Payment

Endpoint:

- `GET /api/v1/payments`
- `POST /api/v1/payments`
- `GET /api/v1/payments/{id}`
- `POST /api/v1/payments/{id}/refund`
- `GET /api/v1/invoices/{id}/payments`

Input payment utama:

- invoice_id
- payment_type
- payment_method_code
- amount
- paid_at
- notes

### 10.3 Rule Penting

- invoice status dihitung otomatis dari total pembayaran
- refund harus meninggalkan jejak audit
- split payment harus didukung

## 11. Blueprint Housekeeping

### 11.1 Endpoint

- `GET /api/v1/housekeeping/tasks`
- `POST /api/v1/housekeeping/tasks`
- `GET /api/v1/housekeeping/tasks/{id}`
- `PATCH /api/v1/housekeeping/tasks/{id}/assign`
- `PATCH /api/v1/housekeeping/tasks/{id}/start`
- `PATCH /api/v1/housekeeping/tasks/{id}/complete`
- `PATCH /api/v1/housekeeping/tasks/{id}/verify`

### 11.2 Endpoint Pendukung

- `GET /api/v1/housekeeping/rooms-dirty`
- `GET /api/v1/housekeeping/summary`

### 11.3 Aksi Tambahan

- lapor kerusakan kamar
- catat pemakaian amenities
- reassign task

## 12. Blueprint Maintenance

### 12.1 Endpoint

- `GET /api/v1/maintenance-requests`
- `POST /api/v1/maintenance-requests`
- `GET /api/v1/maintenance-requests/{id}`
- `PUT /api/v1/maintenance-requests/{id}`
- `PATCH /api/v1/maintenance-requests/{id}/assign`
- `PATCH /api/v1/maintenance-requests/{id}/resolve`

### 12.2 Filter Penting

- maintenance_status
- priority
- room_id
- assigned_employee_id

## 13. Blueprint Inventori

### 13.1 Category dan Item

Endpoint:

- `GET /api/v1/inventory/categories`
- `POST /api/v1/inventory/categories`
- `GET /api/v1/inventory/items`
- `POST /api/v1/inventory/items`
- `GET /api/v1/inventory/items/{id}`
- `PUT /api/v1/inventory/items/{id}`
- `PATCH /api/v1/inventory/items/{id}/status`

### 13.2 Stock Movement

Endpoint:

- `GET /api/v1/inventory/movements`
- `POST /api/v1/inventory/movements/stock-in`
- `POST /api/v1/inventory/movements/stock-out`
- `POST /api/v1/inventory/movements/adjustment`
- `POST /api/v1/inventory/movements/usage`

### 13.3 Endpoint Alert

- `GET /api/v1/inventory/low-stock`

### 13.4 Filter Penting

- category_id
- movement_type
- date range
- item_name

## 14. Blueprint Karyawan

### 14.1 Shift

Endpoint:

- `GET /api/v1/shifts`
- `POST /api/v1/shifts`
- `GET /api/v1/shifts/{id}`
- `PUT /api/v1/shifts/{id}`

### 14.2 Attendance

Endpoint:

- `GET /api/v1/attendances`
- `POST /api/v1/attendances/check-in`
- `POST /api/v1/attendances/check-out`
- `GET /api/v1/attendances/summary`

### 14.3 Data Karyawan

Endpoint:

- `GET /api/v1/employees`
- `GET /api/v1/employees/{id}/attendance-summary`

## 15. Blueprint Laporan

### 15.1 Laporan Operasional

Endpoint:

- `GET /api/v1/reports/daily-revenue`
- `GET /api/v1/reports/room-status`
- `GET /api/v1/reports/arrivals-departures`
- `GET /api/v1/reports/in-house-guests`
- `GET /api/v1/reports/housekeeping`
- `GET /api/v1/reports/inventory-usage`
- `GET /api/v1/reports/employee-attendance`

### 15.2 Export

Endpoint:

- `POST /api/v1/reports/export`
- `GET /api/v1/reports/export/{id}/status`
- `GET /api/v1/reports/export/{id}/download`

Catatan:

- export berat sebaiknya async via queue

## 16. Blueprint Activity dan Audit

### 16.1 Endpoint

- `GET /api/v1/activity-logs`
- `GET /api/v1/activity-logs/{id}`

### 16.2 Aktivitas yang Perlu Tercatat

- login dan logout
- create atau edit reservasi
- check-in dan check-out
- payment dan refund
- perubahan status kamar
- perubahan settings penting

## 17. UI Page Map Utama

### 17.1 Struktur Navigasi

Menu inti yang direkomendasikan:

- Dashboard
- Reservasi
- Front Desk
- Kamar
- Housekeeping
- Billing
- Tamu
- Inventori
- Karyawan
- Laporan
- Settings

Menu harus dapat ditampilkan dalam dua mode:

- sidebar layout
- top navbar layout

### 17.2 Rule Navigasi

- menu tampil berdasarkan role dan permission
- active state jelas
- quick access ke modul operasional utama
- pencarian global tersedia di header

## 18. Blueprint Halaman per Modul

### 18.1 Login

Elemen utama:

- logo
- nama aplikasi
- form login
- toggle theme bila dibutuhkan

Karakter UI:

- minimal
- cepat dimuat
- fokus ke form

### 18.2 Dashboard

Bagian utama:

- summary cards
- room status widget
- arrivals dan departures hari ini
- housekeeping summary
- low stock alert
- revenue summary

Komponen:

- stat cards
- mini chart
- quick action
- status badge

### 18.3 Reservasi List

Bagian utama:

- filter bar
- search booking
- table reservasi
- quick action untuk confirm, cancel, lihat detail

Kolom penting:

- booking code
- guest
- room atau room type
- tanggal
- status
- source
- total amount

### 18.4 Reservasi Form

Bagian utama:

- pilih tanggal
- cek availability
- pilih kamar
- pilih atau buat tamu
- rincian biaya
- deposit
- special request

Pola UX:

- wizard singkat atau form step-based
- ringkasan biaya selalu terlihat

### 18.5 Detail Reservasi

Bagian utama:

- header status
- data tamu
- kamar
- timeline aktivitas
- invoice terkait
- payment history
- action panel

### 18.6 Front Desk

Bagian utama:

- tab arrivals
- tab departures
- in-house guests
- check-in panel
- check-out panel

Pola UX:

- pencarian cepat
- minim klik
- highlight data penting

### 18.7 Kamar

Bagian utama:

- room board
- list kamar
- filter per status
- detail kamar
- status log

Pola tampilan:

- grid visual untuk status kamar
- warna badge konsisten

### 18.8 Housekeeping

Bagian utama:

- task list
- task board berdasarkan status
- filter petugas
- quick update status

Pola UX:

- 1 klik untuk start dan complete
- mobile-tablet friendly

### 18.9 Billing

Bagian utama:

- invoice list
- detail invoice
- item charges
- payment history
- outstanding tracker

### 18.10 Tamu

Bagian utama:

- list tamu
- detail profil
- riwayat reservasi
- riwayat inap
- preferensi dan catatan

### 18.11 Inventori

Bagian utama:

- stock summary
- low stock alerts
- items table
- movement history
- stock adjustment form

### 18.12 Karyawan

Bagian utama:

- data pegawai
- jadwal shift
- kehadiran
- ringkasan absensi

### 18.13 Laporan

Bagian utama:

- daftar laporan
- filter periode
- preview data
- export action

### 18.14 Settings

Bagian utama:

- general settings
- business rules
- branding
- UI preferences
- user preferences

Sub halaman penting:

- warna primary
- pilihan sidebar atau navbar
- logo
- nama aplikasi
- jam check-in dan check-out

## 19. Desain Layout Aplikasi

### 19.1 Sidebar Layout

Cocok untuk:

- admin operasional
- penggunaan desktop
- aplikasi dengan banyak modul

Karakter:

- sidebar collapsible
- header dengan search global
- panel konten lebar

### 19.2 Navbar Layout

Cocok untuk:

- tampilan lebih clean
- property kecil dengan modul lebih sedikit
- user yang ingin navigasi horizontal

Karakter:

- top navigation
- submenu atau mega menu ringan
- konten fokus di tengah

### 19.3 Switch Layout

Aturan:

- layout preference disimpan di settings atau user preference
- komponen halaman harus netral terhadap layout
- breadcrumb tetap konsisten

## 20. Design System yang Disarankan

### 20.1 Token Dasar

- primary color
- success color
- warning color
- danger color
- neutral scale
- border radius
- spacing scale
- shadow scale

### 20.2 Komponen Reusable

- app shell
- page header
- data table
- filter bar
- stat card
- badge status
- modal
- drawer
- tabs
- form field
- date picker
- command palette atau global search

### 20.3 State Visual Penting

- reservation status
- room status
- housekeeping task status
- invoice status
- attendance status
- low stock state

## 21. Aturan UX Modern dan Cepat

- animasi singkat dan ringan
- loading skeleton untuk list dan card
- debounce pada pencarian
- lazy load untuk komponen berat
- data table server-side
- quick actions di halaman operasional utama
- empty state yang jelas
- toast feedback untuk aksi singkat

## 22. Arah Build Frontend

### 22.1 Struktur Tingkat Tinggi

- `layouts`
- `pages`
- `components`
- `features`
- `services/api`
- `stores` atau state
- `theme`

### 22.2 Grup Halaman

- auth pages
- dashboard pages
- reservations pages
- front desk pages
- rooms pages
- housekeeping pages
- billing pages
- guests pages
- inventory pages
- employees pages
- reports pages
- settings pages

## 23. Prioritas Implementasi API dan UI

### Tahap 1

- auth
- dashboard summary
- settings UI dasar
- room types
- rooms
- guests
- reservations
- front desk check-in dan check-out
- invoices
- payments

### Tahap 2

- housekeeping
- inventory
- employees
- shifts
- attendances
- reports dasar

### Tahap 3

- maintenance
- export async
- activity log viewer
- guest portal
- integrasi tambahan

## 24. Keputusan yang Sebaiknya Dikunci Sebelum Scaffold

- frontend pakai Vue atau React
- auth model pakai session atau token
- permission pakai role sederhana atau permission granular
- multi-property aktif dari awal atau ditunda
- invoice dibuat saat reservasi atau check-in
- layout default sidebar atau navbar

## 25. Kesimpulan

Dengan blueprint ini, implementasi bisa dimulai dengan arah yang lebih aman. Backend Laravel sudah punya peta endpoint utama, sedangkan frontend sudah punya struktur halaman, layout, dan prinsip UI modern yang cepat, fleksibel, serta tanpa ketergantungan pada CDN.
