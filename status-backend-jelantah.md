# Status Backend — Sistem Donasi Minyak Jelantah

Dokumen ini melanjutkan `project-brief-jelantah.md`, `database-schema-jelantah.md`, dan `arsitektur-jelantah.md`. Berisi ringkasan status backend Laravel (`jelantah-backend`) yang sudah selesai secara fungsional, sebagai penanda sebelum mulai mengerjakan Flutter app (`jelantah-app`).

## Status: Backend Inti Selesai dan Teruji

Semua fitur backend yang direncanakan sudah diimplementasikan dan diuji satu per satu lewat Postman. Berikut ringkasannya.

## Setup & Infrastruktur
- Laravel terpasang di `jelantah-backend`, terhubung ke MySQL (database `db_jelantah`) via XAMPP
- Laravel Sanctum terpasang untuk autentikasi API berbasis token
- Repo GitHub terpisah dari Flutter app (bukan monorepo): `jelantah-backend`, branch `main`
- Fix penting: `bootstrap/app.php` sudah dikonfigurasi agar semua exception (termasuk error autentikasi) selalu mengembalikan response JSON untuk path `api/*`, bukan mencoba redirect ke halaman login berbasis web

## Database
7 tabel sudah dimigrasikan sesuai skema di `database-schema-jelantah.md`: `users` (dengan tambahan kolom `role` dan `phone`), `lokasi`, `donasi`, `dompet_user`, `konfigurasi_poin`, `data_masyarakat`, `data_pengelola`.

Catatan penyesuaian dari rencana awal: kolom lokasi untuk koordinat menggunakan `latitude` dan `longitude` (dua kolom desimal terpisah), bukan tipe data spasial `POINT` — karena `point()` sudah tidak tersedia langsung di Blueprint Laravel versi yang dipakai (Laravel 13). Pendekatan ini lebih portable dan tetap bisa mendukung fitur pencarian lokasi terdekat lewat perhitungan jarak manual di query.

## Model & Business Logic
- Eloquent Model dengan relasi lengkap untuk semua 7 tabel
- `DonasiObserver` — otomatis menghitung poin dan mengupdate `dompet_user` setiap kali status donasi berubah menjadi `selesai`, memakai rate aktif dari `konfigurasi_poin`
- `PoinService` — mengambil rate `liter_per_poin` yang sedang berlaku dan menghitung poin

## Endpoint API yang Sudah Ada dan Teruji

### Auth (`AuthController`)
- `POST /register` — registrasi donatur (self-service, otomatis membuat baris `data_masyarakat` dan `dompet_user`)
- `POST /login` — login dengan validasi role sesuai field `platform` (mobile/web) yang dikirim dari klien
- `POST /logout`, `GET /me` — memerlukan token (Sanctum)

### Lokasi (`LokasiController`)
- `GET /lokasi`, `GET /lokasi/{id}` — donatur, termasuk status buka/tutup real-time
- `GET /pengelola/lokasi`, `PUT /pengelola/lokasi/{id}` — pengelola atur jam operasional lokasinya sendiri (dengan proteksi kepemilikan)
- `GET /manajemen/lokasi`, `POST /manajemen/lokasi`, `PUT /manajemen/lokasi/{id}`, `DELETE /manajemen/lokasi/{id}` — CRUD penuh oleh manajemen

### Donasi (`DonasiController`)
- `POST /donasi` — donatur ajukan donasi (upload foto bukti via form-data, tersimpan di storage lokal server)
- `GET /donasi`, `GET /donasi/{id}` — riwayat donasi milik sendiri
- `GET /pengelola/donasi` — pengajuan masuk di lokasi pengelola
- `PUT /pengelola/donasi/{id}/verifikasi` — pending → verifikasi
- `PUT /pengelola/donasi/{id}/selesai` — verifikasi → selesai (memicu `DonasiObserver`)
- `GET /pengelola/rekap` — rekap donasi lokasi pengelola
- `GET /manajemen/dashboard` — dashboard agregat semua lokasi

### Dompet (`DompetController`)
- `GET /dompet` — donatur lihat total kontribusi dan poin miliknya

### Konfigurasi Poin (`KonfigurasiPoinController`)
- `GET /konfigurasi-poin` — riwayat rate liter/poin (manajemen)
- `POST /konfigurasi-poin` — set rate baru (append-only, tidak menimpa rate lama, sehingga poin yang sudah didapat tidak berubah retroaktif)

### User/Pengelola (`UserController`)
- `GET /manajemen/pengelola` — daftar akun pengelola
- `POST /manajemen/pengelola` — buat akun pengelola baru (sekaligus baris `data_pengelola`)
- `DELETE /manajemen/pengelola/{id}` — hapus akun pengelola

## Proteksi Akses
- Middleware `role:donatur` / `role:pengelola` / `role:manajemen` (custom, `CheckRole`) — proteksi level route
- Proteksi level record di controller — misalnya pengelola hanya bisa memverifikasi donasi atau mengubah jadwal di lokasi miliknya sendiri, dicek manual di dalam method controller (belum menggunakan Laravel Policy terpisah, cukup untuk scope saat ini)

## Alur yang Sudah Diverifikasi End-to-End via Postman
1. Registrasi donatur → login → dapat token
2. Manajemen membuat lokasi, assign pengelola
3. Donatur mengajukan donasi dengan foto bukti di lokasi tersebut
4. Pengelola melihat pengajuan masuk, memverifikasi (input jumlah aktual), menandai selesai
5. Poin otomatis terhitung dan tersimpan permanen di baris donasi, dompet_user ter-update
6. Donatur bisa melihat riwayat dan dompetnya sendiri
7. Manajemen bisa mengatur rate poin dan mengelola akun pengelola

## Langkah Selanjutnya
Backend inti sudah siap dipakai sebagai API untuk Flutter app. Langkah berikutnya adalah mulai membangun `jelantah-app` (Flutter), dengan struktur folder `donatur/` dan `admin/` seperti yang sudah dirancang di `arsitektur-jelantah.md`, dimulai dari halaman login yang memanggil endpoint `POST /login` di atas.

Catatan untuk development Flutter: base URL API saat ini masih `http://127.0.0.1:8000/api` (localhost) — ini hanya berfungsi selama development di komputer yang sama. Sebelum build APK/PWA final untuk distribusi, backend harus sudah di-deploy ke server publik terlebih dahulu (lihat catatan urutan deployment di `arsitektur-jelantah.md`).
