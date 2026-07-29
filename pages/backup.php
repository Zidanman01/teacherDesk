<?php
$backupItems = [
    ['table' => 'subjects', 'label' => 'Mata pelajaran', 'icon' => 'book'],
    ['table' => 'classes', 'label' => 'Kelas', 'icon' => 'users'],
    ['table' => 'schedules', 'label' => 'Jadwal', 'icon' => 'calendar'],
    ['table' => 'materials', 'label' => 'Materi', 'icon' => 'file'],
    ['table' => 'teaching_journals', 'label' => 'Jurnal', 'icon' => 'journal'],
    ['table' => 'questions', 'label' => 'Soal', 'icon' => 'question'],
];

foreach ($backupItems as &$item) {
    $item['count'] = (int) $db
        ->query("SELECT COUNT(*) FROM `{$item['table']}`")
        ->fetchColumn();
}
unset($item);
?>

<div class="backup-layout">
    <section class="card backup-export-card" id="backup-export">
        <div class="card-header">
            <div>
                <h2>Buat cadangan data</h2>
                <p>Unduh seluruh data utama aplikasi dalam satu file JSON.</p>
            </div>
            <div class="card-header-icon" aria-hidden="true">
                <?= icon('download', 21) ?>
            </div>
        </div>

        <div class="card-body">
            <p class="muted text-sm backup-description">
                File backup mencakup mata pelajaran, kelas, jadwal, materi, jurnal,
                bank soal, dan pengaturan. Lampiran materi tidak disertakan sehingga
                folder <code>storage/materials</code> perlu disalin secara terpisah.
            </p>

            <div class="backup-summary" aria-label="Ringkasan data yang akan dicadangkan">
                <?php foreach ($backupItems as $item): ?>
                    <div class="backup-summary-item">
                        <div class="backup-summary-icon" aria-hidden="true">
                            <?= icon($item['icon'], 17) ?>
                        </div>
                        <div>
                            <strong><?= number_format($item['count'], 0, ',', '.') ?></strong>
                            <span><?= e($item['label']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="post" class="backup-form-action">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="export_backup">
                <button class="btn btn-primary" type="submit">
                    <?= icon('download', 17) ?>
                    Unduh backup sekarang
                </button>
            </form>
        </div>
    </section>

    <section class="card danger-zone backup-restore-card">
        <div class="card-header">
            <div>
                <h2>Pulihkan dari backup</h2>
                <p>Ganti data aplikasi saat ini menggunakan file cadangan.</p>
            </div>
            <div class="card-header-icon card-header-icon--danger" aria-hidden="true">
                <?= icon('upload', 21) ?>
            </div>
        </div>

        <div class="card-body">
            <div class="alert alert-danger backup-warning">
                <span>
                    <strong>Perhatian:</strong> pemulihan akan mengganti seluruh data
                    aplikasi saat ini. Buat backup terbaru sebelum melanjutkan.
                </span>
            </div>

            <form
                method="post"
                enctype="multipart/form-data"
                data-confirm="Semua data saat ini akan diganti. Lanjutkan pemulihan?"
            >
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="restore_backup">

                <div class="form-group">
                    <label for="backup_file">File backup JSON</label>
                    <input
                        class="form-control"
                        id="backup_file"
                        type="file"
                        name="backup_file"
                        accept="application/json,.json"
                        required
                    >
                    <span class="help-text">Gunakan file JSON yang dibuat oleh TeacherDesk.</span>
                </div>

                <div class="backup-form-action backup-form-action--danger">
                    <button class="btn btn-danger" type="submit">
                        <?= icon('upload', 17) ?>
                        Pulihkan data
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>

<section class="card mt-3">
    <div class="card-header">
        <div>
            <h2>Strategi backup yang disarankan</h2>
            <p>Langkah sederhana untuk mengurangi risiko kehilangan data lokal.</p>
        </div>
    </div>

    <div class="card-body">
        <div class="backup-steps">
            <div class="backup-step">
                <span class="backup-step-number">1</span>
                <div>
                    <strong>Backup secara rutin</strong>
                    <p>Unduh file JSON setiap akhir minggu atau setelah perubahan besar.</p>
                </div>
            </div>

            <div class="backup-step">
                <span class="backup-step-number">2</span>
                <div>
                    <strong>Salin folder lampiran</strong>
                    <p>Sertakan folder <code>storage/materials</code> bersama file backup.</p>
                </div>
            </div>

            <div class="backup-step">
                <span class="backup-step-number">3</span>
                <div>
                    <strong>Simpan di lokasi berbeda</strong>
                    <p>Gunakan drive eksternal atau folder cloud yang tersinkron.</p>
                </div>
            </div>
        </div>
    </div>
</section>
