<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($method === 'GET' && $path === '/health') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok', 'build' => '008'], JSON_THROW_ON_ERROR);
    return;
}

Koravik\Platform\Security\Security::startSession();

$returnController = new Koravik\Platform\ReturnExperience\ReturnController(database());
if ($returnController->handle()) {
    return;
}

$worldController = new Koravik\Worlds\WorldController();
if ($worldController->handle($method, $path)) {
    return;
}

ob_start();
(new Koravik\Application())->run();
$html = (string) ob_get_clean();

if (str_contains($html, '<nav aria-label="Primary">') && !str_contains($html, 'href="/worlds"')) {
    $html = str_replace('</nav>', '<a href="/worlds">Worlds</a></nav>', $html);
}

echo $html;
