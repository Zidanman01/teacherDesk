<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

require_once __DIR__ . '/../app/helpers.php';
load_env(dirname(__DIR__) . '/.env');

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

require_once __DIR__ . '/../app/SchemaManager.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/QuestionGenerator.php';
require_once __DIR__ . '/../app/BackupService.php';
require_once __DIR__ . '/../app/actions.php';
