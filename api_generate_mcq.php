<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_time_limit(180);
ini_set('max_execution_time', '180');
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/ai_generation_helpers.php';

function api_response(int $statusCode, array $payload): never
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function assert_same_origin(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === '') {
        return;
    }

    $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
    $requestHost = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')) ?? '');

    if ($originHost === '' || $requestHost === '' || !hash_equals($requestHost, $originHost)) {
        api_response(403, ['status' => 'error', 'message' => 'Permintaan lintas situs ditolak.']);
    }
}

function assert_api_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!is_string($token) || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        api_response(419, [
            'status' => 'error',
            'message' => 'Sesi formulir berakhir. Muat ulang halaman lalu coba kembali.',
        ]);
    }
}

function assert_ai_rate_limit(): void
{
    $now = time();
    $windowSeconds = 600;
    $maximumRequests = 5;

    $requests = array_values(array_filter(
        (array) ($_SESSION['ai_generation_requests'] ?? []),
        static fn ($timestamp): bool => is_int($timestamp) && $timestamp > ($now - $windowSeconds)
    ));

    if (count($requests) >= $maximumRequests) {
        api_response(429, [
            'status' => 'error',
            'message' => 'Batas generate tercapai. Maksimal 5 proses dalam 10 menit untuk mencegah pemakaian API berlebihan.',
        ]);
    }

    $requests[] = $now;
    $_SESSION['ai_generation_requests'] = $requests;
}

function extract_json_array(string $response): array
{
    $response = trim($response);
    $response = preg_replace('/^```(?:json)?\s*/i', '', $response) ?? $response;
    $response = preg_replace('/\s*```$/', '', $response) ?? $response;

    $start = strpos($response, '[');
    $end = strrpos($response, ']');

    if ($start === false || $end === false || $end <= $start) {
        throw new RuntimeException('AI tidak mengirim array JSON yang valid.');
    }

    $json = substr($response, $start, $end - $start + 1);
    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
        throw new RuntimeException('Format jawaban AI tidak dapat dibaca sebagai JSON.');
    }

    return $decoded;
}

function normalize_generated_questions(
    array $items,
    int $limit,
    string $requestedDifficulty,
    string $requestedCognitive,
    bool $includeExplanation
): array {
    $allowedDifficulty = ['mudah', 'sedang', 'sulit'];
    $allowedCognitive = ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'];
    $normalized = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $choices = isset($item['pilihan']) && is_array($item['pilihan']) ? $item['pilihan'] : [];
        $question = trim((string) ($item['pertanyaan'] ?? ''));
        $optionA = trim((string) ($choices['A'] ?? ''));
        $optionB = trim((string) ($choices['B'] ?? ''));
        $optionC = trim((string) ($choices['C'] ?? ''));
        $optionD = trim((string) ($choices['D'] ?? ''));
        $answer = strtoupper(trim((string) ($item['kunci_jawaban'] ?? '')));

        $options = [$optionA, $optionB, $optionC, $optionD];
        $uniqueOptions = array_unique(array_map(static fn (string $value): string => mb_strtolower($value), $options));

        if (
            $question === ''
            || in_array('', $options, true)
            || count($uniqueOptions) < 4
            || !in_array($answer, ['A', 'B', 'C', 'D'], true)
        ) {
            continue;
        }

        $difficulty = strtolower(trim((string) ($item['tingkat_kesulitan'] ?? '')));
        if (!in_array($difficulty, $allowedDifficulty, true)) {
            $difficulty = $requestedDifficulty === 'campuran' ? 'sedang' : $requestedDifficulty;
        }

        $cognitive = strtoupper(trim((string) ($item['level_kognitif'] ?? '')));
        if (!in_array($cognitive, $allowedCognitive, true)) {
            $cognitive = $requestedCognitive === 'campuran' ? 'C2' : $requestedCognitive;
        }

        $explanation = $includeExplanation
            ? trim((string) ($item['pembahasan'] ?? ''))
            : '';

        $normalized[] = [
            'pertanyaan' => mb_substr($question, 0, 4000),
            'pilihan' => [
                'A' => mb_substr($optionA, 0, 1000),
                'B' => mb_substr($optionB, 0, 1000),
                'C' => mb_substr($optionC, 0, 1000),
                'D' => mb_substr($optionD, 0, 1000),
            ],
            'kunci_jawaban' => $answer,
            'pembahasan' => mb_substr($explanation, 0, 4000),
            'tingkat_kesulitan' => $difficulty,
            'level_kognitif' => $cognitive,
        ];

        if (count($normalized) >= $limit) {
            break;
        }
    }

    return $normalized;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_response(405, ['status' => 'error', 'message' => 'Metode tidak diizinkan. Gunakan POST.']);
}

assert_same_origin();
assert_api_csrf();
assert_ai_rate_limit();

$db = Database::connection();
ensure_ai_generation_history_table($db);

$subjectId = (int) ($_POST['subject_id'] ?? 0);
$questionCount = (int) ($_POST['question_count'] ?? 10);
$difficulty = strtolower(trim((string) ($_POST['difficulty'] ?? 'sedang')));
$cognitiveLevel = strtoupper(trim((string) ($_POST['cognitive_level'] ?? 'C2')));
$includeExplanation = (string) ($_POST['include_explanation'] ?? '1') === '1';
$model = (string) env('OPENROUTER_QUESTION_MODEL', 'openai/gpt-oss-20b:free');
$sourceFileName = isset($_FILES['document']['name']) ? basename((string) $_FILES['document']['name']) : 'dokumen.pdf';
$historyContext = [
    'subject_id' => $subjectId,
    'source_file_name' => $sourceFileName,
    'requested_count' => $questionCount,
    'generated_count' => 0,
    'difficulty' => $difficulty,
    'cognitive_level' => $cognitiveLevel,
    'include_explanation' => $includeExplanation,
    'model' => $model,
    'document_characters' => 0,
    'chunks_used' => 0,
];

try {
    if (!in_array($questionCount, [5, 10, 15, 20, 25], true)) {
        throw new InvalidArgumentException('Jumlah soal harus 5, 10, 15, 20, atau 25.');
    }

    if (!in_array($difficulty, ['mudah', 'sedang', 'sulit', 'campuran'], true)) {
        throw new InvalidArgumentException('Tingkat kesulitan tidak valid.');
    }

    if (!in_array($cognitiveLevel, ['C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'CAMPURAN'], true)) {
        throw new InvalidArgumentException('Level kognitif tidak valid.');
    }
    $cognitiveLevel = $cognitiveLevel === 'CAMPURAN' ? 'campuran' : $cognitiveLevel;
    $historyContext['cognitive_level'] = $cognitiveLevel;

    if ($subjectId < 1) {
        throw new InvalidArgumentException('Pilih mata pelajaran terlebih dahulu.');
    }

    $subjectStatement = $db->prepare("SELECT id, name, grade_level FROM subjects WHERE id=? AND status='active' LIMIT 1");
    $subjectStatement->execute([$subjectId]);
    $subject = $subjectStatement->fetch(PDO::FETCH_ASSOC);
    if (!$subject) {
        throw new InvalidArgumentException('Mata pelajaran tidak ditemukan atau sudah diarsipkan.');
    }

    if (!isset($_FILES['document'])) {
        throw new InvalidArgumentException('File PDF belum dipilih.');
    }

    $upload = $_FILES['document'];
    $uploadError = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $uploadMessages = [
            UPLOAD_ERR_INI_SIZE => 'Ukuran PDF melebihi batas server.',
            UPLOAD_ERR_FORM_SIZE => 'Ukuran PDF melebihi batas formulir.',
            UPLOAD_ERR_PARTIAL => 'PDF hanya terunggah sebagian.',
            UPLOAD_ERR_NO_FILE => 'File PDF belum dipilih.',
        ];
        throw new InvalidArgumentException($uploadMessages[$uploadError] ?? 'File PDF gagal diunggah.');
    }

    $fileSize = (int) ($upload['size'] ?? 0);
    if ($fileSize < 1 || $fileSize > 10 * 1024 * 1024) {
        throw new InvalidArgumentException('Ukuran PDF harus lebih dari 0 byte dan maksimal 10 MB.');
    }

    $fileTmpPath = (string) ($upload['tmp_name'] ?? '');
    if ($fileTmpPath === '' || !is_uploaded_file($fileTmpPath)) {
        throw new InvalidArgumentException('Berkas unggahan tidak valid.');
    }

    $extension = strtolower(pathinfo($sourceFileName, PATHINFO_EXTENSION));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string) $finfo->file($fileTmpPath);
    if ($extension !== 'pdf' || !in_array($mimeType, ['application/pdf', 'application/x-pdf'], true)) {
        throw new InvalidArgumentException('Format file harus PDF yang valid.');
    }

    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($fileTmpPath);
    $pageCount = count($pdf->getPages());
    if ($pageCount > 150) {
        throw new InvalidArgumentException('PDF maksimal 150 halaman agar proses tetap stabil.');
    }

    $fullText = normalize_pdf_text($pdf->getText());
    if (mb_strlen($fullText) < 100) {
        throw new InvalidArgumentException(
            'Teks PDF tidak terbaca. Dokumen mungkin berupa hasil scan; ubah ke PDF berbasis teks atau lakukan OCR terlebih dahulu.'
        );
    }

    $context = build_representative_pdf_context($fullText);
    if ($context['prompt_text'] === '') {
        throw new RuntimeException('Materi PDF tidak menghasilkan teks yang dapat diproses.');
    }

    $historyContext['document_characters'] = $context['total_characters'];
    $historyContext['chunks_used'] = $context['chunks_used'];

    $difficultyInstruction = $difficulty === 'campuran'
        ? 'Buat distribusi tingkat kesulitan yang seimbang antara mudah, sedang, dan sulit.'
        : 'Semua soal harus memiliki tingkat kesulitan ' . $difficulty . '.';

    $cognitiveInstruction = $cognitiveLevel === 'campuran'
        ? 'Gunakan variasi level kognitif Bloom C1 sampai C6 secara masuk akal dan seimbang sesuai materi.'
        : 'Semua soal harus berada pada level kognitif Bloom ' . $cognitiveLevel . '.';

    $explanationInstruction = $includeExplanation
        ? 'Sertakan pembahasan singkat yang menjelaskan mengapa jawaban benar.'
        : 'Isi pembahasan dengan string kosong.';

    $systemPrompt = <<<PROMPT
Kamu adalah penyusun soal profesional untuk aplikasi TeacherDesk.
Buat TEPAT {$questionCount} soal pilihan ganda berbahasa Indonesia berdasarkan materi yang diberikan.
{$difficultyInstruction}
{$cognitiveInstruction}
{$explanationInstruction}

Kembalikan HANYA array JSON murni, tanpa markdown, sapaan, atau penjelasan di luar JSON.
Format setiap objek:
{
  "pertanyaan": "...",
  "pilihan": {"A":"...","B":"...","C":"...","D":"..."},
  "kunci_jawaban": "A",
  "pembahasan": "...",
  "tingkat_kesulitan": "mudah|sedang|sulit",
  "level_kognitif": "C1|C2|C3|C4|C5|C6"
}

Aturan:
1. Setiap soal harus dapat dijawab dari materi.
2. Empat opsi harus berbeda, masuk akal, dan hanya satu yang benar.
3. Hindari pertanyaan ambigu, opini, dan frasa "berdasarkan teks di atas".
4. Jangan mengulang pertanyaan atau pola jawaban.
5. Jangan memasukkan karakter newline di dalam nilai string JSON.
6. Pastikan JSON valid dan jumlah objek tepat {$questionCount}.
PROMPT;

    $userPrompt = "Mata pelajaran: {$subject['name']}\n"
        . "Jenjang: {$subject['grade_level']}\n"
        . "Jumlah halaman PDF: {$pageCount}\n\n"
        . "MATERI TERPILIH DARI SELURUH DOKUMEN:\n"
        . $context['prompt_text'];

    $aiService = new OpenRouterService();
    $rawResponse = $aiService->sendMessage(
        $userPrompt,
        $systemPrompt,
        $model,
        [
            'temperature' => 0.25,
            'max_tokens' => min(14000, max(3500, $questionCount * 500)),
        ]
    );

    $decoded = extract_json_array($rawResponse);
    $questions = normalize_generated_questions(
        $decoded,
        $questionCount,
        $difficulty,
        $cognitiveLevel,
        $includeExplanation
    );

    if (count($questions) === 0) {
        throw new RuntimeException('AI tidak menghasilkan soal valid. Coba kembali atau gunakan PDF dengan teks yang lebih jelas.');
    }

    $historyContext['generated_count'] = count($questions);
    $historyContext['result'] = $questions;
    $historyContext['status'] = 'success';
    $historyId = save_ai_generation_history($db, $historyContext);

    api_response(200, [
        'status' => 'success',
        'message' => count($questions) === $questionCount
            ? $questionCount . ' soal berhasil dibuat.'
            : count($questions) . ' soal valid berhasil dibuat dari ' . $questionCount . ' soal yang diminta.',
        'data' => $questions,
        'meta' => [
            'history_id' => $historyId,
            'requested_count' => $questionCount,
            'generated_count' => count($questions),
            'document_characters' => $context['total_characters'],
            'total_chunks' => $context['total_chunks'],
            'chunks_used' => $context['chunks_used'],
            'page_count' => $pageCount,
            'model' => $model,
        ],
    ]);
} catch (Throwable $error) {
    try {
        $historyContext['status'] = 'failed';
        $historyContext['error_message'] = $error->getMessage();
        save_ai_generation_history($db, $historyContext);
    } catch (Throwable $historyError) {
        // Kegagalan pencatatan tidak boleh menutupi kesalahan utama.
    }

    $statusCode = $error instanceof InvalidArgumentException ? 400 : 500;
    api_response($statusCode, [
        'status' => 'error',
        'message' => $error->getMessage(),
    ]);
}
