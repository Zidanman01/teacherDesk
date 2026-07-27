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
require_once __DIR__ . '/app/Database.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metode tidak diizinkan. Gunakan POST."]);
    exit();
}

$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true);

if (!isset($inputData['messages']) || !is_array($inputData['messages'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Data 'messages' tidak ditemukan atau format salah."]);
    exit();
}

$chatHistory = $inputData['messages'];
$sessionId = $inputData['session_id'] ?? 'default_session'; 

try {
    $lastUserMessage = end($chatHistory);
    $userContent = $lastUserMessage['content'] ?? '';

    $conn = Database::connection();

    if (!empty($userContent) && $lastUserMessage['role'] === 'user') {
        $stmtUser = $conn->prepare("INSERT INTO chat_history (session_id, role, content) VALUES (?, 'user', ?)");
        $stmtUser->execute([$sessionId, $userContent]);
    }

    $aiService = new OpenRouterService();
    $balasanAi = $aiService->sendConversation($chatHistory);

    if (!empty($balasanAi)) {
        $stmtAi = $conn->prepare("INSERT INTO chat_history (session_id, role, content) VALUES (?, 'assistant', ?)");
        $stmtAi->execute([$sessionId, $balasanAi]);
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "reply" => $balasanAi
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Terjadi kesalahan server: " . $e->getMessage()
    ]);
}