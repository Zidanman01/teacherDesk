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
    echo json_encode(["status" => "error", "message" => "Metode tidak diizinkan. Gunakan POST."]);
    exit();
}

if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "File PDF tidak ditemukan atau gagal diunggah."]);
    exit();
}

$fileTmpPath = $_FILES['document']['tmp_name'];
$fileMimeType = mime_content_type($fileTmpPath);

if ($fileMimeType !== 'application/pdf') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Format file harus PDF."]);
    exit();
}

try {
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($fileTmpPath);
    $rawText = $pdf->getText();
    $materi = substr($rawText, 0, 1500);

    if (empty(trim($materi))) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Dokumen PDF kosong atau teks tidak terbaca."]);
        exit();
    }

    $aiService = new OpenRouterService();
    $systemPrompt = 'Kamu adalah mesin generator soal untuk platform Learning Management System. Berdasarkan teks materi yang diberikan, buatlah 10 soal pilihan ganda. 
    Kembalikan HANYA dalam format array JSON murni tanpa pembuka/penutup teks apapun. 
    Struktur wajib: [{"pertanyaan": "...", "pilihan": {"A": "...", "B": "...", "C": "...", "D": "..."}, "kunci_jawaban": "A"}]';

    $jsonResponse = $aiService->sendMessage($materi, $systemPrompt);

    $cleanJson = str_replace(['```json', '```'], '', $jsonResponse);
    $soalArray = json_decode(trim($cleanJson), true);

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
            "message" => "Gagal memproses soal dari AI, format rusak.",
            "raw_response" => $jsonResponse
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Terjadi kesalahan sistem: " . $e->getMessage()
    ]);
}