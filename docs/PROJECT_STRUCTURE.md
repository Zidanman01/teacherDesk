# Struktur Proyek TeacherDesk

Struktur ini memakai pendekatan **minimum-risk refactor**. File yang menjadi entry point publik tetap berada di root, sedangkan implementasi dipisahkan menurut tanggung jawabnya.

```text
teacherDesk/
├── api/                         # Implementasi endpoint HTTP/JSON
│   ├── chat.php
│   ├── generate-mcq.php
├── app/                         # Logika aplikasi, service, helper, dan database
│   ├── BackupService.php
│   ├── Database.php
│   ├── QuestionGenerator.php
│   ├── SchemaManager.php
│   ├── actions.php
│   ├── helpers.php
│   └── openRouterService.php
├── assets/
│   ├── css/                     # Gaya global dan layout halaman
│   └── js/                      # JavaScript global
├── config/                      # Bootstrap dan konfigurasi lingkungan
├── database/                    # Skema SQL dan ERD
├── docs/                        # Dokumentasi proyek
├── includes/                    # Header, sidebar, footer, dan partial layout
├── pages/                       # Konten halaman yang dimuat oleh index.php
├── storage/
│   ├── backups/                 # Arsip lokal bila digunakan
│   └── materials/               # Lampiran materi
├── vendor/                      # Dependensi Composer, jangan diedit manual
├── api_chat.php                 # Wrapper kompatibilitas ke api/chat.php
├── api_generate_mcq.php         # Wrapper kompatibilitas ke api/generate-mcq.php
├── index.php                    # Front controller aplikasi
├── print.php                    # Entry point cetak
└── setup.php                    # Installer lokal
```

## Aturan penempatan file

1. **`pages/` hanya untuk tampilan halaman.** Query sederhana untuk menyiapkan data tampilan masih boleh berada di halaman, tetapi proses simpan, hapus, ekspor, dan restore tetap ditangani melalui `app/actions.php` atau service.
2. **`app/` untuk logika bisnis.** Hindari HTML di folder ini.
3. **`api/` untuk respons JSON.** Endpoint harus melakukan validasi metode HTTP, input, dan penanganan kesalahan.
4. **`includes/` untuk komponen layout bersama.** Judul, topbar, sidebar, dan footer tidak perlu disalin ke setiap halaman.
5. **`assets/` untuk CSS dan JavaScript bersama.** Hindari menambah blok `<style>` atau `<script>` besar langsung di halaman baru.
6. **`storage/` hanya untuk data yang dihasilkan aplikasi.** Folder ini harus dapat ditulis oleh PHP dan tidak boleh berisi kode sumber.
7. **Root dijaga tipis.** Root hanya berisi entry point, konfigurasi proyek, dan wrapper kompatibilitas.

## Migrasi lanjutan yang disarankan

Migrasi berikut sebaiknya dilakukan terpisah agar mudah diuji:

- Pindahkan CSS/JavaScript inline dari halaman AI ke `assets/css` dan `assets/js`.
- Pisahkan `app/actions.php` menjadi handler per modul setelah pengujian otomatis tersedia.
- Tambahkan konstanta versi aplikasi terpusat agar nomor versi tidak ditulis berulang di beberapa file.
- Tambahkan pengujian smoke untuk halaman utama, ekspor backup, restore, dan endpoint AI.
