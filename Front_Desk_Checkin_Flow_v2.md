# Front Desk Check-in Flow v2

## 1. Tujuan Dokumen

Dokumen ini melengkapi blueprint API dan ERD final dengan fokus khusus pada alur kedatangan tamu, verifikasi identitas, registrasi, tanda tangan, dan penyelesaian check-in. Tujuannya adalah menutup gap operasional dan legal yang belum tertangani pada implementasi saat ini.

Dokumen ini dipakai sebagai acuan untuk:

- desain database tambahan
- kontrak API front desk
- desain UI step-by-step check-in
- validasi bisnis dan compliance
- urutan implementasi bertahap

## 1.1 Batasan Konteks

Flow pada dokumen ini khusus untuk area operasional internal seperti front desk, reservation desk, dan dashboard internal.

Catatan penting:

- user CMS atau portal publik tidak menyatu dengan dashboard internal saat ini
- alur portal publik dan CMS tetap diperlakukan sebagai surface terpisah
- implementasi front desk tidak boleh mengasumsikan session, layout, atau navigation yang sama dengan portal publik
- perubahan di modul front desk harus menjaga pemisahan antara user publik dan user internal

## 2. Masalah yang Ingin Diselesaikan

Saat ini sistem baru kuat pada tahap pre-arrival inquiry. Flow setelah tamu datang ke properti belum cukup rinci untuk kebutuhan front desk harian.

Gap utama yang harus ditutup:

- belum ada arrival queue yang nyata
- belum ada guest master yang siap dipakai operasional
- belum ada verifikasi KTP atau passport yang tersimpan rapi
- belum ada registration card yang bisa direview dan ditandatangani
- belum ada consent house rules dan privacy
- belum ada stay record yang dibentuk saat check-in selesai
- belum ada audit trail check-in yang memadai

## 3. Prinsip Flow Check-in

- proses front desk harus cepat, jelas, dan dapat diaudit
- data identitas tamu utama wajib tervalidasi
- guest tambahan harus dicatat eksplisit, bukan hanya angka
- signature dan consent harus tersimpan sebagai bukti
- check-in tidak boleh selesai jika syarat minimum belum terpenuhi
- supervisor override boleh ada, tetapi wajib mencatat alasan

## 4. Definisi Status Utama

### 4.1 Reservation Status

- `reserved`
- `arrival_due`
- `arrived`
- `id_pending`
- `registration_pending`
- `ready_for_checkin`
- `checked_in`
- `no_show`
- `cancelled`

### 4.2 Room Operational Status

- `available`
- `reserved`
- `occupied`
- `dirty`
- `cleaning`
- `maintenance`
- `out_of_order`

### 4.3 Identity Verification Status

- `not_started`
- `pending_review`
- `verified`
- `rejected`
- `override_approved`

### 4.4 Registration Signature Status

- `not_requested`
- `waiting_signature`
- `signed`
- `declined`
- `manual_signed_scan_uploaded`

## 5. Flow Bisnis End-to-End

### 5.1 Sebelum Tamu Datang

- reservasi dibuat dari direct booking, OTA, corporate, walk-in draft, atau hasil konversi inquiry
- sistem menempatkan reservasi ke arrival queue sesuai tanggal check-in
- room assignment boleh sudah ditentukan atau dibiarkan pending

### 5.2 Tamu Datang ke Properti

- staff front desk membuka arrival queue
- staff mencari tamu berdasarkan:
  - booking code
  - nama tamu
  - nomor telepon
  - room number bila sudah assigned
- bila tidak ada reservasi, staff dapat membuat walk-in reservation draft

### 5.3 Arrival Review

- staff membuka detail reservasi
- staff memverifikasi:
  - tanggal stay
  - room type
  - jumlah tamu
  - status pembayaran atau guarantee
  - request khusus
- jika kamar belum assigned, kamar dipilih pada tahap ini
- jika kamar belum ready, reservasi tetap dapat ditandai `arrived` tetapi belum boleh `checked_in`

### 5.4 Verifikasi Identitas

- staff meminta KTP, passport, atau dokumen identitas lain
- staff mengisi atau memindai:
  - jenis identitas
  - nomor identitas
  - nama sesuai identitas
  - kebangsaan
  - tanggal lahir
  - gender
  - alamat
  - masa berlaku bila relevan
- sistem menyimpan bukti dokumen:
  - foto depan
  - foto belakang opsional
  - scan PDF opsional
- sistem mencatat:
  - siapa yang memverifikasi
  - kapan diverifikasi
  - metode verifikasi
- jika nama tamu pada booking tidak sama dengan identitas, staff wajib memilih alasan mismatch

### 5.5 Registrasi Tamu

- staff memastikan atau memperbarui data kontak
- staff melengkapi emergency contact bila diperlukan
- staff mengisi daftar tamu tambahan
- tiap tamu tambahan minimal menyimpan:
  - nama lengkap
  - kategori dewasa atau anak
  - hubungan dengan tamu utama
  - identitas bila diwajibkan policy properti

### 5.6 Review Billing dan Deposit

- staff melihat ringkasan:
  - rate plan
  - total room charge awal
  - deposit yang diwajibkan
  - payment method
  - status pelunasan
- jika properti mensyaratkan deposit sebelum check-in, sistem harus menahan proses completion sampai syarat terpenuhi atau ada override

### 5.7 Consent dan Signature

- sistem menampilkan registration card summary
- tamu menyetujui:
  - house rules
  - smoking policy
  - visitor policy
  - privacy dan consent pemrosesan data
  - policy biaya tambahan atau kerusakan
- tamu menandatangani secara:
  - digital di tablet
  - manual lalu dipindai
  - upload gambar tanda tangan bila alur memungkinkan

### 5.8 Final Check-in

- sistem memvalidasi syarat minimum:
  - reservasi valid
  - kamar assigned
  - identitas tamu utama verified
  - signature tersedia
  - consent tersimpan
  - deposit rule terpenuhi atau override tersedia
- jika lolos validasi:
  - reservation status menjadi `checked_in`
  - room status menjadi `occupied`
  - stay record dibuat
  - folio atau invoice aktif dibuka
  - audit log ditulis
  - keycard issuance dapat dicatat

## 6. Step UI yang Direkomendasikan

Check-in sebaiknya tidak dibuat sebagai satu form panjang. Gunakan stepper agar operasional front desk lebih jelas.

### 6.1 Screen Arrival Queue

Data utama:

- expected arrivals hari ini
- overdue arrival
- early arrival
- waiting room ready
- checked-in today

Filter:

- status
- property
- source
- room type
- search guest

Action utama:

- start check-in
- assign room
- mark arrived
- open walk-in

### 6.2 Screen Check-in Workspace

Langkah yang direkomendasikan:

- langkah 1: Reservation
- langkah 2: Identity
- langkah 3: Additional Guests
- langkah 4: Room and Billing
- langkah 5: Signature and Consent
- langkah 6: Review and Complete

### 6.3 Screen Registration Card Preview

Menampilkan:

- identitas tamu utama
- daftar tamu tambahan
- room dan tanggal stay
- policy acknowledgement
- signature block

### 6.4 Screen In-House Guest Detail

Setelah check-in selesai, data harus pindah ke layar in-house guest dengan informasi:

- current stay
- room
- balance
- notes
- ID verification status
- registration document
- history perubahan kamar atau extension

## 7. Desain Data Tambahan

Bagian ini melengkapi tabel `guests`, `reservations`, `reservation_rooms`, dan `stay_records` dari ERD final.

### 7.1 guests

Tambahan atau penegasan field:

- `identity_verified`
- `identity_verified_at`
- `identity_verified_by_user_id`
- `identity_verification_status`
- `full_name_on_id`
- `id_expired_at`
- `emergency_contact_name`
- `emergency_contact_phone`
- `is_blacklisted`
- `blacklist_reason`

### 7.2 guest_documents

Tujuan:

- menyimpan berkas identitas dan dokumen tamu

Kolom minimum:

- id
- guest_id
- document_type
- document_number
- issued_country
- expired_at
- file_front_path
- file_back_path
- file_scan_path
- verification_status
- verification_notes
- verified_by_user_id
- verified_at
- created_at
- updated_at

### 7.3 reservation_guests

Tujuan:

- menghubungkan satu reservasi dengan satu atau banyak tamu aktual

Kolom minimum:

- id
- reservation_id
- guest_id nullable
- full_name
- guest_role
- is_primary
- is_registered
- id_type
- id_number
- notes
- created_at
- updated_at

Catatan:

- `guest_role` dapat berupa `primary`, `companion`, `child`, `corporate_booker`
- field nama dan identitas tetap disimpan agar tidak hilang walau guest master belum lengkap

### 7.4 reservation_checkin_sessions

Tujuan:

- menyimpan proses check-in sebagai sesi kerja front desk

Kolom minimum:

- id
- reservation_id
- arrival_status
- current_step
- id_verification_status
- registration_status
- signature_status
- deposit_status
- override_reason nullable
- override_approved_by_user_id nullable
- started_by_user_id
- completed_by_user_id nullable
- started_at
- completed_at nullable
- created_at
- updated_at

### 7.5 guest_signatures

Tujuan:

- menyimpan signature tamu dan bukti consent

Kolom minimum:

- id
- reservation_id
- guest_id nullable
- reservation_guest_id nullable
- signature_type
- signed_name
- signed_at
- file_path
- consent_version
- consent_channel
- created_by_user_id
- created_at
- updated_at

### 7.6 stay_records

Penegasan tambahan field:

- `actual_check_in_at`
- `actual_check_out_at`
- `checked_in_by_user_id`
- `checked_out_by_user_id`
- `primary_guest_name_snapshot`
- `registration_signed`
- `registration_signed_at`

### 7.7 front_desk_audit_logs

Tujuan:

- menyimpan histori penting selama flow arrival dan check-in

Kolom minimum:

- id
- reservation_id nullable
- stay_record_id nullable
- action_type
- action_label
- actor_user_id
- payload_json
- happened_at
- created_at
- updated_at

Contoh `action_type`:

- `arrival_opened`
- `guest_matched`
- `identity_verified`
- `identity_rejected`
- `signature_captured`
- `room_assigned`
- `deposit_confirmed`
- `checkin_completed`
- `override_approved`

## 8. Relasi Data yang Direkomendasikan

- guests 1..n guest_documents
- reservations 1..n reservation_guests
- guests 1..n reservation_guests
- reservations 1..1 reservation_checkin_sessions aktif
- reservations 1..n guest_signatures
- reservations 1..n front_desk_audit_logs
- stay_records 1..n front_desk_audit_logs
- users 1..n reservation_checkin_sessions
- users 1..n front_desk_audit_logs

## 9. API Contract yang Direkomendasikan

### 9.1 Arrival Queue

- `GET /api/v1/front-desk/arrivals`
- `GET /api/v1/front-desk/arrivals/{reservation_id}`

Filter penting:

- `page`
- `per_page`
- `search`
- `status`
- `property_id`
- `date_from`
- `date_to`
- `source`
- `sort_by`
- `sort_direction`

Response data per row minimum:

- reservation id
- booking code
- guest name
- phone
- room type
- assigned room
- arrival date
- departure date
- guest count
- arrival status
- payment status
- room readiness

### 9.2 Walk-in Draft

- `POST /api/v1/front-desk/walk-ins`

Request minimum:

```json
{
  "property_id": 1,
  "full_name": "Nama Tamu",
  "phone": "0812xxxx",
  "check_in_date": "2026-04-08",
  "check_out_date": "2026-04-09",
  "adult_count": 2,
  "child_count": 0,
  "room_type_id": 3
}
```

### 9.3 Assign Room

- `PATCH /api/v1/front-desk/arrivals/{reservation_id}/assign-room`

Request:

```json
{
  "room_id": 12,
  "notes": "Near lobby"
}
```

### 9.4 Verify Identity

- `PATCH /api/v1/front-desk/arrivals/{reservation_id}/verify-identity`

Request minimum:

```json
{
  "guest": {
    "full_name": "Nama sesuai identitas",
    "id_type": "ktp",
    "id_number": "3174xxxxxxxxxxxx",
    "nationality": "ID",
    "birth_date": "1998-01-10",
    "gender": "female",
    "address": "Alamat lengkap"
  },
  "document": {
    "document_type": "ktp",
    "file_front_path": "uploads/guest-documents/ktp-front.jpg",
    "file_back_path": null
  },
  "verification": {
    "method": "manual",
    "notes": "Nama valid dan cocok dengan tamu utama"
  }
}
```

### 9.5 Save Additional Guests

- `PUT /api/v1/front-desk/arrivals/{reservation_id}/guests`

Request minimum:

```json
{
  "guests": [
    {
      "full_name": "Nama utama",
      "guest_role": "primary",
      "is_primary": true,
      "id_type": "ktp",
      "id_number": "3174xxxxxxxxxxxx"
    },
    {
      "full_name": "Nama pendamping",
      "guest_role": "companion",
      "is_primary": false,
      "id_type": null,
      "id_number": null
    }
  ]
}
```

### 9.6 Billing and Deposit Review

- `PATCH /api/v1/front-desk/arrivals/{reservation_id}/billing`

Request minimum:

```json
{
  "deposit_amount": 300000,
  "deposit_status": "received",
  "payment_method_id": 1,
  "notes": "Deposit cash diterima"
}
```

### 9.7 Capture Signature

- `PATCH /api/v1/front-desk/arrivals/{reservation_id}/signature`

Request minimum:

```json
{
  "signature_type": "digital_pad",
  "signed_name": "Nama Tamu",
  "file_path": "uploads/signatures/signature-123.png",
  "consent_version": "2026.04",
  "consent_channel": "front_desk_tablet"
}
```

### 9.8 Complete Check-in

- `POST /api/v1/front-desk/arrivals/{reservation_id}/complete-check-in`

Request minimum:

```json
{
  "confirm_identity_verified": true,
  "confirm_registration_signed": true,
  "confirm_terms_accepted": true,
  "issue_keycard": true,
  "notes": "Guest checked in normally"
}
```

Response minimum:

```json
{
  "success": true,
  "message": "Check-in berhasil diselesaikan",
  "data": {
    "reservation_id": 102,
    "stay_record_id": 88,
    "status": "checked_in",
    "room_number": "203",
    "checked_in_at": "2026-04-08T14:15:00+07:00"
  },
  "meta": {}
}
```

## 10. Rules dan Blocking Validation

Check-in tidak boleh selesai jika salah satu syarat ini belum terpenuhi:

- reservation belum valid
- kamar belum assigned
- tamu utama belum verified
- signature belum tersimpan
- consent belum disetujui
- jumlah tamu aktual belum diisi

Rule tambahan yang direkomendasikan:

- walk-in harus selalu membuat reservation record lebih dulu
- mismatch nama booking dan KTP wajib menyimpan alasan
- override wajib mencatat approver dan alasan
- perubahan setelah checked-in harus tetap membuat audit log

## 11. Desain Override Supervisor

Override dipakai bila operasional perlu tetap jalan, tetapi compliance minimum tetap ingin diawasi.

Kondisi override yang diperbolehkan:

- KTP belum bisa dipindai tetapi nomor identitas sudah diverifikasi manual
- signature digital gagal dan tamu menandatangani hardcopy
- deposit belum diterima tetapi ada approval manager

Field minimum:

- `override_reason`
- `override_type`
- `approved_by_user_id`
- `approved_at`
- `supporting_note`

## 12. Checklist Implementasi Bertahap

### Phase 1

- buat tabel `guests`
- buat tabel `reservation_guests`
- buat tabel `reservation_checkin_sessions`
- buat endpoint arrival queue
- buat endpoint assign room
- buat endpoint verify identity
- buat endpoint complete check-in tanpa signature digital lebih dulu

### Phase 2

- buat tabel `guest_documents`
- buat tabel `guest_signatures`
- tambah consent versioning
- tambah registration card preview
- tambah signature capture digital
- tambah supervisor override

### Phase 3

- tambahkan OCR dokumen identitas
- tambahkan audit dashboard untuk compliance
- tambahkan repeat guest verification history
- tambahkan notifikasi room readiness dan front desk alerts

## 13. Rekomendasi Implementasi UI

- halaman arrival queue default memakai datatable server-side
- detail arrival dibuka dalam drawer atau workspace page
- stepper check-in dibuat persistent agar progress tidak hilang saat refresh
- signature dibuat sebagai step terpisah, bukan modal kecil
- summary akhir harus printable

## 14. Kesimpulan

Flow check-in yang aman tidak cukup hanya dengan reservation dan room assignment. Sistem harus mencakup identitas tamu, registrasi, consent, signature, aktivasi stay, dan audit trail.

Dokumen ini menjadi baseline agar implementasi front desk tidak berhenti di level inquiry atau flow statis, tetapi benar-benar siap untuk operasional hotel sehari-hari.

## 15. Progress Implementasi

### 15.1 Yang Sudah Diimplementasikan

Pada fase fondasi backend, struktur awal untuk flow check-in sudah mulai dibuat agar implementasi berikutnya tidak lagi berangkat dari nol.

Perubahan yang sudah ada di codebase:

- migration fondasi check-in di `backend/database/migrations/2026_04_08_090000_create_checkin_foundation_tables.php`
- model `Guest`
- model `Reservation`
- model `ReservationGuest`
- model `StayRecord`
- model `ReservationCheckinSession`
- model `FrontDeskAuditLog`
- feature test fondasi relasi schema
- API front desk phase 1 untuk arrival queue, assign room, verify identity, dan complete check-in
- frontend internal arrival queue yang terpisah dari portal/CMS
- room picker assignable rooms untuk workflow assign room internal

### 15.2 Tabel yang Sudah Ditambahkan

- `guests`
- `reservations`
- `reservation_guests`
- `stay_records`
- `reservation_checkin_sessions`
- `front_desk_audit_logs`

### 15.3 Cakupan Fondasi yang Sudah Siap

- relasi reservasi ke tamu utama
- relasi reservasi ke room type dan assigned room
- relasi reservasi ke daftar tamu aktual
- relasi reservasi ke sesi check-in
- relasi reservasi ke stay record
- relasi stay record ke audit log front desk
- field identity verification dasar pada guest
- field registration signed dasar pada stay record
- field override approval dasar pada session check-in

### 15.4 Yang Belum Diimplementasikan

Bagian berikut masih belum ada dan menjadi target fase API atau fitur berikutnya:

- upload guest documents
- signature capture
- consent versioning
- supervisor override flow lengkap
- registration card preview
- business rule blocking validation end-to-end
- additional guests management yang lengkap
- walk-in draft frontend dan backend

### 15.5 Status Saat Ini

- fondasi schema: selesai untuk phase 1
- fondasi model OOP: selesai untuk phase 1
- kontrak API: sudah terdokumentasi
- implementasi API front desk phase 1: selesai
- frontend arrival queue internal phase 1: selesai

### 15.6 Catatan Arsitektur yang Harus Dijaga

- CMS user atau portal user tidak digabung dengan dashboard internal
- front desk, reservation, dan dashboard memakai konteks user internal terautentikasi
- portal publik dan CMS publik tetap dipisah dari alur operasional internal
