# Manual Book Testing Alur 0

Dokumen ini dipakai untuk testing manual dari nol berdasarkan flow yang sekarang benar-benar ada di codebase.

## 1. Tujuan

- Menjalankan aplikasi dari kondisi fresh
- Memastikan login admin bekerja
- Memastikan shell admin, sidebar, topbar, dan account dropdown bekerja
- Memastikan flow yang sudah implemented bisa dites end-to-end
- Memisahkan flow yang sudah real dengan halaman yang masih berupa shell/generic workspace

## 2. Scope Yang Sudah Bisa Dites

Flow yang sudah layak dites manual:

- login manual admin
- callback login Google jika credential Google memang sudah dikonfigurasi
- sidebar admin, collapse/expand, command palette, favorites, profile dropdown
- account information update
- change password
- settings UI personalization
- portal CMS
- guest portal public
- submit inquiry dari guest portal
- booking inquiries di admin
- front desk arrivals phase 1

Yang belum perlu dianggap flow penuh:

- mayoritas menu generic workspace selain halaman khusus
- halaman generic sekarang hanya dipakai untuk smoke test navigasi, bukan business flow nyata

## 3. Dokumen Acuan

Gunakan dokumen ini bersamaan dengan file acuan berikut:

- `README.md`
- `Requirement_Final_HMS.md`
- `ERD_dan_Flow_Final_HMS.md`
- `API_Blueprint_dan_UI_Page_Map_HMS.md`
- `Front_Desk_Checkin_Flow_v2.md`

## 4. Prasyarat

- PHP, Composer, Node.js, npm sudah terpasang
- MySQL aktif untuk backend Laravel
- environment backend sudah valid
- port default:
  - backend: `8000`
  - frontend: `5173`

## 5. Reset Dari Nol

### 5.1 Backend

Jalankan dari root project:

```bash
cd backend
composer install
php artisan migrate:fresh --seed
php artisan serve
```

Hasil yang diharapkan:

- database fresh
- user demo kembali ke default
- data property, room type, room, dan portal seed tersedia

### 5.2 Frontend

Di terminal lain:

```bash
cd frontend
npm install
npm run dev
```

Frontend default:

- `http://localhost:5173`

## 6. Akun Demo

Pakai akun berikut untuk testing manual:

- email: `admin@local.test`
- username: `admin`
- password: `password`

Catatan:

- kalau password sudah diganti saat testing, untuk kembali ke baseline jalankan lagi:

```bash
cd backend
php artisan migrate:fresh --seed
```

## 7. Urutan Testing Dari Nol

## 7.1 Smoke Check Awal

1. Buka `http://localhost:5173`
2. Pastikan halaman login tampil
3. Pastikan backend tidak error
4. Pastikan frontend tidak blank

Expected:

- login page muncul normal
- tidak ada error 500
- demo account info terlihat di halaman login

## 7.2 Login Admin Manual

1. Masukkan `admin@local.test` atau `admin`
2. Masukkan password `password`
3. Klik `Login manual`

Expected:

- redirect ke dashboard `/`
- token tersimpan
- nama user muncul di topbar
- sidebar admin tampil

## 7.3 Testing Shell Admin

Lakukan smoke test untuk shell admin:

1. buka beberapa menu dari sidebar
2. coba collapse sidebar
3. coba expand sidebar
4. buka command palette dengan `⌘K` atau `Ctrl+K`
5. tambahkan beberapa favorites
6. cek topbar profile dropdown

Expected:

- navigasi halus
- dropdown profile tampil di atas konten
- favorites bisa dipin/unpin
- command palette bisa buka dan close dengan `Esc`

## 7.4 Testing Profile Dropdown, Information, dan Password

### A. Information

1. klik profile user di kanan atas
2. klik `Information`
3. ubah:
   - nama
   - username
   - email
   - avatar URL
4. klik `Simpan informasi`

Expected:

- muncul success message
- nama/email di topbar ikut update
- reload halaman tetap menampilkan data terbaru

### B. Change Password

1. klik profile user
2. klik `Change Password`
3. isi password saat ini: `password`
4. isi password baru, misalnya `password-baru-123`
5. konfirmasi password baru
6. klik `Perbarui password`

Expected:

- success message muncul
- logout manual
- login ulang dengan password baru berhasil

Setelah selesai bagian ini, pilih salah satu:

- lanjut testing dengan password baru
- atau reset ulang database pakai `php artisan migrate:fresh --seed`

## 7.5 Testing Settings UI

Masuk ke:

- `http://localhost:5173/settings/general`

Checklist:

1. ubah primary color light
2. ubah primary color dark
3. ubah layout mode sidebar/navbar
4. ubah table density comfortable/compact
5. refresh browser

Expected:

- perubahan UI langsung terasa
- preference tetap tersimpan setelah refresh

## 7.6 Testing Portal CMS

Masuk ke:

- `http://localhost:5173/settings/portal-cms`

Checklist:

1. tunggu CMS selesai load
2. ubah beberapa field:
   - announcement
   - hero title
   - hero subtitle
   - nav items
   - destinations
3. klik `Simpan portal CMS`
4. klik `Preview portal`

Expected:

- data tersimpan
- preview portal ikut berubah
- tidak ada error validasi aneh untuk perubahan normal

## 7.7 Testing Guest Portal Public

Buka:

- `http://localhost:5173/portal/main`

Checklist:

1. cek hero content sesuai CMS terbaru
2. cek fasilitas property muncul
3. cek room list muncul
4. tes toggle theme portal
5. buka inquiry modal

Expected:

- halaman public bisa dibuka tanpa login
- data hotel dan room tampil
- perubahan CMS tercermin di portal

## 7.8 Testing Inquiry Dari Portal Sampai Admin

### A. Submit Inquiry dari Portal

Di guest portal:

1. buka inquiry modal
2. isi form inquiry dengan data valid
3. submit inquiry

Expected:

- muncul success message
- inquiry baru berhasil dikirim

### B. Review Inquiry di Admin

Masuk ke:

- `http://localhost:5173/reservations/inquiries`

Checklist:

1. cari nama tamu yang tadi dikirim
2. buka detail inquiry
3. ubah status inquiry beberapa kali, misalnya:
   - new
   - contacted
   - qualified
4. refresh halaman

Expected:

- inquiry muncul di list
- detail modal tampil
- update status berhasil tersimpan

## 7.9 Testing Front Desk Arrivals Phase 1

Karena seed default belum membuat reservation arrival queue, siapkan dulu 1 data arrival.

### A. Siapkan Sample Arrival

Jalankan:

```bash
cd backend
php artisan tinker
```

Lalu jalankan perintah berikut satu per satu:

```php
use App\Domain\Guest\Models\Guest;
use App\Domain\Property\Models\Property;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Room\Models\RoomType;

$property = Property::where('code', 'MAIN')->first();
$roomType = RoomType::where('property_id', $property->id)->where('code', 'SUP')->first();

$guest = Guest::create([
    'property_id' => $property->id,
    'full_name' => 'Manual Test Arrival',
    'phone' => '081234567890',
    'email' => 'manual-arrival@test.local',
]);

Reservation::create([
    'property_id' => $property->id,
    'primary_guest_id' => $guest->id,
    'room_type_id' => $roomType->id,
    'booking_code' => 'MANUAL-ARR-001',
    'source' => 'direct',
    'reservation_status' => 'arrival_due',
    'adult_count' => 2,
    'child_count' => 0,
    'check_in_date' => now()->toDateString(),
    'check_out_date' => now()->addDay()->toDateString(),
]);
```

### B. Test Arrival Queue

Masuk ke:

- `http://localhost:5173/front-desk/arrivals`

Checklist:

1. cari booking code `MANUAL-ARR-001`
2. buka detail arrival
3. assign room
4. verify identity
5. complete check-in

Expected urutan status:

- awal: `arrival_due`
- setelah assign room: `arrived`
- setelah verify identity: `registration_pending`
- setelah complete check-in: `checked_in`

Catatan:

- assign room akan mengambil kamar dari backend assignable rooms
- pastikan pilih room yang available

## 7.10 Testing Login Ulang Setelah Perubahan Password

Bagian ini wajib kalau tadi sempat ganti password.

Checklist:

1. logout dari profile dropdown
2. login ulang dengan password terbaru

Expected:

- login berhasil
- data profile tetap sesuai update terakhir

## 7.11 Testing Google Login

Bagian ini opsional.

Prerequisite:

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`

Checklist:

1. buka login page
2. klik `Login dengan Google`
3. selesaikan login Google
4. pastikan redirect ke `AuthCallbackPage`
5. pastikan redirect akhir ke dashboard

Expected:

- token tersimpan
- user tampil di dashboard

## 8. Smoke Test Menu Admin Lain

Menu generic yang belum punya business action penuh tetap perlu dicek navigasinya:

- reservations list
- departures
- in-house guests
- room board
- master kamar
- room types
- housekeeping
- billing
- guests
- inventory
- employees
- reports

Checklist:

1. buka menu dari sidebar
2. pastikan title, summary, dan metrics tampil
3. pastikan tidak ada error render
4. pastikan topbar dan profile dropdown tetap normal

Expected:

- halaman tampil clean
- tidak ada card informasi tambahan yang dulu sempat muncul

## 9. Checklist Error Yang Perlu Diperhatikan

Kalau ada masalah, cek poin ini:

- login gagal karena password sudah berubah
- frontend blank karena backend belum jalan
- profile update gagal karena email/username bentrok
- portal CMS gagal save karena field invalid
- arrival queue kosong karena sample reservation belum dibuat
- inquiry tidak muncul karena submit portal gagal

## 10. Cara Kembali ke Kondisi Fresh

Kalau testing sudah acak dan ingin reset:

```bash
cd backend
php artisan migrate:fresh --seed
```

Lalu restart backend kalau perlu:

```bash
php artisan serve
```

Frontend cukup tetap jalan atau restart:

```bash
cd frontend
npm run dev
```

## 11. Penutup

Kalau tujuan testing adalah validasi implementasi yang sekarang benar-benar ada, urutan paling aman adalah:

1. reset database
2. login admin
3. test shell admin
4. test profile info + password
5. test settings UI
6. test portal CMS
7. test guest portal + submit inquiry
8. test booking inquiries admin
9. test front desk arrivals phase 1
10. smoke test menu generic lain

Kalau semua langkah di atas lolos, berarti baseline flow utama yang sudah implemented saat ini sudah aman untuk dilanjutkan ke fase berikutnya.
