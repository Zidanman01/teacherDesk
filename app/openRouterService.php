<?php

class OpenRouterService {
    private string $apiKey;
    private string $baseUrl = "https://openrouter.ai/api/v1/chat/completions";

    public function __construct() {
        $this->apiKey = env('OPENROUTER_API_KEY', '');
        
        if (empty($this->apiKey)) {
            throw new Exception("API Key OpenRouter tidak ditemukan.");
        }
    }

    public function sendMessage(string $prompt, string $systemPrompt = "", string $model = "nvidia/nemotron-3-ultra-550b-a55b:free"): string {
        $messages = [];
        
        if (!empty($systemPrompt)) {
            $messages[] = [
                "role" => "system", 
                "content" => $systemPrompt
            ];
        }
        
        $messages[] = [
            "role" => "user", 
            "content" => $prompt
        ];

        $data = [
            "model" => $model,
            "messages" => $messages
        ];

        $ch = curl_init($this->baseUrl);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->apiKey,
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 200) {
            $responseData = json_decode($response, true);
            if (isset($responseData['choices'][0]['message']['content'])) {
                return $responseData['choices'][0]['message']['content'];
            }
            return "Error: Format respons dari API tidak sesuai.";
        }

        return "Error: Gagal menghubungi API (HTTP " . $httpCode . "). Detail: " . $response;
    }

    public function sendConversation(array $messages, string $model = "nvidia/nemotron-3-ultra-550b-a55b:free"): string {
        $data = [
            "model" => $model,
            "messages" => $messages
        ];

        $ch = curl_init($this->baseUrl);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->apiKey,
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 200) {
            $responseData = json_decode($response, true);
            if (isset($responseData['choices'][0]['message']['content'])) {
                return $responseData['choices'][0]['message']['content'];
            }
            return "Error: Format respons dari API tidak sesuai.";
        }

        return "Error: Gagal menghubungi API (HTTP " . $httpCode . ").";
    }
}