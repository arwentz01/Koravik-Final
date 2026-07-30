<?php

declare(strict_types=1);

namespace Koravik\Worlds;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class WorldHomeController
{
    public function __construct(private readonly Database $database)
    {
    }

    public function handle(string $method, string $path): bool
    {
        if ($method === 'GET' && $path === '/worlds') {
            $account = Security::requireAccount();
            $dashboard = (new WorldHomeService($this->database))->dashboard((string) $account['id']);
            $this->page('Worlds', $this->flash() . (new WorldHomeView())->render($dashboard));

            return true;
        }

        if ($method === 'POST' && preg_match('#^/worlds/reactions/([a-f0-9-]{36})/review$#', $path, $matches)) {
            $account = Security::requireAccount();
            $this->csrf();

            try {
                (new WorldHomeService($this->database))->markReactionReviewed(
                    (string) $account['id'],
                    $matches[1]
                );
                $_SESSION['flash'] = 'World change marked as reviewed.';
                $this->redirect('/worlds');
            } catch (RuntimeException $exception) {
                http_response_code(404);
                $this->page(
                    'World reaction unavailable',
                    '<section class="state-panel"><h1>That World change is unavailable.</h1><p>'
                    . self::e($exception->getMessage())
                    . '</p><a class="button" href="/worlds">Return to Worlds</a></section>'
                );
            }

            return true;
        }

        return false;
    }

    private function page(string $title, string $body): void
    {
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta name="color-scheme" content="light dark"><title>' . self::e($title) . ' · Koravik</title>'
            . '<link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/world-home.css">'
            . '</head><body><a class="skip-link" href="#main">Skip to content</a>'
            . '<main id="main" class="page" tabindex="-1">' . $body . '</main>'
            . '<footer>Koravik helps you act, then get back to living.</footer></body></html>';
    }

    private function csrf(): void
    {
        if (!Security::verifyCsrf((string) ($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Your session changed. Please try again.');
        }
    }

    private function flash(): string
    {
        $message = (string) ($_SESSION['flash'] ?? '');
        unset($_SESSION['flash']);

        return $message === '' ? '' : '<div class="notice" role="status">' . self::e($message) . '</div>';
    }

    private function redirect(string $location): never
    {
        header('Location: ' . $location, true, 303);
        exit;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
