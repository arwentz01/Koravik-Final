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
use Koravik\Worlds\EpicOrdinary\EpicOrdinaryService;
use RuntimeException;
use Throwable;

final class Application
{
    public function run(): void
    {
        $this->securityHeaders();
        Security::startSession();
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = \app_request_path();

        try {
            if ($method === 'GET' && $path === '/') $this->redirect(Security::account() ? '/hearth' : '/login');
            if ($method === 'GET' && $path === '/health') $this->json(['status' => 'ok', 'build' => '006']);
            if ($method === 'GET' && $path === '/login') { $this->loginPage(); return; }
            if ($method === 'POST' && $path === '/login') { $this->login(); return; }
            if ($method === 'POST' && $path === '/logout') { $this->requireCsrf(); Security::logout(); $this->redirect('/login'); }
            if ($method === 'GET' && $path === '/hearth') { $this->hearth(); return; }
            if ($method === 'GET' && $path === '/chronicle') { $this->chronicle(); return; }
            if ($method === 'GET' && $path === '/quests') { $this->quests(); return; }
            if ($method === 'GET' && $path === '/quests/create') { $this->createQuestPage($_GET); return; }
            if ($method === 'POST' && $path === '/quests') { $this->createQuest(); return; }
            if ($method === 'GET' && preg_match('#^/quests/([a-f0-9-]{36})$#', $path, $matches)) { $this->questDetail($matches[1]); return; }
            if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/steps$#', $path, $matches)) { $this->addQuestStep($matches[1]); return; }
            if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/steps/([a-f0-9-]{36})/(complete|reopen|skip)$#', $path, $matches)) { $this->changeStep($matches[1], $matches[2], $matches[3]); return; }
            if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/complete$#', $path, $matches)) { $this->completeQuest($matches[1]); return; }
            if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/(pause|resume|archive|restore)$#', $path, $matches)) { $this->changeQuestLifecycle($matches[1], $matches[2]); return; }
            if ($method === 'GET' && preg_match('#^/completions/([a-f0-9-]{36})$#', $path, $matches)) { $this->completionSummary($matches[1]); return; }
            if ($method === 'POST' && preg_match('#^/completions/([a-f0-9-]{36})/reflection$#', $path, $matches)) { $this->saveReflection($matches[1]); return; }
            if ($method === 'POST' && preg_match('#^/completions/([a-f0-9-]{36})/undo$#', $path, $matches)) { $this->undoCompletion($matches[1]); return; }
            if ($method === 'GET' && $path === '/world/epic-ordinary') { $this->epicOrdinary(); return; }
            if ($method === 'POST' && $path === '/world/epic-ordinary/choice') { $this->chooseWorldSupport(); return; }
            if ($method === 'GET' && $path === '/world/reaction') { $this->worldReaction(); return; }

            http_response_code(404);
            $this->render('Page not found', '<section class="panel"><h1>That path is not here.</h1><a class="button" href="/hearth">Return to Hearth</a></section>');
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
        $this->render('Sign in', '<section class="auth-card"><p class="eyebrow">Welcome to Koravik</p><h1>Pick up where life left off.</h1><p>Sign in to see what matters now.</p>' . $errorHtml . '<form method="post" action="/login">' . $this->csrfField() . '<label>Email<input type="email" name="email" autocomplete="email" required></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button class="button" type="submit">Sign in</button></form></section>', false);
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
        $world = (new EpicOrdinaryService(\database()))->stateForAccount($accountId);
        $reaction = $this->latestReaction($accountId);
        $pillarHtml = '';
        foreach ($experience['pillars'] as $pillar) $pillarHtml .= '<article class="mini-card"><strong>' . self::escape((string) $pillar['name']) . '</strong><span>' . (int) $pillar['contribution_count'] . ' meaningful action' . ((int) $pillar['contribution_count'] === 1 ? '' : 's') . '</span></article>';
        $chronicle = $experience['chronicle'][0] ?? null;
        $chronicleHtml = $chronicle ? '<article class="chronicle-card"><p class="eyebrow">Chronicle</p><h2>' . self::escape((string) $chronicle['title']) . '</h2><p>' . self::escape((string) $chronicle['body']) . '</p><a href="/chronicle">Open Chronicle</a></article>' : '<article class="chronicle-card muted"><p class="eyebrow">Chronicle</p><h2>Your story will gather here.</h2><p>Completed actions and reflections become a calm record, not a scorecard.</p></article>';
        $worldMessage = $world['support_choice'] ? 'The Caretaker remembers: ' . (string) $world['support_choice_label'] . '.' : 'The Caretaker is waiting to learn how you want to be supported.';
        $reactionText = $reaction ? self::escape((string) $reaction['message']) : self::escape($worldMessage);
        $reactionHtml = '<article class="world-card"><p class="eyebrow">Epic Ordinary · ' . self::escape(ucfirst((string) $world['relationship_stage'])) . '</p><h2>The First Light</h2><p>' . $reactionText . '</p><a class="quiet-link" href="/world/epic-ordinary">Continue with the Caretaker</a></article>';
        $body = $this->flashHtml($this->takeFlash()) . '<section class="hero"><p class="eyebrow">Hearth</p><h1>Good to see you, ' . self::escape((string) $account['display_name']) . '.</h1><p>One meaningful next step is enough.</p><div class="hero-actions"><a class="button" href="/quests/create">New Quest</a><a class="button secondary" href="/world/epic-ordinary">Enter Epic Ordinary</a></div></section>' .
            '<section><div class="section-heading"><h2>What matters now</h2><a href="/quests">View Quests</a></div><div class="grid">' . ($this->questCards(array_slice($quests, 0, 3)) ?: '<article class="empty-state"><h3>Nothing is asking for your attention.</h3></article>') . '</div></section>' .
            ($pillarHtml ? '<section><h2>What you have been supporting</h2><div class="mini-grid">' . $pillarHtml . '</div></section>' : '') . '<section class="experience-grid">' . $chronicleHtml . $reactionHtml . '</section>';
        $this->render('Hearth', $body, 'hearth');
    }

    private function epicOrdinary(): void
    {
        $account = Security::requireAccount();
        $world = (new EpicOrdinaryService(\database()))->stateForAccount((string) $account['id']);
        $historyHtml = '';
        foreach ($world['relationship_history'] as $moment) $historyHtml .= '<li><strong>+' . (int) $moment['delta_value'] . '</strong> ' . self::escape((string) $moment['explanation']) . '<span>' . self::escape((string) $moment['created_at']) . ' UTC</span></li>';
        if (!$world['support_choice']) {
            $scene = '<section class="dialogue-scene"><p class="eyebrow">Chapter One · The First Light</p><h1>The Caretaker was already awake.</h1><blockquote>“I can remember what matters to you. But I should not decide how to speak into your life.”</blockquote><p>How should the Caretaker support you?</p><form method="post" action="/world/epic-ordinary/choice">' . $this->csrfField() . '<div class="choice-list"><button name="choice" value="gentle" type="submit"><strong>Remind me gently</strong><span>Offer warmth and a manageable next step.</span></button><button name="choice" value="direct" type="submit"><strong>Tell me plainly</strong><span>Be clear when something needs attention.</span></button><button name="choice" value="quiet" type="submit"><strong>Give me room, but remember</strong><span>Hold continuity without crowding the moment.</span></button></div></form></section>';
        } else {
            $tone = match ((string) $world['support_choice']) {'direct'=>'“You asked me to speak plainly. You have been following through, and the pattern is becoming visible.”','quiet'=>'“I have kept watch without filling the room. What you have done is still here.”',default=>'“You asked for gentleness. So let us notice what changed without making it heavier than it is.”'};
            $scene = '<section class="dialogue-scene"><p class="eyebrow">Chapter One · The First Light</p><h1>The room remembers you.</h1><blockquote>' . self::escape($tone) . '</blockquote><p>The Caretaker knows you as <strong>' . self::escape((string) $world['relationship_stage']) . '</strong>. Trust: ' . (int) $world['trust_score'] . '.</p><p class="meta">Your remembered choice: ' . self::escape((string) $world['support_choice_label']) . '</p><a class="button" href="/hearth">Carry on from Hearth</a></section>';
        }
        $history = '<section class="relationship-panel"><p class="eyebrow">Relationship memory</p><h2>What shaped this connection</h2>' . ($historyHtml ? '<ol class="memory-list">' . $historyHtml . '</ol>' : '<p class="muted-text">The relationship is new.</p>') . '</section>';
        $this->render('Epic Ordinary', $this->flashHtml($this->takeFlash()) . '<div class="world-page">' . $scene . $history . '</div>', 'world');
    }

    private function chooseWorldSupport(): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        try { (new EpicOrdinaryService(\database()))->chooseSupportStyle((string) $account['id'], (string) ($_POST['choice'] ?? '')); $_SESSION['flash'] = 'The Caretaker will remember that choice.'; }
        catch (RuntimeException $exception) { $_SESSION['flash'] = $exception->getMessage(); }
        $this->redirect('/world/epic-ordinary');
    }

    private function chronicle(): void
    {
        $account = Security::requireAccount();
        $entries = (new ExperienceService(\database()))->dashboard((string) $account['id'])['chronicle'];
        $html = '';
        foreach ($entries as $entry) $html .= '<article class="chronicle-entry"><p class="eyebrow">' . self::escape(ucfirst((string) $entry['entry_type'])) . '</p><h2>' . self::escape((string) $entry['title']) . '</h2><p>' . self::escape((string) $entry['body']) . '</p><p class="meta">' . self::escape((string) $entry['created_at']) . ' UTC</p></article>';
        $this->render('Chronicle', '<section class="page-heading"><div><p class="eyebrow">Chronicle</p><h1>Moments worth remembering.</h1><p>A quiet record of action, reflection, and story.</p></div></section><div class="chronicle-list">' . ($html ?: '<article class="empty-state"><h2>No Chronicle moments yet.</h2></article>') . '</div>', 'chronicle');
    }

    private function quests(): void
    {
        $account = Security::requireAccount();
        $quests = (new QuestService(\database()))->listForAccount((string) $account['id']);
        $this->render('Quests', $this->flashHtml($this->takeFlash()) . '<section class="page-heading"><div><p class="eyebrow">Quests</p><h1>Actions, projects, and journeys.</h1><p>Use the lightest structure that fits the life experience.</p></div><a class="button" href="/quests/create">New Quest</a></section><div class="grid">' . ($this->questCards($quests) ?: '<article class="empty-state"><h2>No active Quests.</h2></article>') . '</div>', 'quests');
    }

    private function createQuestPage(array $values = [], ?string $error = null): void
    {
        Security::requireAccount();
        $value = static fn (string $key, string $default = ''): string => self::escape((string) ($values[$key] ?? $default));
        $checked = static fn (int $day): string => in_array($day, array_map('intval', (array) ($values['weekdays'] ?? [])), true) ? ' checked' : '';
        $frequency = (string) ($values['frequency'] ?? 'none');
        $option = static fn (string $current, string $candidate, string $label): string => '<option value="' . $candidate . '"' . ($current === $candidate ? ' selected' : '') . '>' . $label . '</option>';
        $pillarOptions = '<option value="">No Pillar selected</option>';
        foreach ((new ExperienceService(\database()))->pillars() as $pillar) $pillarOptions .= $option((string) ($values['pillar_key'] ?? ''), (string) $pillar['pillar_key'], (string) $pillar['name']);
        $type = (string) ($values['quest_type'] ?? 'action');
        $sourceRaw = preg_replace('/[^a-z0-9_.-]/i', '', (string)($values['source'] ?? $values['origin_type'] ?? 'personal')) ?: 'personal';
        $source = str_starts_with($sourceRaw, 'gather') ? 'gather' : (str_starts_with($sourceRaw, 'companion') ? 'companion' : (str_starts_with($sourceRaw, 'health') ? 'health' : (str_starts_with($sourceRaw, 'beacon') ? 'beacon' : (str_starts_with($sourceRaw, 'world') ? 'story' : 'personal'))));
        $sourceReference = (string)($values['source_reference'] ?? $values['origin_reference'] ?? '');
        $draftPanel = $sourceRaw !== 'personal' || $sourceReference !== ''
            ? '<section class="trust-panel quest-from-anywhere-draft-bridge decision-consequence-preview"><p class="eyebrow">Quest-from-Anywhere Draft Bridge</p><h2>Review before this becomes a Quest.</h2><p>What changes: submitting creates a Quest-owned record. What does not: the source note, follow-up, Companion proposal, or Chronicle context is not deleted or rewritten. Who owns the result: Quests owns the saved action and provenance reference.</p></section>'
            : '';
        $errorHtml = $error ? '<div class="notice error" role="alert">' . self::escape($error) . '</div>' : '';
        $body = $draftPanel . '<section class="form-panel"><p class="eyebrow">New Quest</p><h1>What kind of undertaking is this?</h1><p>A simple action stays simple. Projects and journeys can grow steps and milestones after saving.</p>' . $errorHtml . '<form method="post" action="/quests">' . $this->csrfField() .
            '<input type="hidden" name="origin_type" value="' . self::escape($source) . '"><input type="hidden" name="origin_reference" value="' . self::escape($sourceReference) . '">' .
            '<label>Quest type<select name="quest_type">' . $option($type,'action','Single action') . $option($type,'habit','Habit') . $option($type,'project','Project') . $option($type,'journey','Journey') . $option($type,'responsibility','Responsibility') . $option($type,'world_objective','World objective') . '</select></label>' .
            '<label>Title <span class="required">Required</span><input type="text" name="title" maxlength="180" value="' . $value('title') . '" required autofocus></label><label>Notes <span class="optional">Optional</span><textarea name="description" maxlength="4000" rows="5">' . $value('description') . '</textarea></label><label>Supports <span class="optional">Optional</span><select name="pillar_key">' . $pillarOptions . '</select></label>' .
            '<fieldset><legend>Repeat</legend><div class="form-grid"><label>Pattern<select name="frequency">' . $option($frequency,'none','Does not repeat') . $option($frequency,'daily','Daily') . $option($frequency,'weekly','Weekly') . $option($frequency,'monthly','Monthly') . $option($frequency,'yearly','Yearly') . '</select></label><label>Every<input type="number" name="interval_count" min="1" max="365" value="' . $value('interval_count','1') . '"></label><label>Starts<input type="date" name="starts_on" value="' . $value('starts_on',date('Y-m-d')) . '"></label><label>Ends <span class="optional">Optional</span><input type="date" name="ends_on" value="' . $value('ends_on') . '"></label></div><div class="weekday-picker"><span>On these days</span>' . implode('', array_map(fn(int $day): string => '<label><input type="checkbox" name="weekdays[]" value="' . $day . '"' . $checked($day) . '>' . [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'][$day] . '</label>', range(1,7))) . '</div><details><summary>Monthly options</summary><div class="form-grid"><label>Monthly pattern<select name="monthly_mode"><option value="day_of_month">Day of month</option><option value="ordinal_weekday">Ordinal weekday</option></select></label><label>Day of month<input type="number" name="day_of_month" min="1" max="31" value="' . $value('day_of_month',date('j')) . '"></label><label>Week<select name="ordinal_week"><option value="1">First</option><option value="2">Second</option><option value="3">Third</option><option value="4">Fourth</option><option value="-1">Last</option></select></label><label>Weekday<select name="ordinal_weekday"><option value="1">Monday</option><option value="2">Tuesday</option><option value="3">Wednesday</option><option value="4">Thursday</option><option value="5">Friday</option><option value="6">Saturday</option><option value="7">Sunday</option></select></label></div></details></fieldset>' .
            '<input type="hidden" name="timezone" value="America/New_York"><div class="form-actions"><button class="button" type="submit">Save Quest</button><a class="button secondary" href="/quests">Cancel</a></div></form></section>';
        $this->render('New Quest', $body, 'quests');
    }

    private function createQuest(): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        $values = ['quest_type'=>(string)($_POST['quest_type']??'action'),'title'=>(string)($_POST['title']??''),'description'=>(string)($_POST['description']??''),'pillar_key'=>(string)($_POST['pillar_key']??''),'frequency'=>(string)($_POST['frequency']??'none'),'interval_count'=>(string)($_POST['interval_count']??'1'),'starts_on'=>(string)($_POST['starts_on']??date('Y-m-d')),'ends_on'=>(string)($_POST['ends_on']??''),'weekdays'=>(array)($_POST['weekdays']??[]),'monthly_mode'=>(string)($_POST['monthly_mode']??'day_of_month'),'day_of_month'=>(string)($_POST['day_of_month']??date('j')),'ordinal_week'=>(string)($_POST['ordinal_week']??'1'),'ordinal_weekday'=>(string)($_POST['ordinal_weekday']??'1'),'timezone'=>(string)($_POST['timezone']??'America/New_York'),'origin_type'=>(string)($_POST['origin_type']??'personal'),'origin_reference'=>(string)($_POST['origin_reference']??'')];
        try { $questId = (new QuestService(\database()))->create((string)$account['id'],$values['title'],$values['description'],$values); (new ExperienceService(\database()))->linkQuest($questId,(string)$account['id'],$values['pillar_key']); }
        catch (RuntimeException $exception) { http_response_code(422); $this->createQuestPage($values,$exception->getMessage()); return; }
        $_SESSION['flash'] = 'Quest saved. Add steps when the undertaking needs them.'; $this->redirect('/quests/' . $questId);
    }

    private function questDetail(string $questId): void
    {
        $account = Security::requireAccount(); $quest = (new QuestService(\database()))->getForAccount($questId,(string)$account['id']);
        if (!$quest) { http_response_code(404); $this->render('Quest unavailable','<section class="panel"><h1>This Quest is unavailable.</h1></section>','quests'); return; }
        $recurrence = QuestService::recurrenceLabel($quest); $schedule = ($recurrence ? '<p class="schedule-label">' . self::escape($recurrence) . '</p>' : '<p class="schedule-label">One time</p>') . '<p><a href="/quests/' . self::escape($questId) . '/recurrence">Edit recurrence</a></p>';
        $stepsHtml = '';
        foreach ($quest['steps'] as $step) {
            $nextAction = $step['status'] === 'completed' ? 'reopen' : 'complete';
            $nextLabel = $step['status'] === 'completed' ? 'Reopen' : 'Complete';
            $stepsHtml .= '<li class="quest-step ' . self::escape((string)$step['status']) . '"><div><strong>' . self::escape((string)$step['title']) . '</strong><span>' . ((bool)$step['is_required'] ? 'Required' : 'Optional') . '</span></div><form method="post" action="/quests/' . self::escape($questId) . '/steps/' . self::escape((string)$step['id']) . '/' . $nextAction . '">' . $this->csrfField() . '<button class="quiet-button" type="submit">' . $nextLabel . '</button></form></li>';
        }
        $structured = in_array($quest['quest_type'], ['project','journey','responsibility','world_objective'], true);
        $progress = $structured ? '<section class="quest-progress"><div class="section-heading"><h2>Progress</h2><strong>' . (int)$quest['progress_percent'] . '%</strong></div><div class="progress-track"><span style="width:' . (int)$quest['progress_percent'] . '%"></span></div>' . ($stepsHtml ? '<ol class="quest-steps">' . $stepsHtml . '</ol>' : '<p class="muted-text">No steps yet. Add the next meaningful piece, not every imaginable detail.</p>') . '<form method="post" action="/quests/' . self::escape($questId) . '/steps">' . $this->csrfField() . '<div class="step-form"><input type="text" name="step_title" maxlength="180" placeholder="Add a meaningful step" required><label class="checkbox-row"><input type="checkbox" name="required" value="1" checked>Required</label><button class="button secondary" type="submit">Add step</button></div></form></section>' : '';
        $milestones = '';
        foreach ($quest['milestones'] as $milestone) $milestones .= '<li class="' . ($milestone['reached_at'] ? 'reached' : '') . '"><strong>' . (int)$milestone['threshold_percent'] . '%</strong> ' . self::escape((string)$milestone['title']) . '</li>';
        $milestoneHtml = $milestones ? '<section><h2>Milestones</h2><ol class="milestone-list">' . $milestones . '</ol></section>' : '';
        $status = (string)$quest['lifecycle_status'];
        $action = $status === 'active' && !(bool)$quest['completed'] ? '<form method="post" action="/quests/' . self::escape($questId) . '/complete">' . $this->csrfField() . '<button class="button" type="submit">Complete this ' . strtolower(QuestService::typeLabel((string)$quest['quest_type'])) . '</button></form>' : '<p class="status complete">' . ($status === 'paused' ? 'Paused' : 'No occurrence waiting') . '</p>';
        $lifecycle = '<div class="inline-actions">' . ($status === 'active' ? '<form method="post" action="/quests/' . self::escape($questId) . '/pause">' . $this->csrfField() . '<button class="quiet-button" type="submit">Pause</button></form>' : '') . ($status === 'paused' ? '<form method="post" action="/quests/' . self::escape($questId) . '/resume">' . $this->csrfField() . '<button class="quiet-button" type="submit">Resume</button></form>' : '') . '<form method="post" action="/quests/' . self::escape($questId) . '/archive">' . $this->csrfField() . '<button class="quiet-button" type="submit">Archive</button></form></div>';
        $description = trim((string)$quest['description']) !== '' ? '<p>' . self::escape((string)$quest['description']) . '</p>' : '<p class="muted-text">No notes were added.</p>';
        $this->render((string)$quest['title'], $this->flashHtml($this->takeFlash()) . '<section class="panel quest-detail"><p class="eyebrow">' . self::escape(QuestService::typeLabel((string)$quest['quest_type'])) . '</p><h1>' . self::escape((string)$quest['title']) . '</h1>' . $description . $schedule . $progress . $milestoneHtml . $action . $lifecycle . '</section>', 'quests');
    }

    private function addQuestStep(string $questId): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        try { (new QuestService(\database()))->addStep($questId,(string)$account['id'],(string)($_POST['step_title']??''),isset($_POST['required'])); $_SESSION['flash'] = 'Step added.'; }
        catch (RuntimeException $exception) { $_SESSION['flash'] = $exception->getMessage(); }
        $this->redirect('/quests/' . $questId);
    }

    private function changeStep(string $questId, string $stepId, string $action): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        $status = match ($action) {'complete'=>'completed','reopen'=>'pending','skip'=>'skipped',default=>throw new RuntimeException('Unknown step action.')};
        try { (new QuestService(\database()))->setStepStatus($questId,$stepId,(string)$account['id'],$status); $_SESSION['flash'] = 'Quest progress updated.'; }
        catch (RuntimeException $exception) { $_SESSION['flash'] = $exception->getMessage(); }
        $this->redirect('/quests/' . $questId);
    }

    private function completeQuest(string $questId): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        try { $eventId = (new QuestService(\database()))->complete($questId,(string)$account['id']); }
        catch (RuntimeException $exception) { $_SESSION['flash'] = $exception->getMessage(); $this->redirect('/quests/' . $questId); }
        $this->worker()->run(10); $this->redirect('/completions/' . $eventId);
    }

    private function completionSummary(string $eventId): void
    {
        $account = Security::requireAccount(); $summary = (new ExperienceService(\database()))->completionSummary((string)$account['id'],$eventId);
        if (!$summary) { http_response_code(404); $this->render('Completion unavailable','<section class="panel"><h1>That completion is unavailable.</h1></section>'); return; }
        $pillarText = $summary['pillars'] ? implode(', ',$summary['pillars']) : 'No Pillar selected';
        $body = $this->flashHtml($this->takeFlash()) . '<section class="completion-panel quest-completion-confirmation"><p class="eyebrow">Quest completion confirmation</p><h1>' . self::escape((string)$summary['title']) . '</h1><p>The completion was saved by Quests. You have a bounded undo window, and any World reaction must come from an approved minimized fact rather than Quest notes.</p><div class="result-list"><p><strong>Supported</strong><span>' . self::escape($pillarText) . '</span></p><p><strong>Chronicle</strong><span>Optional reflection only if you save one</span></p><p><strong>World eligibility</strong><span>An approved World may react if current permission allows it</span></p></div><form method="post" action="/completions/' . self::escape($eventId) . '/reflection">' . $this->csrfField() . '<label>Add a reflection <span class="optional">Optional</span><textarea name="reflection" maxlength="2000" rows="4"></textarea></label><button class="button" type="submit">Save reflection</button></form><div class="inline-actions"><a class="button secondary" href="/world/epic-ordinary">See World context</a><form method="post" action="/completions/' . self::escape($eventId) . '/undo">' . $this->csrfField() . '<button class="quiet-button" type="submit">Undo completion</button></form></div></section>';
        $this->render('Quest completed',$body,'hearth');
    }

    private function saveReflection(string $eventId): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        try { (new ExperienceService(\database()))->addReflection((string)$account['id'],$eventId,(string)($_POST['reflection']??'')); $_SESSION['flash'] = 'Reflection saved to Chronicle.'; }
        catch (RuntimeException $exception) { $_SESSION['flash'] = $exception->getMessage(); }
        $this->redirect('/completions/' . $eventId);
    }

    private function undoCompletion(string $eventId): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        try { (new ExperienceService(\database()))->undoCompletion((string)$account['id'],$eventId); $this->worker()->run(10); $_SESSION['flash'] = 'Completion undone. Related effects were reversed.'; }
        catch (RuntimeException $exception) { $_SESSION['flash'] = $exception->getMessage(); }
        $this->redirect('/hearth');
    }

    private function changeQuestLifecycle(string $questId, string $action): void
    {
        $this->requireCsrf(); $account = Security::requireAccount();
        $status = match ($action) {'pause'=>'paused','resume','restore'=>'active','archive'=>'archived',default=>throw new RuntimeException('Unknown Quest action.')};
        (new QuestService(\database()))->setLifecycle($questId,(string)$account['id'],$status); $_SESSION['flash'] = 'Quest ' . ($status === 'active' ? 'resumed' : $status) . '.'; $this->redirect('/quests');
    }

    private function worldReaction(): void
    {
        $account = Security::requireAccount(); $reaction = $this->latestReaction((string)$account['id']);
        if (!$reaction) $this->redirect('/world/epic-ordinary');
        $this->render('World reaction','<section class="panel world-detail"><p class="eyebrow">Epic Ordinary</p><h1>' . self::escape((string)$reaction['title']) . '</h1><blockquote>' . self::escape((string)$reaction['message']) . '</blockquote><h2>Why this happened</h2><p>' . self::escape((string)$reaction['explanation']) . '</p><a class="button" href="/world/epic-ordinary">Continue the World</a></section>','world');
    }

    private function worker(): OutboxWorker { return new OutboxWorker(\database(),new CompositeConsumer([new ExperienceConsumer(\database()),new EpicOrdinaryConsumer(\database())])); }
    private function latestReaction(string $accountId): ?array { $statement = \database()->pdo()->prepare('SELECT wr.title, wr.message, wr.explanation, wr.created_at FROM world_reactions wr JOIN world_installations wi ON wi.id = wr.installation_id WHERE wi.account_id = :account_id AND wi.world_key = "epic-ordinary" ORDER BY wr.created_at DESC LIMIT 1'); $statement->execute(['account_id'=>$accountId]); $reaction = $statement->fetch(); return $reaction ?: null; }

    private function questCards(array $quests): string
    {
        $html = '';
        foreach ($quests as $quest) {
            $description = trim((string)$quest['description']);
            $type = QuestService::typeLabel((string)$quest['quest_type']);
            $stepText = (int)$quest['step_count'] > 0 ? '<p class="meta">' . (int)$quest['completed_step_count'] . ' of ' . (int)$quest['step_count'] . ' steps complete</p>' : '';
            $schedule = !empty($quest['frequency']) ? '<p class="meta">Repeats ' . self::escape((string)$quest['frequency']) . (!empty($quest['next_scheduled_for']) ? ' · next ' . self::escape((string)$quest['next_scheduled_for']) : '') . '</p>' : '';
            $html .= '<article class="card"><div><p class="eyebrow">' . self::escape($type) . '</p><h2>' . self::escape((string)$quest['title']) . '</h2>' . ($description !== '' ? '<p>' . self::escape($description) . '</p>' : '') . $stepText . $schedule . '</div><a class="button" href="/quests/' . self::escape((string)$quest['id']) . '">Open ' . self::escape($type) . '</a></article>';
        }
        return $html;
    }

    private function render(string $title, string $body, bool|string $authenticated = true): void
    {
        $account = Security::account(); $active = is_string($authenticated) ? $authenticated : '';
        $navigation = $authenticated && $account ? '<header class="app-header"><a class="brand" href="/hearth">Koravik</a><nav aria-label="Primary"><a href="/hearth"' . ($active === 'hearth' ? ' aria-current="page"' : '') . '>Hearth</a><a href="/quests"' . ($active === 'quests' ? ' aria-current="page"' : '') . '>Quests</a><a href="/world/epic-ordinary"' . ($active === 'world' ? ' aria-current="page"' : '') . '>World</a><a href="/chronicle"' . ($active === 'chronicle' ? ' aria-current="page"' : '') . '>Chronicle</a></nav><form method="post" action="/logout">' . $this->csrfField() . '<button class="quiet-button" type="submit">Sign out</button></form></header>' : '<header class="app-header simple"><span class="brand">Koravik</span></header>';
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><title>' . self::escape($title) . ' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><a class="skip-link" href="#main">Skip to content</a>' . $navigation . '<main id="main" class="page" tabindex="-1">' . $body . '</main><footer>Koravik helps you act, then get back to living.</footer></body></html>';
    }

    private function flashHtml(?string $flash): string { return $flash ? '<div class="notice" role="status">' . self::escape($flash) . '</div>' : ''; }
    private function takeFlash(): ?string { $flash = isset($_SESSION['flash']) ? (string)$_SESSION['flash'] : null; unset($_SESSION['flash']); return $flash; }
    private function csrfField(): string { return '<input type="hidden" name="csrf" value="' . self::escape(Security::csrfToken()) . '">'; }
    private function requireCsrf(): void { if (!Security::verifyCsrf(isset($_POST['csrf']) ? (string)$_POST['csrf'] : null)) { http_response_code(419); throw new RuntimeException('Your session changed. Please try again.'); } }
    private function redirect(string $location): never { header('Location: ' . $location,true,303); exit; }
    private function json(array $data): never { header('Content-Type: application/json; charset=utf-8'); echo json_encode($data,JSON_THROW_ON_ERROR); exit; }
    private function securityHeaders(): void { header('X-Content-Type-Options: nosniff'); header('X-Frame-Options: DENY'); header('Referrer-Policy: strict-origin-when-cross-origin'); header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'"); }
    private static function escape(string $value): string { return htmlspecialchars($value,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8'); }
}
