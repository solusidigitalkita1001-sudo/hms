# Booking WPA Workspace

Workspace ini berisi fondasi awal WPA untuk Sistem Manajemen Hotel / Homestay dengan pendekatan terpisah antara backend API dan frontend modern.

## Struktur Workspace

- `backend` untuk Laravel API
- `frontend` untuk Vue + TypeScript SPA
- `Requirement_Final_HMS.md` untuk requirement final
- `ERD_dan_Flow_Final_HMS.md` untuk desain data dan flow
- `API_Blueprint_dan_UI_Page_Map_HMS.md` untuk blueprint API dan UI

## Arah Arsitektur

- Backend memakai Laravel 12
- Frontend memakai Vue 3 + TypeScript + Vite
- Semua dependency dibundel lokal tanpa CDN
- Struktur backend dipisah ke layer `Application`, `Domain`, `Http`, dan `Models`
- Validasi request dilakukan melalui Form Request
- Setting UI disiapkan untuk primary color dan layout mode

## Endpoint Dasar yang Sudah Tersedia

- `GET /api/v1/health`
- `GET /api/v1/dashboard/summary`
- `GET /api/v1/settings`
- `PUT /api/v1/settings/ui`

## Menjalankan Backend

```bash
cd backend
composer install
php artisan migrate:fresh --seed
php artisan serve
```

## Menjalankan Frontend

```bash
cd frontend
npm install
npm run dev
```

## Verifikasi yang Sudah Dilakukan

### Backend

```bash
cd backend
./vendor/bin/pint
php artisan test
```

### Frontend

```bash
cd frontend
npm run build
```

## Catatan Scaffold

- Frontend sudah memiliki WPA shell modern dengan mode sidebar atau navbar
- Primary color dapat diubah langsung dari halaman settings
- Preference UI disimpan lokal agar tetap bekerja tanpa internet publik
- Backend menyiapkan baseline OOP dan secure coding untuk flow lanjutan
