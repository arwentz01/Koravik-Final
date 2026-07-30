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

function app_base_path(): string
{
    $path = parse_url(env('APP_URL', '') ?? '', PHP_URL_PATH);
    if (!is_string($path) || $path === '' || $path === '/') return '';
    return '/' . trim($path, '/');
}

function app_request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = app_base_path();
    if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
        $path = substr($path, strlen($base)) ?: '/';
    }
    return str_starts_with($path, '/') ? $path : '/' . $path;
}

function app_with_base_path(string $path): string
{
    if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) return $path;
    $base = app_base_path();
    return $base === '' || $path === $base || str_starts_with($path, $base . '/') ? $path : $base . $path;
}

function app_rewrite_html_paths(string $html): string
{
    if (app_base_path() === '') return $html;
    return preg_replace_callback(
        '/\b(href|src|action)="(\/(?!\/)[^"]*)"/i',
        static fn(array $match): string => $match[1] . '="' . app_with_base_path($match[2]) . '"',
        $html
    ) ?? $html;
}

register_shutdown_function(static function (): void {
    $base = app_base_path();
    if ($base === '') return;
    foreach (headers_list() as $header) {
        if (!str_starts_with(strtolower($header), 'location:')) continue;
        $location = trim(substr($header, strlen('Location:')));
        if ($location === '' || !str_starts_with($location, '/') || str_starts_with($location, '//')) return;
        header_remove('Location');
        header('Location: ' . app_with_base_path($location));
        return;
    }
});

function database(): Database
{
    static $database;
    return $database ??= Database::connect(
        env('DB_DSN', '') ?? '',
        env('DB_USER', '') ?? '',
        env('DB_PASS', '') ?? ''
    );
}
