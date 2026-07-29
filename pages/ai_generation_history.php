<?php
require_once __DIR__ . '/../app/ai_generation_helpers.php';

ensure_ai_generation_history_table($db);

$statusFilter = in_array($_GET['status'] ?? '', ['success', 'failed'], true)
    ? (string) $_GET['status']
    : '';
$subjectFilter = max(0, (int) ($_GET['subject_id'] ?? 0));
$viewId = max(0, (int) ($_GET['view'] ?? 0));

$subjects = $db->query(
    "SELECT id, name, grade_level FROM subjects ORDER BY name, grade_level"
)->fetchAll(PDO::FETCH_ASSOC);

$where = [];
$params = [];
if ($statusFilter !== '') {
    $where[] = 'h.status = ?';
    $params[] = $statusFilter;
}
if ($subjectFilter > 0) {
    $where[] = 'h.subject_id = ?';
    $params[] = $subjectFilter;
}

$sql = "SELECT h.*, s.name AS subject_name, s.grade_level
        FROM ai_generation_history h
        LEFT JOIN subjects s ON s.id = h.subject_id";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY h.id DESC LIMIT 100';

$statement = $db->prepare($sql);
$statement->execute($params);
$histories = $statement->fetchAll(PDO::FETCH_ASSOC);

$detail = null;
$detailQuestions = [];
if ($viewId > 0) {
    $detailStatement = $db->prepare(
        "SELECT h.*, s.name AS subject_name, s.grade_level
         FROM ai_generation_history h
         LEFT JOIN subjects s ON s.id = h.subject_id
         WHERE h.id=? LIMIT 1"
    );
    $detailStatement->execute([$viewId]);
    $detail = $detailStatement->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($detail && !empty($detail['result_json'])) {
        $decoded = json_decode((string) $detail['result_json'], true);
        $detailQuestions = is_array($decoded) ? $decoded : [];
    }
}
?>

<style>
    .history-wrap { max-width: 1120px; margin: 0 auto; }
    .history-filters { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; align-items: end; }
    .history-table-wrap { overflow-x: auto; }
    .history-table { width: 100%; border-collapse: collapse; }
    .history-table th, .history-table td { padding: 11px 10px; border-bottom: 1px solid #e7ebf0; text-align: left; vertical-align: top; }
    .history-table th { font-size: .83rem; color: #64748b; white-space: nowrap; }
    .status-pill { display: inline-flex; padding: 3px 9px; border-radius: 999px; font-size: .78rem; font-weight: 700; }
    .status-success { color: #166534; background: #dcfce7; }
    .status-failed { color: #991b1b; background: #fee2e2; }
    .history-detail { margin-top: 20px; }
    .detail-meta { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 14px 0; }
    .detail-meta > div { padding: 12px; border: 1px solid #e3e8ef; border-radius: 10px; background: #fafcff; }
    .detail-question { padding: 16px; border: 1px solid #e3e8ef; border-radius: 12px; margin-top: 12px; }
    .detail-options { margin: 10px 0 0 20px; }
    .error-box { padding: 12px; border-radius: 10px; color: #991b1b; background: #fee2e2; }
    @media (max-width: 760px) {
        .history-filters, .detail-meta { grid-template-columns: 1fr; }
    }
</style>

<div class="history-wrap">
    <div class="section-title">
        <div>
            <h2>Riwayat Generator AI</h2>
            <p>Mencatat proses berhasil maupun gagal agar hasil tidak menghilang seperti tab browser yang ditutup tanpa sengaja.</p>
        </div>
        <a class="btn btn-primary" href="<?= url('generate_soal') ?>">Generate Soal Baru</a>
    </div>

    <section class="card">
        <div class="card-header"><h2>Filter Riwayat</h2></div>
        <div class="card-body">
            <form method="get" action="index.php" class="history-filters">
                <input type="hidden" name="page" value="ai_generation_history">
                <div class="form-group">
                    <label for="historySubject">Mata pelajaran</label>
                    <select class="form-control" id="historySubject" name="subject_id">
                        <option value="0">Semua mata pelajaran</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= (int) $subject['id'] ?>" <?= $subjectFilter === (int) $subject['id'] ? 'selected' : '' ?>>
                                <?= e($subject['name'] . ' • ' . $subject['grade_level']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="historyStatus">Status</label>
                    <select class="form-control" id="historyStatus" name="status">
                        <option value="">Semua status</option>
                        <option value="success" <?= $statusFilter === 'success' ? 'selected' : '' ?>>Berhasil</option>
                        <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Gagal</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Terapkan Filter</button>
                    <a class="btn btn-secondary" href="<?= url('ai_generation_history') ?>">Reset</a>
                </div>
            </form>
        </div>
    </section>

    <section class="card" style="margin-top:18px">
        <div class="card-header">
            <div>
                <h2>100 Proses Terbaru</h2>
                <p><?= count($histories) ?> riwayat ditampilkan.</p>
            </div>
        </div>
        <div class="card-body history-table-wrap">
            <?php if (!$histories): ?>
                <p class="muted">Belum ada riwayat generate pada filter ini.</p>
            <?php else: ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Waktu</th>
                            <th>PDF / Mata Pelajaran</th>
                            <th>Pengaturan</th>
                            <th>Hasil</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($histories as $history): ?>
                        <tr>
                            <td>#<?= (int) $history['id'] ?></td>
                            <td><?= e(date('d M Y H:i', strtotime((string) $history['created_at']))) ?></td>
                            <td>
                                <strong><?= e($history['source_file_name']) ?></strong><br>
                                <span class="muted text-sm">
                                    <?= e(($history['subject_name'] ?? 'Mata pelajaran tidak tersedia') . (!empty($history['grade_level']) ? ' • ' . $history['grade_level'] : '')) ?>
                                </span>
                            </td>
                            <td>
                                <?= (int) $history['requested_count'] ?> soal • <?= e(ucfirst($history['difficulty'])) ?><br>
                                <span class="muted text-sm"><?= e($history['cognitive_level']) ?> • Pembahasan <?= (int) $history['include_explanation'] === 1 ? 'aktif' : 'nonaktif' ?></span>
                            </td>
                            <td>
                                <?= (int) $history['generated_count'] ?> valid<br>
                                <span class="muted text-sm"><?= number_format((int) $history['document_characters']) ?> karakter • <?= (int) $history['chunks_used'] ?> bagian</span>
                            </td>
                            <td>
                                <span class="status-pill <?= $history['status'] === 'success' ? 'status-success' : 'status-failed' ?>">
                                    <?= $history['status'] === 'success' ? 'Berhasil' : 'Gagal' ?>
                                </span>
                            </td>
                            <td><a class="btn btn-secondary" href="<?= url('ai_generation_history', ['view' => (int) $history['id']]) ?>">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($detail): ?>
        <section class="card history-detail">
            <div class="card-header">
                <div>
                    <h2>Detail Riwayat #<?= (int) $detail['id'] ?></h2>
                    <p><?= e($detail['source_file_name']) ?></p>
                </div>
                <a class="btn btn-secondary" href="<?= url('ai_generation_history') ?>">Tutup Detail</a>
            </div>
            <div class="card-body">
                <div class="detail-meta">
                    <div><span class="muted text-sm">Mata pelajaran</span><br><strong><?= e($detail['subject_name'] ?? '-') ?></strong></div>
                    <div><span class="muted text-sm">Jumlah</span><br><strong><?= (int) $detail['generated_count'] ?> / <?= (int) $detail['requested_count'] ?> soal</strong></div>
                    <div><span class="muted text-sm">Kesulitan & Bloom</span><br><strong><?= e(ucfirst($detail['difficulty']) . ' • ' . $detail['cognitive_level']) ?></strong></div>
                    <div><span class="muted text-sm">Model</span><br><strong><?= e($detail['model']) ?></strong></div>
                </div>

                <?php if ($detail['status'] === 'failed'): ?>
                    <div class="error-box"><strong>Proses gagal:</strong> <?= e($detail['error_message'] ?? 'Kesalahan tidak diketahui.') ?></div>
                <?php elseif (!$detailQuestions): ?>
                    <p class="muted">Hasil soal tidak tersedia pada riwayat ini.</p>
                <?php else: ?>
                    <?php foreach ($detailQuestions as $index => $question): ?>
                        <?php $choices = is_array($question['pilihan'] ?? null) ? $question['pilihan'] : []; ?>
                        <article class="detail-question">
                            <strong><?= $index + 1 ?>. <?= e((string) ($question['pertanyaan'] ?? '')) ?></strong>
                            <ol class="detail-options" type="A">
                                <?php foreach (['A', 'B', 'C', 'D'] as $letter): ?>
                                    <li><?= e((string) ($choices[$letter] ?? '')) ?></li>
                                <?php endforeach; ?>
                            </ol>
                            <p><strong>Kunci:</strong> <?= e((string) ($question['kunci_jawaban'] ?? '-')) ?> • <strong>Kesulitan:</strong> <?= e((string) ($question['tingkat_kesulitan'] ?? '-')) ?> • <strong>Bloom:</strong> <?= e((string) ($question['level_kognitif'] ?? '-')) ?></p>
                            <?php if (!empty($question['pembahasan'])): ?>
                                <p><strong>Pembahasan:</strong> <?= nl2br(e((string) $question['pembahasan'])) ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    <?php elseif ($viewId > 0): ?>
        <div class="error-box" style="margin-top:18px">Riwayat yang diminta tidak ditemukan.</div>
    <?php endif; ?>
</div>
