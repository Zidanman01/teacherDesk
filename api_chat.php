<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/config/bootstrap.php';

function chat_api_response(int $statusCode, array $payload): never
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function assert_chat_same_origin(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === '') {
        return;
    }

    $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
    $requestHost = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')) ?? '');
    if ($originHost === '' || $requestHost === '' || !hash_equals($requestHost, $originHost)) {
        chat_api_response(403, ['status' => 'error', 'message' => 'Permintaan lintas situs ditolak.']);
    }
}

function assert_chat_rate_limit(): void
{
    $now = time();
    $requests = array_values(array_filter(
        (array) ($_SESSION['ai_chat_requests'] ?? []),
        static fn ($timestamp): bool => is_int($timestamp) && $timestamp > ($now - 60)
    ));

    if (count($requests) >= 20) {
        chat_api_response(429, [
            'status' => 'error',
            'message' => 'Terlalu banyak pesan dalam satu menit. Tunggu sebentar lalu coba kembali.',
        ]);
    }

    $requests[] = $now;
    $_SESSION['ai_chat_requests'] = $requests;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chat_api_response(405, ['status' => 'error', 'message' => 'Metode tidak diizinkan. Gunakan POST.']);
}

assert_chat_same_origin();
assert_chat_rate_limit();

$inputJSON = file_get_contents('php://input');
$inputData = json_decode((string) $inputJSON, true);
if (!is_array($inputData)) {
    chat_api_response(400, ['status' => 'error', 'message' => 'Payload JSON tidak valid.']);
}

$csrfToken = $inputData['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';
if (!is_string($csrfToken) || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    chat_api_response(419, [
        'status' => 'error',
        'message' => 'Sesi formulir berakhir. Muat ulang halaman lalu coba kembali.',
    ]);
}

if (!isset($inputData['messages']) || !is_array($inputData['messages'])) {
    chat_api_response(400, ['status' => 'error', 'message' => "Data 'messages' tidak ditemukan atau formatnya salah."]);
}

$systemPrompt = <<<'PROMPT'
Anda adalah Konsultan Kurikulum AI di TeacherDesk yang membantu guru Indonesia menyusun perangkat pembelajaran dan asesmen.

Aturan jawaban:
1. Gunakan bahasa Indonesia yang jelas, profesional, dan mudah diterapkan, kecuali pengguna meminta bahasa lain.
2. Tulis dalam Markdown yang rapi. Gunakan judul pendek hanya ketika diperlukan.
3. Mulai dengan jawaban langsung. Hindari pembukaan panjang, pengulangan pertanyaan, dan kalimat pengisi.
4. Gunakan paragraf pendek. Gunakan daftar bernomor untuk langkah, bullet untuk rincian, dan tabel hanya untuk perbandingan atau data yang memang lebih mudah dibaca sebagai tabel.
5. Tebalkan istilah penting secara secukupnya. Jangan memenuhi jawaban dengan huruf tebal.
6. Jika pengguna meminta contoh, berikan contoh yang siap disalin atau diterapkan.
7. Jika pengguna meminta CP, TP, ATP, indikator, modul ajar, kisi-kisi, rubrik, atau asesmen, susun komponen secara konsisten dan tunjukkan keterkaitan antarbagian.
8. Jangan menggunakan emoji.
9. Jangan mengarang peraturan, nomor regulasi, atau isi dokumen resmi. Nyatakan keterbatasan dan sarankan verifikasi dokumen resmi jika informasi tidak tersedia dalam konteks.
10. Jika informasi pengguna belum cukup, sebutkan asumsi yang digunakan secara singkat dan tetap berikan jawaban yang berguna.
11. Akhiri setelah kebutuhan pengguna terpenuhi. Jangan menambahkan tawaran bantuan yang tidak diminta.
12. Jangan gunakan tag HTML seperti <br>, <p>, <div>, atau tabel HTML. Gunakan sintaks Markdown dan baris baru biasa.
13. Setiap baris tabel Markdown harus memiliki jumlah kolom yang sama. Jangan memecah isi satu baris tabel ke baris baru. Jika satu komponen memiliki beberapa butir, gunakan satu baris per butir dengan nama komponen diulang, atau gunakan daftar di luar tabel.
PROMPT;

$rawMessages = array_slice($inputData['messages'], -30);
$normalizedMessages = [];

foreach ($rawMessages as $message) {
    if (!is_array($message)) {
        continue;
    }

    $role = (string) ($message['role'] ?? '');
    $content = trim((string) ($message['content'] ?? ''));

    // Prompt sistem ditetapkan di server agar format jawaban konsisten dan tidak dapat diubah dari browser.
    if (!in_array($role, ['user', 'assistant'], true) || $content === '') {
        continue;
    }

    $normalizedMessages[] = [
        'role' => $role,
        'content' => mb_substr($content, 0, 8000),
    ];
}

// Ambil percakapan terbaru terlebih dahulu agar pertanyaan terakhir tidak terpotong saat konteks terlalu panjang.
$selectedMessages = [];
$totalCharacters = mb_strlen($systemPrompt);
for ($index = count($normalizedMessages) - 1; $index >= 0; $index--) {
    $messageLength = mb_strlen($normalizedMessages[$index]['content']);
    if (($totalCharacters + $messageLength) > 40000) {
        break;
    }

    array_unshift($selectedMessages, $normalizedMessages[$index]);
    $totalCharacters += $messageLength;
}

$chatHistory = array_merge(
    [['role' => 'system', 'content' => $systemPrompt]],
    $selectedMessages
);

if (count($chatHistory) === 1) {
    chat_api_response(400, ['status' => 'error', 'message' => 'Tidak ada pesan valid untuk dikirim.']);
}

$sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($inputData['session_id'] ?? 'default_session'));
$sessionId = mb_substr($sessionId ?: 'default_session', 0, 100);

try {
    $lastUserMessage = null;
    for ($index = count($chatHistory) - 1; $index >= 0; $index--) {
        if ($chatHistory[$index]['role'] === 'user') {
            $lastUserMessage = $chatHistory[$index]['content'];
            break;
        }
    }

    if ($lastUserMessage === null) {
        throw new InvalidArgumentException('Pesan pengguna tidak ditemukan.');
    }

    $conn = Database::connection();
    $stmtUser = $conn->prepare("INSERT INTO chat_history (session_id, role, content) VALUES (?, 'user', ?)");
    $stmtUser->execute([$sessionId, $lastUserMessage]);

    $aiService = new OpenRouterService();
    $reply = $aiService->sendConversation(
        $chatHistory,
        (string) env('OPENROUTER_CHAT_MODEL', 'nvidia/nemotron-3-super-120b-a12b:free'),
        [
            'temperature' => (float) env('OPENROUTER_CHAT_TEMPERATURE', '0.25'),
            'max_tokens' => (int) env('OPENROUTER_CHAT_MAX_TOKENS', '3000'),
        ]
    );

    $reply = trim($reply);
    $reply = preg_replace("/\r\n?/", "\n", $reply) ?? $reply;

    // Tag <br> tidak diubah di server. Renderer browser menanganinya secara
    // kontekstual agar line break di dalam sel tabel tidak merusak kolom.
    if ($reply === '') {
        throw new RuntimeException('Konsultan AI mengirim jawaban kosong.');
    }

    $stmtAi = $conn->prepare("INSERT INTO chat_history (session_id, role, content) VALUES (?, 'assistant', ?)");
    $stmtAi->execute([$sessionId, $reply]);

    chat_api_response(200, ['status' => 'success', 'reply' => $reply]);
} catch (Throwable $error) {
    $statusCode = $error instanceof InvalidArgumentException ? 400 : 500;
    chat_api_response($statusCode, [
        'status' => 'error',
        'message' => $error->getMessage(),
    ]);
}
