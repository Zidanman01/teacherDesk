<?php

header('Content-Type: application/json');

echo json_encode([
    'php_version' => PHP_VERSION,
    'curl' => extension_loaded('curl'),
    'fileinfo' => extension_loaded('fileinfo'),
    'mbstring' => extension_loaded('mbstring'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'json' => extension_loaded('json'),
    'vendor_autoload' => file_exists(__DIR__ . '/vendor/autoload.php'),
]);