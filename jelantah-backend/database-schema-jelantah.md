# Skema Database & Struktur Backend — Sistem Donasi Minyak Jelantah

Dokumen ini melanjutkan `project-brief-jelantah.md`. Berisi keputusan skema database dan struktur folder Laravel yang sudah final untuk mulai implementasi.

## Skema Database

### 1. `users` — data akun umum semua role
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED, PK | |
| name | VARCHAR(255) | |
| email | VARCHAR(255), UNIQUE | |
| email_verified_at | TIMESTAMP, NULLABLE | |
| password | VARCHAR(255) | hashed |
| role | ENUM('donatur','pengelola','manajemen') | |
| phone | VARCHAR(20), NULLABLE | |
| created_at, updated_at | TIMESTAMP | |

### 2. `lokasi` — titik-titik donasi
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED, PK | |
| nama | VARCHAR(255) | |
| alamat | TEXT | |
| koordinat | POINT, SRID 4326 | untuk fitur cari lokasi terdekat |
| jam_buka | TIME | |
| jam_tutup | TIME | |
| hari_operasional | VARCHAR(255) / JSON | |
| pengelola_id | BIGINT UNSIGNED, FK → users.id | satu lokasi = satu pengelola; hanya admin/manajemen yang boleh mengganti |
| status_aktif | BOOLEAN, DEFAULT true | |
| created_at, updated_at | TIMESTAMP | |

Status buka/tutup dihitung on-the-fly di backend (bukan kolom statis), berdasarkan jam_buka, jam_tutup, hari_operasional dibanding waktu sekarang.

### 3. `donasi` — pengajuan donasi (tabel inti)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED, PK | |
| user_id | BIGINT UNSIGNED, FK → users.id | donatur yang mengajukan |
| lokasi_id | BIGINT UNSIGNED, FK → lokasi.id | |
| jumlah_input | DECIMAL(8,2) | angka dari input donatur (hasil baca timbangan) |
| jumlah_terverifikasi | DECIMAL(8,2), NULLABLE | angka final setelah dicek pengelola |
| foto_bukti | VARCHAR(255) | path file di storage server sendiri |
| status | ENUM('pending','verifikasi','selesai') | |
| poin_diperoleh | INTEGER, NULLABLE | dihitung & dikunci permanen saat status jadi 'selesai', pakai rate yang aktif waktu itu |
| verified_by | BIGINT UNSIGNED, FK → users.id, NULLABLE | pengelola yang memverifikasi |
| verified_at | TIMESTAMP, NULLABLE | |
| created_at, updated_at | TIMESTAMP | |

### 4. `dompet_user` — akumulasi total kontribusi & poin
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED, PK | |
| user_id | BIGINT UNSIGNED, FK → users.id, UNIQUE | |
| total_kontribusi | DECIMAL(10,2), DEFAULT 0 | akumulasi liter/kg jelantah dari donasi selesai |
| total_poin | INTEGER, DEFAULT 0 | akumulasi poin |
| updated_at | TIMESTAMP | |

Di-update via Laravel Observer setiap kali status donasi berubah menjadi 'selesai' — bukan dihitung ulang (SUM) tiap kali halaman diakses.

### 5. `konfigurasi_poin` — riwayat rate konversi liter → poin
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED, PK | |
| liter_per_poin | DECIMAL(6,2) | contoh: 1.00 = 1 liter/poin, 2.00 = 2 liter/poin |
| berlaku_mulai | TIMESTAMP | kapan rate ini mulai berlaku |
| dibuat_oleh | BIGINT UNSIGNED, FK → users.id | admin/manajemen yang mengatur |
| created_at | TIMESTAMP | |

Tabel bersifat append-only — tiap perubahan rate insert baris baru (bukan update), sehingga rate lama tetap ada sebagai riwayat dan poin yang sudah didapat sebelumnya tidak ikut berubah saat rate diganti.

### 6. `data_masyarakat` — data khusus role donatur
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED, PK | |
| user_id | BIGINT UNSIGNED, FK → users.id, UNIQUE | |
| alamat | TEXT | |
| nomor_ktp | VARCHAR(20), NULLABLE | |
| created_at, updated_at | TIMESTAMP | |

### 7. `data_pengelola` — data khusus role pengelola
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED, PK | |
| user_id | BIGINT UNSIGNED, FK → users.id, UNIQUE | |
| nomor_kk | VARCHAR(20), NULLABLE | |
| jabatan | VARCHAR(100), NULLABLE | |
| created_at, updated_at | TIMESTAMP | |

### Relasi Antar Tabel
```
users (1) ──── (banyak) donasi              [user_id]
users (1) ──── (banyak) lokasi              [pengelola_id]
lokasi (1) ──── (banyak) donasi             [lokasi_id]
users (1) ──── (banyak) donasi              [verified_by]
users (1) ──── (1) dompet_user              [user_id]
users (1) ──── (banyak) konfigurasi_poin    [dibuat_oleh]
users (1) ──── (1) data_masyarakat          [user_id] (hanya role donatur)
users (1) ──── (1) data_pengelola           [user_id] (hanya role pengelola)
```

## Struktur Folder Laravel

```
app/
├── Models/
│   ├── User.php
│   ├── Lokasi.php
│   ├── Donasi.php
│   ├── DompetUser.php
│   ├── KonfigurasiPoin.php
│   ├── DataMasyarakat.php
│   └── DataPengelola.php
│
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── LokasiController.php
│   │   ├── DonasiController.php
│   │   ├── DompetController.php
│   │   ├── KonfigurasiPoinController.php
│   │   └── UserController.php
│   ├── Requests/
│   │   ├── RegisterRequest.php
│   │   ├── DonasiStoreRequest.php
│   │   ├── DonasiVerifikasiRequest.php
│   │   └── LokasiStoreRequest.php
│   └── Resources/
│       ├── UserResource.php
│       ├── LokasiResource.php
│       ├── DonasiResource.php
│       └── DompetResource.php
│
├── Observers/
│   └── DonasiObserver.php   # hitung & kunci poin, update dompet_user saat status jadi 'selesai'
│
├── Services/
│   ├── PoinService.php          # ambil rate aktif dari konfigurasi_poin, hitung poin
│   └── LokasiStatusService.php  # hitung status buka/tutup real-time
│
└── Policies/
    ├── DonasiPolicy.php   # pengelola hanya boleh verifikasi donasi di lokasi miliknya
    └── LokasiPolicy.php

database/
├── migrations/  (satu file per tabel di atas)
└── seeders/
    ├── UserSeeder.php
    └── KonfigurasiPoinSeeder.php  # rate awal, misal 1 liter = 1 poin

routes/
└── api.php
```

## Struktur Routing (`routes/api.php`)

**Public (tanpa login):**
- `POST /register`, `POST /login`, `POST /email/verify/{id}/{hash}`

**Auth required — semua role (Sanctum):**
- `POST /logout`, `GET /me`

**Role: donatur**
- `GET /lokasi`, `GET /lokasi/{id}` — lihat lokasi + status buka/tutup
- `POST /donasi` — ajukan donasi (+ upload foto)
- `GET /donasi`, `GET /donasi/{id}` — riwayat donasi milik sendiri
- `GET /dompet` — total kontribusi & poin sendiri

**Role: pengelola**
- `GET /pengelola/lokasi` — lokasi yang dipegang
- `PUT /pengelola/lokasi/{id}` — atur jam operasional
- `GET /pengelola/donasi` — pengajuan masuk di lokasinya
- `PUT /pengelola/donasi/{id}/verifikasi` — pending → verifikasi
- `PUT /pengelola/donasi/{id}/selesai` — verifikasi → selesai
- `GET /pengelola/rekap` — rekap lokasinya

**Role: manajemen**
- `apiResource /manajemen/lokasi` — full CRUD lokasi
- `apiResource /manajemen/pengelola` — kelola akun pengelola
- `GET /manajemen/dashboard` — rekap agregat semua lokasi
- `GET /manajemen/laporan` — laporan per periode
- `GET /konfigurasi-poin`, `POST /konfigurasi-poin` — lihat riwayat & set rate baru

## Catatan Proteksi Akses
Dua lapis proteksi digunakan:
1. **Middleware `role:xxx`** (custom middleware) — proteksi level route, cek role user secara umum
2. **Policy** (`DonasiPolicy`, dll) — proteksi level record, misal pengelola hanya boleh memverifikasi donasi di lokasi yang menjadi tanggung jawabnya, bukan lokasi pengelola lain

## Urutan Implementasi Selanjutnya
1. Buat migration untuk semua tabel di atas
2. Buat model + relasi Eloquent
3. Implementasi AuthController (register, login, verifikasi email) + Sanctum
4. Implementasi DonasiObserver + PoinService
5. Implementasi endpoint per role sesuai routing di atas
