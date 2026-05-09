# ERD dan Flow Final Sistem Manajemen Hotel / Homestay

## 1. Tujuan Dokumen

Dokumen ini melengkapi requirement final dengan fokus pada desain data dan alur operasional per modul. Tujuannya adalah agar implementasi Laravel dapat dimulai dengan struktur entitas, relasi, state, dan flow bisnis yang sudah lebih jelas.

## 2. Prinsip Desain Data

- Desain data harus mendukung operasional harian hotel yang cepat
- Status penting harus tersimpan eksplisit dan dapat diaudit
- Semua transaksi penting harus memiliki jejak histori
- Entitas master dipisahkan dari entitas transaksi
- Relasi harus siap untuk pengembangan bertahap tanpa merusak struktur inti
- Semua nama tabel menggunakan bentuk plural snake_case

## 3. Ruang Lingkup Entitas

### 3.1 Entitas Master

- properties
- room_types
- rooms
- guests
- employees
- users
- roles
- inventory_categories
- inventory_items
- payment_methods

### 3.2 Entitas Transaksi

- reservations
- reservation_rooms
- stay_records
- invoices
- invoice_items
- payments
- payment_transactions
- housekeeping_tasks
- maintenance_requests
- inventory_movements
- shifts
- attendances

### 3.3 Entitas Sistem

- settings
- activity_logs
- notifications
- room_status_logs

## 4. ERD Konseptual

### 4.1 Relasi Utama

- Satu property memiliki banyak room_types
- Satu property memiliki banyak rooms
- Satu room_type memiliki banyak rooms
- Satu guest memiliki banyak reservations
- Satu reservation dapat memiliki satu atau banyak reservation_rooms
- Satu reservation dapat menghasilkan satu atau banyak invoice sesuai kebutuhan bisnis
- Satu invoice memiliki banyak invoice_items
- Satu invoice memiliki banyak payments
- Satu payment dapat memiliki satu atau banyak payment_transactions
- Satu room memiliki banyak housekeeping_tasks
- Satu room memiliki banyak room_status_logs
- Satu room dapat memiliki banyak maintenance_requests
- Satu inventory_item memiliki banyak inventory_movements
- Satu employee dapat memiliki satu user
- Satu employee memiliki banyak shifts
- Satu employee memiliki banyak attendances

### 4.2 Relasi yang Direkomendasikan

- properties 1..n room_types
- properties 1..n rooms
- room_types 1..n rooms
- guests 1..n reservations
- reservations 1..n reservation_rooms
- rooms 1..n reservation_rooms
- reservations 1..n stay_records
- reservations 1..n invoices
- invoices 1..n invoice_items
- invoices 1..n payments
- payments 1..n payment_transactions
- rooms 1..n housekeeping_tasks
- employees 1..n housekeeping_tasks
- rooms 1..n maintenance_requests
- employees 1..n maintenance_requests
- inventory_categories 1..n inventory_items
- inventory_items 1..n inventory_movements
- employees 1..1 users atau 1..n users sesuai kebutuhan masa depan
- roles 1..n users
- rooms 1..n room_status_logs
- users 1..n activity_logs

## 5. Struktur Tabel Inti

Bagian ini adalah desain minimum yang cukup aman untuk memulai implementasi.

### 5.1 properties

Kolom minimum:

- id
- code
- name
- address
- phone
- email
- timezone
- currency
- is_active
- created_at
- updated_at

### 5.2 room_types

Kolom minimum:

- id
- property_id
- code
- name
- capacity
- base_price
- weekend_price
- extra_bed_price
- description
- is_active
- created_at
- updated_at

### 5.3 rooms

Kolom minimum:

- id
- property_id
- room_type_id
- room_number
- floor
- current_status
- housekeeping_status
- serviceability_status
- is_active
- notes
- created_at
- updated_at

Catatan:

- `current_status` difokuskan untuk occupancy status seperti `available`, `reserved`, dan `occupied`
- `housekeeping_status` memisahkan kondisi kebersihan seperti `clean`, `dirty`, dan `inspected`
- `serviceability_status` dipakai untuk kondisi teknis seperti `normal`, `maintenance`, atau `out_of_service`

### 5.4 guests

Kolom minimum:

- id
- full_name
- id_type
- id_number
- phone
- email
- address
- nationality
- birth_date
- gender
- notes
- last_stay_at
- total_stays
- created_at
- updated_at

### 5.5 employees

Kolom minimum:

- id
- property_id
- employee_code
- full_name
- phone
- email
- job_title
- department
- hire_date
- employment_status
- base_salary
- is_active
- created_at
- updated_at

### 5.6 roles

Kolom minimum:

- id
- name
- code
- description
- created_at
- updated_at

### 5.7 users

Kolom minimum:

- id
- employee_id
- role_id
- name
- username
- email
- password_hash
- last_login_at
- is_active
- created_at
- updated_at

### 5.8 reservations

Kolom minimum:

- id
- property_id
- guest_id
- booking_code
- source
- reservation_status
- booked_at
- check_in_date
- check_out_date
- total_nights
- total_guests
- subtotal_amount
- tax_amount
- discount_amount
- total_amount
- deposit_amount
- paid_amount
- remaining_amount
- expiry_at
- special_request
- cancellation_reason
- created_by_user_id
- confirmed_at
- expired_at
- cancelled_at
- no_show_at
- status_reason
- created_at
- updated_at

Catatan:

- `expiry_at` adalah batas waktu validasi reservasi pending
- `expired_at` menyimpan waktu saat sistem benar-benar menandai reservasi menjadi `expired`
- `status_reason` dipakai untuk alasan pembatalan, no-show, atau override status bila dibutuhkan

### 5.9 reservation_rooms

Kolom minimum:

- id
- reservation_id
- room_id
- room_type_id
- guest_count
- rate_per_night
- extra_bed_fee
- check_in_date
- check_out_date
- line_total
- room_status_snapshot
- created_at
- updated_at

Catatan:

- Tabel ini membuat sistem siap untuk multi-room booking
- Untuk fase awal, satu reservasi tetap bisa dibatasi satu kamar di level UI

### 5.10 stay_records

Kolom minimum:

- id
- reservation_id
- room_id
- check_in_business_date
- checked_in_at
- check_out_business_date
- checked_out_at
- actual_guest_count
- assigned_by_user_id
- checked_in_by_user_id
- checked_out_by_user_id
- early_check_in_fee
- late_check_out_fee
- room_condition_note
- created_at
- updated_at

Catatan:

- Kolom business date membantu laporan operasional yang tidak selalu sama dengan tanggal kalender
- Jika tamu pindah kamar, histori perpindahan sebaiknya dicatat terpisah agar stay record utama tetap rapi

### 5.11 invoices

Kolom minimum:

- id
- reservation_id
- invoice_number
- invoice_status
- issued_at
- subtotal_amount
- tax_amount
- service_amount
- discount_amount
- grand_total
- paid_amount
- remaining_amount
- due_at
- notes
- created_by_user_id
- voided_at
- refunded_at
- created_at
- updated_at

### 5.12 invoice_items

Kolom minimum:

- id
- invoice_id
- item_type
- item_name
- reference_type
- reference_id
- quantity
- unit_price
- line_total
- notes
- created_at
- updated_at

Contoh item:

- room_charge
- extra_bed
- minibar
- laundry
- late_checkout_fee
- damage_fee
- tax_adjustment
- discount

### 5.13 payments

Kolom minimum:

- id
- invoice_id
- payment_code
- payment_type
- payment_status
- payment_method_code
- amount
- payment_reference
- business_date
- paid_at
- refunded_at
- voided_at
- received_by_user_id
- notes
- created_at
- updated_at

Catatan:

- `payment_reference` dapat dipakai untuk nomor referensi tunai, transfer, EDC, atau sumber eksternal lain
- `business_date` penting untuk closing kasir dan rekap pembayaran harian

### 5.14 payment_transactions

Kolom minimum:

- id
- payment_id
- gateway_name
- provider_reference
- transaction_status
- raw_response_reference
- processed_at
- created_at
- updated_at

Catatan:

- Untuk pembayaran tunai lokal, entitas ini tetap bisa dipakai dengan data sederhana
- Untuk integrasi gateway nanti, tabel ini sudah siap

### 5.15 housekeeping_tasks

Kolom minimum:

- id
- property_id
- room_id
- reservation_id
- assigned_employee_id
- task_type
- priority
- task_status
- scheduled_for
- started_at
- completed_at
- verified_at
- verified_by_user_id
- issue_note
- created_by_user_id
- created_at
- updated_at

### 5.16 maintenance_requests

Kolom minimum:

- id
- property_id
- room_id
- reported_by_employee_id
- assigned_employee_id
- maintenance_status
- priority
- title
- description
- estimated_cost
- actual_cost
- reported_at
- resolved_at
- created_at
- updated_at

### 5.17 inventory_categories

Kolom minimum:

- id
- property_id
- name
- code
- description
- created_at
- updated_at

### 5.18 inventory_items

Kolom minimum:

- id
- property_id
- category_id
- sku
- item_name
- unit
- minimum_stock
- current_stock
- last_purchase_price
- is_active
- created_at
- updated_at

### 5.19 inventory_movements

Kolom minimum:

- id
- inventory_item_id
- movement_type
- quantity
- stock_before
- stock_after
- reference_type
- reference_id
- notes
- moved_by_user_id
- moved_at
- created_at
- updated_at

Jenis movement:

- stock_in
- stock_out
- adjustment
- usage
- transfer

### 5.20 shifts

Kolom minimum:

- id
- employee_id
- shift_date
- start_time
- end_time
- shift_type
- notes
- created_at
- updated_at

### 5.21 attendances

Kolom minimum:

- id
- employee_id
- shift_id
- attendance_date
- check_in_at
- check_out_at
- attendance_status
- notes
- created_at
- updated_at

### 5.22 settings

Kolom minimum:

- id
- property_id
- setting_group
- setting_key
- setting_value
- created_at
- updated_at

Contoh setting penting:

- ui.primary_color
- ui.layout_mode
- branding.logo_path
- business.check_in_time
- business.check_out_time
- billing.default_tax_percent

### 5.23 room_status_logs

Kolom minimum:

- id
- room_id
- status_domain
- from_status
- to_status
- changed_by_user_id
- reference_type
- reference_id
- changed_at
- note
- created_at
- updated_at

Catatan:

- `status_domain` direkomendasikan berisi `occupancy`, `housekeeping`, atau `serviceability`
- Dengan begitu satu tabel log tetap bisa dipakai walau status kamar sudah dipisah per domain

### 5.24 activity_logs

Kolom minimum:

- id
- user_id
- action
- subject_type
- subject_id
- description
- ip_address
- user_agent
- created_at
- updated_at

### 5.25 reservation_status_logs

Kolom minimum:

- id
- reservation_id
- from_status
- to_status
- changed_by_user_id
- reason
- reference_type
- reference_id
- changed_at
- created_at
- updated_at

### 5.26 invoice_status_logs

Kolom minimum:

- id
- invoice_id
- from_status
- to_status
- changed_by_user_id
- reason
- reference_type
- reference_id
- changed_at
- created_at
- updated_at

### 5.27 payment_status_logs

Kolom minimum:

- id
- payment_id
- from_status
- to_status
- changed_by_user_id
- reason
- reference_type
- reference_id
- changed_at
- created_at
- updated_at

### 5.28 room_availability_locks

Kolom minimum:

- id
- property_id
- room_id
- reservation_id
- locked_by_user_id
- lock_source
- expires_at
- released_at
- release_reason
- created_at
- updated_at

Catatan:

- Tabel ini dipakai untuk mengurangi konflik booking saat beberapa user memilih kamar yang sama
- Lock sementara harus memiliki expiry yang pendek dan dapat dibersihkan otomatis

### 5.29 stay_guests

Kolom minimum:

- id
- stay_record_id
- guest_id
- is_primary
- occupancy_role
- identity_verified_at
- notes
- created_at
- updated_at

Catatan:

- Tabel ini dipakai untuk menyimpan penghuni aktual per stay, bukan hanya guest utama pada reservasi
- Cocok untuk keluarga, tamu tambahan, dan audit identitas penghuni

## 6. State Machine yang Direkomendasikan

### 6.1 State Reservasi

- pending
- expired
- confirmed
- checked_in
- checked_out
- cancelled
- no_show

Transisi utama:

- pending ke confirmed
- pending ke expired
- pending ke cancelled
- pending ke no_show
- confirmed ke checked_in
- confirmed ke cancelled
- confirmed ke no_show
- checked_in ke checked_out

Catatan implementasi:

- Setiap transisi status reservasi harus disimpan ke histori status khusus atau minimal activity log yang terstruktur
- Status `expired` dipakai untuk reservasi yang melewati batas waktu konfirmasi atau pembayaran awal
- Status `no_show` hanya boleh diproses setelah melewati cutoff check-in yang ditetapkan bisnis

### 6.2 State Kamar

Rekomendasi aman: status kamar dipisah menjadi 3 domain agar tidak ambigu.

1. Occupancy status:
   - available
   - reserved
   - occupied
2. Housekeeping status:
   - clean
   - dirty
   - inspected
3. Serviceability status:
   - normal
   - maintenance
   - out_of_service

Transisi utama occupancy:

- available ke reserved
- reserved ke occupied
- occupied ke available

Transisi utama housekeeping:

- clean ke dirty
- dirty ke inspected
- inspected ke clean

Transisi utama serviceability:

- normal ke maintenance
- maintenance ke normal
- normal ke out_of_service
- out_of_service ke normal

Catatan implementasi:

- Pada fase awal, `current_status` dapat difokuskan untuk occupancy status
- `housekeeping_status` tetap dipakai terpisah seperti yang sudah direncanakan
- Jika belum ingin menambah kolom baru, status maintenance dapat diperlakukan sebagai blocking flag sampai struktur final dipisah sempurna

### 6.3 State Invoice

- draft
- unpaid
- partial
- paid
- refunded
- void

Transisi utama:

- draft ke unpaid
- unpaid ke partial
- unpaid ke paid
- partial ke paid
- paid ke refunded
- draft ke void
- unpaid ke void

Catatan implementasi:

- Perubahan status invoice harus mengikuti hasil agregasi item tagihan dan pembayaran
- Void dan refund sebaiknya memerlukan otorisasi sesuai role

### 6.4 State Payment

- pending
- paid
- failed
- refunded
- void

Transisi utama:

- pending ke paid
- pending ke failed
- paid ke refunded
- pending ke void

Catatan implementasi:

- Untuk pembayaran tunai langsung, status bisa dibuat `paid` pada saat transaksi dicatat
- Untuk transfer atau gateway, gunakan `pending` sampai ada konfirmasi akhir

### 6.5 State Housekeeping Task

- pending
- assigned
- in_progress
- completed
- verified

Transisi utama:

- pending ke assigned
- assigned ke in_progress
- in_progress ke completed
- completed ke verified

## 7. Flow Detail Per Modul

### 7.1 Flow Reservasi

1. Staff memilih tanggal, tipe kamar, dan jumlah tamu
2. Sistem mengecek availability kamar berdasarkan occupancy status, serviceability status, dan tanggal bisnis
3. Sistem menampilkan opsi kamar dan estimasi biaya
4. Staff memilih tamu lama atau membuat tamu baru
5. Saat staff memilih kamar, sistem membuat lock availability sementara untuk mencegah konflik booking
6. Reservasi dibuat dengan status `pending` atau `confirmed` sesuai aturan bisnis
7. Sistem membuat `reservation_rooms`
8. Sistem menghasilkan booking code
9. Jika ada deposit atau aturan prepayment, sistem membuat invoice dan payment awal
10. Sistem menyimpan `expiry_at` bila reservasi belum memenuhi syarat konfirmasi
11. Status kamar berubah menjadi `reserved` hanya jika kamar benar-benar dialokasikan dan lock berhasil dikonversi
12. Sistem mencatat histori status reservasi dan room status log
13. Sistem mengirim notifikasi konfirmasi bila fitur aktif

Kasus tambahan yang harus didukung:

- reservasi tanpa deposit dengan batas waktu expiry
- edit tanggal sebelum check-in
- pembatalan dengan alasan
- no-show
- walk-in yang langsung lanjut ke check-in
- overbooking harus ditolak dengan pesan konflik yang jelas
- perubahan sumber booking harus tetap menyimpan histori

### 7.2 Flow Check-in

1. Front desk mencari booking code, nama tamu, atau nomor kamar
2. Sistem memuat data reservasi, tagihan, dan status pembayaran
3. Front desk verifikasi identitas tamu
4. Sistem memastikan reservasi belum `cancelled`, `expired`, atau `no_show`
5. Jika kamar belum final, lakukan room assignment dengan validasi availability terakhir
6. Sistem cek apakah reservasi valid untuk check-in berdasarkan tanggal bisnis, status pembayaran minimum, dan rule bisnis
7. Sistem membuat `stay_records`
8. Sistem menyimpan jumlah tamu aktual dan petugas yang melakukan check-in
9. Status reservasi berubah menjadi `checked_in`
10. Occupancy status kamar berubah menjadi `occupied`
11. Sistem mencatat histori status reservasi dan room status log
12. Front desk mencetak atau menampilkan registrasi bila dibutuhkan

Kasus tambahan:

- early check-in
- tamu walk-in
- pindah kamar
- jumlah tamu aktual berbeda dari reservasi
- check-in dengan deposit belum lunas harus mengikuti rule approval yang jelas

### 7.3 Flow Check-out

1. Front desk membuka data stay
2. Sistem mengambil invoice aktif dan item berjalan
3. Housekeeping atau petugas melakukan pengecekan kamar
4. Sistem menambahkan biaya tambahan bila ada
5. Sistem menghitung sisa tagihan
6. Jika ada dispute, koreksi item tagihan harus dicatat sebagai histori, bukan diam-diam mengganti angka
7. Front desk menerima pembayaran akhir
8. Jika ada deposit, sistem menghitung pengembalian atau pemotongan
9. Status invoice dan payment diperbarui otomatis
10. Status reservasi berubah menjadi `checked_out`
11. Occupancy status kamar dilepas, housekeeping status berubah menjadi `dirty`
12. Sistem membuat `housekeeping_tasks`
13. Sistem mencatat histori status reservasi dan room status log
14. Jika perlu, sistem menandai kebutuhan maintenance atau damage follow-up

Kasus tambahan:

- late check-out fee
- kerusakan barang atau kamar
- split payment
- dispute tagihan
- refund deposit parsial

### 7.4 Flow Housekeeping

1. Tugas dibuat otomatis setelah check-out atau manual oleh supervisor
2. Supervisor atau sistem assign petugas
3. Petugas memulai tugas dan status jadi `in_progress`
4. Pemakaian amenities dapat dicatat ke `inventory_movements`
5. Jika ditemukan kerusakan, dibuat `maintenance_requests`
6. Setelah selesai, tugas diubah menjadi `completed`
7. Supervisor atau petugas berwenang verifikasi tugas
8. Housekeeping status kamar berubah ke `clean` atau `inspected`, lalu kamar kembali siap jual bila tidak ada blocking maintenance
9. Sistem mencatat room status log

### 7.5 Flow Billing dan Payment

1. Saat reservasi dibuat, sistem bisa membuat invoice draft
2. Item kamar utama dibuat otomatis
3. Item tambahan bisa ditambahkan selama tamu menginap
4. Saat pembayaran dilakukan, sistem membuat payment
5. Jika perlu, detail transaksi disimpan di `payment_transactions`
6. Sistem menghitung ulang `paid_amount` dan `remaining_amount`
7. Status invoice berubah otomatis berdasarkan total pembayaran tervalidasi
8. Void, refund, dan koreksi pembayaran harus menyimpan histori serta alasan
9. Jika payment masih menunggu konfirmasi, status payment tetap `pending` dan tidak langsung dianggap lunas

Jenis payment yang harus siap:

- deposit
- pelunasan
- pembayaran tambahan
- refund

### 7.6 Flow Inventori

1. Admin gudang atau petugas membuat item inventori
2. Barang masuk dicatat sebagai `stock_in`
3. Saat pemakaian operasional, sistem mencatat `usage` atau `stock_out`
4. Saldo stok diperbarui otomatis
5. Jika stok di bawah minimum, sistem memberi alert
6. Riwayat mutasi harus bisa difilter per item, tanggal, dan tipe gerakan

### 7.7 Flow Karyawan

1. Admin membuat data pegawai
2. Jika pegawai membutuhkan akses, sistem membuat user
3. Role ditentukan sesuai jabatan
4. Shift dijadwalkan
5. Kehadiran dicatat saat check-in dan check-out kerja
6. Rekap absensi ditampilkan ke laporan

## 8. Aturan Bisnis yang Perlu Disepakati Saat Implementasi

### 8.1 Keputusan Awal yang Direkomendasikan

- Fase pertama boleh menyimpan banyak kamar per reservasi di level data, tetapi UI boleh dibatasi satu kamar lebih dulu
- Status kamar berubah ke `reserved` hanya setelah room assignment valid dan lock availability berhasil dikonversi
- Invoice utama direkomendasikan dibuat saat reservasi confirmed atau saat ada kewajiban deposit
- Deposit direkam sebagai payment riil agar jejak kas tidak terpisah dari tagihan
- Housekeeping task yang selesai sebaiknya diverifikasi sebelum kamar kembali siap jual bila properti memiliki supervisor
- Inventori amenities dapat dipotong manual pada fase awal, lalu diotomatisasi setelah flow housekeeping stabil
- Satu pegawai direkomendasikan punya satu user login aktif pada fase awal
- Payroll tetap ditunda dari fase awal

### 8.2 Aturan Kritis yang Harus Dipastikan Sejak Awal

- Sistem harus mengenal `business_date` untuk proses harian, laporan, dan cutoff no-show
- Reservasi `pending` harus memiliki `expiry_at` yang wajib diproses otomatis oleh scheduler
- Semua perubahan status penting harus menyimpan user, waktu, status sebelum, status sesudah, dan alasan perubahan
- Check-in tidak boleh lolos jika kamar tidak valid, reservasi sudah expired, atau ada blocking maintenance
- Check-out tidak boleh final bila rule pembayaran akhir belum terpenuhi, kecuali ada override berizin
- Refund, void invoice, dan koreksi pembayaran harus melalui role yang berwenang
- Room move harus menyimpan histori kamar asal, kamar tujuan, waktu pindah, dan alasan

### 8.3 Keputusan Lanjutan yang Bisa Menyusul Setelah Fondasi Stabil

- Kapan folio atau guest ledger diperkenalkan bila invoice dasar sudah tidak cukup
- Apakah approval diskon, refund, dan damage fee memakai satu level atau multi-level approval
- Apakah user dapat mengakses lebih dari satu property dalam fase multi-properti
- Kapan cashier closing, reconciliation, dan night audit dibuka sebagai modul operasional penuh

## 9. Rekomendasi Implementasi Laravel

### 9.1 Modul Eloquent yang Paling Penting

- Property
- RoomType
- Room
- Guest
- Employee
- User
- Role
- Reservation
- ReservationRoom
- StayRecord
- Invoice
- InvoiceItem
- Payment
- PaymentTransaction
- HousekeepingTask
- MaintenanceRequest
- InventoryCategory
- InventoryItem
- InventoryMovement
- Shift
- Attendance
- Setting
- RoomStatusLog
- ActivityLog

### 9.2 Layer yang Direkomendasikan

- Controllers untuk request-response
- Form request untuk validation
- Services untuk business flow
- Policies atau permission layer untuk akses
- Jobs untuk proses berat
- Events untuk perubahan status penting

### 9.3 Event yang Berguna

- reservation.created
- reservation.confirmed
- guest.checked_in
- guest.checked_out
- room.status_changed
- payment.recorded
- housekeeping.task_completed
- inventory.stock_below_minimum

## 10. Prioritas Build Berdasarkan ERD

### Tahap 1

- roles
- employees
- users
- settings
- room_types
- rooms
- guests
- reservations
- reservation_rooms
- invoices
- invoice_items
- payments
- room_status_logs

### Tahap 2

- stay_records
- housekeeping_tasks
- inventory_categories
- inventory_items
- inventory_movements
- shifts
- attendances
- activity_logs

### Tahap 3

- maintenance_requests
- payment_transactions
- notifications
- fitur multi-properti penuh

## 11. Kesimpulan

Dokumen ini menetapkan struktur data dan flow final yang lebih siap dipakai untuk implementasi Laravel. Dengan desain ini, sistem sudah memiliki fondasi yang cukup kuat untuk mengakomodasi UI modern, performa cepat, flow operasional hotel, inventori, karyawan, dan pengembangan bertahap tanpa ketergantungan pada CDN.
