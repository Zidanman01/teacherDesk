<?php
$stats = [
    'subjects' => (int)$db->query("SELECT COUNT(*) FROM subjects WHERE status='active'")->fetchColumn(),
    'classes' => (int)$db->query("SELECT COUNT(*) FROM classes WHERE status='active'")->fetchColumn(),
    'materials' => (int)$db->query("SELECT COUNT(*) FROM materials")->fetchColumn(),
    'questions' => (int)$db->query("SELECT COUNT(*) FROM questions")->fetchColumn(),
];
$todayStmt = $db->prepare("SELECT s.*,sub.name subject_name,c.name class_name,m.title material_title FROM schedules s JOIN subjects sub ON sub.id=s.subject_id JOIN classes c ON c.id=s.class_id LEFT JOIN materials m ON m.id=s.material_id WHERE s.schedule_date=? ORDER BY s.start_time");
$todayStmt->execute([date('Y-m-d')]);
$todaySchedules = $todayStmt->fetchAll();
$upcoming = $db->query("SELECT s.*,sub.name subject_name,c.name class_name FROM schedules s JOIN subjects sub ON sub.id=s.subject_id JOIN classes c ON c.id=s.class_id WHERE CONCAT(s.schedule_date,' ',s.start_time)>=NOW() AND s.status='scheduled' ORDER BY s.schedule_date,s.start_time LIMIT 5")->fetchAll();
$materialCounts = $db->query("SELECT status,COUNT(*) total FROM materials GROUP BY status")->fetchAll();
$materialMap = ['planned'=>0,'in_progress'=>0,'completed'=>0];
foreach($materialCounts as $row){$materialMap[$row['status']] = (int)$row['total'];}
$totalMaterials = array_sum($materialMap);
$progress = $totalMaterials ? round(($materialMap['completed']/$totalMaterials)*100) : 0;
$unlogged = (int)$db->query("SELECT COUNT(*) FROM schedules s LEFT JOIN teaching_journals j ON j.schedule_id=s.id WHERE s.schedule_date<CURDATE() AND s.status='scheduled' AND j.id IS NULL")->fetchColumn();
$draftQuestions = (int)$db->query("SELECT COUNT(*) FROM questions WHERE status='draft'")->fetchColumn();
?>
<div class="grid grid-4">
    <div class="card stat-card"><div class="stat-icon"><?= icon('book') ?></div><div class="stat-content"><span>Mata pelajaran aktif</span><strong><?= $stats['subjects'] ?></strong></div></div>
    <div class="card stat-card"><div class="stat-icon"><?= icon('users') ?></div><div class="stat-content"><span>Kelas aktif</span><strong><?= $stats['classes'] ?></strong></div></div>
    <div class="card stat-card"><div class="stat-icon"><?= icon('file') ?></div><div class="stat-content"><span>Total materi</span><strong><?= $stats['materials'] ?></strong></div></div>
    <div class="card stat-card"><div class="stat-icon"><?= icon('question') ?></div><div class="stat-content"><span>Bank soal</span><strong><?= $stats['questions'] ?></strong></div></div>
</div>

<div class="grid grid-3 mt-3">
    <section class="card span-2">
        <div class="card-header"><div><h2>Jadwal hari ini</h2><p>Rencana mengajar pada <?= e(format_date(date('Y-m-d'))) ?></p></div><a class="btn btn-secondary btn-sm" href="<?= url('schedules', ['week' => date('Y-m-d')]) ?>">Kalender minggu ini</a></div>
        <div class="card-body">
            <?php if($todaySchedules): ?><div class="schedule-list">
                <?php foreach($todaySchedules as $schedule): ?>
                    <div class="schedule-item"><div class="schedule-time"><?= e(substr($schedule['start_time'],0,5)) ?>–<?= e(substr($schedule['end_time'],0,5)) ?></div><div class="schedule-main"><strong><?= e($schedule['subject_name']) ?> • <?= e($schedule['class_name']) ?></strong><span><?= e($schedule['material_title'] ?: 'Materi belum dipilih') ?><?= $schedule['location'] ? ' • '.e($schedule['location']) : '' ?></span></div><div><?= status_badge($schedule['status']) ?></div></div>
                <?php endforeach; ?>
            </div><?php else: ?><div class="empty-state"><div class="empty-icon"><?= icon('calendar',25) ?></div><h3>Tidak ada jadwal hari ini</h3><p>Gunakan waktu ini untuk menyiapkan materi atau membuat bank soal.</p><a class="btn btn-primary btn-sm" href="<?= url('schedules',['create'=>1]) ?>">Tambah jadwal</a></div><?php endif; ?>
        </div>
    </section>
    <section class="card">
        <div class="card-header"><div><h2>Progres materi</h2><p>Ketuntasan seluruh materi</p></div></div>
        <div class="card-body">
            <div class="progress-label"><strong><?= $progress ?>% selesai</strong><span><?= $materialMap['completed'] ?>/<?= $totalMaterials ?></span></div><div class="progress"><div class="progress-bar" style="width:<?= $progress ?>%"></div></div>
            <div class="mt-3 text-sm"><p><strong><?= $materialMap['planned'] ?></strong> direncanakan</p><p><strong><?= $materialMap['in_progress'] ?></strong> sedang berjalan</p><p><strong><?= $materialMap['completed'] ?></strong> sudah selesai</p></div>
        </div>
    </section>
</div>

<div class="grid grid-3 mt-3">
    <section class="card span-2">
        <div class="card-header"><div><h2>Jadwal berikutnya</h2><p>Lima agenda terdekat</p></div></div>
        <div class="card-body">
            <?php if($upcoming): ?><div class="schedule-list"><?php foreach($upcoming as $schedule): ?><div class="schedule-item"><div class="schedule-time"><?= e(format_date($schedule['schedule_date'],'d M')) ?><br><span class="muted"><?= e(substr($schedule['start_time'],0,5)) ?></span></div><div class="schedule-main"><strong><?= e($schedule['subject_name']) ?> • <?= e($schedule['class_name']) ?></strong><span><?= e(indo_day($schedule['schedule_date'])) ?><?= $schedule['location'] ? ' • '.e($schedule['location']) : '' ?></span></div><a class="btn btn-secondary btn-sm" href="<?= url('schedules',['edit'=>$schedule['id']]) ?>">Detail</a></div><?php endforeach; ?></div><?php else: ?><p class="muted text-sm">Belum ada jadwal mendatang.</p><?php endif; ?>
        </div>
    </section>
    <section class="card">
        <div class="card-header"><div><h2>Perlu perhatian</h2><p>Item yang perlu ditindaklanjuti</p></div></div>
        <div class="card-body">
            <div class="alert alert-warning"><span><strong><?= $unlogged ?></strong> jadwal lampau belum memiliki jurnal.</span></div>
            <div class="alert alert-info"><span><strong><?= $draftQuestions ?></strong> soal masih berstatus draf.</span></div>
            <div class="quick-actions mt-2"><a class="quick-action" href="<?= url('materials',['create'=>1]) ?>"><?= icon('file',18) ?> Materi baru</a><a class="quick-action" href="<?= url('generator') ?>"><?= icon('sparkles',18) ?> Buat soal</a><a class="quick-action" href="<?= url('journals',['create'=>1]) ?>"><?= icon('journal',18) ?> Isi jurnal</a><a class="quick-action" href="<?= url('backup') ?>"><?= icon('backup',18) ?> Backup data</a></div>
        </div>
    </section>
</div>
