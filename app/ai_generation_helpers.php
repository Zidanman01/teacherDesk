<?php

declare(strict_types=1);

/**
 * Menyiapkan tabel riwayat generator AI tanpa memaksa pengguna menjalankan
 * migrasi secara manual. Berkas SQL migrasi tetap disediakan untuk instalasi
 * yang mengelola perubahan skema secara terpisah.
 */
function ensure_ai_generation_history_table(PDO $db): void
{
    $db->exec(
        "CREATE TABLE IF NOT EXISTS ai_generation_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subject_id BIGINT UNSIGNED NULL,
            source_file_name VARCHAR(190) NOT NULL,
            requested_count TINYINT UNSIGNED NOT NULL DEFAULT 10,
            generated_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
            difficulty VARCHAR(20) NOT NULL DEFAULT 'sedang',
            cognitive_level VARCHAR(20) NOT NULL DEFAULT 'C2',
            include_explanation TINYINT(1) NOT NULL DEFAULT 1,
            model VARCHAR(190) NOT NULL,
            document_characters INT UNSIGNED NOT NULL DEFAULT 0,
            chunks_used TINYINT UNSIGNED NOT NULL DEFAULT 0,
            result_json LONGTEXT NULL,
            status ENUM('success','failed') NOT NULL DEFAULT 'success',
            error_message TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ai_history_subject (subject_id),
            INDEX idx_ai_history_created (created_at),
            INDEX idx_ai_history_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function save_ai_generation_history(PDO $db, array $data): int
{
    ensure_ai_generation_history_table($db);

    $statement = $db->prepare(
        "INSERT INTO ai_generation_history
        (subject_id, source_file_name, requested_count, generated_count, difficulty,
         cognitive_level, include_explanation, model, document_characters, chunks_used,
         result_json, status, error_message)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $statement->execute([
        $data['subject_id'] ?: null,
        mb_substr((string) ($data['source_file_name'] ?? 'dokumen.pdf'), 0, 190),
        (int) ($data['requested_count'] ?? 10),
        (int) ($data['generated_count'] ?? 0),
        (string) ($data['difficulty'] ?? 'sedang'),
        (string) ($data['cognitive_level'] ?? 'C2'),
        !empty($data['include_explanation']) ? 1 : 0,
        mb_substr((string) ($data['model'] ?? ''), 0, 190),
        (int) ($data['document_characters'] ?? 0),
        (int) ($data['chunks_used'] ?? 0),
        isset($data['result']) ? json_encode($data['result'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ($data['status'] ?? 'success') === 'failed' ? 'failed' : 'success',
        isset($data['error_message']) ? mb_substr((string) $data['error_message'], 0, 4000) : null,
    ]);

    return (int) $db->lastInsertId();
}

/**
 * Membersihkan teks PDF agar prompt tidak dipenuhi spasi dan karakter kontrol.
 */
function normalize_pdf_text(string $text): string
{
    $text = str_replace(["\0", "\r"], ['', "\n"], $text);
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text) ?? $text;
    $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
    $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

    return trim($text);
}

/**
 * Memilih bagian dokumen secara merata dari awal sampai akhir. Ini jauh lebih
 * representatif daripada kebiasaan lama mengambil 3.000 karakter pertama lalu
 * bertingkah seolah seluruh PDF telah dibaca.
 *
 * @return array{prompt_text:string,total_chunks:int,chunks_used:int,total_characters:int}
 */
function build_representative_pdf_context(
    string $text,
    int $chunkSize = 7000,
    int $maxChunks = 7,
    int $maxCharacters = 49000
): array {
    $text = normalize_pdf_text($text);
    $totalCharacters = mb_strlen($text);

    if ($totalCharacters === 0) {
        return [
            'prompt_text' => '',
            'total_chunks' => 0,
            'chunks_used' => 0,
            'total_characters' => 0,
        ];
    }

    $chunks = [];
    for ($offset = 0; $offset < $totalCharacters; $offset += $chunkSize) {
        $chunk = trim(mb_substr($text, $offset, $chunkSize));
        if ($chunk !== '') {
            $chunks[] = $chunk;
        }
    }

    $totalChunks = count($chunks);
    if ($totalChunks <= $maxChunks) {
        $selectedIndexes = range(0, max(0, $totalChunks - 1));
    } else {
        $selectedIndexes = [];
        for ($i = 0; $i < $maxChunks; $i++) {
            $index = (int) round($i * ($totalChunks - 1) / ($maxChunks - 1));
            $selectedIndexes[$index] = $index;
        }
        $selectedIndexes = array_values($selectedIndexes);
    }

    $parts = [];
    $usedCharacters = 0;
    foreach ($selectedIndexes as $position => $index) {
        $remaining = $maxCharacters - $usedCharacters;
        if ($remaining <= 0) {
            break;
        }

        $chunk = mb_substr($chunks[$index], 0, $remaining);
        $parts[] = sprintf(
            "[Bagian dokumen %d dari %d]\n%s",
            $index + 1,
            $totalChunks,
            $chunk
        );
        $usedCharacters += mb_strlen($chunk);
    }

    return [
        'prompt_text' => implode("\n\n", $parts),
        'total_chunks' => $totalChunks,
        'chunks_used' => count($parts),
        'total_characters' => $totalCharacters,
    ];
}
