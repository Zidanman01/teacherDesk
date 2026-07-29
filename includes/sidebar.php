<?php
$nav = [
    ['dashboard', 'dashboard', 'Dashboard'],
    ['subjects', 'book', 'Mata Pelajaran'],
    ['classes', 'users', 'Kelas'],
    ['schedules', 'calendar', 'Jadwal Mengajar'],
    ['materials', 'file', 'Materi'],
    ['journals', 'journal', 'Jurnal Mengajar'],
    ['questions', 'question', 'Bank Soal'],
    ['chat_dashboard', 'message-circle', 'Konsultan AI'],
    ['generate_soal', 'sparkles', 'Generator Soal AI'],
    ['backup', 'backup', 'Backup'],
    ['settings', 'settings', 'Pengaturan'],
];
$currentPage = $_GET['page'] ?? 'dashboard';
$pageMeta = [
    'subjects' => [
        'title' => 'Mata Pelajaran',
        'subtitle' => 'Kelola kurikulum, jenjang, dan capaian pembelajaran.',
        'icon' => 'book',
        'action' => [
            'label' => 'Tambah Mata Pelajaran',
            'icon' => 'plus',
            'url' => url('subjects', ['create' => 1]),
        ],
    ],
    'classes' => [
        'title' => 'Manajemen Kelas',
        'subtitle' => 'Simpan informasi kelas, ruangan, dan jumlah siswa.',
        'icon' => 'users',
        'action' => [
            'label' => 'Tambah Kelas',
            'icon' => 'plus',
            'url' => url('classes', ['create' => 1]),
        ],
    ],
    'schedules' => [
        'title' => 'Jadwal Mengajar',
        'subtitle' => 'Atur waktu, kelas, mata pelajaran, dan agenda pembelajaran.',
        'icon' => 'calendar',
        'action' => [
            'label' => 'Tambah Jadwal',
            'icon' => 'plus',
            'url' => url('schedules', ['create' => 1]),
        ],
    ],
    'materials' => [
        'title' => 'Materi Pembelajaran',
        'subtitle' => 'Kelola bahan ajar sebagai sumber jadwal, jurnal, dan soal.',
        'icon' => 'file',
        'action' => [
            'label' => 'Tambah Materi',
            'icon' => 'plus',
            'url' => url('materials', ['create' => 1]),
        ],
    ],
    'journals' => [
        'title' => 'Jurnal Mengajar',
        'subtitle' => 'Catat pelaksanaan pembelajaran, hasil, dan refleksi mengajar.',
        'icon' => 'journal',
        'action' => [
            'label' => 'Tambah Jurnal',
            'icon' => 'plus',
            'url' => url('journals', ['create' => 1]),
        ],
    ],
    'questions' => [
        'title' => 'Bank Soal Pilihan Ganda',
        'subtitle' => 'Kelola soal, tingkat kesulitan, level kognitif, dan validasi.',
        'icon' => 'question',
        'action' => [
            'label' => 'Tambah Soal',
            'icon' => 'plus',
            'url' => url('questions', ['create' => 1]),
        ],
    ],
    'chat_dashboard' => [
        'title' => 'Konsultan Kurikulum AI',
        'subtitle' => 'Diskusikan kurikulum, materi, dan strategi pembelajaran.',
        'icon' => 'message-circle',
        'action' => [
            'label' => 'Chat Baru',
            'icon' => 'plus',
            'url' => url('chat_dashboard'),
        ],
    ],
    'generate_soal' => [
        'title' => 'AI Generator Soal',
        'subtitle' => 'Unggah materi PDF untuk membuat soal pilihan ganda.',
        'icon' => 'sparkles',
    ],
    'generator' => [
        'title' => 'Generator Soal Lokal',
        'subtitle' => 'Buat soal cloze dari materi yang tersimpan tanpa AI daring.',
        'icon' => 'sparkles',
        'action' => [
            'label' => 'Kembali ke Materi',
            'icon' => 'file',
            'url' => url('materials'),
        ],
    ],
    'backup' => [
        'title' => 'Backup dan Pemulihan',
        'subtitle' => 'Cadangkan data aplikasi dan pulihkan arsip JSON ketika diperlukan.',
        'icon' => 'backup',
    ],
];

// Dashboard dan Pengaturan mempertahankan topbar lama. Backup memakai layout standar.
$legacyPages = ['dashboard', 'settings'];
$isStandardPage = !in_array($currentPage, $legacyPages, true);
$currentMeta = $pageMeta[$currentPage] ?? [
    'title' => 'TeacherDesk',
    'subtitle' => 'Kelola aktivitas pembelajaran dalam satu tempat.',
    'icon' => 'dashboard',
];
?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-mark">TD</div>
        <div><strong>TeacherDesk</strong><span>Lokal Desktop</span></div>
    </div>
    <nav class="nav-list">
        <?php foreach ($nav as [$route, $ico, $label]): ?>
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
    <?php if ($isStandardPage): ?>
        <header class="topbar topbar--page">
            <div class="topbar-main">
                <div class="topbar-page-icon" aria-hidden="true">
                    <?= icon($currentMeta['icon'], 20) ?>
                </div>
                <div class="topbar-copy">
                    <h1><?= e($currentMeta['title']) ?></h1>
                    <p class="topbar-subtitle"><?= e($currentMeta['subtitle']) ?></p>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="topbar-date" title="Tanggal hari ini">
                    <?= icon('calendar', 14) ?>
                    <span><?= e(indo_day(date('Y-m-d')) . ', ' . format_date(date('Y-m-d'))) ?></span>
                </div>
                <?php if (!empty($currentMeta['action'])): ?>
                    <a class="btn btn-primary topbar-page-action" href="<?= e($currentMeta['action']['url']) ?>">
                        <?= icon($currentMeta['action']['icon'] ?? 'plus', 16) ?>
                        <span><?= e($currentMeta['action']['label']) ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </header>
    <?php else: ?>
        <header class="topbar">
            <div>
                <p class="eyebrow"><?= e(indo_day(date('Y-m-d')) . ', ' . format_date(date('Y-m-d'))) ?></p>
                <h1><?= e([
                    'dashboard' => 'Dashboard',
                    'settings' => 'Pengaturan',
                ][$currentPage] ?? 'TeacherDesk') ?></h1>
            </div>
            <div class="topbar-actions">
                <a class="btn btn-primary" href="<?= url('schedules', ['create' => 1]) ?>">
                    <?= icon('plus', 17) ?> Jadwal
                </a>
            </div>
        </header>
    <?php endif; ?>
    <main class="content page-<?= e($currentPage) ?> <?= $isStandardPage ? 'content--standard' : '' ?>">
        <?php foreach ($flashes as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" data-alert>
                <?= e($flash['message']) ?>
                <button type="button" aria-label="Tutup">×</button>
            </div>
        <?php endforeach; ?>
