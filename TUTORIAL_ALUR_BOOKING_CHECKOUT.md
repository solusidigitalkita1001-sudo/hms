# Tutorial Alur Booking — Tamu sampai Checkout

> Dokumen ini menjelaskan alur lengkap dari tamu melakukan booking via portal sampai check-out,
> dan apa yang harus dilakukan Frontliner (resepsionis) di setiap tahap.

---

## 📋 Daftar Isi

1. [Ringkasan Alur](#1-ringkasan-alur)
2. [Sisi Tamu — Booking via Portal](#2-sisi-tamu--booking-via-portal)
3. [Sisi Frontliner — Arrival & Check-in](#3-sisi-frontliner--arrival--check-in)
4. [Sisi Tamu — During Stay](#4-sisi-tamu--during-stay)
5. [Sisi Frontliner — Check-out](#5-sisi-frontliner--check-out)
6. [Flow Exception](#6-flow-exception)
7. [API Endpoints per Tahap](#7-api-endpoints-per-tahap)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. Ringkasan Alur

```
TAMU (Portal)                  FRONTLINER (Admin)
─────────────────              ──────────────────

① Buka portal hotel           
② Cari kamar tersedia         
③ Isi form booking            
④ Dapat kode booking          
                               ⑤ Cari booking di Arrivals
                               ⑥ Assign kamar
                               ⑦ Verifikasi identitas
                               ⑧ Complete check-in
⑨ Lapor kondisi kamar         
⑩ Ajukan pinjaman aset        
                               ⑪ Prepare check-out
                               ⑫ Final bill
                               ⑬ Complete check-out
⑭ Selesai                     
```

---

## 2. Sisi Tamu — Booking via Portal

### 2.1. Akses Portal

**URL Portal:**
```
http://localhost:5173/portal/main
```

Tamu akan melihat halaman portal dengan:
- Hero section (judul, deskripsi, stats)
- Navbar dengan navigasi cepat
- Daftar kamar yang tersedia
- Fasilitas hotel
- Rekomendasi

### 2.2. Cari Kamar

Tamu bisa:
1. **Scroll ke section "Kamar tersedia"** — melihat semua room type dengan harga
2. **Klik "Inquiry kamar ini"** untuk mengirim permintaan booking
3. Atau akses langsung halaman search:
   ```
   http://localhost:5173/portal/main/search
   ```

**Form inquiry** akan muncul dengan data:
- Room type (dropdown)
- Jumlah tamu
- Nama lengkap
- No. WhatsApp
- Email
- Tanggal check-in / check-out
- Catatan tambahan

### 2.3. Booking Langsung (Walk-in oleh Frontliner)

Jika tamu datang langsung tanpa booking online:

1. Frontliner buka **Walk-in** di menu Front Desk Arrivals
2. Isi data tamu (nama, No HP, identitas)
3. Pilih room type & kamar
4. Proses check-in langsung

---

## 3. Sisi Frontliner — Arrival & Check-in

### 3.1. Login ke Admin

**URL:**
```
http://localhost:5173/login
```

**Kredensial:**
| Field | Value |
|---|---|
| Username | `admin` |
| Password | `password` |

### 3.2. Cari Booking Tamu

**Halaman: Arrivals**

```http
GET /api/v1/front-desk/arrivals?search={nama|kode_booking}
```

**Yang dilakukan Frontliner:**
1. Buka menu **Front Desk → Arrivals**
2. Cari tamu berdasarkan:
   - Kode booking (BK-20260618-XXXXXX)
   - Nama tamu
   - No. HP
3. Status yang muncul: `arrival_due`, `confirmed`, `reserved`

### 3.3. Assign Kamar

Setelah booking ditemukan, klik tombol **Assign Room**.

**Sistem akan:**
1. Lock kamar dengan `SELECT ... FOR UPDATE` (cegah double-booking)
2. Validasi kamar available, housekeeping clean, tidak maintenance
3. Update status kamar → `Reserved`
4. Buat `RoomAvailabilityLock`
5. Buat `ReservationCheckinSession`

**Yang dilakukan Frontliner:**
- Pilih kamar dari daftar assignable rooms
- Klik **Assign**

```http
GET  /api/v1/front-desk/arrivals/{reservation}/assignable-rooms
PATCH /api/v1/front-desk/arrivals/{reservation}/assign-room
Body: { "room_id": 1, "notes": "..." }
```

### 3.4. Verifikasi Identitas

Setelah room diassign, frontliner validasi KTP tamu.

**Yang dilakukan Frontliner:**
1. Cocokkan KTP fisik dengan data booking
2. Jika cocok, klik **Verify Identity**

```http
PATCH /api/v1/front-desk/arrivals/{reservation}/verify-identity
Body: { "id_verified": true }
```

Sistem akan:
- Update guest `identity_verified = true`
- Update `ReservationCheckinSession.id_verification_status = 'verified'`
- Lanjut ke step selanjutnya (registration / signature)

### 3.5. Complete Check-in

Setelah identitas terverifikasi, klik **Complete Check-in**.

```http
POST /api/v1/front-desk/arrivals/{reservation}/complete-check-in
```

**Sistem akan melakukan:**
1. Buat `StayRecord` (status `in_house`)
2. Update room → `Occupied`
3. Update reservation → `checked_in` dengan `checked_in_at = now()`
4. Buat `FrontDeskAuditLog`
5. Catat `ReservationStatusLog`
6. Update `RoomStatusLog`

### 3.6. Check-in Selesai

Setelah check-in:
- ✅ Tamu menerima kunci kamar
- ✅ Kamar terupdate jadi `Occupied`
- ✅ Stay record tercatat
- 🟡 Tamu bisa submit laporan kondisi kamar (30 menit window)
- 🟡 Tamu bisa pinjam aset hotel

---

## 4. Sisi Tamu — During Stay

### 4.1. Laporan Kondisi Kamar (Pre Check-in Report)

Tamu bisa lapor kondisi kamar dalam 30 menit setelah check-in.

**Akses via portal:**
```
http://localhost:5173/portal/main/my-bookings/{bookingId}/condition-report
```

Atau via halaman detail booking:
```
http://localhost:5173/portal/main/my-bookings/{bookingId}
```

**Yang dilaporkan tamu:**
- Kategori kerusakan (rusak ringan, kotor, dll)
- Deskripsi
- Foto

**Manfaat:** Melindungi tamu dari charge kerusakan yang sudah ada sebelumnya.

### 4.2. Peminjaman Aset Hotel

Tamu bisa pinjam aset selama menginap:
- Remote TV, adaptor, bantal extra, setrika, dll

**Yang dilakukan Frontliner:**
1. Buka menu **Reservations → pilih reservation**
2. Klik tab **Asset Loans**
3. Pilih aset yang dipinjam
4. Catat kondisi saat peminjaman

```http
GET  /api/v1/reservations/{reservation}/asset-loans
POST /api/v1/reservations/{reservation}/asset-loans
Body: { "loanable_asset_id": 1, "notes": "..." }
```

### 4.3. Extend Stay

Jika tamu ingin memperpanjang masa inap:

```http
POST /api/v1/stays/{stayRecord}/extend
Body: { "new_check_out_date": "2026-06-20", "additional_charge_per_night": 500000 }
```

**Yang dilakukan Frontliner:**
1. Buka reservation
2. Klik **Extend Stay**
3. Masukkan tanggal check-out baru
4. Set harga tambahan per malam (jika berbeda)

### 4.4. Room Move

Jika tamu pindah kamar:

```http
POST /api/v1/front-desk/reservations/{reservation}/move-room
Body: { "new_room_id": 5, "reason": "AC rusak" }
```

**Yang dilakukan Frontliner:**
1. Buka reservation
2. Klik **Move Room**
3. Pilih kamar baru
4. Sistem akan: release old room → reserve new room → update stay record

---

## 5. Sisi Frontliner — Check-out

### 5.1. Buka Halaman Departures

**Halaman:**
```
http://localhost:5173/front-desk/departures
```

```http
GET /api/v1/front-desk/departures?date=2026-06-18
```

**Yang terlihat:**
- Daftar tamu yang check-out hari ini
- Status kamar (occupied)
- Nama tamu, nomor kamar, booking code

### 5.2. Preview Bill (Sebelum Check-out)

Klik tombol **Preview Bill** untuk lihat tagihan akhir.

```http
GET /api/v1/front-desk/departures/{reservation}/preview
```

**Sistem akan menghitung:**
- Room charge (base rate × malam)
- Damage fee (jika ada)
- Late check-out fee (jika lewat 12:00)
- Lost keycard fee
- Tax 10%
- Total tagihan

### 5.3. Finalisasi Check-out

Jika setuju, klik **Complete Check-out**.

```http
POST /api/v1/front-desk/departures/{reservation}/complete-checkout
Body: {
    "room_inspected": true,
    "keycard_returned": true,
    "damage_fee_amount": 0,
    "payment_method_code": "cash",
    "payment_amount": 943500
}
```

**Sistem akan melakukan:**
1. ✅ **Final bill** — recalculate invoice (room charge + additional charges)
2. ✅ **Payment** — create payment record, update invoice
3. ✅ **Stay record** — update status jadi `checked_out`
4. ✅ **Reservation** — update status jadi `checked_out`
5. ✅ **Room status** → `Available` (occupancy) + `Dirty` (housekeeping)
6. ✅ **Housekeeping task** — buat task `checkout_cleaning` priority `high`
7. ✅ **Audit log** — catat semua transisi
8. ✅ **Invoice** — final status `paid` / `partial` / `unpaid`

### 5.4. Setelah Check-out

**Housekeeping:**
- Lihat task cleaning di dashboard
- Bersihkan kamar
- Update housekeeping status → `Clean` atau `Inspected`

**Room jadi available lagi setelah:**
- `current_status = available`
- `housekeeping_status = clean` atau `inspected`
- `serviceability_status = normal`

---

## 6. Flow Exception

| Skenario | Aksi Frontliner | Endpoint |
|---|---|---|
| **No-show** | Mark no-show → release room + void invoice | `POST /.../mark-no-show` |
| **Extend stay** | Update check-out date + add charge | `POST /stays/{id}/extend` |
| **Room move** | Pindah ke kamar baru | `POST /.../move-room` |
| **Walk-in** | Create booking langsung + check-in | `POST /.../walk-in` |
| **Damage fee** | Tambah charge di checkout | `POST /.../complete-checkout` |
| **Lost keycard** | Tambah fee keycard | `POST /.../complete-checkout` |
| **Void invoice** | Void invoice + payments | `POST /invoices/{id}/void` |

---

## 7. API Endpoints per Tahap

### Fase 1 — Public (Tanpa Login)

| Metode | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/v1/portal/{code}` | Lihat portal hotel |
| POST | `/api/v1/portal/{code}/inquiries` | Kirim inquiry booking |
| GET | `/api/v1/public/rooms/search` | Cari kamar tersedia |
| GET | `/api/v1/public/rooms/{code}` | Detail room type |
| POST | `/api/v1/public/bookings` | Booking langsung |
| GET | `/api/v1/public/bookings/{code}` | Cek booking by kode |

### Fase 2 — Guest Auth (Token Tamu)

| Metode | Endpoint | Fungsi |
|---|---|---|
| POST | `/api/v1/guest/auth/login` | Login tamu (kode+HP) |
| GET | `/api/v1/guest/auth/me` | Profil tamu |
| GET | `/api/v1/guest/bookings` | Riwayat booking |
| GET | `/api/v1/guest/bookings/{id}` | Detail booking |

### Fase 3 — Admin (Auth API Token)

| Metode | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/v1/front-desk/arrivals` | Daftar arrival |
| PATCH | `/api/v1/front-desk/arrivals/{id}/assign-room` | Assign kamar |
| PATCH | `/api/v1/front-desk/arrivals/{id}/verify-identity` | Verifikasi identitas |
| POST | `/api/v1/front-desk/arrivals/{id}/complete-check-in` | Check-in |
| POST | `/api/v1/front-desk/walk-in` | Walk-in |
| GET | `/api/v1/front-desk/departures` | Daftar departure |
| GET | `/api/v1/front-desk/departures/{id}/preview` | Preview bill |
| POST | `/api/v1/front-desk/departures/{id}/complete-checkout` | Check-out |
| POST | `/api/v1/stays/{id}/extend` | Extend stay |
| POST | `/api/v1/front-desk/reservations/{id}/move-room` | Pindah kamar |
| POST | `/api/v1/front-desk/arrivals/{id}/mark-no-show` | No-show |

---

## 8. Troubleshooting

### Portal tidak muncul (404)

```bash
# Pastikan database sudah di-migrate dan di-seed
php artisan migrate:fresh --seed

# Pastikan Laravel jalan
php artisan serve
# Harusnya di http://127.0.0.1:8000

# Pastikan Vite jalan (terminal terpisah)
cd frontend && npm run dev
# Harusnya di http://localhost:5173
```

### Vite proxy 404

Cek di `frontend/vite.config.ts`:
```ts
proxy: {
  '/api': {
    target: 'http://127.0.0.1:8000',  // Sesuaikan port Laravel
    changeOrigin: true,
  },
}
```

### Login admin gagal

Kredensial default:
- Username: `admin`
- Password: `password`

Reset data:
```bash
php artisan migrate:fresh --seed
```

### Check-out gagal (422)

Cek:
1. Apakah reservation status `checked_in`?
2. Apakah ada StayRecord dengan status `in_house`?
3. Apakah business_date sudah di-set?

### "Could not establish connection" di console browser

⚠️ Itu dari Chrome extension (password manager / adblocker), bukan dari app. Aman diabaikan.
