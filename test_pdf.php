<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "Memulai proses ekstraksi teks PDF...\n\n";

$filePdf = 'Memberikan-Otak-pada-Aplikasi.pdf';

if (!file_exists($filePdf)) {
    die("Error: File '$filePdf' tidak ditemukan. Silakan taruh file PDF uji coba di folder ini.\n");
}

try {
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($filePdf);
    $text = $pdf->getText();
    
    echo "=== Hasil Ekstraksi Teks ===\n";

    $previewText = substr($text, 0, 500); 
    echo $previewText . "...\n";
    
    echo "\n============================\n";
    echo "Status: Berhasil mengekstrak teks!\n";

} catch (Exception $e) {
    echo "Terjadi kesalahan saat mem-parsing PDF: " . $e->getMessage() . "\n";
}