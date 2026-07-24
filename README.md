# TeacherDesk Lokal Desktop v1.0

TeacherDesk Lokal Desktop adalah aplikasi web untuk pengajar yang berjalan di komputer sendiri. Aplikasi mengelola mata pelajaran, kelas, jadwal mengajar, materi, jurnal pelaksanaan, bank soal pilihan ganda empat opsi, generator soal berbasis materi, pencetakan soal, dan backup data.

Versi ini tidak memiliki halaman login. Aplikasi langsung membuka dashboard. Antarmuka dibuat khusus untuk layar desktop dengan lebar minimum 1.200 piksel dan tidak menyesuaikan tata letak untuk ponsel atau tablet.

## Teknologi

- PHP 8.1 atau lebih baru dengan ekstensi `pdo_mysql`
- MySQL 8 atau MariaDB 10.4 atau lebih baru
- HTML, CSS, dan JavaScript tanpa framework eksternal
- Tidak membutuhkan Composer, Node.js, akun pengguna, atau internet

## Fitur versi 1.0 Desktop

1. Akses langsung ke dashboard tanpa login.
2. Dashboard jadwal hari ini, progres materi, dan item yang perlu ditindaklanjuti.
3. CRUD mata pelajaran dan kelas.
4. Jadwal mengajar dengan deteksi bentrok waktu.
5. Materi pembelajaran dengan lampiran maksimal 5 MB.
6. Jurnal mengajar yang menandai jadwal sebagai terlaksana.
7. Bank soal pilihan ganda dengan empat opsi A, B, C, dan D.
8. Generator soal lokal berbasis kalimat dan istilah pada materi.
9. Penyaringan, pencarian, validasi status soal, dan cetak soal terpilih.
10. Backup dan restore data dalam format JSON.
11. Tata letak desktop-only tanpa menu seluler dan tanpa breakpoint responsif.

## Instalasi cepat dengan Laragon

1. Ekstrak folder `teacherdesk-local-desktop` ke `C:\laragon\www\`.
2. Jalankan Laragon.
3. Klik **Start All**.
4. Buka `http://localhost/teacherdesk-local-desktop/setup.php`.
5. Gunakan konfigurasi standar:
   - Host: `127.0.0.1`
   - Port: `3306`
   - Database: `teacherdesk_local`
   - User: `root`
   - Password: kosong
6. Klik **Pasang aplikasi**.
7. Klik **Buka aplikasi**. Dashboard langsung terbuka.

## Instalasi cepat dengan XAMPP

1. Ekstrak folder ke `C:\xampp\htdocs\teacherdesk-local-desktop`.
2. Aktifkan Apache dan MySQL dari XAMPP Control Panel.
3. Buka `http://localhost/teacherdesk-local-desktop/setup.php`.
4. Isi konfigurasi database lalu jalankan instalasi.

## Instalasi manual

1. Buat database MySQL bernama `teacherdesk_local`.
2. Impor `database/database.sql` melalui phpMyAdmin.
3. Salin `.env.example` menjadi `.env`.
4. Sesuaikan kredensial database di `.env`.
5. Buka `index.php` melalui server Apache.

## Catatan generator soal

Generator tidak menggunakan AI daring. Sistem memilih kalimat dari materi, menghapus satu istilah, lalu mengambil tiga istilah lain sebagai pengecoh. Semua hasil disimpan sebagai **draf** dan perlu diperiksa oleh pengajar.

Materi terbaik untuk generator memiliki beberapa paragraf lengkap, definisi dan istilah yang jelas, serta minimal empat istilah berbeda.

## Backup

Menu Backup mengunduh data tabel dalam format JSON. File lampiran tidak masuk ke JSON. Salin juga folder `storage/materials` saat membuat backup lengkap.

## Struktur folder

```text
teacherdesk-local-desktop/
├── app/                 Logika backend
├── assets/              CSS dan JavaScript
├── config/              Bootstrap dan koneksi database
├── database/            Skema dan data awal
├── includes/            Layout antarmuka
├── pages/               Halaman fitur
├── storage/materials/   Lampiran materi
├── index.php            Entry point aplikasi
├── print.php            Halaman cetak soal
└── setup.php            Installer database
```

## Catatan akses lokal

Aplikasi tetap memakai prepared statements, token CSRF, validasi upload, dan validasi input. Karena tidak ada autentikasi, setiap orang yang dapat membuka alamat aplikasi pada komputer atau jaringan lokal dapat mengubah data. Jangan mempublikasikan aplikasi langsung ke internet.
