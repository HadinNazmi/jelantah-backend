# Project Brief — Sistem Donasi Minyak Jelantah

## Latar Belakang
Project ini dikembangkan untuk keperluan magang, terpisah dari project skripsi BankJatah. Sistem ini dimodelkan mengikuti pola aplikasi SIMINAH (Sistem Informasi Minyak Jelantah) — aplikasi yang dikembangkan dosen PCR bekerja sama dengan PT KPI RU II Dumai (Pertamina) untuk program CSR "Sedekah Jelantah".

Scope pengembangan saat ini adalah versi awal/dasar — belum mencakup Machine Learning classification maupun sensor IoT (fitur-fitur itu ada di pengembangan lanjutan SIMINAH versi asli, bukan bagian dari scope sekarang).

## Requirement Utama
- Data harus disimpan di server sendiri (self-hosted) — bukan menggunakan BaaS cloud pihak ketiga seperti Supabase atau Firebase. Ini requirement dari perusahaan terkait kontrol data.
- Masyarakat wajib mendaftar akun menggunakan email aktif sebelum bisa menggunakan aplikasi.

## Tech Stack (Keputusan Final)
| Layer | Teknologi |
|---|---|
| Mobile (Donatur) | Flutter (native build) |
| Web (Pengelola & Manajemen) | Flutter Web dengan pendekatan PWA |
| Backend/API | Laravel |
| Database | MySQL |
| Auth | Laravel Sanctum + email verification |
| Storage foto bukti | Local storage di server sendiri (storage/app/public, bukan cloud pihak ketiga) |

Satu codebase Flutter digunakan untuk mobile dan web sekaligus (build target Android + Web/PWA), dikonsumsi lewat satu backend Laravel yang sama.

## Role & Alur Kerja
Total ada 3 role: 1 di mobile (Donatur), 2 di web (Pengelola & Manajemen).

### 1. Donatur / Masyarakat (Mobile — Flutter)
- Daftar akun pakai email aktif, login
- Melihat daftar lokasi donasi yang tersebar, lengkap dengan status buka/tutup sesuai jam operasional real-time
- Memilih lokasi, datang ke sana, menimbang jelantah sendiri menggunakan timbangan yang tersedia di lokasi
- Input manual angka hasil timbangan + upload foto bukti hasil penimbangan
- Melihat status pengajuan donasi (Pending → Verifikasi → Selesai)
- Melihat riwayat seluruh donasi beserta status masing-masing, dan total poin/kontribusi

### 2. Pengelola / Kelurahan (Web — Flutter Web/PWA)
- Mengelola lokasi: mengatur jam operasional (buka/tutup) per lokasi yang menjadi tanggung jawabnya
- Melihat daftar pengajuan donasi yang masuk di lokasinya (termasuk angka input dan foto bukti dari masyarakat)
- Memverifikasi tiap pengajuan — mencocokkan foto dan angka, lalu mengubah status dari Pending → Verifikasi → Selesai
- Melihat rekap donasi per lokasi

### 3. Manajemen / CSR (Web — Flutter Web/PWA)
- Dashboard agregat lintas semua lokasi (status buka/tutup semua titik, volume donasi masuk)
- Laporan agregat per periode
- Mengelola akun pengelola dan data lokasi (menambah lokasi baru, menetapkan pengelola ke lokasi tertentu)
- Monitoring keseluruhan poin/kontribusi masyarakat

## Status Donasi (State Machine)
1. Pending — pengajuan baru masuk (masyarakat sudah timbang, input angka, dan upload foto), menunggu dicek pengelola
2. Verifikasi — pengelola sudah mengecek dan mencocokkan data/foto, dinyatakan valid
3. Selesai — proses donasi tercatat final, poin/kontribusi ter-update, muncul di riwayat

## Urutan Pengembangan yang Direncanakan
1. Rancang skema database (ERD): tabel users (dengan role), lokasi, donasi, riwayat/poin
2. Setup project Laravel + koneksi MySQL + migration + Laravel Sanctum untuk auth (register dengan email, login)
3. Setup project Flutter + hubungkan ke API Laravel, uji alur register-login end-to-end
4. Bangun fitur inti Donatur: daftar lokasi, input donasi + upload foto, status, riwayat
5. Bangun fitur Pengelola: kelola lokasi, daftar pengajuan masuk, aksi verifikasi
6. Bangun fitur Manajemen: dashboard agregat, laporan, kelola akun pengelola & lokasi




# Arsitektur Project — Sistem Donasi Minyak Jelantah

Dokumen ini melanjutkan `project-brief-jelantah.md` dan `database-schema-jelantah.md`. Berisi keputusan struktur repository, struktur folder Flutter, dan catatan penting soal urutan deployment.

## Struktur Repository

Menggunakan 1 repo GitHub sebagai monorepo: `VibeProject` (github.com/HadinNazmi/VibeProject), berisi 2 folder project independen:

```
VibeProject/
├── jelantah-backend/     <- Laravel (API), PHP
└── jelantah-app/         <- Flutter (mobile APK + web PWA), Dart
```

Backend dan Flutter app dipisah folder karena beda bahasa/runtime sepenuhnya. Masing-masing di-commit terpisah (cd ke folder masing-masing sebelum `git add`/`git commit`), meski keduanya berada dalam satu repo GitHub yang sama.

Backend sudah berjalan di local development: PHP 8.3.30, Composer, XAMPP untuk MySQL (database `db_jelantah`), migration dasar Laravel sudah dijalankan dengan sukses.

## Kenapa Flutter Mobile & Web TIDAK Dipisah Repo

Flutter App (`jelantah-app`) adalah **satu codebase tunggal** yang di-build ke dua target berbeda:
- `flutter build apk` → Android app (untuk Donatur, didistribusikan lewat Play Store)
- `flutter build web` → PWA (untuk Pengelola & Manajemen, diakses lewat URL, bisa di-install ke home screen/desktop)

Karena sama-sama Dart dan berbagi codebase, model data, dan logic API yang sama, keduanya tetap satu folder/project/repo — bukan dipisah seperti halnya backend vs frontend.

## Struktur Folder Flutter (`jelantah-app/lib/`)

Folder dipisah berdasarkan audience dari awal, bukan disatukan lalu dicabang belakangan:

```
lib/
├── main.dart                   # entry point; cek kIsWeb untuk arahkan ke donatur/ atau admin/
│
├── core/                       # dipakai bersama oleh donatur/ dan admin/
│   ├── api/                    # api_client, auth_api, donasi_api, lokasi_api, dompet_api
│   ├── models/                 # user_model, donasi_model, lokasi_model, dompet_model
│   ├── services/                # auth_service, storage_service
│   └── router/                  # app_router.dart
│
├── donatur/                    # SEMUA halaman mobile (APK)
│   ├── auth/                   # login_page.dart, register_page.dart (donatur bisa daftar sendiri)
│   ├── pages/                  # lokasi_list, donasi_form, riwayat, dompet
│   └── widgets/
│
├── admin/                      # SEMUA halaman web (PWA) — untuk Pengelola & Manajemen
│   ├── auth/                   # login_page.dart — satu pintu login untuk pengelola & manajemen
│   │                           #   (bukan self-register; akun dibuat oleh manajemen)
│   ├── pengelola/
│   │   ├── pages/               # lokasi_kelola, donasi_masuk, verifikasi, rekap
│   │   └── widgets/
│   └── manajemen/
│       ├── pages/               # dashboard, laporan, kelola_pengelola, kelola_lokasi, konfigurasi_poin
│       └── widgets/
│
└── shared_widgets/              # widget umum lintas role (button, loading indicator, dll)
```

Prinsip pembagian:
- `core/` — logic pemanggilan API dan model data, satu implementasi dipakai oleh mobile maupun web, tidak diduplikasi
- `donatur/` — lengkap berisi auth, pages, dan widget khusus mobile; tidak pernah dipanggil saat build web
- `admin/` — lengkap berisi auth, pages, dan widget khusus web; tidak pernah dipanggil saat build APK; login satu pintu untuk pengelola & manajemen, tapi setelah login diarahkan ke dashboard sesuai role masing-masing

Pemisahan mobile vs web ini murni di level kode (folder dan branching `kIsWeb`), bukan di level repo — backend Laravel yang sama tetap melayani APK dan PWA sekaligus lewat REST API yang sama.

## Catatan Penting: Urutan Deployment

Backend WAJIB di-deploy ke server publik (bukan localhost) sebelum Flutter di-build final untuk distribusi. Selama development, Flutter memanggil backend lewat `http://127.0.0.1:8000` — alamat ini hanya bisa diakses dari laptop development sendiri, dan akan gagal total kalau APK sudah terpasang di HP orang lain atau PWA diakses dari device lain.

Urutan yang benar sebelum publish:
1. Deploy backend Laravel ke server publik (VPS/server perusahaan) — harus online 24/7, dapat domain/URL tetap (misal `https://api.jelantah-kpi.com`)
2. Ubah `baseUrl` di `core/api/api_client.dart` dari `127.0.0.1:8000` ke URL server tersebut
3. Baru build APK final untuk Play Store
4. Baru build web/PWA final untuk hosting

Backend harus tetap online selamanya setelah APK beredar, karena baik APK maupun PWA bergantung penuh pada backend yang sama sebagai satu-satunya sumber data.