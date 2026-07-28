# 📚 TeacherDesk
**Versi: 1.4**

TeacherDesk adalah platform Sistem Manajemen Pembelajaran (LMS) dan portal edukasi komprehensif yang dirancang untuk mempermudah tugas pengajar serta meningkatkan pengalaman belajar siswa. Dengan pendekatan interaktif, TeacherDesk membantu dalam pengelolaan kelas, penugasan, dan evaluasi jalur pembelajaran (*Learning Path*) secara adaptif.

## ✨ Fitur Utama (Pembaruan di Versi 1.4)
- **Evaluasi Personal Learning Path**: Menerapkan sistem pemantauan adaptif untuk melihat perkembangan setiap siswa secara *real-time*.
- **Konsep Gamifikasi Terintegrasi**: Modul pembelajaran interaktif dengan elemen gamifikasi untuk meningkatkan keterlibatan, difokuskan pada materi Rekayasa Perangkat Lunak (RPL).
- **Manajemen Kelas & Penugasan**: Kemudahan dalam mengatur kelas, memberikan tugas terstruktur, dan menilai hasil kerja siswa berdasarkan model *Problem Based Learning* dan *Project Based Learning*.
- **Optimasi Database**: Struktur basis data yang telah dioptimalkan dengan *indexing* untuk pencarian dan pemrosesan data yang jauh lebih cepat.

## 🛠️ Teknologi yang Digunakan
TeacherDesk dibangun dengan memanfaatkan arsitektur modern:
- **Frontend**: [React.js](https://reactjs.org/) - Menyajikan antarmuka pengguna yang responsif, dinamis, dan menarik.
- **Backend**: [Laravel](https://laravel.com/) - Framework PHP tangguh untuk mengelola API dan logika bisnis.
- **Database**: [MySQL]((https://www.mysql.com/)) - Sistem manajemen database relasional dengan skalabilitas dan keandalan tinggi.

## 🚀 Instalasi & Persiapan

### Prasyarat
Pastikan sistem Anda telah terinstal:
- Node.js & npm (untuk *Frontend* React)
- PHP >= 8.1 & Composer (untuk *Backend* Laravel)
- PostgreSQL

### Langkah-langkah Menjalankan Proyek

1. **Clone Repositori**
   ```bash
   git clone https://github.com/Zidanman01/teacherDesk.git
   cd teacherDesk
   ```

2. **Pengaturan Backend (Laravel)**
   ```bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   ```
   *Konfigurasikan koneksi database MySQL pada file `.env`.*
   ```bash
   php artisan migrate
   php artisan serve
   ```

3. **Pengaturan Frontend (React.js)**
   ```bash
   cd ../frontend
   npm install
   npm run dev
   ```

## 🤝 Kontribusi
Jika Anda ingin berkontribusi dalam pengembangan TeacherDesk, silakan lakukan *fork* pada repositori ini dan ajukan *pull request* untuk setiap fitur baru atau perbaikan.

## 📄 Lisensi
Proyek ini didistribusikan di bawah lisensi MIT
