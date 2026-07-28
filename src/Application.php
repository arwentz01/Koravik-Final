<?php

declare(strict_types=1);

namespace Koravik;

use Koravik\Districts\Chronicle\ChronicleService;
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
            if ($method === 'GET' && $path === '/') $this->redirect(Security::account() ? '/hearth' : '/login');
            if ($method === 'GET' && $path === '/health') $this->json(['status' => 'ok', 'build' => '004']);
            if ($method === 'GET' && $path === '/login') { $this->loginPage(); return; }
            if ($method === 'POST' && $path === '/login') { $this->login(); return; }
            if ($method === 'POST' && $path === '/logout') { $this->requireCsrf(); Security::logout(); $this->redirect('/login'); }
            if ($method === 'GET' && $path === '/hearth') { $this->hearth(); return; }
            if ($method === 'GET' && $path === '/quests') { $this->quests(); return; }
            if ($method === 'GET' && $path === '/quests/create') { $this->createQuestPage(); return; }
            if ($method === 'POST' && $path === '/quests') { $this->createQuest(); return; }
            if ($method === 'GET' && preg_match('#^/quests/([a-f0-9-]{36})$#', $path, $matches)) { $this->questDetail($matches[1]); return; }
            if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/complete$#', $path, $matches)) { $this->completeQuest($matches[1]); return; }
            if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/(pause|resume|archive|restore)$#', $path, $matches)) { $this->changeQuestLifecycle($matches[1], $matches[2]); return; }
            if ($method === 'GET' && preg_match('#^/quests/([a-f0-9-]{36})/reflection/([a-f0-9-]{36})$#', $path, $matches)) { $this->reflectionPage($matches[1], $matches[2]); return; }
            if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/reflection/([a-f0-9-]{36})$#', $path, $matches)) { $this->saveReflection($matches[1], $matches[2]); return; }
            if ($method === 'GET' && $path === '/chronicle') { $this->chronicle(); return; }
            if ($method === 'GET' && preg_match('#^/chronicle/([a-f0-9-]{36})$#', $path, $matches)) { $this->chronicleEntry($matches[1]); return; }
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
        if (!Security::attempt(\database()->pdo(), (string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            usleep(250000); http_response_code(422); $this->loginPage('That email and password did not match.'); return;
        }
        $_SESSION['flash'] = 'Welcome back.';
        $this->redirect('/hearth');
    }

    private function hearth(): void
    {
        $account = Security::requireAccount();
        $questsService = new QuestService(\database());
        $quests = $questsService->listForAccount((string) $account['id']);
        $pillars = $questsService->pillarSummary((string) $account['id']);
        $reaction = $this->latestReaction((string) $account['id']);
        $reactionHtml = $reaction
            ? '<article class="world-card"><p class="eyebrow">Epic Ordinary</p><h2>' . self::escape((string) $reaction['title']) . '</h2><p>' . self::escape((string) $reaction['message']) . '</p><a class="quiet-link" href="/world/reaction">See why this happened</a></article>'
            : '<article class="world-card muted"><p class="eyebrow">Epic Ordinary</p><h2>The story is listening.</h2><p>Complete a Quest occurrence to see how the World responds.</p></article>';
        $pillarHtml = '';
        foreach ($pillars as $pillar) {
            $count = (int) $pillar['completed_count'];
            $pillarHtml .= '<article class="pillar-card"><span class="pillar-count">' . $count . '</span><div><h3>' . self::escape((string) $pillar['name']) . '</h3><p>' . ($count === 0 ? 'Quiet lately—not neglected.' : ($count === 1 ? 'One meaningful action.' : $count . ' meaningful actions.')) . '</p></div></article>';
        }
        $body = $this->flashHtml($this->takeFlash()) . '<section class="hero"><p class="eyebrow">Hearth</p><h1>Good to see you, ' . self::escape((string) $account['display_name']) . '.</h1><p>One meaningful next step is enough.</p><div class="hero-actions"><a class="button" href="/quests/create">New Quest</a><a class="button secondary" href="/chronicle">Open Chronicle</a></div></section>' .
            '<section aria-labelledby="today-heading"><div class="section-heading"><h2 id="today-heading">What matters now</h2><a href="/quests">View all Quests</a></div><div class="grid">' . ($this->questCards($quests) ?: '<article class="empty-state"><h3>Nothing is asking for your attention.</h3><p>Create one small, useful next step when you are ready.</p><a class="button" href="/quests/create">Create a Quest</a></article>') . '</div></section>' .
            '<section aria-labelledby="pillars-heading"><div class="section-heading"><div><h2 id="pillars-heading">Life in view</h2><p class="muted-text">A 30-day reflection, not a scorecard.</p></div></div><div class="pillar-grid">' . $pillarHtml . '</div></section>' .
            '<section aria-labelledby="world-heading"><h2 id="world-heading">What changed</h2>' . $reactionHtml . '</section>';
        $this->render('Hearth', $body, 'hearth');
    }

    private function quests(): void
    {
        $account = Security::requireAccount();
        $quests = (new QuestService(\database()))->listForAccount((string) $account['id']);
        $body = $this->flashHtml($this->takeFlash()) . '<section class="page-heading"><div><p class="eyebrow">Quests</p><h1>Actions with a wider purpose.</h1><p>Quests can support a Pillar, repeat, pause, and feed the rest of Koravik.</p></div><a class="button" href="/quests/create">New Quest</a></section><div class="grid">' . ($this->questCards($quests) ?: '<article class="empty-state"><h2>No active Quests.</h2><p>Start with one action that would make life a little easier.</p><a class="button" href="/quests/create">Create your first Quest</a></article>') . '</div>';
        $this->render('Quests', $body, 'quests');
    }

    private function createQuestPage(array $values = [], ?string $error = null): void
    {
        Security::requireAccount();
        $value = static fn (string $key, string $default = ''): string => self::escape((string) ($values[$key] ?? $default));
        $checked = static fn (int $day): string => in_array($day, array_map('intval', (array) ($values['weekdays'] ?? [])), true) ? ' checked' : '';
        $selected = static fn (string $current, string $candidate): string => $current === $candidate ? ' selected' : '';
        $frequency = (string) ($values['frequency'] ?? 'none');
        $pillarOptions = '<option value="">No Pillar selected</option>';
        foreach (QuestService::pillarNames() as $key => $name) $pillarOptions .= '<option value="' . $key . '"' . $selected((string) ($values['pillar_key'] ?? ''), $key) . '>' . $name . '</option>';
        $typeOptions = '';
        foreach (QuestService::typeNames() as $key => $name) $typeOptions .= '<option value="' . $key . '"' . $selected((string) ($values['quest_type'] ?? 'action'), $key) . '>' . $name . '</option>';
        $frequencyOptions = '';
        foreach (['none' => 'Does not repeat', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $key => $label) $frequencyOptions .= '<option value="' . $key . '"' . $selected($frequency, $key) . '>' . $label . '</option>';
        $errorHtml = $error ? '<div class="notice error" role="alert">' . self::escape($error) . '</div>' : '';
        $body = '<section class="form-panel"><p class="eyebrow">New Quest</p><h1>What would help?</h1><p>Describe the action, then optionally connect it to the part of life it serves.</p>' . $errorHtml . '<form method="post" action="/quests">' . $this->csrfField() .
            '<label for="quest-title">Title <span class="required">Required</span><input id="quest-title" type="text" name="title" maxlength="180" value="' . $value('title') . '" required autofocus></label>' .
            '<div class="form-grid"><label>Quest type<select name="quest_type">' . $typeOptions . '</select></label><label>Pillar<select name="pillar_key">' . $pillarOptions . '</select></label></div>' .
            '<label for="quest-purpose">Why this matters <span class="optional">Optional</span><textarea id="quest-purpose" name="purpose" maxlength="500" rows="3">' . $value('purpose') . '</textarea></label>' .
            '<label for="quest-description">Notes <span class="optional">Optional</span><textarea id="quest-description" name="description" maxlength="4000" rows="4">' . $value('description') . '</textarea></label>' .
            '<fieldset><legend>Repeat</legend><div class="form-grid"><label>Pattern<select name="frequency">' . $frequencyOptions . '</select></label><label>Every <input type="number" name="interval_count" min="1" max="365" value="' . $value('interval_count', '1') . '"></label><label>Starts <input type="date" name="starts_on" value="' . $value('starts_on', date('Y-m-d')) . '"></label><label>Ends <span class="optional">Optional</span><input type="date" name="ends_on" value="' . $value('ends_on') . '"></label></div>' .
            '<div class="weekday-picker" aria-label="Repeat on weekdays"><span>On these days</span><label><input type="checkbox" name="weekdays[]" value="1"' . $checked(1) . '>Mon</label><label><input type="checkbox" name="weekdays[]" value="2"' . $checked(2) . '>Tue</label><label><input type="checkbox" name="weekdays[]" value="3"' . $checked(3) . '>Wed</label><label><input type="checkbox" name="weekdays[]" value="4"' . $checked(4) . '>Thu</label><label><input type="checkbox" name="weekdays[]" value="5"' . $checked(5) . '>Fri</label><label><input type="checkbox" name="weekdays[]" value="6"' . $checked(6) . '>Sat</label><label><input type="checkbox" name="weekdays[]" value="7"' . $checked(7) . '>Sun</label></div>' .
            '<details><summary>Monthly options</summary><div class="form-grid"><label>Monthly pattern<select name="monthly_mode"><option value="day_of_month">Day of month</option><option value="ordinal_weekday">Ordinal weekday</option></select></label><label>Day of month<input type="number" name="day_of_month" min="1" max="31" value="' . $value('day_of_month', date('j')) . '"></label><label>Week<select name="ordinal_week"><option value="1">First</option><option value="2">Second</option><option value="3">Third</option><option value="4">Fourth</option><option value="-1">Last</option></select></label><label>Weekday<select name="ordinal_weekday"><option value="1">Monday</option><option value="2">Tuesday</option><option value="3">Wednesday</option><option value="4">Thursday</option><option value="5">Friday</option><option value="6">Saturday</option><option value="7">Sunday</option></select></label></div></details></fieldset>' .
            '<input type="hidden" name="timezone" value="America/New_York"><div class="form-actions"><button class="button" type="submit">Save Quest</button><a class="button secondary" href="/quests">Cancel</a></div></form></section>';
        $this->render('New Quest', $body, 'quests');
    }

    private function createQuest(): void
    {
        $this->requireCsrf();
        $account = Security::requireAccount();
        $values = [
            'title' => (string) ($_POST['title'] ?? ''), 'description' => (string) ($_POST['description'] ?? ''),
            'purpose' => (string) ($_POST['purpose'] ?? ''), 'quest_type' => (string) ($_POST['quest_type'] ?? 'action'),
            'pillar_key' => (string) ($_POST['pillar_key'] ?? ''), 'frequency' => (string) ($_POST['frequency'] ?? 'none'),
            'interval_count' => (string) ($_POST['interval_count'] ?? '1'), 'starts_on' => (string) ($_POST['starts_on'] ?? date('Y-m-d')),
            'ends_on' => (string) ($_POST['ends_on'] ?? ''), 'weekdays' => (array) ($_POST['weekdays'] ?? []),
            'monthly_mode' => (string) ($_POST['monthly_mode'] ?? 'day_of_month'), 'day_of_month' => (string) ($_POST['day_of_month'] ?? date('j')),
            'ordinal_week' => (string) ($_POST['ordinal_week'] ?? '1'), 'ordinal_weekday' => (string) ($_POST['ordinal_weekday'] ?? '1'),
            'timezone' => (string) ($_POST['timezone'] ?? 'America/New_York'),
        ];
        try { $questId = (new QuestService(\database()))->create((string) $account['id'], $values['title'], $values['description'], $values); }
        catch (RuntimeException $exception) { http_response_code(422); $this->createQuestPage($values, $exception->getMessage()); return; }
        $_SESSION['flash'] = 'Quest saved with its meaning and schedule.';
        $this->redirect('/quests/' . $questId);
    }

    private function questDetail(string $questId): void
    {
        $account = Security::requireAccount();
        $quest = (new QuestService(\database()))->getForAccount($questId, (string) $account['id']);
        if (!$quest) { http_response_code(404); $this->render('Quest unavailable', '<section class="panel"><h1>This Quest is unavailable.</h1><a class="button" href="/quests">Return to Quests</a></section>', 'quests'); return; }
        $recurrence = QuestService::recurrenceLabel($quest);
        $schedule = '<p class="schedule-label">' . self::escape($recurrence ?: 'One time') . '</p>';
        $next = $quest['next_scheduled_for'] ? '<p class="meta">Next occurrence: ' . self::escape((string) $quest['next_scheduled_for']) . '</p>' : '';
        $status = (string) $quest['lifecycle_status'];
        $action = $status === 'active' && !(bool) $quest['completed'] ? '<form method="post" action="/quests/' . self::escape($questId) . '/complete">' . $this->csrfField() . '<button class="button" type="submit">Complete this occurrence</button></form>' : '<p class="status complete">' . ($status === 'paused' ? 'Paused' : 'No occurrence waiting') . '</p>';
        $lifecycle = '<div class="inline-actions">' . ($status === 'active' ? '<form method="post" action="/quests/' . self::escape($questId) . '/pause">' . $this->csrfField() . '<button class="quiet-button" type="submit">Pause</button></form>' : '') . ($status === 'paused' ? '<form method="post" action="/quests/' . self::escape($questId) . '/resume">' . $this->csrfField() . '<button class="quiet-button" type="submit">Resume</button></form>' : '') . '<form method="post" action="/quests/' . self::escape($questId) . '/archive">' . $this->csrfField() . '<button class="quiet-button" type="submit">Archive</button></form></div>';
        $description = trim((string) $quest['description']) !== '' ? '<p>' . self::escape((string) $quest['description']) . '</p>' : '<p class="muted-text">No notes were added.</p>';
        $meaning = '<div class="meaning-strip"><span>' . self::escape(ucfirst((string) $quest['quest_type'])) . '</span>' . ($quest['pillar_name'] ? '<span>' . self::escape((string) $quest['pillar_name']) . '</span>' : '') . '</div>' . ($quest['purpose'] ? '<aside class="purpose-callout"><strong>Why this matters</strong><p>' . self::escape((string) $quest['purpose']) . '</p></aside>' : '');
        $body = $this->flashHtml($this->takeFlash()) . '<section class="panel"><p class="eyebrow">Quest</p><h1>' . self::escape((string) $quest['title']) . '</h1>' . $meaning . $description . $schedule . $next . $action . $lifecycle . '</section>';
        $this->render((string) $quest['title'], $body, 'quests');
    }

    private function completeQuest(string $questId): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        try { $result = (new QuestService(\database()))->complete($questId, (string) $account['id']); }
        catch (RuntimeException $exception) { $_SESSION['flash'] = $exception->getMessage(); $this->redirect('/quests/' . $questId); }
        (new OutboxWorker(\database(), new EpicOrdinaryConsumer(\database())))->run(5);
        $this->redirect('/quests/' . $questId . '/reflection/' . $result['occurrence_id']);
    }

    private function reflectionPage(string $questId, string $occurrenceId, array $values = [], ?string $error = null): void
    {
        $account = Security::requireAccount();
        $quest = (new QuestService(\database()))->getForAccount($questId, (string) $account['id']);
        if (!$quest) { http_response_code(404); $this->render('Reflection unavailable', '<section class="panel"><h1>This reflection is unavailable.</h1><a class="button" href="/hearth">Return to Hearth</a></section>', 'quests'); return; }
        $errorHtml = $error ? '<div class="notice error" role="alert">' . self::escape($error) . '</div>' : '';
        $body = '<section class="form-panel"><p class="eyebrow">Quest complete</p><h1>What should be remembered?</h1><p>Epic Ordinary has already received the completion fact. Saving a personal reflection is optional and private.</p>' . $errorHtml . '<form method="post" action="/quests/' . self::escape($questId) . '/reflection/' . self::escape($occurrenceId) . '">' . $this->csrfField() . '<label>Reflection<textarea name="reflection" maxlength="8000" rows="7" autofocus>' . self::escape((string) ($values['reflection'] ?? '')) . '</textarea></label><label>How did this feel? <span class="optional">Optional</span><select name="mood"><option value="">No label</option><option value="lighter">Lighter</option><option value="steady">Steady</option><option value="proud">Proud</option><option value="thoughtful">Thoughtful</option><option value="tired">Tired</option><option value="mixed">Mixed</option></select></label><div class="form-actions"><button class="button" type="submit">Save to Chronicle</button><a class="button secondary" href="/hearth">Skip reflection</a></div></form></section>';
        $this->render('Quest reflection', $body, 'quests');
    }

    private function saveReflection(string $questId, string $occurrenceId): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        $values = ['reflection' => (string) ($_POST['reflection'] ?? ''), 'mood' => (string) ($_POST['mood'] ?? '')];
        try { $entryId = (new ChronicleService(\database()))->saveQuestReflection((string) $account['id'], $occurrenceId, $values['reflection'], $values['mood']); }
        catch (RuntimeException $exception) { http_response_code(422); $this->reflectionPage($questId, $occurrenceId, $values, $exception->getMessage()); return; }
        $_SESSION['flash'] = 'Reflection saved privately to Chronicle.';
        $this->redirect('/chronicle/' . $entryId);
    }

    private function chronicle(): void
    {
        $account = Security::requireAccount();
        $entries = (new ChronicleService(\database()))->listForAccount((string) $account['id']);
        $cards = '';
        foreach ($entries as $entry) $cards .= '<article class="chronicle-card"><p class="eyebrow">' . self::escape((string) $entry['occurred_on']) . '</p><h2>' . self::escape((string) $entry['title']) . '</h2><p>' . self::escape(mb_strimwidth((string) $entry['body'], 0, 220, '…')) . '</p><div class="meaning-strip"><span>Private</span>' . ($entry['pillar_key'] ? '<span>' . self::escape((string) QuestService::pillarNames()[$entry['pillar_key']]) . '</span>' : '') . '</div><a class="quiet-link" href="/chronicle/' . self::escape((string) $entry['id']) . '">Read reflection</a></article>';
        $body = $this->flashHtml($this->takeFlash()) . '<section class="page-heading"><div><p class="eyebrow">Chronicle</p><h1>What you chose to remember.</h1><p>Chronicle is private by default and never an automatic activity feed.</p></div></section><div class="chronicle-list">' . ($cards ?: '<article class="empty-state"><h2>No reflections yet.</h2><p>After completing a Quest, you may choose to save what the moment meant.</p><a class="button" href="/quests">Open Quests</a></article>') . '</div>';
        $this->render('Chronicle', $body, 'chronicle');
    }

    private function chronicleEntry(string $entryId): void
    {
        $account = Security::requireAccount();
        $entry = (new ChronicleService(\database()))->getForAccount($entryId, (string) $account['id']);
        if (!$entry) { http_response_code(404); $this->render('Chronicle entry unavailable', '<section class="panel"><h1>This entry is unavailable.</h1><a class="button" href="/chronicle">Return to Chronicle</a></section>', 'chronicle'); return; }
        $source = $entry['quest_title'] ? '<aside class="source-note"><strong>Source</strong><p>Created from the completed Quest “' . self::escape((string) $entry['quest_title']) . '.”</p></aside>' : '';
        $body = $this->flashHtml($this->takeFlash()) . '<article class="panel chronicle-detail"><p class="eyebrow">' . self::escape((string) $entry['occurred_on']) . ' · Private</p><h1>' . self::escape((string) $entry['title']) . '</h1><div class="reflection-body">' . nl2br(self::escape((string) $entry['body'])) . '</div>' . $source . '<a class="button secondary" href="/chronicle">Return to Chronicle</a></article>';
        $this->render((string) $entry['title'], $body, 'chronicle');
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
        $body = '<section class="panel world-detail"><p class="eyebrow">Epic Ordinary</p><h1>' . self::escape((string) $reaction['title']) . '</h1><blockquote>' . self::escape((string) $reaction['message']) . '</blockquote><h2>Why this happened</h2><p>' . self::escape((string) $reaction['explanation']) . '</p><p class="meta">Recorded ' . self::escape((string) $reaction['created_at']) . ' UTC</p><a class="button" href="/hearth">Return to Hearth</a></section>';
        $this->render('World reaction', $body);
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
            $meaning = '<div class="meaning-strip"><span>' . self::escape(ucfirst((string) $quest['quest_type'])) . '</span>' . ($quest['pillar_name'] ? '<span>' . self::escape((string) $quest['pillar_name']) . '</span>' : '') . '</div>';
            $html .= '<article class="card"><div><p class="eyebrow">Quest</p><h2>' . self::escape((string) $quest['title']) . '</h2>' . $meaning . ($description !== '' ? '<p>' . self::escape($description) . '</p>' : '') . $schedule . '</div>' . ($completed ? '<span class="status complete">Complete</span>' : '<a class="button" href="/quests/' . self::escape((string) $quest['id']) . '">Open Quest</a>') . '</article>';
        }
        return $html;
    }

    private function render(string $title, string $body, bool|string $authenticated = true): void
    {
        $account = Security::account(); $active = is_string($authenticated) ? $authenticated : '';
        $navigation = $authenticated && $account
            ? '<header class="app-header"><a class="brand" href="/hearth">Koravik</a><nav aria-label="Primary"><a href="/hearth"' . ($active === 'hearth' ? ' aria-current="page"' : '') . '>Hearth</a><a href="/quests"' . ($active === 'quests' ? ' aria-current="page"' : '') . '>Quests</a><a href="/chronicle"' . ($active === 'chronicle' ? ' aria-current="page"' : '') . '>Chronicle</a></nav><form method="post" action="/logout">' . $this->csrfField() . '<button class="quiet-button" type="submit">Sign out</button></form></header>'
            : '<header class="app-header simple"><span class="brand">Koravik</span></header>';
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
