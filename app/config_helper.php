<?php

declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $configFile = dirname(__DIR__) . '/config/app.php';

        if (!is_file($configFile)) {
            return $default;
        }

        $config = require $configFile;
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}