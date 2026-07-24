<<<<<<< HEAD
# TeacherDesk Lokal Desktop v1.3
=======
# TeacherDesk Lokal Desktop v1.0
>>>>>>> 85cbdfb528d424e34a863304cf21462377b59205

TeacherDesk Lokal Desktop adalah aplikasi web lokal untuk pengajar. Aplikasi mengelola mata pelajaran, kelas, jadwal mengajar, template jadwal mingguan, kalender mingguan, materi, jurnal pelaksanaan, bank soal pilihan ganda empat opsi, generator soal berbasis materi, pencetakan soal, dan backup data.

Versi ini tidak memiliki halaman login. Aplikasi langsung membuka dashboard. Antarmuka dibuat khusus untuk layar desktop dengan lebar minimum 1.200 piksel dan tidak menyesuaikan tata letak untuk ponsel atau tablet.

## Teknologi

- PHP 8.1 atau lebih baru dengan ekstensi `pdo_mysql`
- MySQL 8 atau MariaDB 10.4 atau lebih baru
- HTML, CSS, dan JavaScript tanpa framework eksternal
- Tidak membutuhkan Composer, Node.js, akun pengguna, atau internet

<<<<<<< HEAD
## Fitur versi 1.3 Desktop
=======
## Fitur versi 1.0 Desktop
>>>>>>> 85cbdfb528d424e34a863304cf21462377b59205

1. Akses langsung ke dashboard tanpa login.
2. Dashboard jadwal hari ini, progres materi, dan item yang perlu ditindaklanjuti.
3. CRUD mata pelajaran dan kelas.
4. Jadwal mengajar dengan deteksi bentrok waktu.
5. Kalender mingguan Senin sampai Minggu.
6. Template jadwal berulang berdasarkan hari dan jam.
7. Menyimpan jadwal yang sudah ada sebagai template dengan satu tombol.
8. Menerapkan beberapa template sekaligus untuk 1 sampai 52 minggu.
9. Pencegahan duplikasi dan bentrok saat template diterapkan.
10. Materi pembelajaran dengan lampiran maksimal 5 MB.
11. Jurnal mengajar yang menandai jadwal sebagai terlaksana.
12. Bank soal pilihan ganda dengan empat opsi A, B, C, dan D.
13. Generator soal lokal berbasis kalimat dan istilah pada materi.
14. Penyaringan, pencarian, validasi status soal, dan cetak soal terpilih.
15. Backup dan restore data dalam format JSON, termasuk template jadwal.
16. Tata letak desktop-only tanpa menu seluler dan tanpa breakpoint responsif.

## Cara menggunakan template jadwal

1. Buka menu **Jadwal Mengajar**.
2. Pada daftar jadwal lama, klik tombol **Template** untuk menyimpan jadwal tersebut sebagai pola mingguan.
3. Ulangi untuk tiga jadwal tetap Anda.
4. Pada bagian **Template jadwal mingguan**, centang template yang ingin dipakai.
5. Pilih minggu awal.
6. Isi jumlah minggu. Nilai dapat diatur dari 1 sampai 52.
7. Klik **Terapkan template**.

Sistem tidak membuat jadwal ganda. Jadwal yang sama akan dilewati. Jadwal yang bertabrakan dengan jadwal lain juga tidak dibuat dan akan dilaporkan.

Materi bawaan pada template bersifat opsional. Kosongkan materi jika materi berubah setiap pertemuan. Setelah jadwal dibuat, Anda tetap dapat memilih atau mengganti materi pada setiap jadwal.

## Pembaruan dari versi 1.2

Versi 1.3 menambahkan tabel `schedule_templates`. Aplikasi menjalankan migrasi kecil secara otomatis saat pertama kali dibuka.

Untuk memperbarui instalasi lama:

1. Buat backup database melalui menu **Backup**.
2. Simpan salinan file `.env` dan folder `storage/materials`.
3. Ganti file aplikasi lama dengan file versi 1.3.
4. Kembalikan `.env` dan folder lampiran.
5. Buka aplikasi seperti biasa. Tabel template akan dibuat otomatis.
6. Buka menu **Jadwal Mengajar** dan simpan tiga jadwal tetap sebagai template.

Jangan menjalankan `setup.php` pada database lama karena installer membuat ulang tabel dan data demonstrasi.

## Instalasi baru dengan Laragon

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

## Instalasi baru dengan XAMPP

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

## Backup

Menu Backup mengunduh seluruh data tabel dalam format JSON, termasuk template jadwal. File lampiran tidak masuk ke JSON. Salin juga folder `storage/materials` saat membuat backup lengkap.

## Struktur folder

```text
teacherdesk-local-desktop/
├── app/                 Logika backend dan migrasi database
├── assets/              CSS dan JavaScript
├── config/              Bootstrap dan koneksi database
├── database/            Skema dan data awal
├── includes/            Layout antarmuka
├── pages/               Halaman fitur
├── storage/materials/   Lampiran materi
├── index.php            Entry point aplikasi
├── print.php            Halaman cetak soal
└── setup.php            Installer database baru
```

## Catatan akses lokal

Aplikasi tetap memakai prepared statements, token CSRF, validasi upload, dan validasi input. Karena tidak ada autentikasi, setiap orang yang dapat membuka alamat aplikasi pada komputer atau jaringan lokal dapat mengubah data. Jangan mempublikasikan aplikasi langsung ke internet.
