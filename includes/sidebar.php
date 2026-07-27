<?php
$nav = [
    ['dashboard','dashboard','Dashboard'],
    ['subjects','book','Mata Pelajaran'],
    ['classes','users','Kelas'],
    ['schedules','calendar','Jadwal Mengajar'],
    ['materials','file','Materi'],
    ['journals','journal','Jurnal Mengajar'],
    ['questions','question','Bank Soal'],
    ['chat_dashboard','message-circle','Konsultan AI'],
    ['generate_soal','sparkles','Generator Soal AI'],
    ['backup','backup','Backup'],
    ['settings','settings','Pengaturan'],
];
$currentPage = $_GET['page'] ?? 'dashboard';
?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-mark">TD</div>
        <div><strong>TeacherDesk</strong><span>Lokal Desktop</span></div>
    </div>
    <nav class="nav-list">
        <?php foreach ($nav as [$route,$ico,$label]): ?>
            <a href="<?= url($route) ?>" class="nav-item <?= $currentPage === $route ? 'active' : '' ?>">
                <?= icon($ico, 19) ?><span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="local-mode">
            <?= icon('desktop', 18) ?>
            <div><strong>Mode lokal</strong><span>Tanpa halaman login</span></div>
        </div>
    </div>
</aside>
<div class="main-area">
    <header class="topbar">
        <div>
            <p class="eyebrow"><?= e(indo_day(date('Y-m-d')) . ', ' . format_date(date('Y-m-d'))) ?></p>
            <h1><?= e([
                'dashboard'=>'Dashboard',
                'subjects'=>'Mata Pelajaran',
                'classes'=>'Manajemen Kelas',
                'schedules'=>'Jadwal Mengajar',
                'materials'=>'Materi Pembelajaran',
                'journals'=>'Jurnal Mengajar',
                'questions'=>'Bank Soal Pilihan Ganda',
                'chat_dashboard'=>'Konsultan Kurikulum AI',
                'generate_soal'=>'AI Generator Soal',
                'backup'=>'Backup dan Pemulihan',
                'settings'=>'Pengaturan'
            ][$currentPage] ?? 'TeacherDesk') ?></h1>
        </div>
        <div class="topbar-actions">
            <a class="btn btn-primary" href="<?= url('schedules', ['create'=>1]) ?>"><?= icon('plus',17) ?> Jadwal</a>
        </div>
    </header>
    <main class="content">
        <?php foreach ($flashes as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" data-alert><?= e($flash['message']) ?><button type="button" aria-label="Tutup">×</button></div>
        <?php endforeach; ?>