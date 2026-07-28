<div align="center">

# 📚 TeacherDesk

### Aplikasi manajemen pengajaran lokal dengan dukungan OpenRouter AI

[![Version](https://img.shields.io/badge/version-1.4.0-2563eb.svg)](#-pembaruan-versi-14)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.1-777BB4.svg?logo=php\&logoColor=white)](#-teknologi)
[![Database](https://img.shields.io/badge/database-MySQL%20%7C%20MariaDB-4479A1.svg?logo=mysql\&logoColor=white)](#-teknologi)
[![AI](https://img.shields.io/badge/AI-OpenRouter-111827.svg)](#-fitur-ai)
[![Interface](https://img.shields.io/badge/interface-desktop--only-0f766e.svg)](#-catatan-penggunaan)

**TeacherDesk v1.4** membantu pengajar mengelola mata pelajaran, kelas, jadwal, materi, jurnal, bank soal, serta konsultasi dan pembuatan soal menggunakan AI.

</div>

---

## 📖 Tentang TeacherDesk

TeacherDesk adalah aplikasi manajemen pengajaran berbasis web yang berjalan secara lokal melalui **Laragon** atau **XAMPP**. Aplikasi dibangun menggunakan **PHP native**, **MySQL/MariaDB**, HTML, CSS, dan JavaScript tanpa framework backend maupun frontend.

Data utama disimpan pada database lokal. Koneksi internet hanya diperlukan untuk fitur **Konsultan AI** dan **Generator Soal AI dari PDF** yang menggunakan OpenRouter.

Aplikasi ditujukan untuk penggunaan pribadi pada komputer pengajar, terbuka langsung tanpa halaman login, dan saat ini dioptimalkan untuk layar desktop.

---

## ✨ Pembaruan Versi 1.4

Versi 1.4 menambahkan:

* Konsultan Kurikulum AI menggunakan OpenRouter.
* Riwayat percakapan berdasarkan sesi.
* Generator soal pilihan ganda dari dokumen PDF.
* Pembuatan 10 soal dengan opsi A–D dan kunci jawaban.
* Parser PDF menggunakan `smalot/pdfparser`.
* Konfigurasi `OPENROUTER_API_KEY` melalui file `.env`.
* Endpoint API terpisah untuk chat dan generator soal.
* Generator soal lokal tetap tersedia tanpa AI daring.

Fitur versi 1.3 tetap dipertahankan, termasuk template jadwal mingguan, penerapan template selama 1–52 minggu, serta pemeriksaan jadwal duplikat dan bentrok.

---

## 🚀 Fitur Utama

### Dashboard

* Ringkasan mata pelajaran, kelas, materi, dan bank soal.
* Jadwal mengajar hari ini.
* Lima jadwal terdekat.
* Progres penyelesaian materi.
* Peringatan jurnal yang belum diisi.
* Peringatan soal yang masih berstatus draf.

### Manajemen Mata Pelajaran

* Nama mata pelajaran dan jenjang.
* Semester dan tahun ajaran.
* Kurikulum.
* Deskripsi.
* Capaian pembelajaran.
* Status aktif atau arsip.

### Manajemen Kelas

* Nama dan jenjang kelas.
* Sekolah atau lembaga.
* Ruang kelas.
* Jumlah siswa.
* Catatan.
* Status aktif atau arsip.

### Jadwal dan Template Mingguan

* Kalender pengajaran mingguan.
* Relasi jadwal dengan mata pelajaran, kelas, dan materi.
* Status terjadwal, terlaksana, ditunda, dibatalkan, atau diganti tugas.
* Deteksi bentrok waktu.
* Penyimpanan jadwal sebagai template.
* Penerapan beberapa template untuk 1–52 minggu.
* Jadwal duplikat dan bentrok otomatis dilewati.

### Materi Pembelajaran

Materi dapat berisi:

* mata pelajaran dan kelas tujuan;
* bab atau topik;
* judul;
* tujuan pembelajaran;
* isi materi;
* estimasi waktu;
* sumber referensi;
* status materi;
* lampiran maksimal 5 MB.

Format lampiran:

```text
PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, JPG, JPEG, PNG
```

### Jurnal Mengajar

* Materi yang disampaikan.
* Metode pembelajaran.
* Aktivitas kelas.
* Jumlah siswa hadir dan tidak hadir.
* Kendala.
* Respons siswa.
* Tindak lanjut.
* Refleksi pengajar.

### Bank Soal

* Soal pilihan ganda dengan empat opsi A–D.
* Kunci jawaban dan pembahasan.
* Kesulitan mudah, sedang, atau sulit.
* Level kognitif Bloom C1–C6.
* Status draf, ditinjau, disetujui, atau ditolak.
* Filter dan pencarian soal.
* Pencetakan soal terpilih beserta kunci jawaban.

### Generator Soal Lokal

Generator lokal bekerja tanpa internet atau layanan AI:

* menghasilkan 1–10 soal;
* mengambil istilah penting dari materi;
* membuat soal cloze atau isian istilah;
* menyediakan tiga pengecoh;
* mendukung pengeditan sebelum disimpan;
* menyimpan hasil sebagai draf ke bank soal.

> Hasil generator lokal harus ditinjau karena kualitas soal bergantung pada struktur materi sumber.

---

## 🤖 Fitur AI

### Konsultan Kurikulum AI

Konsultan AI dapat digunakan untuk:

* mengembangkan ide pembelajaran;
* menyusun aktivitas kelas;
* membahas strategi evaluasi;
* membantu pengembangan materi;
* memberikan masukan terkait kurikulum;
* menyimpan riwayat percakapan per sesi;
* menyalin jawaban AI;
* menghapus sesi percakapan.

Endpoint:

```text
api_chat.php
```

Model bawaan di dalam kode:

```text
nvidia/nemotron-3-ultra-550b-a55b:free
```

### Generator Soal AI dari PDF

Generator PDF:

* menerima berkas PDF;
* membaca teks menggunakan `smalot/pdfparser`;
* mengirim maksimal sebagian teks dokumen ke OpenRouter;
* menghasilkan tepat 10 soal;
* menghasilkan opsi A–D;
* menampilkan kunci jawaban.

Endpoint:

```text
api_generate_mcq.php
```

Model bawaan di dalam kode:

```text
openai/gpt-oss-20b:free
```

> Ketersediaan model gratis pada OpenRouter dapat berubah. Model dapat diganti melalui `app/openRouterService.php`.

---

## 💾 Backup dan Pemulihan

Backup JSON mencakup:

* mata pelajaran;
* kelas;
* template jadwal;
* jadwal;
* materi;
* jurnal;
* bank soal;
* pengaturan.

Lampiran tidak masuk ke file JSON. Salin folder berikut secara terpisah:

```text
storage/materials
```

---

## 🛠 Teknologi

| Komponen           | Teknologi                         |
| ------------------ | --------------------------------- |
| Backend            | PHP native                        |
| Antarmuka          | HTML, CSS, JavaScript             |
| Database           | MySQL atau MariaDB                |
| Database access    | PDO MySQL                         |
| Dependency manager | Composer                          |
| PDF parser         | `smalot/pdfparser`                |
| AI provider        | OpenRouter API                    |
| Local server       | Apache melalui Laragon atau XAMPP |

---

## 📋 Persyaratan Sistem

* PHP 8.1 atau lebih baru.
* Apache.
* MySQL atau MariaDB.
* Composer.
* Browser desktop modern.
* Internet dan API key OpenRouter untuk fitur AI.

Ekstensi PHP:

```text
pdo_mysql
curl
fileinfo
mbstring
json
```

---

## 📦 Instalasi dengan Laragon

### 1. Clone repository

Buka terminal di `C:\laragon\www`:

```bash
git clone https://github.com/Zidanman01/teacherDesk.git
cd teacherDesk
```

Repository juga dapat diunduh sebagai ZIP dan diekstrak ke:

```text
C:\laragon\www\teacherDesk
```

### 2. Instal dependensi

```bash
composer install
```

### 3. Jalankan Apache dan MySQL

Buka Laragon lalu klik:

```text
Start All
```

### 4. Jalankan installer

Buka:

```text
http://localhost/teacherDesk/setup.php
```

Konfigurasi standar Laragon:

```text
Host database : 127.0.0.1
Port          : 3306
Nama database : teacherdesk_local
Pengguna      : root
Kata sandi    : kosongkan
```

Klik **Pasang aplikasi**.

### 5. Tambahkan API key OpenRouter

Setelah installer selesai, buka file `.env` lalu tambahkan:

```env
OPENROUTER_API_KEY="masukkan_api_key_openrouter_anda"
```

Contoh `.env`:

```env
APP_NAME="TeacherDesk Lokal Desktop"
APP_URL="http://localhost/teacherDesk"
APP_TIMEZONE="Asia/Jakarta"

DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="teacherdesk_local"
DB_USER="root"
DB_PASS=""

OPENROUTER_API_KEY="masukkan_api_key_openrouter_anda"
```

Jangan mengunggah API key asli ke GitHub.

### 6. Buka aplikasi

```text
http://localhost/teacherDesk/
```

---

## 📦 Instalasi dengan XAMPP

Ekstrak proyek ke:

```text
C:\xampp\htdocs\teacherDesk
```

Kemudian jalankan:

```bash
cd C:\xampp\htdocs\teacherDesk
composer install
```

Aktifkan Apache dan MySQL, lalu buka:

```text
http://localhost/teacherDesk/setup.php
```

Setelah instalasi, tambahkan `OPENROUTER_API_KEY` ke file `.env`.

---

## 🗂 Struktur Proyek

```text
teacherDesk/
├── app/
│   ├── BackupService.php
│   ├── Database.php
│   ├── QuestionGenerator.php
│   ├── SchemaManager.php
│   ├── actions.php
│   ├── helpers.php
│   └── openRouterService.php
├── assets/
│   ├── css/
│   └── js/
├── config/
│   └── bootstrap.php
├── database/
│   ├── database.sql
│   └── ERD.md
├── includes/
├── pages/
├── storage/
│   └── materials/
├── .env.example
├── api_chat.php
├── api_generate_mcq.php
├── composer.json
├── index.php
├── print.php
└── setup.php
```

---

## 🧩 Tabel Riwayat AI

Modul Konsultan AI menggunakan tabel `chat_history`. Apabila tabel belum tersedia setelah instalasi, jalankan SQL berikut melalui phpMyAdmin:

```sql
CREATE TABLE IF NOT EXISTS chat_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(120) NOT NULL,
    role ENUM('user', 'assistant', 'system') NOT NULL,
    content LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chat_session (session_id),
    INDEX idx_chat_created_at (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
```

---

## 🔐 Keamanan

TeacherDesk v1.4 belum memiliki autentikasi pengguna.

* Gunakan hanya pada komputer tepercaya.
* Jangan membuka aplikasi langsung ke jaringan publik.
* Jangan mengunggah file `.env`.
* Jangan menulis API key di dalam kode sumber.
* Buat backup sebelum memperbarui aplikasi.
* Batasi akses `setup.php` setelah instalasi jika dipasang pada server bersama.

---

## ⚠️ Catatan Penggunaan

* Aplikasi terbuka langsung tanpa login.
* Antarmuka belum responsif untuk ponsel.
* Lebar layar yang disarankan minimal 1.200 piksel.
* Fitur AI membutuhkan internet.
* Generator PDF saat ini selalu membuat 10 soal.
* Hasil generator PDF belum otomatis tersimpan ke bank soal.
* Generator lokal dan generator PDF AI adalah dua modul berbeda.
* Backup JSON tidak menyertakan lampiran.
* Semua soal hasil generator tetap harus diperiksa pengajar.
* Model OpenRouter gratis dapat berubah atau berhenti tersedia.

---

## 🔄 Pembaruan dari Versi Sebelumnya

1. Unduh backup JSON.
2. Salin file `.env`.
3. Salin folder `storage/materials`.
4. Ganti file aplikasi dengan versi terbaru.
5. Kembalikan `.env` dan folder lampiran.
6. Jalankan:

```bash
composer install
```

7. Pastikan `OPENROUTER_API_KEY` tersedia.
8. Jangan menjalankan `setup.php` pada database lama kecuali ingin membuat instalasi baru.

---

## 🧪 Alur Penggunaan

1. Isi profil pengajar.
2. Tambahkan mata pelajaran.
3. Tambahkan kelas.
4. Tambahkan materi.
5. Buat jadwal atau template jadwal.
6. Isi jurnal setelah pembelajaran.
7. Buat dan tinjau soal.
8. Cetak soal yang telah disetujui.
9. Lakukan backup secara berkala.

---

## 🤝 Kontribusi

```bash
git checkout -b feature/nama-fitur
git commit -m "feat: menambahkan nama fitur"
git push origin feature/nama-fitur
```

Setelah itu, kirim pull request ke repository utama.

---

## 🗺 Rencana Pengembangan

* Pengaturan model OpenRouter dari halaman aplikasi.
* Penyimpanan soal PDF langsung ke bank soal.
* Pilihan jumlah soal, kesulitan, dan Bloom C1–C6 pada generator PDF.
* Dukungan CP, TP, dan ATP Kurikulum Merdeka.
* Ekspor DOCX dan PDF.
* Autentikasi dan manajemen pengguna.
* Antarmuka responsif.
* Migrasi tabel fitur AI secara otomatis.
* Statistik kualitas bank soal.

---

## 📄 Lisensi

Repository belum menyertakan file lisensi khusus. Tambahkan file `LICENSE` sebelum mendistribusikan proyek dengan ketentuan lisensi tertentu.

---

## 👨‍💻 Pengembang

Dikembangkan oleh **Zidanman01**.

```text
https://github.com/Zidanman01/teacherDesk
```

<div align="center">

**TeacherDesk v1.4 — pengelolaan pengajaran yang lebih terstruktur, lokal, dan praktis.**

</div>
