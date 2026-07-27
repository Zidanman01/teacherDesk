<?php
require_once __DIR__ . '/config/bootstrap.php';

$aiService = new OpenRouterService();
$chatHistory = [];

$systemPrompt = "Kamu adalah asisten pengajar profesional dan konsultan pendidikan yang terintegrasi di dalam dashboard Learning Management System (LMS). " .
                "Tugas utamamu adalah membantu instruktur atau guru dalam merancang materi, mengevaluasi siswa, dan menyusun strategi mengajar, khususnya pada bidang Rekayasa Perangkat Lunak. " .
                "Kamu memiliki keahlian dalam merancang metode Project Based Learning, menyusun elemen gamifikasi dalam edukasi, dan merumuskan alur pembelajaran adaptif. " .
                "Berikan jawaban yang taktis, terstruktur, dan suportif.";

$chatHistory[] = [
    "role" => "system",
    "content" => $systemPrompt
];

echo "========================================================\n";
echo "    AI TEACHER ASSISTANT - DASHBOARD KONSULTASI         \n";
echo "    (Ketik 'exit' untuk keluar)                         \n";
echo "========================================================\n";

while (true) {
    echo "\nAnda (Pengajar): ";
    $inputUser = trim(fgets(STDIN));

    if (strtolower($inputUser) === 'exit') {
        echo "AI Assistant: Sesi konsultasi ditutup. Selamat melanjutkan manajemen kelas Anda!\n";
        break;
    }

    if (empty($inputUser)) continue;

    $chatHistory[] = [
        "role" => "user", 
        "content" => $inputUser
    ];

    echo "AI sedang memproses...\n";

    $balasanAi = $aiService->sendConversation($chatHistory);

    echo "AI Assistant: \n" . $balasanAi . "\n";

    $chatHistory[] = [
        "role" => "assistant", 
        "content" => $balasanAi
    ];
}