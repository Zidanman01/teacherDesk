<?php
$rows = $db->query('SELECT `key`,`value` FROM settings')->fetchAll();
$settings = [];
foreach ($rows as $row) {
    $settings[$row['key']] = $row['value'];
}
?>
<div class="grid grid-2">
    <section class="card">
        <div class="card-header">
            <div>
                <h2>Profil pengajaran</h2>
                <p>Informasi dasar yang digunakan pada aplikasi.</p>
            </div>
        </div>
        <div class="card-body">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_settings">
                <div class="form-group">
                    <label>Nama pengajar</label>
                    <input class="form-control" name="teacher_name" value="<?= e($settings['teacher_name'] ?? '') ?>">
                </div>
                <div class="form-group mt-2">
                    <label>Nama sekolah atau lembaga</label>
                    <input class="form-control" name="institution_name" value="<?= e($settings['institution_name'] ?? '') ?>">
                </div>
                <div class="form-group mt-2">
                    <label>Tahun ajaran aktif</label>
                    <input class="form-control" name="active_academic_year" placeholder="2026/2027" value="<?= e($settings['active_academic_year'] ?? '2026/2027') ?>">
                </div>
                <div class="form-group mt-2">
                    <label>Pengingat jadwal default</label>
                    <input class="form-control" type="number" min="0" name="default_reminder_minutes" value="<?= e($settings['default_reminder_minutes'] ?? '30') ?>">
                    <span class="help-text">Nilai dalam menit. Versi ini menampilkan pengingat saat dashboard dibuka.</span>
                </div>
                <button class="btn btn-primary mt-3">Simpan pengaturan</button>
            </form>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Konfigurasi aplikasi</h2>
                <p>Aplikasi terbuka langsung pada dashboard dan dirancang untuk layar desktop.</p>
            </div>
        </div>
        <div class="card-body">
            <div class="system-list">
                <div><span>Akses aplikasi</span><strong>Langsung tanpa login</strong></div>
                <div><span>Mode tampilan</span><strong>Desktop-only</strong></div>
                <div><span>Lebar layar minimum</span><strong>1.200 piksel</strong></div>
                <div><span>Penyimpanan</span><strong>MySQL / MariaDB lokal</strong></div>
            </div>
        </div>
    </section>
</div>
<section class="card mt-3">
    <div class="card-header"><div><h2>Informasi sistem</h2></div></div>
    <div class="card-body">
        <div class="grid grid-3">
            <div><span class="muted text-sm">Versi aplikasi</span><strong style="display:block;margin-top:4px">1.3.0 Desktop</strong></div>
            <div><span class="muted text-sm">Zona waktu</span><strong style="display:block;margin-top:4px"><?= e(date_default_timezone_get()) ?></strong></div>
            <div><span class="muted text-sm">Database</span><strong style="display:block;margin-top:4px">MySQL / MariaDB</strong></div>
        </div>
    </div>
</section>
