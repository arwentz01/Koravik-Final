<?php

declare(strict_types=1);

namespace Koravik\Worlds;

use Koravik\Platform\Security\Security;
use RuntimeException;

final class WorldController
{
    public function handle(string $method, string $path): bool
    {
        if (!str_starts_with($path, '/worlds')) {
            return false;
        }

        Security::startSession();
        $account = Security::requireAccount();
        $accountId = (string) $account['id'];
        $service = new WorldService(\database());

        if ($method === 'GET' && $path === '/worlds') {
            $this->catalog($service->catalog($accountId));
            return true;
        }
        if ($method === 'GET' && preg_match('#^/worlds/([a-z0-9-]+)$#', $path, $matches)) {
            $this->detail($service->detail($matches[1], $accountId));
            return true;
        }
        if ($method === 'POST' && preg_match('#^/worlds/([a-z0-9-]+)/(install|resume|suspend|uninstall)$#', $path, $matches)) {
            $this->requireCsrf();
            if ($matches[2] === 'install') {
                $service->install($matches[1], $accountId);
                $_SESSION['flash'] = 'World installed and activated.';
            } else {
                $status = match ($matches[2]) {
                    'resume' => 'active',
                    'suspend' => 'suspended',
                    'uninstall' => 'uninstalled',
                };
                $service->setStatus($matches[1], $accountId, $status);
                $_SESSION['flash'] = match ($status) {
                    'active' => 'World resumed.',
                    'suspended' => 'World suspended. Its state is preserved.',
                    default => 'World uninstalled. Its state is retained unless separately reset.',
                };
            }
            $this->redirect('/worlds/' . $matches[1]);
        }
        if ($method === 'POST' && preg_match('#^/worlds/([a-z0-9-]+)/permissions/quest-completed/(grant|revoke)$#', $path, $matches)) {
            $this->requireCsrf();
            $service->setPermission($matches[1], $accountId, $matches[2] === 'grant');
            $_SESSION['flash'] = $matches[2] === 'grant' ? 'Quest completion access granted.' : 'Quest completion access revoked. Future reactions from that fact will stop.';
            $this->redirect('/worlds/' . $matches[1]);
        }

        http_response_code(404);
        $this->render('World unavailable', '<section class="panel"><h1>That World is unavailable.</h1><a class="button" href="/worlds">Return to Worlds</a></section>');
        return true;
    }

    private function catalog(array $worlds): void
    {
        $cards = '';
        foreach ($worlds as $world) {
            $status = $world['installation_status'] ? ucfirst((string) $world['installation_status']) : 'Not installed';
            $cards .= '<article class="card"><div><p class="eyebrow">World</p><h2>' . self::escape((string) $world['name']) . '</h2><p>' . self::escape((string) $world['tagline']) . '</p><p class="meta">' . self::escape($status) . ' · Package ' . self::escape((string) $world['package_version']) . '</p></div><a class="button" href="/worlds/' . self::escape((string) $world['world_key']) . '">Review World</a></article>';
        }
        $body = $this->flashHtml() . '<section class="page-heading world-catalog-preview"><div><p class="eyebrow">World catalog</p><h1>Stories that respond with permission.</h1><p>Worlds are optional. Review requested event subscriptions, content notices, accessibility notes, data minimization, compatibility, and storage consequences before installing.</p></div><a class="button secondary" href="/worlds/installed">Installed Worlds</a></section><div class="grid">' . ($cards ?: '<article class="empty-state"><h2>No Worlds are available.</h2></article>') . '</div>';
        $this->render('Worlds', $body);
    }

    private function detail(?array $world): void
    {
        if (!$world) {
            http_response_code(404);
            $this->render('World unavailable', '<section class="panel"><h1>That World is unavailable.</h1><a class="button" href="/worlds">Return to Worlds</a></section>');
            return;
        }

        $installed = !empty($world['installation_id']);
        $status = (string) ($world['installation_status'] ?? 'not-installed');
        $granted = (bool) ($world['quest_completed_granted'] ?? false);
        $permission = $installed
            ? '<section class="panel"><p class="eyebrow">Fact permission</p><h2>Quest occurrence completed</h2><p>' . self::escape((string) ($world['permission_explanation'] ?? 'Allows a minimized completion fact.')) . '</p><p class="status ' . ($granted ? 'complete' : '') . '">' . ($granted ? 'Granted' : 'Revoked') . '</p><form method="post" action="/worlds/' . self::escape((string) $world['world_key']) . '/permissions/quest-completed/' . ($granted ? 'revoke' : 'grant') . '">' . $this->csrfField() . '<button class="button secondary" type="submit">' . ($granted ? 'Revoke access' : 'Grant access') . '</button></form></section>'
            : '';

        if (!$installed || $status === 'uninstalled') {
            $action = '<form method="post" action="/worlds/' . self::escape((string) $world['world_key']) . '/install">' . $this->csrfField() . '<button class="button" type="submit">Install and activate</button></form>';
        } elseif ($status === 'active') {
            $action = '<div class="inline-actions"><form method="post" action="/worlds/' . self::escape((string) $world['world_key']) . '/suspend">' . $this->csrfField() . '<button class="button secondary" type="submit">Suspend</button></form><form method="post" action="/worlds/' . self::escape((string) $world['world_key']) . '/uninstall">' . $this->csrfField() . '<button class="quiet-button" type="submit">Uninstall, retain state</button></form></div>';
        } else {
            $action = '<div class="inline-actions"><form method="post" action="/worlds/' . self::escape((string) $world['world_key']) . '/resume">' . $this->csrfField() . '<button class="button" type="submit">Resume</button></form><form method="post" action="/worlds/' . self::escape((string) $world['world_key']) . '/uninstall">' . $this->csrfField() . '<button class="quiet-button" type="submit">Uninstall, retain state</button></form></div>';
        }

        $preview='<section class="panel world-permission-preview world-progress-continuity-pass"><p class="eyebrow">Permission preview · World Progress Continuity Pass</p><h2>Requested subscriptions and continuity</h2><dl><div><dt>Quest occurrence completed</dt><dd>Receives a minimized completion fact only. Quest notes, titles from unrelated records, Health notes, Chronicle prose, Companion memory, and account secrets stay excluded.</dd></div><div><dt>Player returned</dt><dd>May acknowledge a meaningful return without seeing stale item details.</dd></div><div><dt>Room changes and Moments</dt><dd>Approved facts may create World reactions, Healing Home room evidence, and Moment ambience while Worlds still owns fictional interpretation.</dd></div><div><dt>Revocation</dt><dd>You can revoke future delivery from Privacy without deleting District history or existing audit evidence.</dd></div></dl></section>';
        $body = $this->flashHtml() . '<section class="panel world-detail"><p class="eyebrow">World detail and permission preview</p><h1>' . self::escape((string) $world['name']) . '</h1><p class="lead">' . self::escape((string) $world['tagline']) . '</p><p>' . self::escape((string) $world['description']) . '</p><h2>Content notice</h2><p>' . self::escape((string) $world['content_notice']) . '</p><h2>Accessibility</h2><p>' . self::escape((string) $world['accessibility_notes']) . '</p><p class="meta">Package ' . self::escape((string) $world['package_version']) . ' · Status: ' . self::escape(ucfirst($status)) . '</p>' . $action . '</section>' . $preview . $permission;
        $this->render((string) $world['name'], $body);
    }

    private function render(string $title, string $body): void
    {
        $navigation = '<header class="app-header"><a class="brand" href="/hearth">Koravik</a><nav aria-label="Primary"><a href="/hearth">Hearth</a><a href="/quests">Quests</a><a href="/chronicle">Chronicle</a><a href="/worlds" aria-current="page">Worlds</a></nav><form method="post" action="/logout">' . $this->csrfField() . '<button class="quiet-button" type="submit">Sign out</button></form></header>';
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><title>' . self::escape($title) . ' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><a class="skip-link" href="#main">Skip to content</a>' . $navigation . '<main id="main" class="page" tabindex="-1">' . $body . '</main><footer>Koravik helps you act, then get back to living.</footer></body></html>';
    }

    private function flashHtml(): string
    {
        $flash = isset($_SESSION['flash']) ? (string) $_SESSION['flash'] : null;
        unset($_SESSION['flash']);
        return $flash ? '<div class="notice" role="status">' . self::escape($flash) . '</div>' : '';
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

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
