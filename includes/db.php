<?php
declare(strict_types=1);

function db(): ?PDO
{
    static $pdo = null;
    static $failed = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if ($failed) {
        return null;
    }

    $localConfig = [];
    $localConfigPath = __DIR__ . '/config.local.php';

    if (is_file($localConfigPath)) {
        $loadedConfig = require $localConfigPath;
        if (is_array($loadedConfig)) {
            $localConfig = $loadedConfig;
        }
    }

    $host = getenv('ECOCART_DB_HOST') ?: (string) ($localConfig['host'] ?? '127.0.0.1');
    $name = getenv('ECOCART_DB_NAME') ?: (string) ($localConfig['name'] ?? 'ecocart_demo');
    $user = getenv('ECOCART_DB_USER') ?: (string) ($localConfig['user'] ?? 'root');
    $pass = getenv('ECOCART_DB_PASS') ?: (string) ($localConfig['pass'] ?? '');

    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (Throwable $error) {
        $failed = true;
        return null;
    }

    return $pdo;
}

function db_is_ready(): bool
{
    return db() instanceof PDO;
}
