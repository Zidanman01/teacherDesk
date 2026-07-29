<?php

declare(strict_types=1);

class OpenRouterService
{
    private string $apiKey;
    private string $baseUrl = 'https://openrouter.ai/api/v1/chat/completions';
    private int $connectTimeout;
    private int $requestTimeout;
    private int $maxRetries;

    public function __construct()
    {
        $this->apiKey = (string) env('OPENROUTER_API_KEY', '');
        $this->connectTimeout = max(3, (int) env('OPENROUTER_CONNECT_TIMEOUT', '10'));
        $this->requestTimeout = max(15, (int) env('OPENROUTER_REQUEST_TIMEOUT', '120'));
        $this->maxRetries = min(2, max(0, (int) env('OPENROUTER_MAX_RETRIES', '1')));

        if ($this->apiKey === '') {
            throw new RuntimeException('API Key OpenRouter tidak ditemukan.');
        }
    }

    public function sendMessage(
        string $prompt,
        string $systemPrompt = '',
        string $model = 'google/gemma-4-26b-a4b-it:free',
        array $options = []
    ): string {
        $messages = [];
        if ($systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $this->request($messages, $model, $options);
    }

    public function sendConversation(
        array $messages,
        string $model = 'nvidia/nemotron-3-super-120b-a12b:free',
        array $options = []
    ): string {
        return $this->request($messages, $model, $options);
    }

    private function request(array $messages, string $model, array $options = []): string
    {
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => isset($options['temperature']) ? (float) $options['temperature'] : 0.35,
        ];

        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = max(256, (int) $options['max_tokens']);
        }

        if (!empty($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encodedPayload === false) {
            throw new RuntimeException('Payload OpenRouter tidak dapat dibuat.');
        }

        $lastError = 'Permintaan OpenRouter gagal.';

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            $ch = curl_init($this->baseUrl);
            if ($ch === false) {
                throw new RuntimeException('Ekstensi cURL tidak dapat diinisialisasi.');
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $encodedPayload,
                CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
                CURLOPT_TIMEOUT => $this->requestTimeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'HTTP-Referer: ' . (string) env('APP_URL', 'http://localhost/teacherDesk'),
                    'X-Title: TeacherDesk',
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            if ($response === false) {
                $lastError = 'Gagal terhubung ke OpenRouter: ' . ($curlError ?: 'kesalahan jaringan tidak diketahui.');
            } else {
                $responseData = json_decode($response, true);

                if ($httpCode >= 200 && $httpCode < 300) {
                    $content = $responseData['choices'][0]['message']['content'] ?? null;
                    if (is_string($content) && trim($content) !== '') {
                        return $content;
                    }
                    $lastError = 'OpenRouter mengirim respons tanpa isi yang dapat digunakan.';
                } else {
                    $apiMessage = $responseData['error']['message'] ?? null;
                    $lastError = is_string($apiMessage) && $apiMessage !== ''
                        ? 'OpenRouter menolak permintaan: ' . $apiMessage
                        : 'OpenRouter gagal merespons (HTTP ' . $httpCode . ').';
                }
            }

            $shouldRetry = $attempt < $this->maxRetries
                && ($httpCode === 0 || $httpCode === 408 || $httpCode === 429 || $httpCode >= 500);

            if (!$shouldRetry) {
                break;
            }

            usleep(350000 * ($attempt + 1));
        }

        throw new RuntimeException($lastError);
    }
}