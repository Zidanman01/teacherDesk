# Relasi Database TeacherDesk Lokal

```text
subjects 1 ─── n materials
classes  1 ─── n materials (opsional)

subjects 1 ─── n schedule_templates
classes  1 ─── n schedule_templates
materials 1 ── n schedule_templates (opsional)

subjects 1 ─── n schedules
classes  1 ─── n schedules
materials 1 ── n schedules (opsional)

schedules 1 ── 0..1 teaching_journals
materials 1 ── n teaching_journals (opsional)

subjects 1 ─── n questions
materials 1 ── n questions (opsional)
```

## Tabel utama

- `subjects`: mata pelajaran dan konteks kurikulum.
- `classes`: kelas, tingkat, lembaga, dan jumlah siswa.
- `materials`: isi materi, tujuan, status, dan lampiran.
- `schedule_templates`: pola jadwal berdasarkan hari, jam, kelas, dan mata pelajaran.
- `schedules`: jadwal aktual pada tanggal tertentu.
- `teaching_journals`: pelaksanaan dan refleksi per jadwal.
- `questions`: soal pilihan ganda dengan empat opsi tetap.
- `settings`: konfigurasi profil dan tahun ajaran.
