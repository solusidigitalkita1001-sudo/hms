# Step-by-Step Testing HMS

Dokumen ini adalah panduan testing manual yang lebih praktis dan berurutan untuk kondisi project saat ini.

Dokumen ini melengkapi:

- `MANUAL_BOOK_TESTING_ALUR_0.md`
- `ERD_dan_Flow_Final_HMS.md`
- `DRAFT_MIGRATION_PLAN_HMS.md`

## 1. Tujuan

- memberi urutan testing yang gampang diikuti
- memisahkan flow UI yang sudah bisa dites dari fondasi backend yang baru ditambahkan
- memastikan perubahan schema, model, dan relasi baru tidak merusak flow existing

## 2. Kondisi Saat Ini

Yang sudah ada dan bisa dites:

- login admin
- shell admin
- profile information
- change password
- settings UI
- bilingual preference
- portal CMS
- guest portal
- inquiry flow
- front desk arrivals phase 1

Yang baru selesai di level backend dan perlu sanity check:

- lifecycle field tambahan di reservasi, stay, invoice
- tabel `payments`
- tabel `payment_transactions`
- tabel `room_status_logs`
- tabel `reservation_status_logs`
- tabel `invoice_status_logs`
- tabel `payment_status_logs`
- tabel `room_availability_locks`
- tabel `stay_guests`

Catatan penting:

- sebagian besar tabel baru sudah siap di schema dan model
- tetapi belum semua flow UI/service menulis ke tabel-tabel baru tersebut
- jadi testing dibagi dua:
  - testing flow aplikasi yang memang sudah ada
  - testing fondasi database dan model yang baru ditambahkan

## 3. Prasyarat

Pastikan service lokal aktif:

- frontend: `http://127.0.0.1:5173`
- backend API: `http://127.0.0.1:8000`
- health endpoint: `http://127.0.0.1:8000/api/v1/health`

## 4. Reset Jika Mau Mulai Bersih

Jalankan:

```bash
cd /Users/f/Documents/sdk-project/booking/hms/backend
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Di terminal lain:

```bash
cd /Users/f/Documents/sdk-project/booking/hms/frontend
npm run dev -- --host 127.0.0.1 --port 5173
```

## 5. Akun Demo

Pakai akun:

- email: `admin@local.test`
- username: `admin`
- password: `password`

## 6. Urutan Testing Utama

### Step 1. Smoke Check Awal

1. Buka `http://127.0.0.1:5173`
2. Pastikan halaman login tampil
3. Buka `http://127.0.0.1:8000/api/v1/health`
4. Pastikan health API mengembalikan JSON sukses

Expected:

- frontend tampil normal
- backend respons normal
- tidak ada error `502`, `500`, atau blank page

### Step 2. Login Admin

1. Login menggunakan `admin@local.test` atau `admin`
2. Masukkan password `password`
3. Klik login manual

Expected:

- redirect ke dashboard
- token/session tersimpan
- shell admin tampil normal

### Step 3. Smoke Test Shell Admin

1. Buka beberapa menu dari sidebar
2. Collapse dan expand sidebar
3. Buka command palette dengan `Cmd + K` atau `Ctrl + K`
4. Tambah dan hapus favorites
5. Buka dropdown profile

Expected:

- layout stabil
- navigasi lancar
- dropdown profile tidak ketutup elemen lain
- favorites dan command palette berfungsi

### Step 4. Testing Account dan Password

#### A. Information

1. Klik avatar/profile di kanan atas
2. Masuk ke `Information`
3. Ubah nama, username, atau email
4. Simpan perubahan

Expected:

- success message muncul
- data user update
- refresh browser tetap menampilkan data terbaru

#### B. Change Password

1. Masuk ke `Change Password`
2. Isi password lama
3. Isi password baru
4. Konfirmasi password baru
5. Simpan
6. Logout
7. Login ulang dengan password baru

Expected:

- update password berhasil
- login ulang berhasil

### Step 5. Testing Settings dan Bilingual

1. Buka halaman settings
2. Ubah layout mode
3. Ubah table density
4. Ubah warna UI
5. Ganti bahasa ke English
6. Refresh browser
7. Ganti bahasa kembali ke Indonesia

Expected:

- preference tersimpan
- bahasa UI yang sudah di-wire ikut berubah
- perubahan tetap ada setelah refresh

Catatan:

- fokus cek halaman yang memang sudah di-wire bilingual
- jangan menganggap semua string di seluruh app sudah final 100 persen tanpa sweep tambahan

### Step 6. Testing Portal CMS

1. Masuk ke halaman portal CMS
2. Ubah hero title atau announcement
3. Simpan
4. Buka preview portal

Expected:

- save berhasil
- perubahan tampil di portal

### Step 7. Testing Guest Portal

1. Buka `http://127.0.0.1:5173/portal/main`
2. Cek hero section
3. Cek room list
4. Cek fasilitas
5. Buka modal inquiry

Expected:

- portal terbuka tanpa login
- data tampil normal
- modal inquiry bisa dibuka

### Step 8. Testing Inquiry End-to-End

#### A. Submit dari Guest Portal

1. Isi form inquiry dengan data valid
2. Submit inquiry

Expected:

- muncul success response
- inquiry tersimpan

#### B. Review dari Admin

1. Login sebagai admin
2. Buka halaman booking inquiries
3. Cari inquiry yang baru dibuat
4. Ubah status jika flow yang tersedia mendukung

Expected:

- inquiry muncul di admin
- perubahan status tersimpan

### Step 9. Testing Front Desk Arrivals Phase 1

1. Buka halaman front desk arrivals
2. Pastikan data arrival termuat
3. Coba assign room jika ada data yang sesuai
4. Coba verifikasi identity bila flow tersedia
5. Coba complete check-in pada data uji yang valid

Expected:

- halaman load normal
- assign room berhasil bila room tersedia
- check-in flow tidak meledak error

Catatan:

- flow ini masih phase 1
- tidak semua status log baru otomatis terpakai oleh seluruh action

## 7. Sanity Check Schema Baru

Bagian ini penting karena backend baru saja ditambah banyak tabel dan kolom.

### Step 10. Cek Migration Status

Jalankan:

```bash
cd /Users/f/Documents/sdk-project/booking/hms/backend
php artisan migrate:status
```

Expected:

- migration batch terbaru status `Ran`
- terlihat migration untuk:
  - `payments`
  - `payment_transactions`
  - `room_status_logs`
  - `reservation_status_logs`
  - `invoice_status_logs`
  - `payment_status_logs`
  - `room_availability_locks`
  - `stay_guests`

### Step 11. Cek Tabel Baru Eksis

Jalankan via Tinker:

```bash
cd /Users/f/Documents/sdk-project/booking/hms/backend
php artisan tinker --execute="
dump([
    'payments' => Schema::hasTable('payments'),
    'payment_transactions' => Schema::hasTable('payment_transactions'),
    'room_status_logs' => Schema::hasTable('room_status_logs'),
    'reservation_status_logs' => Schema::hasTable('reservation_status_logs'),
    'invoice_status_logs' => Schema::hasTable('invoice_status_logs'),
    'payment_status_logs' => Schema::hasTable('payment_status_logs'),
    'room_availability_locks' => Schema::hasTable('room_availability_locks'),
    'stay_guests' => Schema::hasTable('stay_guests'),
]);
"
```

Expected:

- semua bernilai `true`

### Step 12. Cek Kolom Baru Eksis

Jalankan:

```bash
cd /Users/f/Documents/sdk-project/booking/hms/backend
php artisan tinker --execute="
dump([
    'rooms.serviceability_status' => Schema::hasColumn('rooms', 'serviceability_status'),
    'reservations.expiry_at' => Schema::hasColumn('reservations', 'expiry_at'),
    'reservations.expired_at' => Schema::hasColumn('reservations', 'expired_at'),
    'reservations.no_show_at' => Schema::hasColumn('reservations', 'no_show_at'),
    'reservations.status_reason' => Schema::hasColumn('reservations', 'status_reason'),
    'stay_records.check_in_business_date' => Schema::hasColumn('stay_records', 'check_in_business_date'),
    'stay_records.check_out_business_date' => Schema::hasColumn('stay_records', 'check_out_business_date'),
    'invoices.voided_at' => Schema::hasColumn('invoices', 'voided_at'),
    'invoices.refunded_at' => Schema::hasColumn('invoices', 'refunded_at'),
]);
"
```

Expected:

- semua bernilai `true`

## 8. Sanity Check Model Baru

### Step 13. Cek Class Model Bisa Diresolve

Jalankan:

```bash
cd /Users/f/Documents/sdk-project/booking/hms/backend
composer dump-autoload
php artisan tinker --execute="
dump([
    class_exists(\App\Domain\Billing\Models\Payment::class),
    class_exists(\App\Domain\Billing\Models\PaymentTransaction::class),
    class_exists(\App\Domain\Billing\Models\InvoiceStatusLog::class),
    class_exists(\App\Domain\Billing\Models\PaymentStatusLog::class),
    class_exists(\App\Domain\Reservation\Models\ReservationStatusLog::class),
    class_exists(\App\Domain\Reservation\Models\StayGuest::class),
    class_exists(\App\Domain\Room\Models\RoomAvailabilityLock::class),
    class_exists(\App\Domain\Room\Models\RoomStatusLog::class),
]);
"
```

Expected:

- semua bernilai `true`

### Step 14. Cek Relation Dasar Dengan Tinker

Jalankan:

```bash
cd /Users/f/Documents/sdk-project/booking/hms/backend
php artisan tinker --execute="
$room = \App\Domain\Room\Models\Room::query()->first();
$reservation = \App\Domain\Reservation\Models\Reservation::query()->first();
$invoice = \App\Domain\Billing\Models\Invoice::query()->first();
$stay = \App\Domain\Reservation\Models\StayRecord::query()->first();
dump([
    'room_has_status_logs_relation' => method_exists($room, 'statusLogs'),
    'room_has_availability_locks_relation' => method_exists($room, 'availabilityLocks'),
    'reservation_has_status_logs_relation' => method_exists($reservation, 'statusLogs'),
    'reservation_has_invoices_relation' => method_exists($reservation, 'invoices'),
    'invoice_has_payments_relation' => method_exists($invoice, 'payments'),
    'stay_has_stay_guests_relation' => method_exists($stay, 'stayGuests'),
]);
"
```

Expected:

- semua bernilai `true`

## 9. Regression Check Setelah Perubahan Backend

### Step 15. Jalankan Test Yang Paling Relevan

Jalankan:

```bash
cd /Users/f/Documents/sdk-project/booking/hms/backend
php artisan test --filter=FrontDeskSchemaFoundationTest
```

Expected:

- test `PASS`

Kalau mau lebih yakin:

```bash
cd /Users/f/Documents/sdk-project/booking/hms/backend
php artisan test
```

## 10. Hal Yang Belum Perlu Dianggap Bug

Jangan langsung anggap bug bila menemukan hal berikut:

- tabel status log baru belum otomatis terisi dari semua action
- tabel `payments` belum sepenuhnya dipakai oleh seluruh flow UI
- tabel `room_availability_locks` belum dipakai penuh oleh semua action assign room
- `stay_guests` belum otomatis diisi di semua flow check-in

Itu bukan karena migration salah, tetapi karena service/action layer untuk write flow-nya belum diintegrasikan penuh.

## 11. Definisi Selesai Testing

Testing dianggap lulus minimal bila:

- frontend bisa dibuka
- backend health normal
- login berhasil
- shell admin stabil
- settings dan account flow aman
- portal dan inquiry flow aman
- front desk phase 1 tidak error
- migration baru semua status `Ran`
- tabel dan kolom baru benar-benar ada
- model baru bisa di-resolve
- test fondasi utama tetap lulus

## 12. Langkah Setelah Testing Ini

Kalau semua langkah di atas lolos, langkah berikutnya yang paling tepat adalah:

1. integrasikan service untuk menulis ke `payments`
2. integrasikan status transition logging
3. integrasikan room lock saat room assignment
4. integrasikan `stay_guests` saat check-in
5. tambah test feature untuk flow baru tersebut
