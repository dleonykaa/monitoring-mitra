# Architecture - SIMKM BPS

Sistem Informasi Manajemen Kinerja Mitra (SIMKM) untuk Badan Pusat Statistik (BPS).
Aplikasi monolitik full-stack berbasis Laravel dengan dukungan API mobile.

---

## Stack Ringkasan

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Blade Templates, Tailwind CSS 4, Vanilla JS |
| Database | MySQL (production), SQLite (testing) |
| Auth | Laravel Sanctum, Spatie Permission |
| Build | Vite 7, Laravel Vite Plugin |
| Export | Maatwebsite Excel |

---

## Backend

| Teknologi | Versi | Keterangan |
|-----------|-------|------------|
| PHP | ^8.2 | Runtime bahasa |
| Laravel | ^12.0 | Framework utama |
| Laravel Sanctum | ^4.3 | Auth API token + session web |
| Spatie Laravel Permission | ^6.25 | Role & permission management |
| Maatwebsite Excel | ^3.1 | Export/import file Excel |
| Laravel Tinker | ^2.10 | REPL interaktif (dev) |
| Laravel Pint | ^1.24 | Code formatter PHP |
| Laravel Sail | ^1.41 | Docker dev environment |

### Role Pengguna
- `admin` — Administrator sistem, termasuk monitoring survei lintas tim, peta wilayah, dan kinerja mitra
- `pegawai_bps` — Pegawai BPS
- `mitra` — Mitra/responden survei

### Arsitektur Routing
- **Web routes** — dashboard dan halaman berbasis sesi
- **API routes** — endpoint RESTful untuk client mobile (token Sanctum)

---

## Frontend

| Teknologi | Versi | Keterangan |
|-----------|-------|------------|
| Blade Templates | — | Templating engine Laravel (server-rendered) |
| Tailwind CSS | ^4.0 | Utility-first CSS framework |
| Axios | ^1.11 | HTTP client untuk AJAX request |
| Chart.js | ^4.4 | Visualisasi data & grafik dashboard |
| Vanilla JS | — | Interaksi DOM, dark mode (localStorage) |

Tidak menggunakan SPA framework (Vue/React). Semua halaman di-render server-side via Blade.

---

## Database

| Teknologi | Keterangan |
|-----------|------------|
| MySQL | Database utama production (`DB=simkm`) |
| SQLite | Fallback `.env.example` & in-memory saat testing |

Driver lain yang tersedia di `config/database.php`: MariaDB, PostgreSQL, SQL Server.

### Driver Lainnya (runtime)

| Komponen | Driver |
|----------|--------|
| Queue | Database |
| Cache | Database |
| Session | Database |
| Broadcast | Log (dev) |
| Redis | Opsional (cache/session) |

---

## Build & Tooling

| Teknologi | Versi | Keterangan |
|-----------|-------|------------|
| Vite | ^7.0 | Build tool & dev server |
| Laravel Vite Plugin | ^2.0 | Integrasi Vite dengan Laravel |
| Tailwind CSS Vite | ^4.0 | Plugin Tailwind untuk Vite |

---

## Testing

| Teknologi | Versi | Keterangan |
|-----------|-------|------------|
| PHPUnit | ^11.5 | Framework pengujian PHP |
| Faker | ^1.23 | Generasi data palsu untuk seeder |
| Mockery | ^1.6 | Mocking library |
| Nunomaduro Collision | ^8.6 | Error display yang lebih baik |

Test suite mencakup: modul Admin, Pegawai BPS, Mitra, dan alur API mobile.

---

## Fitur Utama Aplikasi

- **Manajemen Survei** — Buat, tugaskan, dan pantau survei
- **Monitoring Kinerja** — Pelacakan kinerja mitra
- **Multi-role Dashboard** — Portal berbeda per peran pengguna
- **Mobile API** — Endpoint terpisah untuk aplikasi mobile
- **Export Excel** — Ekspor laporan dan data
- **Notifikasi** — Pengingat deadline, progress harian, notifikasi pengajuan
- **Manajemen Wilayah** — Hierarki kecamatan dan kelurahan/desa
- **Audit Trail** — Log aktivitas pengguna

---

## Struktur Direktori Utama

```
laravel/
├── app/
│   ├── Http/Controllers/     # Controller web & API
│   ├── Models/               # Eloquent models
│   └── Notifications/        # Kelas notifikasi
├── config/                   # Konfigurasi Laravel
├── database/
│   ├── migrations/           # Skema database
│   └── seeders/              # Data awal
├── resources/
│   ├── views/                # Blade templates
│   ├── css/                  # Style sumber
│   └── js/                   # JavaScript sumber
├── routes/
│   ├── web.php               # Route web (session-based)
│   └── api.php               # Route API (token-based)
└── tests/                    # Unit & feature tests
```
