<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
require_once __DIR__ . '/config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('generate_soal'));
}

$db = Database::connection();

try {
    verify_csrf();

    $subjectId = (int) ($_POST['subject_id'] ?? 0);
    if ($subjectId < 1) {
        throw new InvalidArgumentException('Mata pelajaran wajib dipilih.');
    }

    $subjectStatement = $db->prepare("SELECT id FROM subjects WHERE id=? AND status='active' LIMIT 1");
    $subjectStatement->execute([$subjectId]);
    if (!$subjectStatement->fetchColumn()) {
        throw new InvalidArgumentException('Mata pelajaran tidak ditemukan atau sudah diarsipkan.');
    }

    $fallbackDifficulty = in_array($_POST['difficulty'] ?? '', ['mudah', 'sedang', 'sulit', 'campuran'], true)
        ? (string) $_POST['difficulty']
        : 'sedang';

    $fallbackCognitive = in_array($_POST['cognitive_level'] ?? '', ['C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'campuran'], true)
        ? (string) $_POST['cognitive_level']
        : 'C2';

    $selected = (array) ($_POST['save'] ?? []);
    $items = (array) ($_POST['items'] ?? []);

    if (!$selected || !$items) {
        throw new InvalidArgumentException('Pilih minimal satu soal untuk disimpan.');
    }

    $sourceFile = basename(trim((string) ($_POST['source_file_name'] ?? '')));
    $sourceFile = mb_substr($sourceFile, 0, 190);
    $sourceNote = $sourceFile !== ''
        ? 'Sumber AI: ' . $sourceFile
        : 'Sumber AI: materi PDF.';

    $insert = $db->prepare(
        'INSERT INTO questions
        (subject_id, material_id, question_text, option_a, option_b, option_c, option_d,
         correct_option, explanation, difficulty, cognitive_level, status, source_type)
        VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $saved = 0;
    $skipped = 0;

    $db->beginTransaction();

    foreach ($items as $index => $item) {
        if (!isset($selected[$index]) || !is_array($item)) {
            continue;
        }

        $questionText = trim((string) ($item['question_text'] ?? ''));
        $optionA = trim((string) ($item['option_a'] ?? ''));
        $optionB = trim((string) ($item['option_b'] ?? ''));
        $optionC = trim((string) ($item['option_c'] ?? ''));
        $optionD = trim((string) ($item['option_d'] ?? ''));
        $correctOption = strtoupper(trim((string) ($item['correct_option'] ?? '')));
        $aiExplanation = trim((string) ($item['explanation'] ?? ''));

        $difficulty = in_array($item['difficulty'] ?? '', ['mudah', 'sedang', 'sulit'], true)
            ? (string) $item['difficulty']
            : ($fallbackDifficulty === 'campuran' ? 'sedang' : $fallbackDifficulty);

        $cognitiveLevel = in_array($item['cognitive_level'] ?? '', ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'], true)
            ? (string) $item['cognitive_level']
            : ($fallbackCognitive === 'campuran' ? 'C2' : $fallbackCognitive);

        $options = [$optionA, $optionB, $optionC, $optionD];
        $hasEmptyValue = $questionText === '' || in_array('', $options, true);
        $hasDuplicateOption = count(array_unique(array_map('mb_strtolower', $options))) < 4;
        $hasInvalidAnswer = !in_array($correctOption, ['A', 'B', 'C', 'D'], true);

        if ($hasEmptyValue || $hasDuplicateOption || $hasInvalidAnswer) {
            $skipped++;
            continue;
        }

        $explanation = $aiExplanation !== ''
            ? mb_substr($aiExplanation . "\n\n" . $sourceNote, 0, 5000)
            : $sourceNote;

        $insert->execute([
            $subjectId,
            mb_substr($questionText, 0, 4000),
            mb_substr($optionA, 0, 1000),
            mb_substr($optionB, 0, 1000),
            mb_substr($optionC, 0, 1000),
            mb_substr($optionD, 0, 1000),
            $correctOption,
            $explanation,
            $difficulty,
            $cognitiveLevel,
            'draft',
            'generator',
        ]);

        $saved++;
    }

    if ($saved === 0) {
        throw new InvalidArgumentException(
            'Tidak ada soal valid yang dapat disimpan. Pastikan pertanyaan terisi, pilihan berbeda, dan kunci jawaban valid.'
        );
    }

    $db->commit();

    $message = $saved . ' soal AI berhasil disimpan ke Bank Soal sebagai draf.';
    if ($skipped > 0) {
        $message .= ' ' . $skipped . ' soal dilewati karena datanya tidak valid.';
    }

    flash('success', $message);
    redirect(url('questions'));
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    flash('danger', $error->getMessage());
    redirect(url('generate_soal'));
}
