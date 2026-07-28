<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Koravik\Platform\Notifications\NotificationController;
use Koravik\Platform\Notifications\NotificationService;
use Koravik\Platform\Privacy\PrivacyController;
use Koravik\Platform\Search\SearchController;
use Koravik\Platform\Security\Security;

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($method === 'GET' && $path === '/health') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok', 'build' => '011'], JSON_THROW_ON_ERROR);
    return;
}

Security::startSession();
ob_start();

$handled = (new PrivacyController(database()))->handle($method, $path);
if (!$handled) $handled = (new SearchController(database()))->handle($method, $path);
if (!$handled) $handled = (new NotificationController(database()))->handle($method, $path);
if (!$handled) $handled = (new Koravik\Platform\ReturnExperience\ReturnController(database()))->handle();
if (!$handled) $handled = (new Koravik\Worlds\WorldController())->handle($method, $path);
if (!$handled) (new Koravik\Application())->run();

$html = (string) ob_get_clean();
$account = Security::account();
if ($account && str_contains($html, '<nav aria-label="Primary">')) {
    if (!str_contains($html, 'href="/worlds"')) $html = str_replace('</nav>', '<a href="/worlds">Worlds</a></nav>', $html);
    if (!str_contains($html, 'href="/search"')) $html = str_replace('</nav>', '<a href="/search">Search</a></nav>', $html);
    if (!str_contains($html, 'href="/notifications"')) {
        $count = (new NotificationService(database()))->unreadCount((string) $account['id']);
        $badge = $count > 0 ? '<span class="notification-badge" aria-label="' . $count . ' unread notifications">' . ($count > 9 ? '9+' : $count) . '</span>' : '';
        $html = str_replace('</nav>', '<a href="/notifications">Notifications' . $badge . '</a></nav>', $html);
    }
    if (!str_contains($html, 'href="/privacy"')) $html = str_replace('</nav>', '<a href="/privacy">Privacy</a></nav>', $html);
}

echo $html;
