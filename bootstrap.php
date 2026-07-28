<?php

declare(strict_types=1);

use Koravik\Platform\Database\Database;

const KORAVIK_ROOT = __DIR__;

spl_autoload_register(static function (string $class): void {
    $prefix = 'Koravik\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = KORAVIK_ROOT . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

load_env(KORAVIK_ROOT . '/.env');
date_default_timezone_set(env('APP_TIMEZONE', 'UTC') ?? 'UTC');

function database(): Database
{
    static $database;
    return $database ??= Database::connect(
        env('DB_DSN', '') ?? '',
        env('DB_USER', '') ?? '',
        env('DB_PASS', '') ?? ''
    );
}
