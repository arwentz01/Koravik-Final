<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

Koravik\Platform\Security\Security::startSession();

$returnController = new Koravik\Platform\ReturnExperience\ReturnController(database());
if ($returnController->handle()) {
    return;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

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
