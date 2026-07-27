<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metode tidak diizinkan."]);
    exit();
}

if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "File PDF gagal diunggah."]);
    exit();
}

$fileTmpPath = $_FILES['document']['tmp_name'];
if (mime_content_type($fileTmpPath) !== 'application/pdf') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Format file harus PDF."]);
    exit();
}

try {
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($fileTmpPath);

    $materi = substr($pdf->getText(), 0, 3000);

    if (empty(trim($materi))) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Dokumen PDF kosong."]);
        exit();
    }

    $aiService = new OpenRouterService();

    $systemPrompt = 'Kamu adalah pembuat soal berbasis API. Buatlah TEPAT 10 soal pilihan ganda dari teks yang diberikan menggunakan Bahasa Indonesia. 
    Berikan HANYA array JSON murni tanpa kalimat sapaan. 
    Format Wajib: [{"pertanyaan": "...", "pilihan": {"A": "...", "B": "...", "C": "...", "D": "..."}, "kunci_jawaban": "A"}]
    Aturan Ketat: 
    1. Jumlah soal HARUS 10.
    2. Jangan gunakan baris baru (Enter/Newline) atau tab di dalam nilai teks. 
    3. Pastikan kurung tutup JSON sempurna di akhir.';

    $jsonResponse = $aiService->sendMessage($materi, $systemPrompt);

    preg_match('/\[.*\]/s', $jsonResponse, $matches);
    $cleanJson = isset($matches[0]) ? $matches[0] : '';
    
    $cleanJson = preg_replace('/[\x00-\x1F\x7F]/', '', $cleanJson);

    $soalArray = json_decode($cleanJson, true);
    $jsonError = json_last_error_msg(); 

    if (is_array($soalArray)) {
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "data" => $soalArray
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "AI merespons dengan format JSON yang salah. Detail PHP: " . $jsonError,
            "raw_response" => $jsonResponse
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Kesalahan sistem: " . $e->getMessage()
    ]);
}