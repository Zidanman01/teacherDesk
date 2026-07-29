<?php
$backupTables = [
    'subjects' => 'Mata pelajaran',
    'classes' => 'Kelas',
    'schedule_templates' => 'Template jadwal',
    'schedules' => 'Jadwal',
    'materials' => 'Materi',
    'teaching_journals' => 'Jurnal',
    'questions' => 'Soal',
    'settings' => 'Pengaturan',
];

$localOnlyTables = [
    'chat_history' => 'Riwayat chat AI',
    'ai_generation_history' => 'Riwayat generate AI',
];

$tableCounts = [];
foreach ($backupTables as $table => $label) {
    $tableCounts[$label] = (int) $db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
}

$localOnlyCounts = [];
foreach ($localOnlyTables as $table => $label) {
    $localOnlyCounts[$label] = (int) $db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
}

$totalRecords = array_sum($tableCounts);
?>
<div class="grid grid-2">
    <section class="card">
        <div class="card-header">
            <div>
                <h2>Unduh backup</h2>
                <p>Simpan data utama aplikasi dalam satu arsip JSON.</p>
            </div>
            <?= icon('download', 22) ?>
        </div>
        <div class="card-body">
            <div class="inline-meta">
                <span class="badge badge-info"><?= number_format($totalRecords, 0, ',', '.') ?> rekaman</span>
                <span class="badge badge-neutral">Format JSON</span>
            </div>

            <p class="muted text-sm mt-2">
                Backup mencakup mata pelajaran, kelas, template jadwal, jadwal, materi, jurnal,
                bank soal, dan pengaturan. Lampiran materi tidak masuk ke arsip JSON.
            </p>

            <div class="grid grid-4 mt-3">
                <?php foreach ($tableCounts as $label => $count): ?>
                    <div>
                        <strong><?= number_format($count, 0, ',', '.') ?></strong>
                        <div class="muted text-sm"><?= e($label) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="alert alert-info mt-3">
                <span>
                    <strong>Belum masuk arsip JSON:</strong>
                    <?php
                    $historySummary = [];
                    foreach ($localOnlyCounts as $label => $count) {
                        $historySummary[] = e($label) . ' (' . number_format($count, 0, ',', '.') . ')';
                    }
                    echo implode(' dan ', $historySummary);
                    ?>.
                    Data tersebut tetap tersimpan pada database lokal.
                </span>
            </div>

            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="export_backup">
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">
                        <?= icon('download', 17) ?> Unduh backup sekarang
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="card danger-zone">
        <div class="card-header">
            <div>
                <h2>Pulihkan backup</h2>
                <p>Ganti data utama saat ini menggunakan arsip TeacherDesk.</p>
            </div>
            <?= icon('upload', 22) ?>
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <span>
                    <strong>Perhatian:</strong> pemulihan akan mengganti seluruh data yang tercakup dalam arsip.
                    Unduh backup terbaru sebelum melanjutkan.
                </span>
            </div>

            <form
                method="post"
                enctype="multipart/form-data"
                data-confirm="Semua data utama saat ini akan diganti. Lanjutkan pemulihan?"
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
                        accept=".json,application/json"
                        required
                    >
                    <span class="help-text">Gunakan file JSON hasil ekspor TeacherDesk, maksimal 15 MB.</span>
                </div>

                <div class="form-actions">
                    <button class="btn btn-danger" type="submit">
                        <?= icon('upload', 17) ?> Pulihkan data
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
            <p>Kurangi risiko kehilangan data pada komputer lokal.</p>
        </div>
        <?= icon('backup', 22) ?>
    </div>
    <div class="card-body">
        <div class="grid grid-3">
            <div>
                <strong>1. Backup rutin</strong>
                <p class="muted text-sm">Unduh arsip setiap akhir minggu atau setelah perubahan data besar.</p>
            </div>
            <div>
                <strong>2. Salin lampiran</strong>
                <p class="muted text-sm">Salin folder <code>storage/materials</code> bersama file JSON.</p>
            </div>
            <div>
                <strong>3. Simpan terpisah</strong>
                <p class="muted text-sm">Gunakan drive eksternal atau folder cloud yang tersinkron.</p>
            </div>
        </div>
    </div>
</section>
