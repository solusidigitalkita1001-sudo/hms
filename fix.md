# HMS — Review Admin & Insight Client Portal

> Dokumen ini merangkum hasil review sistem admin HMS (Hotel Management System) berbasis Laravel 12 + Vue 3, serta insight client/customer portal yang diturunkan dari hasil wawancara langsung dengan tamu hotel.

---

## Daftar Isi

1. [Review Sisi Admin](#1-review-sisi-admin)
2. [Gap & Risiko Kritis](#2-gap--risiko-kritis)
3. [Insight Client Portal](#3-insight-client-portal)
4. [User Journey dari Riset Lapangan](#4-user-journey-dari-riset-lapangan)
5. [Fitur Unik dari Riset](#5-fitur-unik-dari-riset)
6. [Best Practice Client Portal](#6-best-practice-client-portal)
7. [Endpoint API yang Perlu Disiapkan](#7-endpoint-api-yang-perlu-disiapkan)
8. [Roadmap Rekomendasi](#8-roadmap-rekomendasi)

---

## 1. Review Sisi Admin

### 1.1 Kondisi Umum

| Aspek | Status |
|---|---|
| Modul terdokumentasi | 11 modul dari requirement |
| Endpoint blueprint | 70+ endpoint terpeta |
| State machine | 4 (reservasi, kamar, invoice, HK) |
| Kesiapan scaffold | ~40% — fondasi OK, implementasi belum |

### 1.2 Yang Sudah Baik

**Arsitektur Laravel + Vue**
Pemisahan layer Application / Domain / Http / Models sudah benar. Backend sebagai pure REST API dengan frontend SPA adalah pilihan tepat untuk sistem operasional hotel.

**Blueprint API**
Endpoint sudah terpeta per modul dengan pola resource yang konsisten. Versioning `/api/v1`, response format seragam, dan query param standar sudah ada.

**State Machine**
Status untuk reservasi, kamar, invoice, dan housekeeping task sudah eksplisit — mengurangi ambiguitas di level bisnis.

**Theming & Settings**
Primary color, layout mode sidebar/navbar, dan branding masuk ke settings. Design token via CSS variable sudah direncanakan.

**Offline-first Asset**
Semua dependency frontend bundel lokal tanpa CDN. Sangat penting untuk hotel di lokasi dengan koneksi tidak stabil.

**RBAC**
6 role terdefinisi: Super Admin, Manager, Front Desk, Housekeeping, Finance, HR. Cukup untuk operasional hotel menengah.

### 1.3 Flow Admin yang Sudah Terpeta

```
Reservasi → Confirm → Check-in → Occupied → Check-out → Invoice → Payment → HK Task
```

**Flow exception yang BELUM ada:**
- Extend stay
- Room move
- No-show automation
- Night audit
- Split bill

---

## 2. Gap & Risiko Kritis

### 🔴 KRITIS — Harus diselesaikan sebelum production

#### 2.1 Tidak Ada `business_date` & Night Audit

Operasional hotel berputar di tanggal bisnis, bukan jam kalender. Tanpa ini, laporan harian, closing shift, dan tamu check-in lewat tengah malam akan menimbulkan selisih data.

**Solusi:**
- Tambahkan kolom `business_date` di tabel transaksi
- Buat flow `night_audit` sebagai titik kontrol penutupan hari
- Definisikan aturan rollover: jam berapa hari berganti secara bisnis

#### 2.2 Race Condition pada Availability

Dua petugas bisa memesan kamar yang sama dalam waktu bersamaan — potensi overbooking nyata tanpa mekanisme locking.

**Solusi:**
- Implementasi database-level locking saat pemilihan kamar (`SELECT ... FOR UPDATE`)
- Validasi ulang availability tepat sebelum `INSERT` reservasi
- Tambahkan `optimistic lock / version number` di tabel rooms

#### 2.3 Status Kamar Ambigu

`current_status` mencampur occupancy, housekeeping, dan maintenance. Kamar bisa *occupied* tapi ada maintenance — tidak ada aturan prioritas yang jelas.

**Solusi:**
- Pisahkan menjadi 3 dimensi: `occupancy_status` | `housekeeping_status` | `serviceability_status`
- Buat aturan prioritas yang jelas untuk UI dan logika availability

---

### 🟡 PENTING — Perlu segera direncanakan

#### 2.4 Audit Trail Tidak Lengkap

`room_status_logs` sudah ada, tapi histori perubahan reservasi, invoice, dan payment masih kosong.

**Solusi:**
- Tambahkan `reservation_status_logs`
- Tambahkan `invoice_status_logs` dan `payment_status_logs`
- Setiap transisi status wajib menyimpan: `user_id`, `timestamp`, `reason`

#### 2.5 Billing Invoice-Centric, Bukan Folio-Centric

Hotel aktif butuh folio/ledger per tamu: charge harian, laundry, split bill, koreksi tanpa merusak histori.

**Solusi:**
- Pertimbangkan konsep *guest folio* sebagai running ledger
- Posting transaksi berbasis mutasi (debit/credit)
- Pisahkan: accommodation charge, incidental charge, payment, adjustment

#### 2.6 Model Tamu Terlalu Sederhana

Satu reservasi hanya punya satu tamu — tidak bisa handle keluarga, tamu tambahan, atau compliance identitas semua penghuni.

**Solusi:**
- Tambahkan entitas `occupants` (guest per kamar per stay)
- Simpan data identitas semua penghuni, bukan hanya booker

---

### 🔵 DIREKOMENDASIKAN

#### 2.7 Cashier Session & Cash Control Belum Ada

Kontrol kas per shift tidak ada. Void payment, refund approval, dan rekonsiliasi kasir belum terpeta.

**Solusi:**
- Tambahkan `cashier_sessions` (opening/closing balance per shift)
- Flow approval untuk refund dan void invoice

---

## 3. Insight Client Portal

> Berdasarkan hasil wawancara langsung dengan calon tamu hotel.

### 3.1 Halaman & Modul yang Dibutuhkan

| # | Halaman | Prioritas | Keterangan |
|---|---|---|---|
| 1 | Pencarian Kamar | Fase 1 | Filter tanggal, tamu, tipe, harga |
| 2 | Booking Flow (Wizard) | Fase 1 | 3 langkah: kamar → data tamu → bayar |
| 3 | Konfirmasi & E-Ticket | Fase 1 | Booking code + QR + email/WA |
| 4 | My Booking Dashboard | Fase 2 | Login tamu, histori reservasi |
| 5 | Mobile Check-in | Fase 2 | Pre check-in sebelum tiba |
| 6 | Request Layanan In-Stay | Fase 2 | HK request, room service, dll |

### 3.2 Temuan Khusus dari Wawancara

**Booking "Untuk Orang Lain"**
Tamu menyebut opsi booking untuk diri sendiri atau orang lain secara eksplisit. Implikasinya:
- Booker ≠ tamu yang menginap
- Resepsionis harus bisa cari reservasi by nama booker ATAU nama tamu
- KTP yang divalidasi saat check-in adalah KTP tamu, bukan booker
- Form booking perlu 2 set data: data booker + data tamu (jika berbeda)

**Transparansi Waktu Tunggu Check-Out**
Tamu sadar harus menunggu verifikasi kondisi fasilitas. Yang dibutuhkan:
- Estimasi waktu verifikasi (SLA maks 15 menit)
- Status update jika tamu harus menunggu lebih lama

---

## 4. User Journey dari Riset Lapangan

### Phase 1 — Online Booking

```
Akses website
  └─ Isi parameter: tanggal CI/CO, jumlah dewasa, anak, kamar
      └─ Filter kamar: jumlah orang / available / harga
          └─ Lihat detail kamar: foto, luas (m²), fasilitas, smoking status
              └─ Isi form checkout:
              │    ├─ Nama sesuai KTP
              │    ├─ NIK
              │    ├─ No HP
              │    ├─ Opsi: untuk diri sendiri / orang lain
              │    └─ Preferensi: bed type, smoking/no-smoking, special request
              └─ T&C → Konfirmasi → Pilih metode bayar
                  └─ Terima booking code via email / WhatsApp ⚠️ [perlu ditambahkan]
```

### Phase 2 — Tamu Tiba di Hotel

```
Tamu datang ke resepsionis
  └─ Resepsionis cari booking (by code / nama / NIK / HP)
      └─ Validasi KTP fisik dengan data online
          └─ Peminjaman aset hotel (jika ada) ⚠️ [fitur baru]
              └─ Lapor kondisi kamar pre check-in ⚠️ [fitur baru]
                  └─ Check-in selesai
```

### Phase 3 — Check-Out

```
Tamu ke resepsionis
  └─ Inisiasi check-out
      └─ HK verifikasi kondisi kamar & fasilitas
          └─ Verifikasi pengembalian aset pinjaman ⚠️ [fitur baru]
              └─ Cek laporan kerusakan pre check-in
              │    └─ Jika ada kerusakan baru → charge ke invoice
              │    └─ Jika kerusakan sudah dilaporkan → tidak dicharge
              └─ Finalisasi invoice → Done
```

---

## 5. Fitur Unik dari Riset

### 5.1 Modul Peminjaman Aset Hotel

Tamu menyebutkan bisa meminjam aset hotel selama menginap (remote TV, adaptor, bantal extra, setrika, dll).

**Model Data yang Dibutuhkan:**

```
loanable_assets
  - id
  - name
  - description
  - total_stock
  - available_stock
  - condition_notes
  - is_active

asset_loans
  - id
  - reservation_id
  - asset_id
  - loaned_at
  - returned_at
  - return_condition (good / damaged / lost)
  - charge_amount (nullable)
  - staff_id (yang mencatat)
```

**Relasi:** `asset_loans` → `reservation` → `invoice` (jika ada charge kerusakan/kehilangan)

**Rekomendasi:** Integrasikan ke modul Inventori yang sudah ada, bukan modul baru terpisah.

---

### 5.2 Laporan Kondisi Kamar Pre Check-in oleh Tamu

Fitur proteksi dua arah — melindungi tamu dari charge yang tidak adil, dan melindungi hotel dari dispute.

**Flow:**
1. Setelah check-in, dalam window waktu tertentu (misal 30 menit), tamu submit laporan kondisi via portal
2. Konten laporan: foto + kategori (rusak ringan, kotor, dll) + deskripsi singkat
3. Laporan masuk ke housekeeping sebagai catatan dengan tanda "dilaporkan tamu"
4. Saat check-out: sistem cross-check otomatis — kerusakan yang ada di laporan pre check-in tidak bisa dicharge ke tamu

**Model Data yang Dibutuhkan:**

```
room_condition_reports
  - id
  - reservation_id
  - room_id
  - reported_by (guest)
  - report_time
  - window_expired_at (check-in + 30 menit)
  - items: [ { photo_url, category, description } ]
  - acknowledged_by (staff_id, nullable)
```

> **Catatan:** Ini adalah differentiator yang kuat. Jarang ada HMS lokal yang memiliki fitur ini.

---

### 5.3 Lookup Booking Resepsionis yang Optimal

Tamu datang menyebut nama — resepsionis perlu cari dengan cepat.

**Search harus bisa by:**
- Booking code
- Nama booker
- Nama tamu (jika berbeda dari booker)
- NIK
- Nomor HP

**Tampilan hasil:** foto tamu (jika ada), kamar yang dipesan, status bayar, dan highlight jika booking untuk orang lain: *"Dipesan oleh [nama booker]"*

---

## 6. Best Practice Client Portal

### 6.1 Booking Form

- **Jangan minta data berlebihan di awal.** Booking bisa selesai hanya dengan: tanggal, jumlah tamu, nama, NIK, HP. Email opsional. Jangan paksa buat akun sebelum booking selesai.
- **Price breakdown selalu terlihat.** Sticky summary di kanan (desktop) atau fixed bar di bawah (mobile): harga/malam × malam, tax, total. Update real-time saat tamu ubah tanggal.
- **Jangan reset form jika ada error.** Validasi inline per field. Highlight hanya field yang salah — jangan kosongkan seluruh form.
- **Booking confirmation wajib ke HP.** Mayoritas tamu di Indonesia lebih aktif di WhatsApp. Pertimbangkan WA notification via Fonnte atau WA Business API.

### 6.2 Halaman Detail Kamar

Informasi yang wajib ada (urutkan berdasarkan prioritas tamu, bukan abjad):

1. Foto (minimal 4 per kamar)
2. Harga / malam (jelas, termasuk tax)
3. Kapasitas (dewasa + anak)
4. Tipe bed
5. Status smoking / no-smoking
6. Fasilitas utama: WiFi, AC, kamar mandi dalam, TV
7. Luas kamar (m²)
8. Fasilitas lengkap (expandable)

**Badge availability:** "Tersisa 2 kamar" menciptakan urgensi yang sehat. Jika kamar habis, tampilkan kamar serupa — jangan blank.

### 6.3 Transparansi & Kepercayaan

- **Kebijakan pembatalan jelas sebelum bayar.** Tampilkan di halaman checkout, bukan hanya di T&C panjang. Format: *"Batalkan gratis sebelum [tanggal]"*
- **Kebijakan damage charge transparan.** Sertakan daftar aset yang bisa dipinjam + tarif ganti rugi estimatif di T&C.
- **T&C yang actionable.** Versi pendek (3-4 poin kunci) terlihat langsung. Versi lengkap di expandable/link terpisah. Checkbox "Saya setuju" disertai ringkasan poin terpenting.
- **Booking code mudah dicari.** Tamu harus bisa akses booking hanya dengan kode + nomor HP — tanpa perlu ingat email/password.

### 6.4 Mobile UX

- **Date picker nyaman di HP.** Gunakan bottom sheet calendar dengan swipe antar bulan. Tap dua tanggal (check-in → check-out) dalam satu gesture.
- **Keyboard yang tepat per field:**
  - NIK → `inputmode="numeric"`
  - No HP → `type="tel"`
  - Nama → `autocomplete="name"`
- **CTA tombol di thumb zone.** Tombol "Pesan" dan "Bayar" di bawah layar, mudah dijangkau ibu jari. Minimum tinggi tombol 48px.

---

## 7. Endpoint API yang Perlu Disiapkan

### Fase 1 — Tanpa Auth (Publik)

```
GET  /api/v1/public/rooms/search
GET  /api/v1/public/rooms/{id}
POST /api/v1/public/bookings
GET  /api/v1/public/bookings/{code}       ← lookup by kode + HP
```

### Fase 2 — Guest Auth

```
POST /api/v1/guest/auth/login
GET  /api/v1/guest/bookings
GET  /api/v1/guest/bookings/{id}
POST /api/v1/guest/check-in/pre
POST /api/v1/guest/room-condition-reports  ← laporan kondisi kamar
POST /api/v1/guest/asset-loan-requests
POST /api/v1/guest/service-requests
```

### Fase 3 — Payment Gateway

```
POST /api/v1/guest/payments/initiate
POST /api/v1/webhooks/payment             ← callback dari payment gateway
GET  /api/v1/guest/payments/{id}/status
```

> **Catatan keamanan:** Gunakan guard berbeda — `api` untuk admin, `guest-api` untuk client portal. Endpoint publik tidak boleh expose data internal (room_id internal, harga cost, dll).

---

## 8. Roadmap Rekomendasi

| # | Item | Prioritas | Fase |
|---|---|---|---|
| P1 | Tambah `business_date` + konsep night audit ke ERD | 🔴 Kritis | Sekarang |
| P2 | Pisahkan status kamar: occupancy / housekeeping / serviceability | 🔴 Kritis | Sekarang |
| P3 | Implementasi locking availability + validasi double-booking | 🔴 Kritis | Sekarang |
| P4 | Tambah tabel status logs (reservasi, invoice, payment) | 🟡 Penting | Sprint berikutnya |
| P5 | Selesaikan modul core admin: Auth → Dashboard → Kamar → Reservasi → Front Desk → Billing | 🟡 Penting | Fase 1 |
| P6 | Desain entitas `loanable_assets` + `asset_loans` | 🟡 Penting | Fase 1 |
| P7 | Desain entitas `room_condition_reports` | 🟡 Penting | Fase 1 |
| P8 | Siapkan endpoint publik client portal (search + booking tanpa auth) | 🔵 Direkomendasikan | Fase 1 |
| P9 | Flow exception: extend stay, room move, no-show automation | 🔵 Direkomendasikan | Fase 1 |
| P10 | Guest Portal UI: search + booking wizard + konfirmasi + e-ticket | ⚪ Lanjutan | Fase 2 |
| P11 | Guest auth + My Booking dashboard + mobile check-in | ⚪ Lanjutan | Fase 2 |
| P12 | Laporan kondisi kamar pre check-in (portal tamu) | ⚪ Lanjutan | Fase 2 |
| P13 | Payment gateway + OTA integration + cashier session | ⚪ Lanjutan | Fase 3 |

---

## Catatan Teknis Tambahan

- **Pisahkan guard middleware:** `api` untuk admin, `guest-api` untuk client portal.
- **Soft delete:** Semua tabel transaksi (reservations, invoices, payments) hanya soft delete. Master data pakai toggle `is_active`.
- **N+1 query di dashboard:** Gunakan eager loading + caching. Summary cards harus < 100ms.
- **Test coverage state machine:** Prioritaskan unit test untuk service layer state machine reservasi dan kamar — ini yang paling sering bug di HMS.
- **WhatsApp notification:** Lebih efektif dari email untuk tamu Indonesia. Integrasikan di queue yang sudah direncanakan.

---

*Dokumen ini dibuat berdasarkan: review repository HMS, dokumen Requirement, API Blueprint, ERD/Flow, dan hasil wawancara langsung dengan calon tamu hotel.*