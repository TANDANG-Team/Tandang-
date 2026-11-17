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

### 6. Buat tabel + data awal

```bash
php artisan migrate --seed
```

Perintah ini membuat semua tabel lalu mengisi data awal **minimal**: satu akun Admin dan paket Sponsored Project. Tidak ada akun Talenta/Penyedia, proyek, atau rating contoh — semuanya dibuat sendiri lewat aplikasi (lihat [Menguji alur dari awal](#menguji-alur-dari-awal)).

> Mau mengulang dari nol? `php artisan migrate:fresh --seed` (**semua data di database akan dihapus**).

### 7. Jalankan

```bash
php artisan serve
```

Buka **http://127.0.0.1:8000** di browser.

---

## Akun bawaan

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@tandang.id` | `admin12345` |

Hanya untuk development di laptop masing-masing. Akun lain dibuat lewat halaman **Daftar** (`/register`).

Kalau halaman Promosi Proyek menampilkan "Paket promosi belum tersedia" (database lama sebelum fitur Sponsored Project), jalankan `php artisan db:seed --class=PromotionPackageSeeder`.

## Menguji alur dari awal

1. **Daftar Penyedia** di `/register` (pilih Penyedia Proyek) → lengkapi Profil Usaha → **Buat Proyek** dan publikasikan.
2. **Daftar Talenta** di `/register` (pilih Talenta Muda) → lengkapi profil → unggah dokumen di menu **Verifikasi**.
3. Login **Admin** → menu Verifikasi → setujui dokumen Talenta. Talenta yang belum terverifikasi tidak bisa melamar.
4. Talenta mengisi **Asesmen Gaya Kerja** (opsional, untuk skor kecocokan) → **Cari Proyek** → lamar. Kuota pelamar maksimal 2 × jumlah talenta dibutuhkan; lamaran bisa dibatalkan maks. 3 kali per 7 hari.
5. Penyedia membuka **Pelamar** → terima/tolak. Lamaran yang diterima membuat catatan pengerjaan.
6. Saat pekerjaan selesai, salah satu pihak **mengajukan selesai** dan pihak lain **mengonfirmasi** (menu Penilaian Talenta / Riwayat & Penilaian). Setelah itu keduanya bisa saling memberi **rating** dalam 7 hari.
7. **Sponsored Project** hanya bisa dibeli Penyedia dengan rating minimal 4,0 dari minimal 3 penilaian Talenta. Di halaman pembayaran ada timer 5 menit; tombol **Simulasikan Pembayaran Berhasil** (hanya di luar production) mengaktifkan promosi 4 × 24 jam tanpa dihitung sebagai pendapatan. QRIS tim (`public/images/payment/qris-sponsor.png`) dan nama merchant-nya sudah terpasang bawaan, jadi tidak perlu diatur di `.env`. Pembayaran sungguhan lewat QR ini masuk ke rekening merchant tersebut, jadi untuk uji coba pakai tombol simulasi.

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
- **Kuota pelamar**: maksimal 2 × `talents_needed` lamaran *aktif* (menunggu, dalam review, diterima). Lamaran ditolak tidak dihitung, jadi slotnya terbuka lagi. Talenta yang pernah ditolak di suatu proyek tidak bisa melamar ulang ke proyek itu. Semua aturan ada di `app/Services/ProjectApplyService.php` dan dihitung dinamis dari `project_applications`.
- **Pembatalan lamaran**: talenta bisa membatalkan lamaran yang masih *menunggu* / *dalam review*, maksimal 3 kali dalam 7 hari terakhir (jendela bergulir). Lamaran yang dibatalkan membuka slot kuota, tetapi talenta itu tidak bisa mengajukan lagi ke proyek yang sama. Penyedia tidak bisa memproses lamaran yang sudah dibatalkan.
- **Skor kecocokan**: kompetensi 45%, gaya kerja 25%, lokasi 15%, jadwal 15%.

---

## Menjalankan test

```bash
php artisan test
```

Test memakai database SQLite di memori, jadi tidak mengganggu database `tandang_db`.

Uji race condition apply (`ProjectApplyConcurrencyTest`) menjalankan beberapa proses PHP sekaligus di file SQLite sementara. Untuk menguji juga di MySQL (membuat lalu menghapus database terpisah `tandang_apply_race_test`):

```bash
RACE_TEST_MYSQL=1 php artisan test --filter ProjectApplyConcurrencyTest
```
