<?php

declare(strict_types=1);

function app_log(string $level, string $message, array $context = []): void
{
    $directory = dirname(__DIR__) . '/storage/logs';

    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    $record = [
        'time' => date(DATE_ATOM),
        'level' => strtoupper($level),
        'message' => $message,
        'context' => $context,
    ];

    file_put_contents(
        $directory . '/teacherdesk.log',
        json_encode($record, JSON_UNESCAPED_UNICODE) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}