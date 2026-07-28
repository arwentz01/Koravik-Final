<?php

declare(strict_types=1);

namespace Koravik;

use Koravik\Districts\Quests\QuestService;
use Koravik\Platform\Events\CompositeConsumer;
use Koravik\Platform\Events\OutboxWorker;
use Koravik\Platform\Experience\ExperienceConsumer;
use Koravik\Platform\Experience\ExperienceService;
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
            if ($method === 'GET' && $path === '/') $this->redirect(Security::account() ? '/hearth' : '/login');
            if ($method === 'GET' && $path === '/health') $this->json(['status' => 'ok', 'build' => '004']);
            if ($method === 'GET' && $path === '/login') { $this->loginPage(); return; }
            if ($method === 'POST' && $path === '/login') { $this->login(); return; }
            if ($method === 'POST' && $path === '/logout') { $this->requireCsrf(); Security::logout(); $this->redirect('/login'); }
            if ($method === 'GET' && $path === '/hearth') { $this->hearth(); return; }
            if ($method === 'GET' && $path === '/chronicle') { $this->chronicle(); return; }
            if ($method === 'GET' && $path === '/quests') { $this->quests(); return; }
            if ($method === 'GET' && $path === '/quests/create') { $this->createQuestPage(); return; }
            if ($method === 'POST' && $path === '/quests') { $this->createQuest(); return; }
            if ($method === 'GET' && preg_match('#^/quests/([a-f0-9-]{36})$#', $path, $matches)) { $this->questDetail($matches[1]); return; }
            if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/complete$#', $path, $matches)) { $this->completeQuest($matches[1]); return; }
            if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/(pause|resume|archive|restore)$#', $path, $matches)) { $this->changeQuestLifecycle($matches[1], $matches[2]); return; }
            if ($method === 'GET' && preg_match('#^/completions/([a-f0-9-]{36})$#', $path, $matches)) { $this->completionSummary($matches[1]); return; }
            if ($method === 'POST' && preg_match('#^/completions/([a-f0-9-]{36})/reflection$#', $path, $matches)) { $this->saveReflection($matches[1]); return; }
            if ($method === 'POST' && preg_match('#^/completions/([a-f0-9-]{36})/undo$#', $path, $matches)) { $this->undoCompletion($matches[1]); return; }
            if ($method === 'GET' && $path === '/world/reaction') { $this->worldReaction(); return; }

            http_response_code(404);
            $this->render('Page not found', '<section class="panel"><h1>That path is not here.</h1><p>Return to Hearth and continue from there.</p><a class="button" href="/hearth">Return to Hearth</a></section>');
        } catch (Throwable $throwable) {
            error_log(sprintf('[koravik] %s: %s', get_class($throwable), $throwable->getMessage()));
            http_response_code(500);
            $message = filter_var(\env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL) ? self::escape($throwable->getMessage()) : 'Koravik could not complete that request. Nothing has been lost.';
            $this->render('Something went wrong', '<section class="panel"><h1>We hit a snag.</h1><p>' . $message . '</p><a class="button" href="/hearth">Return to Hearth</a></section>');
        }
    }

    private function loginPage(?string $error = null): void
    {
        if (Security::account()) $this->redirect('/hearth');
        $errorHtml = $error ? '<div class="notice error" role="alert">' . self::escape($error) . '</div>' : '';
        $body = '<section class="auth-card"><p class="eyebrow">Welcome to Koravik</p><h1>Pick up where life left off.</h1><p>Sign in to see what matters now.</p>' . $errorHtml . '<form method="post" action="/login">' . $this->csrfField() . '<label>Email<input type="email" name="email" autocomplete="email" required></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button class="button" type="submit">Sign in</button></form></section>';
        $this->render('Sign in', $body, false);
    }

    private function login(): void
    {
        $this->requireCsrf();
        if (!Security::attempt(\database()->pdo(), (string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            usleep(250000); http_response_code(422); $this->loginPage('That email and password did not match.'); return;
        }
        $_SESSION['flash'] = 'Welcome back.';
        $this->redirect('/hearth');
    }

    private function hearth(): void
    {
        $account = Security::requireAccount();
        $accountId = (string) $account['id'];
        $quests = (new QuestService(\database()))->listForAccount($accountId);
        $experience = (new ExperienceService(\database()))->dashboard($accountId);
        $reaction = $this->latestReaction($accountId);
        $pillarHtml = '';
        foreach ($experience['pillars'] as $pillar) {
            $pillarHtml .= '<article class="mini-card"><strong>' . self::escape((string) $pillar['name']) . '</strong><span>' . (int) $pillar['contribution_count'] . ' meaningful action' . ((int) $pillar['contribution_count'] === 1 ? '' : 's') . '</span></article>';
        }
        $chronicle = $experience['chronicle'][0] ?? null;
        $chronicleHtml = $chronicle ? '<article class="chronicle-card"><p class="eyebrow">Chronicle</p><h2>' . self::escape((string) $chronicle['title']) . '</h2><p>' . self::escape((string) $chronicle['body']) . '</p><a href="/chronicle">Open Chronicle</a></article>' : '<article class="chronicle-card muted"><p class="eyebrow">Chronicle</p><h2>Your story will gather here.</h2><p>Completed actions and reflections become a calm record, not a scorecard.</p></article>';
        $reactionHtml = $reaction ? '<article class="world-card"><p class="eyebrow">Epic Ordinary</p><h2>' . self::escape((string) $reaction['title']) . '</h2><p>' . self::escape((string) $reaction['message']) . '</p><a class="quiet-link" href="/world/reaction">See why this happened</a></article>' : '<article class="world-card muted"><p class="eyebrow">Epic Ordinary</p><h2>The story is listening.</h2><p>Complete a Quest occurrence to see how the World responds.</p></article>';
        $body = $this->flashHtml($this->takeFlash()) . '<section class="hero"><p class="eyebrow">Hearth</p><h1>Good to see you, ' . self::escape((string) $account['display_name']) . '.</h1><p>One meaningful next step is enough.</p><div class="hero-actions"><a class="button" href="/quests/create">New Quest</a><a class="button secondary" href="/quests">View Quests</a></div></section>' .
            '<section aria-labelledby="today-heading"><div class="section-heading"><h2 id="today-heading">What matters now</h2><a href="/quests/create">Add a Quest</a></div><div class="grid">' . ($this->questCards(array_slice($quests, 0, 3)) ?: '<article class="empty-state"><h3>Nothing is asking for your attention.</h3><p>Create one useful next step when you are ready.</p></article>') . '</div></section>' .
            ($pillarHtml ? '<section aria-labelledby="pillars-heading"><h2 id="pillars-heading">What you have been supporting</h2><div class="mini-grid">' . $pillarHtml . '</div></section>' : '') .
            '<section class="experience-grid" aria-label="Recent meaning and story">' . $chronicleHtml . $reactionHtml . '</section>';
        $this->render('Hearth', $body, 'hearth');
    }

    private function chronicle(): void
    {
        $account = Security::requireAccount();
        $entries = (new ExperienceService(\database()))->dashboard((string) $account['id'])['chronicle'];
        $html = '';
        foreach ($entries as $entry) {
            $html .= '<article class="chronicle-entry"><p class="eyebrow">' . self::escape(ucfirst((string) $entry['entry_type'])) . '</p><h2>' . self::escape((string) $entry['title']) . '</h2><p>' . self::escape((string) $entry['body']) . '</p><p class="meta">' . self::escape((string) $entry['created_at']) . ' UTC</p></article>';
        }
        $body = '<section class="page-heading"><div><p class="eyebrow">Chronicle</p><h1>Moments worth remembering.</h1><p>A quiet record of action, reflection, and story.</p></div></section><div class="chronicle-list">' . ($html ?: '<article class="empty-state"><h2>No Chronicle moments yet.</h2><p>Complete a Quest or add a reflection when something feels worth keeping.</p></article>') . '</div>';
        $this->render('Chronicle', $body, 'chronicle');
    }

    private function quests(): void
    {
        $account = Security::requireAccount();
        $quests = (new QuestService(\database()))->listForAccount((string) $account['id']);
        $body = $this->flashHtml($this->takeFlash()) . '<section class="page-heading"><div><p class="eyebrow">Quests</p><h1>Actions with a wider purpose.</h1><p>Quests can repeat, pause, and feed the rest of Koravik without becoming an overdue wall.</p></div><a class="button" href="/quests/create">New Quest</a></section><div class="grid">' . ($this->questCards($quests) ?: '<article class="empty-state"><h2>No active Quests.</h2><p>Start with one action that would make life a little easier.</p></article>') . '</div>';
        $this->render('Quests', $body, 'quests');
    }

    private function createQuestPage(array $values = [], ?string $error = null): void
    {
        Security::requireAccount();
        $value = static fn (string $key, string $default = ''): string => self::escape((string) ($values[$key] ?? $default));
        $checked = static fn (int $day): string => in_array($day, array_map('intval', (array) ($values['weekdays'] ?? [])), true) ? ' checked' : '';
        $frequency = (string) ($values['frequency'] ?? 'none');
        $option = static fn (string $current, string $candidate, string $label): string => '<option value="' . $candidate . '"' . ($current === $candidate ? ' selected' : '') . '>' . $label . '</option>';
        $pillarOptions = '<option value="">No Pillar selected</option>';
        foreach ((new ExperienceService(\database()))->pillars() as $pillar) {
            $pillarOptions .= $option((string) ($values['pillar_key'] ?? ''), (string) $pillar['pillar_key'], (string) $pillar['name']);
        }
        $errorHtml = $error ? '<div class="notice error" role="alert">' . self::escape($error) . '</div>' : '';
        $body = '<section class="form-panel"><p class="eyebrow">New Quest</p><h1>What would help?</h1><p>Start simply. Pillar and repeat details are optional.</p>' . $errorHtml . '<form method="post" action="/quests">' . $this->csrfField() .
            '<label>Title <span class="required">Required</span><input type="text" name="title" maxlength="180" value="' . $value('title') . '" required autofocus></label><label>Notes <span class="optional">Optional</span><textarea name="description" maxlength="4000" rows="5">' . $value('description') . '</textarea></label><label>Supports <span class="optional">Optional</span><select name="pillar_key">' . $pillarOptions . '</select></label>' .
            '<fieldset><legend>Repeat</legend><div class="form-grid"><label>Pattern<select name="frequency">' . $option($frequency, 'none', 'Does not repeat') . $option($frequency, 'daily', 'Daily') . $option($frequency, 'weekly', 'Weekly') . $option($frequency, 'monthly', 'Monthly') . $option($frequency, 'yearly', 'Yearly') . '</select></label><label>Every <input type="number" name="interval_count" min="1" max="365" value="' . $value('interval_count', '1') . '"></label><label>Starts <input type="date" name="starts_on" value="' . $value('starts_on', date('Y-m-d')) . '"></label><label>Ends <span class="optional">Optional</span><input type="date" name="ends_on" value="' . $value('ends_on') . '"></label></div>' .
            '<div class="weekday-picker"><span>On these days</span><label><input type="checkbox" name="weekdays[]" value="1"' . $checked(1) . '>Mon</label><label><input type="checkbox" name="weekdays[]" value="2"' . $checked(2) . '>Tue</label><label><input type="checkbox" name="weekdays[]" value="3"' . $checked(3) . '>Wed</label><label><input type="checkbox" name="weekdays[]" value="4"' . $checked(4) . '>Thu</label><label><input type="checkbox" name="weekdays[]" value="5"' . $checked(5) . '>Fri</label><label><input type="checkbox" name="weekdays[]" value="6"' . $checked(6) . '>Sat</label><label><input type="checkbox" name="weekdays[]" value="7"' . $checked(7) . '>Sun</label></div>' .
            '<details><summary>Monthly options</summary><div class="form-grid"><label>Monthly pattern<select name="monthly_mode">' . $option((string) ($values['monthly_mode'] ?? 'day_of_month'), 'day_of_month', 'Day of month') . $option((string) ($values['monthly_mode'] ?? ''), 'ordinal_weekday', 'Ordinal weekday') . '</select></label><label>Day of month<input type="number" name="day_of_month" min="1" max="31" value="' . $value('day_of_month', date('j')) . '"></label><label>Week<select name="ordinal_week"><option value="1">First</option><option value="2">Second</option><option value="3">Third</option><option value="4">Fourth</option><option value="-1">Last</option></select></label><label>Weekday<select name="ordinal_weekday"><option value="1">Monday</option><option value="2">Tuesday</option><option value="3">Wednesday</option><option value="4">Thursday</option><option value="5">Friday</option><option value="6">Saturday</option><option value="7">Sunday</option></select></label></div></details></fieldset>' .
            '<input type="hidden" name="timezone" value="America/New_York"><div class="form-actions"><button class="button" type="submit">Save Quest</button><a class="button secondary" href="/quests">Cancel</a></div></form></section>';
        $this->render('New Quest', $body, 'quests');
    }

    private function createQuest(): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        $values = ['title' => (string) ($_POST['title'] ?? ''), 'description' => (string) ($_POST['description'] ?? ''), 'pillar_key' => (string) ($_POST['pillar_key'] ?? ''), 'frequency' => (string) ($_POST['frequency'] ?? 'none'), 'interval_count' => (string) ($_POST['interval_count'] ?? '1'), 'starts_on' => (string) ($_POST['starts_on'] ?? date('Y-m-d')), 'ends_on' => (string) ($_POST['ends_on'] ?? ''), 'weekdays' => (array) ($_POST['weekdays'] ?? []), 'monthly_mode' => (string) ($_POST['monthly_mode'] ?? 'day_of_month'), 'day_of_month' => (string) ($_POST['day_of_month'] ?? date('j')), 'ordinal_week' => (string) ($_POST['ordinal_week'] ?? '1'), 'ordinal_weekday' => (string) ($_POST['ordinal_weekday'] ?? '1'), 'timezone' => (string) ($_POST['timezone'] ?? 'America/New_York')];
        try {
            $questId = (new QuestService(\database()))->create((string) $account['id'], $values['title'], $values['description'], $values);
            (new ExperienceService(\database()))->linkQuest($questId, (string) $account['id'], $values['pillar_key']);
        } catch (RuntimeException $exception) { http_response_code(422); $this->createQuestPage($values, $exception->getMessage()); return; }
        $_SESSION['flash'] = 'Quest saved with its wider purpose.';
        $this->redirect('/quests/' . $questId);
    }

    private function questDetail(string $questId): void
    {
        $account = Security::requireAccount(); $quest = (new QuestService(\database()))->getForAccount($questId, (string) $account['id']);
        if (!$quest) { http_response_code(404); $this->render('Quest unavailable', '<section class="panel"><h1>This Quest is unavailable.</h1><a class="button" href="/quests">Return to Quests</a></section>', 'quests'); return; }
        $recurrence = QuestService::recurrenceLabel($quest); $schedule = $recurrence ? '<p class="schedule-label">' . self::escape($recurrence) . '</p>' : '<p class="schedule-label">One time</p>';
        $next = $quest['next_scheduled_for'] ? '<p class="meta">Next occurrence: ' . self::escape((string) $quest['next_scheduled_for']) . '</p>' : '';
        $status = (string) $quest['lifecycle_status'];
        $action = $status === 'active' && !(bool) $quest['completed'] ? '<form method="post" action="/quests/' . self::escape($questId) . '/complete">' . $this->csrfField() . '<button class="button" type="submit">Complete this occurrence</button></form>' : '<p class="status complete">' . ($status === 'paused' ? 'Paused' : 'No occurrence waiting') . '</p>';
        $lifecycle = '<div class="inline-actions">' . ($status === 'active' ? '<form method="post" action="/quests/' . self::escape($questId) . '/pause">' . $this->csrfField() . '<button class="quiet-button" type="submit">Pause</button></form>' : '') . ($status === 'paused' ? '<form method="post" action="/quests/' . self::escape($questId) . '/resume">' . $this->csrfField() . '<button class="quiet-button" type="submit">Resume</button></form>' : '') . '<form method="post" action="/quests/' . self::escape($questId) . '/archive">' . $this->csrfField() . '<button class="quiet-button" type="submit">Archive</button></form></div>';
        $description = trim((string) $quest['description']) !== '' ? '<p>' . self::escape((string) $quest['description']) . '</p>' : '<p class="muted-text">No notes were added.</p>';
        $this->render((string) $quest['title'], '<section class="panel"><p class="eyebrow">Quest</p><h1>' . self::escape((string) $quest['title']) . '</h1>' . $description . $schedule . $next . $action . $lifecycle . '</section>', 'quests');
    }

    private function completeQuest(string $questId): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        try { $eventId = (new QuestService(\database()))->complete($questId, (string) $account['id']); }
        catch (RuntimeException $exception) { $_SESSION['flash'] = $exception->getMessage(); $this->redirect('/quests/' . $questId); }
        $this->worker()->run(10);
        $this->redirect('/completions/' . $eventId);
    }

    private function completionSummary(string $eventId): void
    {
        $account = Security::requireAccount(); $summary = (new ExperienceService(\database()))->completionSummary((string) $account['id'], $eventId);
        if (!$summary) { http_response_code(404); $this->render('Completion unavailable', '<section class="panel"><h1>That completion is unavailable.</h1><a class="button" href="/hearth">Return to Hearth</a></section>'); return; }
        $pillarText = $summary['pillars'] ? implode(', ', $summary['pillars']) : 'No Pillar selected';
        $body = $this->flashHtml($this->takeFlash()) . '<section class="completion-panel"><p class="eyebrow">Quest completed</p><h1>' . self::escape((string) $summary['title']) . '</h1><div class="result-list"><p><strong>Supported</strong><span>' . self::escape($pillarText) . '</span></p><p><strong>Chronicle</strong><span>Moment recorded</span></p><p><strong>Epic Ordinary</strong><span>The Caretaker noticed</span></p></div><form method="post" action="/completions/' . self::escape($eventId) . '/reflection">' . $this->csrfField() . '<label>Add a reflection <span class="optional">Optional</span><textarea name="reflection" maxlength="2000" rows="4" placeholder="What made this matter?"></textarea></label><button class="button" type="submit">Save reflection</button></form><div class="inline-actions"><a class="button secondary" href="/hearth">Return to Hearth</a><form method="post" action="/completions/' . self::escape($eventId) . '/undo">' . $this->csrfField() . '<button class="quiet-button" type="submit">Undo completion</button></form></div></section>';
        $this->render('Quest completed', $body, 'hearth');
    }

    private function saveReflection(string $eventId): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        try { (new ExperienceService(\database()))->addReflection((string) $account['id'], $eventId, (string) ($_POST['reflection'] ?? '')); $_SESSION['flash'] = 'Reflection saved to Chronicle.'; }
        catch (RuntimeException $exception) { $_SESSION['flash'] = $exception->getMessage(); }
        $this->redirect('/completions/' . $eventId);
    }

    private function undoCompletion(string $eventId): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        try { (new ExperienceService(\database()))->undoCompletion((string) $account['id'], $eventId); $this->worker()->run(10); $_SESSION['flash'] = 'Completion undone. Related Pillar and Chronicle effects were reversed.'; }
        catch (RuntimeException $exception) { $_SESSION['flash'] = $exception->getMessage(); }
        $this->redirect('/hearth');
    }

    private function changeQuestLifecycle(string $questId, string $action): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        $status = match ($action) { 'pause' => 'paused', 'resume', 'restore' => 'active', 'archive' => 'archived', default => throw new RuntimeException('Unknown Quest action.') };
        (new QuestService(\database()))->setLifecycle($questId, (string) $account['id'], $status);
        $_SESSION['flash'] = 'Quest ' . ($status === 'active' ? 'resumed' : $status) . '.';
        $this->redirect('/quests');
    }

    private function worldReaction(): void
    {
        $account = Security::requireAccount(); $reaction = $this->latestReaction((string) $account['id']);
        if (!$reaction) { $this->render('World reaction', '<section class="panel"><h1>No World reaction yet.</h1><p>Complete an active Quest occurrence first.</p><a class="button" href="/hearth">Return to Hearth</a></section>'); return; }
        $this->render('World reaction', '<section class="panel world-detail"><p class="eyebrow">Epic Ordinary</p><h1>' . self::escape((string) $reaction['title']) . '</h1><blockquote>' . self::escape((string) $reaction['message']) . '</blockquote><h2>Why this happened</h2><p>' . self::escape((string) $reaction['explanation']) . '</p><p class="meta">Recorded ' . self::escape((string) $reaction['created_at']) . ' UTC</p><a class="button" href="/hearth">Return to Hearth</a></section>');
    }

    private function worker(): OutboxWorker
    {
        return new OutboxWorker(\database(), new CompositeConsumer([new ExperienceConsumer(\database()), new EpicOrdinaryConsumer(\database())]));
    }

    private function latestReaction(string $accountId): ?array
    {
        $statement = \database()->pdo()->prepare('SELECT wr.title, wr.message, wr.explanation, wr.created_at FROM world_reactions wr JOIN world_installations wi ON wi.id = wr.installation_id WHERE wi.account_id = :account_id AND wi.world_key = "epic-ordinary" ORDER BY wr.created_at DESC LIMIT 1');
        $statement->execute(['account_id' => $accountId]); $reaction = $statement->fetch(); return $reaction ?: null;
    }

    private function questCards(array $quests): string
    {
        $html = '';
        foreach ($quests as $quest) {
            $completed = (bool) $quest['completed']; $description = trim((string) $quest['description']);
            $schedule = !empty($quest['frequency']) ? '<p class="meta">Repeats ' . self::escape((string) $quest['frequency']) . (!empty($quest['next_scheduled_for']) ? ' · next ' . self::escape((string) $quest['next_scheduled_for']) : '') . '</p>' : '';
            $html .= '<article class="card"><div><p class="eyebrow">Quest</p><h2>' . self::escape((string) $quest['title']) . '</h2>' . ($description !== '' ? '<p>' . self::escape($description) . '</p>' : '') . $schedule . '</div>' . ($completed ? '<span class="status complete">Complete</span>' : '<a class="button" href="/quests/' . self::escape((string) $quest['id']) . '">Open Quest</a>') . '</article>';
        }
        return $html;
    }

    private function render(string $title, string $body, bool|string $authenticated = true): void
    {
        $account = Security::account(); $active = is_string($authenticated) ? $authenticated : '';
        $navigation = $authenticated && $account ? '<header class="app-header"><a class="brand" href="/hearth">Koravik</a><nav aria-label="Primary"><a href="/hearth"' . ($active === 'hearth' ? ' aria-current="page"' : '') . '>Hearth</a><a href="/quests"' . ($active === 'quests' ? ' aria-current="page"' : '') . '>Quests</a><a href="/chronicle"' . ($active === 'chronicle' ? ' aria-current="page"' : '') . '>Chronicle</a></nav><form method="post" action="/logout">' . $this->csrfField() . '<button class="quiet-button" type="submit">Sign out</button></form></header>' : '<header class="app-header simple"><span class="brand">Koravik</span></header>';
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><title>' . self::escape($title) . ' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><a class="skip-link" href="#main">Skip to content</a>' . $navigation . '<main id="main" class="page" tabindex="-1">' . $body . '</main><footer>Koravik helps you act, then get back to living.</footer></body></html>';
    }

    private function flashHtml(?string $flash): string { return $flash ? '<div class="notice" role="status">' . self::escape($flash) . '</div>' : ''; }
    private function takeFlash(): ?string { $flash = isset($_SESSION['flash']) ? (string) $_SESSION['flash'] : null; unset($_SESSION['flash']); return $flash; }
    private function csrfField(): string { return '<input type="hidden" name="csrf" value="' . self::escape(Security::csrfToken()) . '">'; }
    private function requireCsrf(): void { if (!Security::verifyCsrf(isset($_POST['csrf']) ? (string) $_POST['csrf'] : null)) { http_response_code(419); throw new RuntimeException('Your session changed. Please try again.'); } }
    private function redirect(string $location): never { header('Location: ' . $location, true, 303); exit; }
    private function json(array $data): never { header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_THROW_ON_ERROR); exit; }
    private function securityHeaders(): void { header('X-Content-Type-Options: nosniff'); header('X-Frame-Options: DENY'); header('Referrer-Policy: strict-origin-when-cross-origin'); header("Content-Security-Policy: default-src 'self'; style-src 'self'; img-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'"); }
    private static function escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
