<?php
// test_generate_mcq.php

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/vendor/autoload.php';

$filePdf = 'Memberikan-Otak-pada-Aplikasi.pdf';

if (!file_exists($filePdf)) {
    die("Error: File '$filePdf' tidak ditemukan.\n");
}

try {
    $parser = new \Smalot\PdfParser\Parser();$pdf = $parser->parseFile($filePdf);
    $rawText =$pdf->getText();

    $materi = substr($rawText, 0, 1500);

    $aiService = new OpenRouterService();

    $systemPrompt = 'Kamu adalah mesin generator soal untuk platform Learning Management System. Berdasarkan teks materi yang diberikan, buatlah 2 soal pilihan ganda. 
    Kembalikan HANYA dalam format array JSON murni tanpa pembuka/penutup teks apapun. 
    Struktur wajib: [{"pertanyaan": "...", "pilihan": {"A": "...", "B": "...", "C": "...", "D": "..."}, "kunci_jawaban": "A"}]';

    echo "Memproses materi ke AI untuk diubah menjadi soal...\n\n";

    $jsonResponse =$aiService->sendMessage($materi,$systemPrompt);

    echo "=== Teks Mentah dari AI ===\n";
    echo $jsonResponse . "\n\n";

    $cleanJson = str_replace(['```json', '```'], '', $jsonResponse);
    $soalArray = json_decode(trim($cleanJson), true);

    echo "=== Hasil Konversi Array PHP ===\n";
    if (is_array($soalArray)) {
        echo "Berhasil! Struktur ini sudah siap di-looping dan dimasukkan ke Database.\n\n";
        print_r($soalArray);
    } else {
        echo "Peringatan: Gagal mem-parsing JSON. AI mungkin melanggar aturan format.\n";
    }

} catch (Exception $e) {
    echo "Terjadi kesalahan: " . $e->getMessage() . "\n";
}