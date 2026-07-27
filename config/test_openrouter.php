<?php

require_once __DIR__ . '/bootstrap.php'; 

$aiService = new OpenRouterService();

$pertanyaan = "Berikan saya satu ide nama aplikasi yang bagus untuk sistem manajemen sekolah.";
$jawaban = $aiService->sendMessage($pertanyaan);

echo "=== Balasan AI ===\n";
echo $jawaban . "\n";