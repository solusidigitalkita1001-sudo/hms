# Requirement Final Sistem Manajemen Hotel / Homestay

## 1. Ringkasan Produk

Produk yang akan dibangun adalah web app Sistem Manajemen Hotel / Homestay berbasis Laravel sebagai backend API, dengan frontend modern untuk operasional hotel. Sistem difokuskan untuk mendukung proses reservasi, front desk, manajemen kamar, housekeeping, billing, inventori, karyawan, dan laporan operasional dalam satu platform yang cepat, stabil, dan mudah digunakan.

## 2. Tujuan Produk

- Mempercepat proses operasional hotel dari reservasi sampai check-out
- Mengurangi double booking dan kesalahan pencatatan manual
- Menyediakan status kamar real-time
- Menyediakan billing dan pembayaran yang akurat
- Mendukung operasional housekeeping, inventori, dan karyawan
- Menyediakan laporan harian dan bulanan untuk manajemen
- Menyediakan UI modern, fleksibel, dan tetap cepat
- Menjamin aplikasi tidak bergantung pada CDN agar tetap dapat diakses tanpa internet publik

## 3. Keputusan Arsitektur

### 3.1 Backend

- Framework utama: Laravel
- Pola aplikasi: REST API backend
- Auth: token/session sesuai kebutuhan panel internal
- Business logic ditempatkan pada service layer
- Queue dipakai untuk proses non-blocking seperti notifikasi dan pembuatan laporan berat

### 3.2 Frontend

- Pendekatan frontend: SPA modern berbasis Vue atau React
- Semua dependency frontend wajib diinstal lokal melalui package manager
- Semua asset dibundel lokal dan disajikan dari server aplikasi
- Dilarang menggunakan CDN untuk CSS, JS, font, icon, atau library UI

### 3.3 Prinsip Teknis

- Cepat untuk operasional harian
- Mudah dikustom dari sisi branding dan layout
- Role-based access
- Offline-friendly pada level asset dan akses internal
- Mudah dikembangkan bertahap per modul

## 4. Prinsip UI/UX

### 4.1 Arah Desain

- Modern, clean, premium, dan smooth
- Fokus pada keterbacaan, kecepatan input, dan efisiensi operasional
- Visual memakai card layout, spacing lega, badge status yang jelas, dan feedback aksi yang tegas

### 4.2 Kustomisasi

- Primary color dapat diubah dari menu settings
- Layout menu dapat dipilih antara sidebar atau navbar
- Branding dapat menampung logo, nama properti, dan warna utama
- Semua perubahan tema harus memakai design token atau CSS variable, bukan hardcode warna di komponen

### 4.3 Aturan UX

- Animasi harus ringan dan singkat
- Tabel data besar wajib memakai pagination dan filtering server-side
- Form operasional harus cepat dan minim langkah
- Status penting harus langsung terlihat tanpa membuka detail
- Responsive minimal untuk desktop dan tablet

## 5. Persyaratan Performa

- Sistem harus terasa cepat untuk pencarian booking, check-in, check-out, update status kamar, dan billing
- Dashboard harus memprioritaskan summary ringan, lalu memuat elemen berat seperti chart secara bertahap
- Search input harus memakai debounce
- Modul data besar wajib memakai pagination server-side
- Query database harus dioptimasi dan menghindari N+1 query
- Master data dan settings dapat di-cache
- Proses berat seperti notifikasi, export, sinkronisasi, dan report kompleks dipindahkan ke queue

## 6. Persyaratan Offline dan Tanpa CDN

- Seluruh library frontend wajib lokal
- Font wajib self-hosted
- Icon wajib lokal
- Tidak boleh ada dependency runtime dari internet publik
- Sistem harus tetap bisa dipakai ketika internet publik mati, selama server aplikasi dan jaringan internal masih tersedia
- Strategi offline lanjutan seperti asset cache dan service worker dapat dipertimbangkan pada fase berikutnya

## 7. Modul Inti Sistem

Modul yang akan dicakup mengacu pada dokumen awal, dengan penegasan tambahan pada modul yang sebelumnya belum lengkap.

### 7.1 Reservasi

- Buat reservasi baru
- Cek ketersediaan kamar
- Edit reservasi
- Batalkan reservasi
- Kelola deposit
- Notifikasi konfirmasi
- Support sumber booking: walk-in, telepon, website, OTA di fase lanjutan

### 7.2 Front Desk

- Check-in
- Check-out
- Walk-in guest
- Cetak kartu registrasi
- Room assignment
- Early check-in dan late check-out

### 7.3 Manajemen Kamar

- Master data kamar
- Tipe kamar
- Harga dasar
- Status kamar real-time
- Maintenance status
- Harga dinamis pada fase lanjutan

### 7.4 Housekeeping

- Daftar tugas housekeeping
- Assign petugas
- Update progres pembersihan
- Verifikasi kamar siap dipakai
- Pelaporan temuan kerusakan atau kebutuhan maintenance

### 7.5 Billing dan Pembayaran

- Invoice reservasi
- Invoice item detail
- Deposit
- Pembayaran parsial atau penuh
- Multi metode pembayaran
- Cetak invoice dan kwitansi
- Pengembalian deposit

### 7.6 CRM Tamu

- Profil tamu
- Riwayat menginap
- Preferensi tamu
- Catatan khusus tamu
- Repeat guest indicator

### 7.7 Inventori

- Master barang
- Kategori barang
- Stok masuk dan keluar
- Minimum stock alert
- Pemakaian barang oleh housekeeping atau operasional
- Riwayat mutasi stok

### 7.8 Karyawan

- Data pegawai
- Akun user dan role
- Shift kerja
- Absensi
- Payroll dasar pada tahap lanjutan jika diperlukan

### 7.9 Laporan dan Analitik

- Laporan harian
- Laporan bulanan
- Pendapatan
- Occupancy
- Daftar tamu menginap
- Laporan housekeeping
- Laporan inventori
- Laporan kehadiran karyawan

## 8. Kekurangan Dokumen Awal yang Sudah Diperbaiki Arahnya

Dokumen awal memiliki beberapa gap yang harus dianggap selesai di level requirement sebelum implementasi:

- Modul CRM belum rinci
- Modul inventori belum punya flow dan data model
- Modul karyawan belum punya batas fitur yang jelas
- Tabel invoice_items disebut tetapi belum didefinisikan
- Belum ada payment transactions yang rinci
- Belum ada audit log
- Belum ada definisi state transition yang rapi
- Ada inkonsistensi fase untuk OTA, guest portal, dan notifikasi

## 9. Flow Bisnis Inti

### 9.1 Flow Reservasi

1. Staff atau tamu membuat reservasi
2. Sistem cek availability kamar
3. Sistem menghitung total estimasi biaya
4. Reservasi disimpan dengan status awal
5. Deposit dicatat jika ada
6. Sistem menghasilkan kode booking unik
7. Sistem mengirim konfirmasi

### 9.2 Flow Check-in

1. Front desk mencari reservasi atau membuat walk-in booking
2. Sistem memverifikasi data tamu dan kamar
3. Front desk mengonfirmasi tagihan dan deposit
4. Kamar di-assign
5. Status reservasi berubah menjadi checked_in
6. Status kamar berubah menjadi occupied

### 9.3 Flow Check-out

1. Front desk membuka data tamu atau kamar
2. Housekeeping atau petugas memverifikasi kondisi kamar
3. Sistem menghitung tagihan final
4. Pembayaran akhir diproses
5. Deposit dikembalikan atau dipotong jika ada kerusakan
6. Status reservasi berubah menjadi checked_out
7. Status kamar berubah menjadi dirty
8. Tugas housekeeping dibuat

### 9.4 Flow Housekeeping

1. Tugas housekeeping dibuat dari proses check-out atau jadwal rutin
2. Tugas di-assign ke petugas
3. Petugas mengupdate status pekerjaan
4. Jika ditemukan masalah, dibuat catatan atau request maintenance
5. Setelah selesai diverifikasi, status kamar berubah menjadi available

### 9.5 Flow Inventori

1. Barang master didaftarkan
2. Stok masuk dicatat
3. Pemakaian barang dicatat saat operasional
4. Sistem memperbarui saldo stok
5. Sistem memberi alert saat stok minimum tercapai

### 9.6 Flow Karyawan

1. Data pegawai dibuat
2. Akun user dihubungkan ke pegawai jika perlu akses sistem
3. Shift dijadwalkan
4. Absensi dicatat
5. Rekap kehadiran dapat masuk ke laporan

## 10. State yang Wajib Jelas

### 10.1 Reservasi

- pending
- confirmed
- checked_in
- checked_out
- cancelled
- no_show

### 10.2 Kamar

- available
- reserved
- occupied
- dirty
- maintenance
- out_of_service jika dibutuhkan

### 10.3 Invoice

- draft
- unpaid
- partial
- paid
- refunded
- void

### 10.4 Housekeeping Task

- pending
- assigned
- in_progress
- completed
- verified

## 11. Entitas Data Minimum

### 11.1 Master

- properties jika multi-properti dibutuhkan
- room_types
- rooms
- guests
- employees
- users
- inventory_items
- inventory_categories

### 11.2 Transaksi

- reservations
- reservation_rooms jika nanti mendukung multi-room booking
- stays atau check_in_records bila diperlukan
- invoices
- invoice_items
- payments
- payment_transactions
- housekeeping_tasks
- maintenance_requests
- inventory_movements
- attendances
- shifts

### 11.3 Sistem

- settings
- activity_logs
- notifications

## 12. Hak Akses Dasar

- Super Admin: akses penuh
- Manager: semua modul operasional dan laporan
- Front Desk: reservasi, tamu, check-in, check-out, billing dasar
- Housekeeping: tugas kebersihan dan status kamar
- Finance: invoice, pembayaran, laporan keuangan, inventori sesuai izin
- HR atau admin karyawan: data pegawai, shift, absensi

## 13. Requirement Non-Fungsional

- UI cepat dan responsif
- Tidak bergantung pada CDN
- Aman untuk data tamu dan data internal
- Memiliki audit trail untuk aktivitas penting
- Mendukung backup dan recovery pada level deployment
- Mudah di-maintain dan mudah dikembangkan

## 14. Prioritas Pengerjaan yang Direkomendasikan

### Fase Implementasi Awal

- Auth dan role permission
- Settings dasar dan theme system
- Master kamar dan tipe kamar
- Reservasi
- Front desk check-in dan check-out
- Billing, invoice, dan payment dasar
- Housekeeping
- Inventori dasar
- Karyawan dasar
- Laporan harian inti

### Fase Lanjutan

- CRM lebih lengkap
- Harga dinamis
- Notifikasi lanjutan
- Payroll dasar
- Guest portal
- OTA integration
- Payment gateway

## 15. Kesimpulan

Sistem akan dibangun sebagai web app operasional hotel modern berbasis Laravel API dengan frontend modern, cepat, fleksibel, dan tanpa CDN. Fokus implementasi harus memastikan flow bisnis inti hotel benar, performa tinggi, dan pengalaman pengguna tetap smooth tanpa mengorbankan kecepatan kerja operasional.
