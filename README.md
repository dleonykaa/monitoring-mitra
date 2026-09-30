# SIMPROCA

**Sistem Monitoring Progres Pencacahan**: aplikasi web untuk memantau progres pencacahan survei BPS Kabupaten Kepulauan Seribu. SIMPROCA menangani dua metode pendataan dalam satu tempat:

- **PAPI**: mitra mengisi identitas ruta, variabel isian, dan foto bukti langsung di SIMPROCA.
- **CAPI**: progres diambil dari file CSV hasil scraping FASIH yang diimpor admin.

## Dokumentasi

| Dokumen | Isi |
| --- | --- |
| [penjelasan-proyek-aplikasi.md](penjelasan-proyek-aplikasi.md) | Latar belakang, tujuan, pengguna, fitur, alur kerja, dan manfaat aplikasi |
| [architecture.md](architecture.md) | Arsitektur kode, alur data, service, notifikasi, dan catatan teknis |
| [erd.md](erd.md) | Rancangan basis data: diagram ERD, relasi, dan kamus data |
| [use-case-dan-activity-diagram.md](use-case-dan-activity-diagram.md) | Use case diagram per aktor dan activity diagram alur utama |

---

## Fitur per peran

| Peran | Yang bisa dilakukan |
| --- | --- |
| **Admin** | Membuat survei PAPI/CAPI; mengatur form isian, alokasi ruta (manual per SLS atau impor Excel), dan checkpoint target bertahap; menjalankan dan menandai survei selesai; mengimpor data FASIH; mengelola pengguna; melihat daftar mitra dan log aktivitas |
| **Pegawai BPS** | Melihat dashboard, monitoring progres, daftar dan detail survei, serta data entri PAPI beserta ekspornya (lihat saja). Menerima notifikasi saat mitra mengirim entri |
| **Mitra** | Melihat survei yang ditugaskan (menu Daftar Survei), mengisi ruta PAPI (simpan draft lalu kirim beserta foto bukti), dan melihat entri yang sudah dikirim (menu Data Entri) |

Halaman utama:

- **Dashboard**: ringkasan seluruh survei, meliputi:
  - capaian survei berjalan dan status pendataan;
  - entri minggu berjalan;
  - capaian per survei beserta penanda survei yang tertinggal dari jadwal atau checkpoint;
  - progres per kecamatan dan desa;
  - mitra yang belum update dalam 3 hari.
- **Monitoring Progres**: rincian per survei, meliputi:
  - tabel bertingkat (kecamatan › desa › SLS, atau per pencacah);
  - grafik entri harian per minggu;
  - posisi terhadap checkpoint beserta mitra yang di bawah target;
  - ekspor Excel.
- **Data Entri PAPI**: tabel isian mitra dengan filter, pencarian, dan urutan kolom; detail entri berikut foto buktinya; ekspor Excel/CSV.

Notifikasi otomatis:

| Waktu | Isi | Penerima |
| --- | --- | --- |
| Saat terjadi | Penugasan survei baru | Mitra |
| Saat terjadi | Entri baru dikirim | Admin dan pegawai |
| Harian 08.00 | Pengingat memperbarui progres | Mitra dengan ruta yang belum selesai |
| Harian 08.15 | Pengingat 3 hari sebelum tenggat | Mitra yang belum mencapai target |
| Harian 08.20 | Peringatan capaian di bawah checkpoint | Mitra yang tertinggal |

---

## Teknologi

- Laravel 12 (PHP 8.2+) dan MySQL
- Blade dengan CSS dan JavaScript inline; **tidak memerlukan Node.js atau build frontend**
- Spatie Laravel Permission (hanya peran: `admin`, `pegawai_bps`, `mitra`)
- Maatwebsite Excel untuk impor dan ekspor
- Notifikasi berbasis database yang dikirim langsung, tanpa antrean
- Laravel Scheduler untuk pengingat otomatis
- PHPUnit untuk pengujian

---

## Instalasi

Prasyarat: PHP 8.2+ dengan ekstensi GD, Composer, dan MySQL.

1. Buat database MySQL kosong, misalnya `simproca`.
2. Salin konfigurasi dan atur koneksi database di `.env`:

   ```bash
   cp .env.example .env
   ```

   ```
   DB_CONNECTION=mysql
   DB_DATABASE=simproca
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. Pasang dependensi dan siapkan aplikasi:

   ```bash
   composer install
   php artisan key:generate
   php artisan migrate --seed   # tabel + data contoh
   php artisan storage:link     # wajib agar foto bukti tampil
   ```

   Sebagai gantinya, `composer run setup` menjalankan instalasi, key, migration (tanpa data contoh), dan `storage:link` sekaligus.

## Menjalankan

Jalankan dua proses berikut, masing-masing di terminal terpisah:

```bash
php artisan serve          # aplikasi di http://127.0.0.1:8000
php artisan schedule:work  # pengingat, peringatan checkpoint, dan sinkron target
```

Kalau Node.js tersedia, `composer run dev` menjalankan server, scheduler, dan log sekaligus.

Untuk server produksi:
- ganti `schedule:work` dengan cron yang menjalankan `php artisan schedule:run` setiap menit;
- atur `APP_DEBUG=false`;
- ganti semua kata sandi akun contoh.

---

## Akun contoh

Dibuat oleh seeder, dengan kata sandi `password123`:

| Peran | Email |
| --- | --- |
| Admin | admin@bps.go.id |
| Pegawai BPS | pegawai@bps.go.id |
| Mitra | mitra@bps.go.id |

Seeder juga membuat:
- akun pegawai dan mitra tambahan;
- data wilayah (kecamatan, desa/pulau, dan SLS) Kepulauan Seribu;
- survei PAPI **SUSENAS** beserta variabel, alokasi, entri (dengan foto contoh), dan checkpoint;
- survei CAPI **Sensus Ekonomi 2026**.

Data progres survei CAPI diisi dengan mengimpor CSV FASIH lewat halaman Monitoring.

---

## Pengujian

```bash
php artisan test --compact
```

Ada 64 skenario uji yang mencakup alur admin, pegawai, dan mitra, impor alokasi dan FASIH, checkpoint, notifikasi, pengingat terjadwal, login, dashboard, dan konsistensi data contoh. Tes memakai SQLite in-memory dan disk penyimpanan palsu, sehingga tidak menyentuh database maupun file aplikasi.

---

## Lisensi

Proyek ini dikembangkan sebagai tugas akhir (skripsi) dan tidak dilisensikan untuk penggunaan komersial.
