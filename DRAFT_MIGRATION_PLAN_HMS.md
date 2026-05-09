# Draft Migration Plan HMS

## Tujuan

Dokumen ini adalah draft rencana migration Laravel untuk menurunkan revisi terbaru pada `ERD_dan_Flow_Final_HMS.md` ke level database yang realistis berdasarkan kondisi schema backend saat ini.

Fokus utama draft ini:

- menambah auditability
- memperjelas state kamar
- menutup gap availability locking
- menambah dukungan business date
- menyiapkan payment dan status log yang lebih aman

Dokumen ini sengaja memakai pendekatan **incremental** dan **non-destructive** agar aman diterapkan pada schema yang sudah berjalan.

## Kondisi Schema Saat Ini

Berdasarkan migration yang sudah ada di `/Users/f/Documents/sdk-project/booking/hms/backend/database/migrations`, kondisi penting saat ini adalah:

- `rooms` sudah punya `current_status` dan `housekeeping_status`, tetapi belum punya `serviceability_status`
- `reservations` sudah ada, tetapi bentuknya berbeda dari ERD konseptual:
  - memakai `primary_guest_id`
  - punya `assigned_room_id`
  - punya `payment_status` dan `guarantee_status`
  - belum punya `expiry_at`, `expired_at`, `no_show_at`, `status_reason`
- `stay_records` sudah ada, tetapi belum punya `business_date`
- `invoices` sudah ada, tetapi belum punya `voided_at` dan `refunded_at`
- tabel `payments` dan `payment_transactions` belum terlihat pada migration existing
- `reservation_guests` sudah ada
- `front_desk_audit_logs` sudah ada, tetapi sifatnya masih event log umum, belum menggantikan status log khusus

## Prinsip Eksekusi

- Hindari rename atau drop kolom pada tahap awal
- Dahulukan migration additive: tambah kolom, tambah tabel, tambah index
- Lakukan backfill seperlunya setelah kolom baru tersedia
- Pisahkan migration schema dan backfill data bila perubahan cukup besar
- Semua perubahan status kritis nantinya harus lewat service layer, bukan query liar

## Urutan Implementasi yang Direkomendasikan

### Batch 1: Tambahan Kolom Inti

Tujuan batch ini adalah membuat schema siap menerima flow baru tanpa langsung mengubah behavior aplikasi secara besar.

#### 1. Add serviceability status to rooms

**Nama migration yang disarankan**

- `2026_05_09_100000_add_serviceability_status_to_rooms_table.php`

**Perubahan**

- tambah kolom `serviceability_status` default `normal`
- tambah index gabungan untuk kebutuhan availability

**Kolom**

- `serviceability_status` string default `normal`

**Index yang disarankan**

- `index(['current_status', 'housekeeping_status', 'serviceability_status', 'is_active'])`

#### 2. Add reservation lifecycle fields

**Nama migration yang disarankan**

- `2026_05_09_101000_add_lifecycle_fields_to_reservations_table.php`

**Perubahan**

- tambah `expiry_at`
- tambah `expired_at`
- tambah `no_show_at`
- tambah `status_reason`

**Kolom**

- `expiry_at` timestamp nullable
- `expired_at` timestamp nullable
- `no_show_at` timestamp nullable
- `status_reason` text nullable

**Index yang disarankan**

- `index(['reservation_status', 'expiry_at'])`
- `index(['assigned_room_id', 'reservation_status', 'check_in_date'])`

#### 3. Add business date to stay records

**Nama migration yang disarankan**

- `2026_05_09_102000_add_business_date_to_stay_records_table.php`

**Perubahan**

- tambah `check_in_business_date`
- tambah `check_out_business_date`

**Kolom**

- `check_in_business_date` date nullable
- `check_out_business_date` date nullable

**Catatan**

- Backfill awal bisa mengambil tanggal dari `actual_check_in_at` dan `actual_check_out_at` jika ada

#### 4. Add invoice lifecycle fields

**Nama migration yang disarankan**

- `2026_05_09_103000_add_lifecycle_fields_to_invoices_table.php`

**Perubahan**

- tambah `voided_at`
- tambah `refunded_at`

**Kolom**

- `voided_at` timestamp nullable
- `refunded_at` timestamp nullable

### Batch 2: Tabel Payment

Batch ini perlu karena target ERD sudah mengandalkan payment sebagai entitas riil, sementara migration existing belum menyiapkannya.

#### 5. Create payments table

**Nama migration yang disarankan**

- `2026_05_09_104000_create_payments_table.php`

**Kolom minimum**

- `id`
- `invoice_id`
- `payment_code`
- `payment_type`
- `payment_status`
- `payment_method_code`
- `amount`
- `payment_reference`
- `business_date`
- `paid_at`
- `refunded_at`
- `voided_at`
- `received_by_user_id`
- `notes`
- `created_at`
- `updated_at`

**Foreign key**

- `invoice_id -> invoices.id`
- `received_by_user_id -> users.id`

**Index yang disarankan**

- `unique('payment_code')`
- `index(['payment_status', 'paid_at'])`
- `index(['business_date', 'payment_method_code'])`
- `index('invoice_id')`

#### 6. Create payment transactions table

**Nama migration yang disarankan**

- `2026_05_09_105000_create_payment_transactions_table.php`

**Kolom minimum**

- `id`
- `payment_id`
- `gateway_name`
- `provider_reference`
- `transaction_status`
- `raw_response_reference`
- `processed_at`
- `created_at`
- `updated_at`

**Foreign key**

- `payment_id -> payments.id`

**Index yang disarankan**

- `index(['payment_id', 'transaction_status'])`
- `index('provider_reference')`

### Batch 3: Status Log Khusus

Batch ini menutup gap auditability yang sebelumnya terlalu bergantung pada log generik.

#### 7. Add status domain to room status logs

**Nama migration yang disarankan**

- `2026_05_09_106000_add_status_domain_to_room_status_logs_table.php`

**Perubahan**

- tambah kolom `status_domain`

**Kolom**

- `status_domain` string default `occupancy`

**Catatan**

- Untuk log lama, default `occupancy` cukup aman pada tahap awal

#### 8. Create reservation status logs table

**Nama migration yang disarankan**

- `2026_05_09_107000_create_reservation_status_logs_table.php`

**Kolom minimum**

- `id`
- `reservation_id`
- `from_status`
- `to_status`
- `changed_by_user_id`
- `reason`
- `reference_type`
- `reference_id`
- `changed_at`
- `created_at`
- `updated_at`

#### 9. Create invoice status logs table

**Nama migration yang disarankan**

- `2026_05_09_108000_create_invoice_status_logs_table.php`

**Kolom minimum**

- `id`
- `invoice_id`
- `from_status`
- `to_status`
- `changed_by_user_id`
- `reason`
- `reference_type`
- `reference_id`
- `changed_at`
- `created_at`
- `updated_at`

#### 10. Create payment status logs table

**Nama migration yang disarankan**

- `2026_05_09_109000_create_payment_status_logs_table.php`

**Kolom minimum**

- `id`
- `payment_id`
- `from_status`
- `to_status`
- `changed_by_user_id`
- `reason`
- `reference_type`
- `reference_id`
- `changed_at`
- `created_at`
- `updated_at`

### Batch 4: Availability Locking dan Occupant Aktual

Batch ini penting untuk mencegah conflict booking dan memperjelas penghuni aktual.

#### 11. Create room availability locks table

**Nama migration yang disarankan**

- `2026_05_09_110000_create_room_availability_locks_table.php`

**Kolom minimum**

- `id`
- `property_id`
- `room_id`
- `reservation_id`
- `locked_by_user_id`
- `lock_source`
- `expires_at`
- `released_at`
- `release_reason`
- `created_at`
- `updated_at`

**Foreign key**

- `property_id -> properties.id`
- `room_id -> rooms.id`
- `reservation_id -> reservations.id`
- `locked_by_user_id -> users.id`

**Index yang disarankan**

- `index(['room_id', 'expires_at'])`
- `index(['reservation_id', 'released_at'])`

#### 12. Create stay guests table

**Nama migration yang disarankan**

- `2026_05_09_111000_create_stay_guests_table.php`

**Alasan**

- `reservation_guests` sudah ada, tetapi itu masih level reservasi
- sistem tetap butuh data penghuni aktual saat tamu benar-benar menginap

**Kolom minimum**

- `id`
- `stay_record_id`
- `guest_id`
- `is_primary`
- `occupancy_role`
- `identity_verified_at`
- `notes`
- `created_at`
- `updated_at`

**Foreign key**

- `stay_record_id -> stay_records.id`
- `guest_id -> guests.id`

### Batch 5: Data Backfill

Batch ini sebaiknya dipisah dari schema migration jika data existing sudah mulai terisi.

#### 13. Backfill serviceability status

**Nama migration atau script yang disarankan**

- `2026_05_09_112000_backfill_serviceability_status_on_rooms.php`

**Aturan awal**

- semua room existing diisi `normal`

#### 14. Backfill business date

**Nama migration atau script yang disarankan**

- `2026_05_09_113000_backfill_business_date_on_stay_records.php`

**Aturan awal**

- `check_in_business_date = date(actual_check_in_at)` jika ada
- `check_out_business_date = date(actual_check_out_at)` jika ada

#### 15. Seed initial reservation status logs

**Opsional**

- hanya dilakukan jika data existing sudah cukup penting untuk audit continuity
- kalau environment masih awal, ini bisa ditunda

## Mapping ke Model Laravel

Model yang hampir pasti perlu ditambah atau diperbarui:

- `Room`
- `Reservation`
- `StayRecord`
- `Invoice`
- `Payment` baru
- `PaymentTransaction` baru
- `ReservationStatusLog` baru
- `InvoiceStatusLog` baru
- `PaymentStatusLog` baru
- `RoomAvailabilityLock` baru
- `StayGuest` baru

## Urutan Artisan yang Disarankan

Contoh urutan generate migration:

```bash
php artisan make:migration add_serviceability_status_to_rooms_table --table=rooms
php artisan make:migration add_lifecycle_fields_to_reservations_table --table=reservations
php artisan make:migration add_business_date_to_stay_records_table --table=stay_records
php artisan make:migration add_lifecycle_fields_to_invoices_table --table=invoices
php artisan make:migration create_payments_table --create=payments
php artisan make:migration create_payment_transactions_table --create=payment_transactions
php artisan make:migration add_status_domain_to_room_status_logs_table --table=room_status_logs
php artisan make:migration create_reservation_status_logs_table --create=reservation_status_logs
php artisan make:migration create_invoice_status_logs_table --create=invoice_status_logs
php artisan make:migration create_payment_status_logs_table --create=payment_status_logs
php artisan make:migration create_room_availability_locks_table --create=room_availability_locks
php artisan make:migration create_stay_guests_table --create=stay_guests
```

## Risiko dan Catatan Implementasi

- `reservations` existing sudah punya `payment_status`; jangan langsung dihapus walau nanti payment sudah menjadi entitas sendiri
- `front_desk_audit_logs` tetap berguna dan tidak perlu diganti, karena fungsinya berbeda dari status log khusus
- Bila nanti mau menambah `business_date` ke `payments`, pertimbangkan juga modul cashier shift di tahap berikutnya
- Jika sistem sudah memiliki data nyata, migration backfill harus dites di salinan database dulu
- Jangan langsung mengubah enum atau konstanta aplikasi sebelum schema baru siap

## Rekomendasi Eksekusi

Urutan paling aman:

1. kerjakan Batch 1
2. kerjakan Batch 2
3. kerjakan Batch 3
4. kerjakan Batch 4
5. jalankan backfill
6. baru update model, service, dan test

## Deliverable Setelah Migration Plan

Setelah plan ini, langkah berikutnya yang paling masuk akal adalah:

- generate migration file satu per satu
- update model relation
- update service layer untuk status transition dan payment recording
- tambah feature test untuk schema baru dan flow kritis
