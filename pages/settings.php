<?php
$rows = $db->query('SELECT `key`,`value` FROM settings')->fetchAll();
$settings = [];

foreach ($rows as $row) {
    $settings[$row['key']] = $row['value'];
}
?>

<div class="grid grid-2 settings-grid">
    <section class="card">
        <div class="card-header">
            <div>
                <h2>Profil pengajaran</h2>
                <p>Informasi dasar yang digunakan pada seluruh aplikasi.</p>
            </div>
            <div class="card-header-icon" aria-hidden="true">
                <?= icon('users', 21) ?>
            </div>
        </div>

        <div class="card-body">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_settings">

                <div class="form-grid settings-form-grid">
                    <div class="form-group">
                        <label for="teacher_name">Nama pengajar</label>
                        <input
                            class="form-control"
                            id="teacher_name"
                            name="teacher_name"
                            value="<?= e($settings['teacher_name'] ?? '') ?>"
                            autocomplete="name"
                        >
                    </div>

                    <div class="form-group">
                        <label for="institution_name">Nama sekolah atau lembaga</label>
                        <input
                            class="form-control"
                            id="institution_name"
                            name="institution_name"
                            value="<?= e($settings['institution_name'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="active_academic_year">Tahun ajaran aktif</label>
                        <input
                            class="form-control"
                            id="active_academic_year"
                            name="active_academic_year"
                            placeholder="2026/2027"
                            value="<?= e($settings['active_academic_year'] ?? '2026/2027') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="default_reminder_minutes">Pengingat jadwal default</label>
                        <input
                            class="form-control"
                            id="default_reminder_minutes"
                            type="number"
                            min="0"
                            name="default_reminder_minutes"
                            value="<?= e($settings['default_reminder_minutes'] ?? '30') ?>"
                        >
                        <span class="help-text">
                            Nilai dalam menit. Pengingat ditampilkan ketika dashboard dibuka.
                        </span>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">
                        <?= icon('settings', 17) ?>
                        Simpan pengaturan
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Konfigurasi aplikasi</h2>
                <p>Ringkasan cara TeacherDesk dijalankan pada perangkat lokal.</p>
            </div>
            <div class="card-header-icon" aria-hidden="true">
                <?= icon('desktop', 21) ?>
            </div>
        </div>

        <div class="card-body">
            <div class="system-list settings-system-list">
                <div>
                    <span>Akses aplikasi</span>
                    <strong>Langsung tanpa login</strong>
                </div>
                <div>
                    <span>Mode tampilan</span>
                    <strong>Desktop lokal</strong>
                </div>
                <div>
                    <span>Lebar layar minimum</span>
                    <strong>1.200 piksel</strong>
                </div>
                <div>
                    <span>Penyimpanan</span>
                    <strong>MySQL / MariaDB lokal</strong>
                </div>
            </div>

            <div class="settings-note">
                <?= icon('backup', 17) ?>
                <p>
                    Buat backup sebelum memperbarui aplikasi atau melakukan perubahan
                    besar pada database.
                </p>
            </div>
        </div>
    </section>
</div>

<section class="card mt-3">
    <div class="card-header">
        <div>
            <h2>Informasi sistem</h2>
            <p>Informasi teknis singkat dari instalasi TeacherDesk saat ini.</p>
        </div>
    </div>

    <div class="card-body">
        <div class="system-metrics">
            <div class="system-metric">
                <span>Versi aplikasi</span>
                <strong>1.4.2 Desktop</strong>
            </div>
            <div class="system-metric">
                <span>Zona waktu</span>
                <strong><?= e(date_default_timezone_get()) ?></strong>
            </div>
            <div class="system-metric">
                <span>Database</span>
                <strong>MySQL / MariaDB</strong>
            </div>
        </div>
    </div>
</section>
