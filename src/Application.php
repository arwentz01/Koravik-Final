<?php

declare(strict_types=1);

namespace Koravik;

use Koravik\Districts\Quests\QuestService;
use Koravik\Platform\Events\OutboxWorker;
use Koravik\Platform\Security\Security;
use Koravik\Worlds\EpicOrdinary\EpicOrdinaryConsumer;
use RuntimeException;
use Throwable;

final class Application
{
    public function run(): void
    {
        $this->securityHeaders();
        Security::startSession();

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        try {
            if ($method === 'GET' && $path === '/') {
                $this->redirect(Security::account() ? '/hearth' : '/login');
            }
            if ($method === 'GET' && $path === '/health') {
                $this->json(['status' => 'ok', 'build' => '001']);
            }
            if ($method === 'GET' && $path === '/login') {
                $this->loginPage();
                return;
            }
            if ($method === 'POST' && $path === '/login') {
                $this->login();
                return;
            }
            if ($method === 'POST' && $path === '/logout') {
                $this->requireCsrf();
                Security::logout();
                $this->redirect('/login');
            }
            if ($method === 'GET' && $path === '/hearth') {
                $this->hearth();
                return;
            }
            if ($method === 'GET' && preg_match('#^/quests/([a-f0-9-]{36})$#', $path, $matches)) {
                $this->questDetail($matches[1]);
                return;
            }
            if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/complete$#', $path, $matches)) {
                $this->completeQuest($matches[1]);
                return;
            }
            if ($method === 'GET' && $path === '/world/reaction') {
                $this->worldReaction();
                return;
            }

            http_response_code(404);
            $this->render('Page not found', '<section class="panel"><h1>That path is not here.</h1><p>Return to Hearth and continue from there.</p><a class="button" href="/hearth">Return to Hearth</a></section>');
        } catch (Throwable $throwable) {
            error_log(sprintf('[koravik] %s: %s', get_class($throwable), $throwable->getMessage()));
            http_response_code(500);
            $message = filter_var(\env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL)
                ? self::escape($throwable->getMessage())
                : 'Koravik could not complete that request. Nothing has been lost.';
            $this->render('Something went wrong', '<section class="panel"><h1>We hit a snag.</h1><p>' . $message . '</p><a class="button" href="/hearth">Return to Hearth</a></section>');
        }
    }

    private function loginPage(?string $error = null): void
    {
        if (Security::account()) {
            $this->redirect('/hearth');
        }

        $errorHtml = $error ? '<div class="notice error" role="alert">' . self::escape($error) . '</div>' : '';
        $body = '<section class="auth-card"><p class="eyebrow">Welcome to Koravik</p><h1>Pick up where life left off.</h1><p>Sign in to see what matters now.</p>' . $errorHtml .
            '<form method="post" action="/login">' . $this->csrfField() .
            '<label>Email<input type="email" name="email" autocomplete="email" required></label>' .
            '<label>Password<input type="password" name="password" autocomplete="current-password" required></label>' .
            '<button class="button" type="submit">Sign in</button></form></section>';
        $this->render('Sign in', $body, false);
    }

    private function login(): void
    {
        $this->requireCsrf();
        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        if (!Security::attempt(\database()->pdo(), $email, $password)) {
            usleep(250000);
            http_response_code(422);
            $this->loginPage('That email and password did not match.');
            return;
        }
        $_SESSION['flash'] = 'Welcome back.';
        $this->redirect('/hearth');
    }

    private function hearth(): void
    {
        $account = Security::requireAccount();
        $quests = (new QuestService(\database()))->listForAccount((string) $account['id']);
        $reaction = $this->latestReaction((string) $account['id']);
        $flash = isset($_SESSION['flash']) ? (string) $_SESSION['flash'] : null;
        unset($_SESSION['flash']);

        $questHtml = '';
        foreach ($quests as $quest) {
            $completed = (bool) $quest['completed'];
            $questHtml .= '<article class="card"><div><p class="eyebrow">Quest</p><h2>' . self::escape((string) $quest['title']) . '</h2><p>' . self::escape((string) $quest['description']) . '</p></div>' .
                ($completed
                    ? '<span class="status complete">Complete</span>'
                    : '<a class="button" href="/quests/' . self::escape((string) $quest['id']) . '">Open Quest</a>') . '</article>';
        }

        $reactionHtml = $reaction
            ? '<article class="world-card"><p class="eyebrow">Epic Ordinary</p><h2>' . self::escape((string) $reaction['title']) . '</h2><p>' . self::escape((string) $reaction['message']) . '</p><a class="quiet-link" href="/world/reaction">See why this happened</a></article>'
            : '<article class="world-card muted"><p class="eyebrow">Epic Ordinary</p><h2>The story is listening.</h2><p>Complete today’s Quest to see how the World responds.</p></article>';

        $flashHtml = $flash ? '<div class="notice" role="status">' . self::escape($flash) . '</div>' : '';
        $body = $flashHtml . '<section class="hero"><p class="eyebrow">Hearth</p><h1>Good to see you, ' . self::escape((string) $account['display_name']) . '.</h1><p>One meaningful next step is enough.</p></section>' .
            '<section aria-labelledby="today-heading"><h2 id="today-heading">What matters now</h2><div class="grid">' . ($questHtml ?: '<p>No active Quests need your attention.</p>') . '</div></section>' .
            '<section aria-labelledby="world-heading"><h2 id="world-heading">What changed</h2>' . $reactionHtml . '</section>';
        $this->render('Hearth', $body);
    }

    private function questDetail(string $questId): void
    {
        $account = Security::requireAccount();
        $quest = (new QuestService(\database()))->getForAccount($questId, (string) $account['id']);
        if (!$quest) {
            http_response_code(404);
            $this->render('Quest unavailable', '<section class="panel"><h1>This Quest is unavailable.</h1><a class="button" href="/hearth">Return to Hearth</a></section>');
            return;
        }

        $action = (bool) $quest['completed']
            ? '<p class="status complete">This Quest is complete.</p><a class="button secondary" href="/hearth">Return to Hearth</a>'
            : '<form method="post" action="/quests/' . self::escape($questId) . '/complete">' . $this->csrfField() . '<button class="button" type="submit">Mark complete</button></form>';
        $body = '<section class="panel"><p class="eyebrow">Quest</p><h1>' . self::escape((string) $quest['title']) . '</h1><p>' . self::escape((string) $quest['description']) . '</p>' . $action . '</section>';
        $this->render((string) $quest['title'], $body);
    }

    private function completeQuest(string $questId): void
    {
        $this->requireCsrf();
        $account = Security::requireAccount();
        try {
            (new QuestService(\database()))->complete($questId, (string) $account['id']);
        } catch (RuntimeException $exception) {
            $_SESSION['flash'] = $exception->getMessage();
            $this->redirect('/hearth');
        }

        $worker = new OutboxWorker(\database(), new EpicOrdinaryConsumer(\database()));
        $worker->run(5);
        $_SESSION['flash'] = 'Quest complete. Epic Ordinary has responded.';
        $this->redirect('/hearth');
    }

    private function worldReaction(): void
    {
        $account = Security::requireAccount();
        $reaction = $this->latestReaction((string) $account['id']);
        if (!$reaction) {
            $this->render('World reaction', '<section class="panel"><h1>No World reaction yet.</h1><p>Complete the active Quest first.</p><a class="button" href="/hearth">Return to Hearth</a></section>');
            return;
        }
        $body = '<section class="panel world-detail"><p class="eyebrow">Epic Ordinary</p><h1>' . self::escape((string) $reaction['title']) . '</h1><blockquote>' . self::escape((string) $reaction['message']) . '</blockquote><h2>Why this happened</h2><p>' . self::escape((string) $reaction['explanation']) . '</p><p class="meta">Recorded ' . self::escape((string) $reaction['created_at']) . ' UTC</p><a class="button" href="/hearth">Return to Hearth</a></section>';
        $this->render('World reaction', $body);
    }

    private function latestReaction(string $accountId): ?array
    {
        $statement = \database()->pdo()->prepare(
            'SELECT wr.title, wr.message, wr.explanation, wr.created_at
             FROM world_reactions wr
             JOIN world_installations wi ON wi.id = wr.installation_id
             WHERE wi.account_id = :account_id AND wi.world_key = "epic-ordinary"
             ORDER BY wr.created_at DESC LIMIT 1'
        );
        $statement->execute(['account_id' => $accountId]);
        $reaction = $statement->fetch();
        return $reaction ?: null;
    }

    private function render(string $title, string $body, bool $authenticated = true): void
    {
        $account = Security::account();
        $navigation = $authenticated && $account
            ? '<header class="app-header"><a class="brand" href="/hearth">Koravik</a><nav aria-label="Primary"><a href="/hearth" aria-current="page">Hearth</a></nav><form method="post" action="/logout">' . $this->csrfField() . '<button class="quiet-button" type="submit">Sign out</button></form></header>'
            : '<header class="app-header simple"><span class="brand">Koravik</span></header>';

        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><title>' . self::escape($title) . ' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><a class="skip-link" href="#main">Skip to content</a>' . $navigation . '<main id="main" class="page" tabindex="-1">' . $body . '</main><footer>Koravik helps you act, then get back to living.</footer></body></html>';
    }

    private function csrfField(): string
    {
        return '<input type="hidden" name="csrf" value="' . self::escape(Security::csrfToken()) . '">';
    }

    private function requireCsrf(): void
    {
        if (!Security::verifyCsrf(isset($_POST['csrf']) ? (string) $_POST['csrf'] : null)) {
            http_response_code(419);
            throw new RuntimeException('Your session changed. Please try again.');
        }
    }

    private function redirect(string $location): never
    {
        header('Location: ' . $location, true, 303);
        exit;
    }

    private function json(array $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }

    private function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; style-src 'self'; img-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
