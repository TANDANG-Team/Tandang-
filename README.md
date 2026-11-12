# TANDANG

Platform yang mempertemukan **talenta muda** (pelajar, mahasiswa, fresh graduate) dengan **proyek nyata dari UMKM dan mitra**. Dibangun dengan Laravel 12.

Ada tiga peran pengguna:

| Peran | Yang bisa dilakukan |
|---|---|
| **Talenta Muda** | Verifikasi status, isi profil & portofolio, asesmen gaya kerja, cari/rekomendasi proyek, melamar, pantau status lamaran |
| **Penyedia Proyek** | Profil usaha, buat & kelola proyek, lihat profil + portofolio pelamar, terima/tolak pelamar |
| **Admin** | Verifikasi dokumen talenta, kelola pengguna, kelola proyek |

---

## Cara menjalankan project di laptopmu

### 1. Yang perlu di-install dulu

- **PHP 8.2 atau lebih baru** (paling mudah lewat [Laragon](https://laragon.org) atau XAMPP)
- **Composer**: https://getcomposer.org
- **MySQL** (sudah termasuk di Laragon/XAMPP)
- **Git**

Node.js/npm **tidak wajib**, karena halaman-halaman TANDANG memakai CSS biasa dari folder `public/css`.

### 2. Clone repo dan pindah ke branch yang benar

```bash
git clone https://github.com/TANDANG-Team/Tandang-.git
cd Tandang-
git checkout BismillahFront
```

### 3. Install dependency

```bash
composer install
```

### 4. Buat file `.env`

File `.env` berisi pengaturan lokal dan **tidak ikut di-push ke GitHub**, jadi setiap orang membuatnya sendiri dari contoh:

```bash
cp .env.example .env
php artisan key:generate
```

> Di Windows (CMD) pakai `copy .env.example .env`.

Buka `.env` lalu sesuaikan bagian database dengan MySQL di laptopmu. Bawaannya:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tandang_db
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Buat database

Buat database kosong bernama **`tandang_db`** (lewat phpMyAdmin / HeidiSQL / Laragon), atau lewat terminal:

```bash
mysql -u root -e "CREATE DATABASE tandang_db"
```

### 6. Buat tabel + isi data contoh

```bash
php artisan migrate --seed
```

Perintah ini membuat semua tabel dan mengisi akun & proyek contoh (lihat daftar akun di bawah).

> Mau mengulang dari nol? `php artisan migrate:fresh --seed` (**semua data di database akan dihapus**).

### 7. Jalankan

```bash
php artisan serve
```

Buka **http://127.0.0.1:8000** di browser.

---

## Akun contoh (dari seeder)

Hanya untuk development di laptop masing-masing.

| Peran | Email | Password | Catatan |
|---|---|---|---|
| Admin | `admin@tandang.id` | `admin12345` | |
| Penyedia | `penyedia@tandang.id` | `demo12345` | |
| Penyedia | `halo@kopisejenak.id` | `password123` | |
| Talenta | `budi.pratama@student.ui.ac.id` | `password123` | Terverifikasi, bisa melamar |
| Talenta | `ahmad.fauzi@alumni.telkomuniversity.ac.id` | `password123` | Terverifikasi |
| Talenta | `siti.nurhaliza@smkn1cimahi.sch.id` | `password123` | Verifikasi masih menunggu |
| Talenta | `rina.wijaya@gmail.com` | `password123` | Verifikasi ditolak |

Talenta yang statusnya belum terverifikasi tidak bisa melamar proyek; verifikasinya bisa disetujui dari akun Admin.

---

## Alur kerja Git bersama tim

Sebelum mulai mengerjakan, ambil perubahan terbaru dari teman:

```bash
git checkout BismillahFront
git pull origin BismillahFront
```

Setelah selesai:

```bash
git add .
git commit -m "pesan singkat perubahanmu"
git pull origin BismillahFront   # ambil perubahan teman dulu, selesaikan konflik kalau ada
git push origin BismillahFront
```

Kalau ada **migration baru** dari teman setelah `git pull`, jalankan:

```bash
php artisan migrate
```

---

## Yang sengaja tidak ada di GitHub

| File/folder | Kenapa | Cara mendapatkannya |
|---|---|---|
| `.env` | Berisi pengaturan & kunci rahasia tiap laptop | Langkah 4 |
| `vendor/` | Library Composer, ukurannya besar | `composer install` |
| Data di database | Tiap orang punya database lokal sendiri | `php artisan migrate --seed` |
| `storage/app/private/talent_documents/` | Dokumen verifikasi yang di-upload pengguna (data pribadi) | Muncul otomatis saat talenta meng-upload dokumen |
| `storage/logs/`, cache | File sementara | Dibuat otomatis oleh Laravel |

---

## Masalah yang sering muncul

| Error | Solusi |
|---|---|
| `No application encryption key has been specified` | `php artisan key:generate` |
| `SQLSTATE[HY000] [1049] Unknown database 'tandang_db'` | Database belum dibuat (langkah 5) |
| `SQLSTATE[HY000] [2002] ... connection refused` | MySQL belum dinyalakan (start di Laragon/XAMPP) |
| `Base table or view not found` | `php artisan migrate` |
| Tampilan tidak berubah setelah `git pull` | `php artisan optimize:clear` lalu refresh browser (Ctrl+F5) |
| Ikon tab masih logo Laravel | Cache browser, tekan Ctrl+F5 |

---

## Struktur penting

```
app/Http/Controllers/
  TalentController.php        → semua halaman Talenta Muda
  Penyedia/                   → halaman Penyedia (proyek, pelamar, profil, notifikasi)
  Admin/                      → halaman Admin (verifikasi, pengguna, proyek)
app/Services/
  WorkstyleMatchingService.php → asesmen gaya kerja & skor kecocokan proyek
  TalentEligibilityService.php → aturan kelayakan talenta muda
app/Models/                   → User, TalentProfile, Project, ProjectApplication, dll.
resources/views/
  talent/  penyedia/  admin/  layouts/
public/css/                   → CSS tiap area (talent.css, admin.css, dashboard.css, ...)
database/migrations/          → struktur tabel
database/seeders/             → data contoh
routes/web.php                → semua route
```

### Aturan bisnis yang perlu diketahui

- **Status lamaran**: status di sisi penyedia (`project_applications`) adalah sumber utama dan otomatis disalin ke tabel `applications` yang dibaca dashboard talenta.
- **Kuota proyek**: proyek otomatis disembunyikan dari katalog talenta & halaman depan ketika jumlah pelamar *diterima* sudah mencapai `talents_needed`. Kalau ada yang dibatalkan, proyek tampil lagi.
- **Skor kecocokan**: kompetensi 45%, gaya kerja 25%, lokasi 15%, jadwal 15%.

---

## Menjalankan test

```bash
php artisan test
```

Test memakai database SQLite di memori, jadi tidak mengganggu database `tandang_db`.
