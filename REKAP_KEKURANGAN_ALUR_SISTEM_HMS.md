# Rekap Kekurangan Alur Sistem HMS

## Tujuan

Dokumen ini merangkum kekurangan utama dari alur sistem pada `ERD_dan_Flow_Final_HMS.md` agar bisa dipakai sebagai bahan revisi desain, prioritas implementasi, dan mitigasi risiko sebelum sistem dipakai lebih jauh.

## Ringkasan

Secara fondasi, desain saat ini sudah cukup baik untuk memulai implementasi. Entitas inti sudah ada, alur utama sudah tergambar, dan state penting sudah mulai dipisahkan. Namun, masih ada beberapa celah yang cukup signifikan bila sistem ingin dipakai untuk operasional hotel yang benar-benar aktif.

Kekurangan paling terasa ada pada:

- audit trail yang belum lengkap
- kontrol konflik booking dan availability
- model billing yang belum cukup fleksibel
- alur operasional harian hotel seperti night audit
- pemisahan status kamar yang masih berpotensi ambigu
- flow exception seperti extend stay, room move, refund, dan approval

## Kekurangan Utama

### 1. Belum ada konsep business date dan night audit

Alur yang ada masih berorientasi ke waktu transaksi biasa, padahal operasional hotel sering bergantung pada tanggal bisnis, bukan sekadar jam kalender.

Dampaknya:

- tamu yang check-in lewat tengah malam bisa membingungkan pencatatan
- closing harian tidak punya titik kontrol yang jelas
- laporan okupansi, revenue, dan pembayaran per hari bisa meleset

Yang perlu ditambahkan:

- `business_date`
- flow `night_audit`
- aturan roll-over transaksi harian
- relasi antara tanggal bisnis dan shift/front desk closing

### 2. Belum ada mekanisme lock availability dan pencegahan race condition

Flow reservasi sudah menyebut cek availability lalu lock kamar, tetapi belum menjelaskan bagaimana mencegah dua staff memesan kamar yang sama pada waktu hampir bersamaan.

Dampaknya:

- potensi overbooking
- konflik status kamar
- hasil availability tidak konsisten saat trafik tinggi

Yang perlu ditambahkan:

- aturan locking saat pemilihan kamar
- validasi ulang availability saat submit
- strategi timeout lock
- penanganan konflik bila kamar sudah diambil user lain

### 3. Audit trail status penting belum lengkap

Dokumen sudah menekankan bahwa status penting harus eksplisit dan dapat diaudit, tetapi implementasi histori status yang benar-benar spesifik baru terlihat jelas pada `room_status_logs`.

Masih kurang histori khusus untuk:

- perubahan status reservasi
- perubahan status invoice
- perubahan status payment
- perubahan status maintenance
- approval dan override operasional

Risikonya:

- sulit melakukan investigasi insiden
- sulit melacak siapa mengubah apa dan kapan
- rawan dispute saat ada komplain atau selisih tagihan

Yang perlu ditambahkan:

- `reservation_status_logs`
- `invoice_status_logs`
- `payment_status_logs`
- mekanisme reason note untuk perubahan sensitif
- pencatatan user dan timestamp pada setiap transisi penting

### 4. Billing masih invoice-centric, belum folio-centric

Struktur saat ini cukup untuk kasus dasar, tetapi operasional hotel biasanya membutuhkan model tagihan yang berkembang selama tamu menginap.

Contoh kasus yang belum tertopang dengan nyaman:

- minibar, laundry, atau charge tambahan harian
- split bill
- company bill
- deposit dan refund parsial
- koreksi charge tanpa merusak jejak histori

Risikonya:

- invoice cepat menjadi rumit
- perubahan charge sulit ditelusuri
- fleksibilitas kasir/front office menjadi terbatas

Yang perlu dipertimbangkan:

- folio atau guest ledger
- posting transaksi berbasis mutasi
- pemisahan charge, payment, adjustment, refund

### 5. Model data tamu masih terlalu sederhana

Saat ini desain masih sangat berpusat pada satu guest utama di reservasi. Padahal di operasional nyata sering ada beberapa penghuni dalam satu reservasi atau satu kamar.

Kasus yang belum terwakili dengan baik:

- pasangan atau keluarga
- tamu tambahan
- occupant per kamar pada multi-room booking
- penggantian tamu utama
- pencatatan identitas semua penghuni

Risikonya:

- data tamu tidak lengkap
- compliance identitas tamu menjadi lemah
- laporan occupancy per orang tidak akurat

Yang perlu ditambahkan:

- entitas occupant atau guest stay detail
- relasi tamu per kamar/per stay
- pencatatan identitas penghuni aktual

### 6. Pemisahan status kamar masih berpotensi ambigu

Dokumen sudah mencoba memisahkan `current_status` dan `housekeeping_status`, tetapi state machine kamar masih mencampur aspek okupansi dengan kondisi operasional.

Contoh ambiguitas:

- kamar bisa occupied tapi juga sedang ada issue maintenance
- kamar vacant tapi dirty
- kamar available secara okupansi tetapi belum release dari housekeeping

Risikonya:

- konflik interpretasi antar modul
- dashboard operasional menampilkan status yang membingungkan
- logika availability bisa salah baca

Yang perlu dipertimbangkan:

- pisahkan occupancy status
- pisahkan housekeeping status
- pisahkan maintenance/serviceability status
- tetapkan aturan prioritas status untuk UI dan reservasi

### 7. Multi-property dan permission model belum matang

Beberapa tabel sudah memiliki `property_id`, tetapi belum jelas bagaimana akses user, role, dan scope otorisasi akan berjalan jika sistem berkembang ke multi-property penuh.

Pertanyaan yang belum terjawab:

- apakah role berlaku global atau per property
- apakah satu user bisa aktif di beberapa property
- bagaimana pembatasan data lintas property
- bagaimana approval lintas jabatan dan lintas properti

Risikonya:

- permission jadi sulit dirapikan di tengah jalan
- potensi kebocoran akses data antar properti
- refactor besar saat scale-up

Yang perlu ditambahkan:

- model role scope
- user-property access mapping
- kebijakan authorization per property

### 8. Flow kasir/front desk payment belum lengkap

Payment sudah ada, tetapi alur kasir belum mencakup kebutuhan operasional yang umum di lapangan.

Contoh gap:

- cash drawer per shift
- opening balance dan closing balance
- void payment
- refund approval
- selisih kas
- rekonsiliasi pembayaran per cashier/per shift

Risikonya:

- kontrol kas lemah
- sulit audit transaksi tunai
- rawan masalah saat pergantian shift

Yang perlu ditambahkan:

- cashier session atau shift cash control
- cash reconciliation flow
- approval flow untuk refund dan void

## Gap Alur Operasional

### 1. Extend stay dan shorten stay belum dijabarkan

Dokumen menyebut edit tanggal sebelum check-in, tetapi belum menjelaskan perubahan durasi saat tamu sudah menginap.

Yang perlu dijelaskan:

- cek ulang availability
- update harga dan invoice
- dampak ke housekeeping
- dampak ke room assignment

### 2. Room move belum punya flow utuh

Pindah kamar hanya disebut sebagai kasus tambahan, tetapi belum dijabarkan dampaknya terhadap data inti.

Yang perlu dijelaskan:

- perubahan `reservation_rooms`
- perubahan `stay_records`
- update status kamar lama dan baru
- charge tambahan bila upgrade/downgrade
- histori alasan pindah kamar

### 3. Expiry reservation dan no-show automation belum jelas

Sudah ada `expiry_at` dan state `no_show`, tetapi belum ada aturan otomatisasinya.

Yang perlu dijelaskan:

- kapan pending reservation expired
- kapan kamar dilepas kembali
- kapan no-show diproses otomatis
- siapa yang boleh override

### 4. Approval flow untuk tindakan sensitif belum ada

Masih belum terlihat jalur approval untuk tindakan yang secara bisnis berisiko.

Contoh tindakan sensitif:

- diskon besar
- refund
- void invoice
- waive damage fee
- override check-in/check-out
- perubahan harga manual

Yang perlu ditambahkan:

- approval matrix
- level otorisasi
- log alasan approval/rejection

## Risiko Bila Langsung Diimplementasikan

Kalau dokumen ini dipakai langsung tanpa penyesuaian tambahan, sistem masih cukup layak untuk:

- fase awal
- demo internal
- pilot terbatas

Tetapi akan mulai bermasalah saat dipakai untuk operasional aktif, terutama pada:

- overbooking
- audit pembayaran
- room move
- extend stay
- refund
- closing harian
- pelacakan histori perubahan status

## Prioritas Perbaikan

### Prioritas 1

- tambahkan `business_date` dan flow `night_audit`
- tambahkan mekanisme lock availability
- tambahkan status history khusus untuk reservasi, invoice, dan payment
- rapikan definisi status kamar

### Prioritas 2

- desain folio atau ledger billing
- tambahkan model occupant per stay/per room
- lengkapi flow extend stay, shorten stay, dan room move
- tambahkan approval flow untuk aksi sensitif

### Prioritas 3

- matangkan multi-property access model
- tambahkan cashier closing dan cash reconciliation
- tetapkan rule otomatis untuk expiry dan no-show

## Rekomendasi Praktis

Supaya implementasi tidak berat di awal tetapi tetap aman berkembang, pendekatan yang paling masuk akal adalah:

1. pertahankan struktur inti yang sekarang
2. tambahkan histori status dan kontrol transaksi lebih dulu
3. jangan tunda desain `business_date`
4. bedakan dengan tegas status okupansi, housekeeping, dan maintenance
5. siapkan flow exception sejak awal walau UI-nya bisa menyusul

## Kesimpulan

Dokumen alur saat ini sudah cukup kuat sebagai fondasi awal, tetapi masih belum lengkap untuk kebutuhan operasional hotel yang benar-benar berjalan setiap hari. Kekurangan paling penting ada pada auditability, kontrol konflik booking, fleksibilitas billing, dan flow operasional khusus hotel yang belum dimodelkan secara eksplisit.

Kalau kekurangan ini ditutup lebih awal, risiko refactor besar di tengah implementasi akan jauh berkurang.
