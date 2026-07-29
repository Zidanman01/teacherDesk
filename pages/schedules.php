<?php
$subjects = $db->query("SELECT id,name,grade_level FROM subjects WHERE status='active' ORDER BY name")->fetchAll();
$classes = $db->query("SELECT id,name,grade_level FROM classes WHERE status='active' ORDER BY name")->fetchAll();
$materials = $db->query("SELECT id,subject_id,title FROM materials ORDER BY title")->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM schedules WHERE id=?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();
}
$showForm = isset($_GET['create']) || $edit;

$templateEdit = null;
if (isset($_GET['template_edit'])) {
    $stmt = $db->prepare('SELECT * FROM schedule_templates WHERE id=?');
    $stmt->execute([(int) $_GET['template_edit']]);
    $templateEdit = $stmt->fetch();
}
$showTemplateForm = isset($_GET['template_create']) || $templateEdit;

$weekInput = (string) ($_GET['week'] ?? date('Y-m-d'));
$weekReference = DateTimeImmutable::createFromFormat('!Y-m-d', $weekInput);
if (!$weekReference || $weekReference->format('Y-m-d') !== $weekInput) {
    $weekReference = new DateTimeImmutable('today');
}
$weekStart = $weekReference->modify('-' . ((int) $weekReference->format('N') - 1) . ' days');
$weekEnd = $weekStart->modify('+6 days');
$previousWeek = $weekStart->modify('-7 days')->format('Y-m-d');
$nextWeek = $weekStart->modify('+7 days')->format('Y-m-d');
$today = date('Y-m-d');

$weekStmt = $db->prepare(
    "SELECT s.*, sub.name subject_name, c.name class_name, m.title material_title
     FROM schedules s
     JOIN subjects sub ON sub.id=s.subject_id
     JOIN classes c ON c.id=s.class_id
     LEFT JOIN materials m ON m.id=s.material_id
     WHERE s.schedule_date BETWEEN ? AND ?
     ORDER BY s.schedule_date, s.start_time, s.end_time"
);
$weekStmt->execute([$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')]);
$weeklySchedules = $weekStmt->fetchAll();
$weeklyByDate = [];
foreach ($weeklySchedules as $schedule) {
    $weeklyByDate[$schedule['schedule_date']][] = $schedule;
}

$weekDays = [];
for ($day = 0; $day < 7; $day++) {
    $date = $weekStart->modify('+' . $day . ' days');
    $weekDays[] = [
        'date' => $date->format('Y-m-d'),
        'day' => indo_day($date->format('Y-m-d')),
        'number' => $date->format('d'),
        'month' => format_date($date->format('Y-m-d'), 'M'),
    ];
}

$from = (string) ($_GET['from'] ?? date('Y-m-01'));
$to = (string) ($_GET['to'] ?? date('Y-m-t'));
$stmt = $db->prepare(
    "SELECT s.*,sub.name subject_name,c.name class_name,m.title material_title
     FROM schedules s
     JOIN subjects sub ON sub.id=s.subject_id
     JOIN classes c ON c.id=s.class_id
     LEFT JOIN materials m ON m.id=s.material_id
     WHERE s.schedule_date BETWEEN ? AND ?
     ORDER BY s.schedule_date DESC,s.start_time"
);
$stmt->execute([$from, $to]);
$schedules = $stmt->fetchAll();

$templates = $db->query(
    "SELECT t.*,sub.name subject_name,c.name class_name,m.title material_title
     FROM schedule_templates t
     JOIN subjects sub ON sub.id=t.subject_id
     JOIN classes c ON c.id=t.class_id
     LEFT JOIN materials m ON m.id=t.material_id
     ORDER BY t.status='active' DESC,t.day_of_week,t.start_time"
)->fetchAll();
$activeTemplates = array_values(array_filter($templates, static fn(array $item): bool => $item['status'] === 'active'));

$statusLabels = [
    'scheduled' => 'Terjadwal',
    'done' => 'Terlaksana',
    'postponed' => 'Ditunda',
    'cancelled' => 'Dibatalkan',
    'assignment' => 'Diganti tugas',
];
$dayLabels = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
?>
<div class="section-title">
    <div>
        <h2>Kalender pengajaran</h2>
        <p>Gunakan template mingguan agar jadwal tetap tidak perlu dibuat ulang satu per satu.</p>
    </div>
    <div class="inline-meta">
        <a class="btn btn-secondary" href="<?= url('schedules', ['template_create' => 1, 'week' => $weekStart->format('Y-m-d')]) ?>"><?= icon('copy', 17) ?> Tambah template</a>
        <a class="btn btn-primary" href="<?= url('schedules', ['create' => 1, 'week' => $weekStart->format('Y-m-d')]) ?>"><?= icon('plus', 17) ?> Tambah jadwal</a>
    </div>
</div>

<?php if ($showTemplateForm): ?>
<section class="card">
    <div class="card-header">
        <div>
            <h2><?= $templateEdit ? 'Edit' : 'Tambah' ?> template jadwal</h2>
            <p>Template menyimpan hari, jam, kelas, dan mata pelajaran untuk digunakan kembali setiap minggu.</p>
        </div>
    </div>
    <div class="card-body">
        <form method="post" data-material-filter-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_schedule_template">
            <input type="hidden" name="id" value="<?= e((string) ($templateEdit['id'] ?? '')) ?>">
            <input type="hidden" name="return_week" value="<?= e($weekStart->format('Y-m-d')) ?>">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Nama template *</label>
                    <input class="form-control" name="name" required maxlength="160" placeholder="Contoh: IPA VIII A setiap Senin" value="<?= e((string) ($templateEdit['name'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>Mata pelajaran *</label>
                    <select class="form-control" name="subject_id" required data-subject-filter>
                        <option value="">Pilih mata pelajaran</option>
                        <?php foreach ($subjects as $item): ?>
                            <option value="<?= (int) $item['id'] ?>" <?= selected($templateEdit['subject_id'] ?? '', $item['id']) ?>><?= e($item['name'] . ' • ' . $item['grade_level']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kelas *</label>
                    <select class="form-control" name="class_id" required>
                        <option value="">Pilih kelas</option>
                        <?php foreach ($classes as $item): ?>
                            <option value="<?= (int) $item['id'] ?>" <?= selected($templateEdit['class_id'] ?? '', $item['id']) ?>><?= e($item['name'] . ' • ' . $item['grade_level']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Materi bawaan</label>
                    <select class="form-control" name="material_id" data-material-select>
                        <option value="">Tanpa materi bawaan</option>
                        <?php foreach ($materials as $item): ?>
                            <option data-subject="<?= (int) $item['subject_id'] ?>" value="<?= (int) $item['id'] ?>" <?= selected($templateEdit['material_id'] ?? '', $item['id']) ?>><?= e($item['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="help-text">Kosongkan bila materi berubah setiap pertemuan. Materi dapat dipilih setelah jadwal dibuat.</span>
                </div>
                <div class="form-group">
                    <label>Hari mengajar *</label>
                    <select class="form-control" name="day_of_week" required>
                        <?php foreach ($dayLabels as $key => $label): ?>
                            <option value="<?= $key ?>" <?= selected($templateEdit['day_of_week'] ?? 1, $key) ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" name="status">
                        <option value="active" <?= selected($templateEdit['status'] ?? 'active', 'active') ?>>Aktif</option>
                        <option value="archived" <?= selected($templateEdit['status'] ?? 'active', 'archived') ?>>Arsip</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jam mulai *</label>
                    <input class="form-control" type="time" name="start_time" required value="<?= e(substr((string) ($templateEdit['start_time'] ?? '08:00'), 0, 5)) ?>">
                </div>
                <div class="form-group">
                    <label>Jam selesai *</label>
                    <input class="form-control" type="time" name="end_time" required value="<?= e(substr((string) ($templateEdit['end_time'] ?? '09:30'), 0, 5)) ?>">
                </div>
                <div class="form-group">
                    <label>Lokasi atau ruangan</label>
                    <input class="form-control" name="location" value="<?= e((string) ($templateEdit['location'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <input class="form-control" name="notes" value="<?= e((string) ($templateEdit['notes'] ?? '')) ?>">
                </div>
            </div>
            <div class="form-actions">
                <a class="btn btn-secondary" href="<?= url('schedules', ['week' => $weekStart->format('Y-m-d')]) ?>">Batal</a>
                <button class="btn btn-primary">Simpan template</button>
            </div>
        </form>
    </div>
</section>
<?php endif; ?>

<?php if ($showForm): ?>
<section class="card <?= $showTemplateForm ? 'mt-3' : '' ?>">
    <div class="card-header">
        <div>
            <h2><?= $edit ? 'Edit' : 'Tambah' ?> jadwal</h2>
            <p>Sistem menolak jadwal yang waktunya bertabrakan.</p>
        </div>
    </div>
    <div class="card-body">
        <form method="post" data-material-filter-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_schedule">
            <input type="hidden" name="id" value="<?= e((string) ($edit['id'] ?? '')) ?>">
            <input type="hidden" name="return_week" value="<?= e($weekStart->format('Y-m-d')) ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Mata pelajaran *</label>
                    <select class="form-control" name="subject_id" required data-subject-filter>
                        <option value="">Pilih mata pelajaran</option>
                        <?php foreach ($subjects as $item): ?>
                            <option value="<?= (int) $item['id'] ?>" <?= selected($edit['subject_id'] ?? '', $item['id']) ?>><?= e($item['name'] . ' • ' . $item['grade_level']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kelas *</label>
                    <select class="form-control" name="class_id" required>
                        <option value="">Pilih kelas</option>
                        <?php foreach ($classes as $item): ?>
                            <option value="<?= (int) $item['id'] ?>" <?= selected($edit['class_id'] ?? '', $item['id']) ?>><?= e($item['name'] . ' • ' . $item['grade_level']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Materi</label>
                    <select class="form-control" name="material_id" data-material-select>
                        <option value="">Belum ditentukan</option>
                        <?php foreach ($materials as $item): ?>
                            <option data-subject="<?= (int) $item['subject_id'] ?>" value="<?= (int) $item['id'] ?>" <?= selected($edit['material_id'] ?? '', $item['id']) ?>><?= e($item['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal *</label>
                    <input class="form-control" type="date" name="schedule_date" required value="<?= e((string) ($edit['schedule_date'] ?? ($_GET['date'] ?? date('Y-m-d')))) ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" name="status">
                        <?php foreach ($statusLabels as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= selected($edit['status'] ?? 'scheduled', $key) ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jam mulai *</label>
                    <input class="form-control" type="time" name="start_time" required value="<?= e(substr((string) ($edit['start_time'] ?? '08:00'), 0, 5)) ?>">
                </div>
                <div class="form-group">
                    <label>Jam selesai *</label>
                    <input class="form-control" type="time" name="end_time" required value="<?= e(substr((string) ($edit['end_time'] ?? '09:30'), 0, 5)) ?>">
                </div>
                <div class="form-group">
                    <label>Lokasi atau ruangan</label>
                    <input class="form-control" name="location" value="<?= e((string) ($edit['location'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <input class="form-control" name="notes" value="<?= e((string) ($edit['notes'] ?? '')) ?>">
                </div>
            </div>
            <div class="form-actions">
                <a class="btn btn-secondary" href="<?= url('schedules', ['week' => $weekStart->format('Y-m-d')]) ?>">Batal</a>
                <button class="btn btn-primary">Simpan jadwal</button>
            </div>
        </form>
    </div>
</section>
<?php endif; ?>

<section class="card mt-3 template-manager-card">
    <div class="card-header">
        <div>
            <h2>Template jadwal mingguan</h2>
            <p><?= count($activeTemplates) ?> template aktif dari <?= count($templates) ?> template tersimpan.</p>
        </div>
        <a class="btn btn-secondary btn-sm" href="<?= url('schedules', ['template_create' => 1, 'week' => $weekStart->format('Y-m-d')]) ?>"><?= icon('plus', 14) ?> Template baru</a>
    </div>
    <div class="card-body template-apply-panel">
        <div class="template-apply-copy">
            <strong>Buat jadwal dari template</strong>
            <span>Pilih template, tentukan minggu awal, lalu buat jadwal untuk satu atau beberapa minggu sekaligus.</span>
        </div>
        <form method="post" class="template-apply-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="apply_schedule_templates">
            <input type="hidden" name="template_selection_present" value="1">
            <div class="template-selector">
                <?php foreach ($activeTemplates as $item): ?>
                    <label class="template-choice">
                        <input type="checkbox" name="template_ids[]" value="<?= (int) $item['id'] ?>" checked>
                        <span>
                            <strong><?= e($item['name']) ?></strong>
                            <small><?= e(($dayLabels[(int) $item['day_of_week']] ?? '-') . ', ' . substr((string) $item['start_time'], 0, 5) . '–' . substr((string) $item['end_time'], 0, 5)) ?></small>
                        </span>
                    </label>
                <?php endforeach; ?>
                <?php if (!$activeTemplates): ?>
                    <div class="template-empty-inline">Belum ada template aktif. Simpan salah satu jadwal sebagai template terlebih dahulu.</div>
                <?php endif; ?>
            </div>
            <div class="template-apply-controls">
                <div class="form-group">
                    <label>Minggu awal</label>
                    <input class="form-control" type="date" name="week_start" value="<?= e($weekStart->format('Y-m-d')) ?>" required>
                </div>
                <div class="form-group weeks-count-group">
                    <label>Jumlah minggu</label>
                    <input class="form-control" type="number" name="weeks_count" min="1" max="52" value="1" required>
                </div>
                <button class="btn btn-primary" <?= !$activeTemplates ? 'disabled' : '' ?>><?= icon('calendar', 15) ?> Terapkan template</button>
            </div>
        </form>
        <div class="generator-note mt-2">Jadwal yang sudah ada tidak akan digandakan. Jadwal yang bentrok juga akan dilewati dan dilaporkan setelah proses selesai.</div>
    </div>
    <div class="table-wrap">
        <table class="data-table template-table">
            <thead>
                <tr><th>Template</th><th>Hari dan waktu</th><th>Kelas dan mapel</th><th>Materi bawaan</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $item): ?>
                    <tr>
                        <td><strong><?= e($item['name']) ?></strong><div class="muted text-sm"><?= e($item['location'] ?: 'Tanpa lokasi') ?></div></td>
                        <td><strong><?= e($dayLabels[(int) $item['day_of_week']] ?? '-') ?></strong><div class="muted text-sm"><?= e(substr((string) $item['start_time'], 0, 5) . '–' . substr((string) $item['end_time'], 0, 5)) ?></div></td>
                        <td><strong><?= e($item['subject_name']) ?></strong><div class="muted text-sm"><?= e($item['class_name']) ?></div></td>
                        <td class="truncate"><?= e($item['material_title'] ?: 'Tidak ditentukan') ?></td>
                        <td><?= status_badge($item['status']) ?></td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-secondary btn-sm" title="Edit template" href="<?= url('schedules', ['template_edit' => (int) $item['id'], 'week' => $weekStart->format('Y-m-d')]) ?>"><?= icon('edit', 14) ?></a>
                                <form method="post" data-confirm="Hapus template ini? Jadwal yang sudah dibuat tidak akan terhapus.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_schedule_template">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="return_week" value="<?= e($weekStart->format('Y-m-d')) ?>">
                                    <button class="btn btn-danger btn-sm" title="Hapus template"><?= icon('trash', 14) ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$templates): ?>
                    <tr><td colspan="6"><div class="empty-state"><h3>Belum ada template jadwal</h3><p>Gunakan tombol “Jadikan template” pada jadwal yang sudah ada atau buat template baru.</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card mt-3 weekly-calendar-card">
    <div class="card-header weekly-calendar-toolbar">
        <div>
            <h2>Kalender mingguan</h2>
            <p><?= e(format_date($weekStart->format('Y-m-d'), 'd M') . ' – ' . format_date($weekEnd->format('Y-m-d'), 'd M Y')) ?> • <?= count($weeklySchedules) ?> jadwal</p>
        </div>
        <div class="week-navigation">
            <a class="btn btn-secondary btn-sm" title="Minggu sebelumnya" href="<?= url('schedules', ['week' => $previousWeek, 'from' => $from, 'to' => $to]) ?>">‹ Sebelumnya</a>
            <a class="btn btn-secondary btn-sm" href="<?= url('schedules', ['week' => $today, 'from' => $from, 'to' => $to]) ?>">Minggu ini</a>
            <a class="btn btn-secondary btn-sm" title="Minggu berikutnya" href="<?= url('schedules', ['week' => $nextWeek, 'from' => $from, 'to' => $to]) ?>">Berikutnya ›</a>
        </div>
    </div>
    <div class="weekly-calendar-wrap">
        <div class="weekly-calendar-grid">
            <?php foreach ($weekDays as $day): ?>
                <?php
                $isToday = $day['date'] === $today;
                $daySchedules = $weeklyByDate[$day['date']] ?? [];
                ?>
                <div class="weekly-day <?= $isToday ? 'is-today' : '' ?>">
                    <div class="weekly-day-header">
                        <span class="weekly-day-name"><?= e($day['day']) ?></span>
                        <div class="weekly-date">
                            <strong><?= e($day['number']) ?></strong>
                            <span><?= e($day['month']) ?></span>
                        </div>
                        <?php if ($isToday): ?><span class="today-label">Hari ini</span><?php endif; ?>
                    </div>
                    <div class="weekly-day-body">
                        <?php foreach ($daySchedules as $item): ?>
                            <a class="weekly-event status-<?= e($item['status']) ?>" href="<?= url('schedules', ['edit' => (int) $item['id'], 'week' => $weekStart->format('Y-m-d')]) ?>" title="Klik untuk mengedit jadwal">
                                <div class="weekly-event-time"><?= e(substr((string) $item['start_time'], 0, 5) . '–' . substr((string) $item['end_time'], 0, 5)) ?></div>
                                <strong><?= e($item['subject_name']) ?></strong>
                                <span class="weekly-event-class"><?= e($item['class_name']) ?></span>
                                <span class="weekly-event-material"><?= e($item['material_title'] ?: 'Materi belum dipilih') ?></span>
                                <?php if ($item['location']): ?><span class="weekly-event-location"><?= icon('desktop', 12) ?> <?= e($item['location']) ?></span><?php endif; ?>
                                <span class="weekly-event-status"><?= e($statusLabels[$item['status']] ?? ucfirst((string) $item['status'])) ?></span>
                            </a>
                        <?php endforeach; ?>
                        <?php if (!$daySchedules): ?>
                            <div class="weekly-empty">Tidak ada jadwal</div>
                        <?php endif; ?>
                    </div>
                    <a class="weekly-add" href="<?= url('schedules', ['create' => 1, 'week' => $weekStart->format('Y-m-d'), 'date' => $day['date']]) ?>"><?= icon('plus', 13) ?> Tambah</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="weekly-legend">
        <span><i class="legend-dot status-scheduled"></i>Terjadwal</span>
        <span><i class="legend-dot status-done"></i>Terlaksana</span>
        <span><i class="legend-dot status-postponed"></i>Ditunda</span>
        <span><i class="legend-dot status-assignment"></i>Diganti tugas</span>
        <span><i class="legend-dot status-cancelled"></i>Dibatalkan</span>
    </div>
</section>

<section class="card mt-3">
    <div class="card-header">
        <div>
            <h2>Daftar jadwal</h2>
            <p><?= count($schedules) ?> jadwal pada rentang terpilih</p>
        </div>
        <form class="filter-bar" method="get">
            <input type="hidden" name="page" value="schedules">
            <input type="hidden" name="week" value="<?= e($weekStart->format('Y-m-d')) ?>">
            <input class="form-control" type="date" name="from" value="<?= e($from) ?>">
            <input class="form-control" type="date" name="to" value="<?= e($to) ?>">
            <button class="btn btn-secondary btn-sm">Terapkan</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Tanggal</th><th>Waktu</th><th>Kelas dan mapel</th><th>Materi</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $item): ?>
                    <tr>
                        <td><strong><?= e(indo_day($item['schedule_date'])) ?></strong><div class="muted text-sm"><?= e(format_date($item['schedule_date'])) ?></div></td>
                        <td><?= e(substr((string) $item['start_time'], 0, 5) . '–' . substr((string) $item['end_time'], 0, 5)) ?><div class="muted text-sm"><?= e($item['location'] ?: '-') ?></div></td>
                        <td><strong><?= e($item['subject_name']) ?></strong><div class="muted text-sm"><?= e($item['class_name']) ?></div></td>
                        <td class="truncate"><?= e($item['material_title'] ?: 'Belum dipilih') ?></td>
                        <td><?= status_badge($item['status']) ?></td>
                        <td>
                            <div class="actions">
                                <form method="post" title="Simpan jadwal ini sebagai template">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="save_schedule_as_template">
                                    <input type="hidden" name="schedule_id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="return_week" value="<?= e($weekStart->format('Y-m-d')) ?>">
                                    <button class="btn btn-secondary btn-sm"><?= icon('copy', 14) ?> Template</button>
                                </form>
                                <a class="btn btn-secondary btn-sm" href="<?= url('schedules', ['edit' => (int) $item['id'], 'week' => $weekStart->format('Y-m-d')]) ?>"><?= icon('edit', 14) ?></a>
                                <form method="post" data-confirm="Hapus jadwal ini?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_schedule">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button class="btn btn-danger btn-sm"><?= icon('trash', 14) ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$schedules): ?>
                    <tr><td colspan="6"><div class="empty-state"><h3>Tidak ada jadwal</h3><p>Ubah rentang tanggal, terapkan template, atau tambahkan jadwal baru.</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
