<?php

declare(strict_types=1);

namespace Koravik\Platform\Journey;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;

final class HealingHomeController
{
    public function __construct(private readonly Database $database)
    {
    }

    public function handle(string $method, string $path): bool
    {
        if ($method === 'GET' && in_array($path, ['/home', '/healing-home'], true)) {
            $account = Security::requireAccount();
            $journey = (new JourneyService($this->database))->homeForAccount((string) $account['id']);
            echo $this->renderHome($account, $journey);

            return true;
        }

        if ($method === 'GET' && preg_match('#^/home/rooms/([a-z0-9_]+)$#', $path, $matches)) {
            $account = Security::requireAccount();
            $room = (new JourneyService($this->database))->roomForAccount((string) $account['id'], $matches[1]);
            if (!$room) {
                http_response_code(404);
                echo $this->document(
                    'Room unavailable',
                    '<section class="panel"><h1>This room is unavailable.</h1><p>The house could not find that doorway.</p><a class="button" href="/home">Return home</a></section>'
                );

                return true;
            }

            echo $this->renderRoom($room);

            return true;
        }

        if ($method === 'POST' && preg_match('#^/home/rooms/([a-z0-9_]+)/rest$#', $path, $matches)) {
            $account = Security::requireAccount();
            if (!$this->verifyRoomCsrf($matches[1])) {
                return true;
            }

            try {
                (new JourneyService($this->database))->restInRoom((string) $account['id'], $matches[1]);
                $_SESSION['flash'] = 'You are resting in that room now.';
            } catch (\RuntimeException $exception) {
                $_SESSION['flash'] = $exception->getMessage();
            }
            header('Location: /home/rooms/' . $matches[1], true, 303);
            return true;
        }

        if ($method === 'POST' && preg_match('#^/home/rooms/([a-z0-9_]+)/note$#', $path, $matches)) {
            $account = Security::requireAccount();
            if (!$this->verifyRoomCsrf($matches[1])) {
                return true;
            }

            try {
                (new JourneyService($this->database))->saveRoomNote((string) $account['id'], $matches[1], (string) ($_POST['note_text'] ?? ''));
                $_SESSION['flash'] = 'Room note saved.';
            } catch (\RuntimeException $exception) {
                $_SESSION['flash'] = $exception->getMessage();
            }
            header('Location: /home/rooms/' . $matches[1], true, 303);
            return true;
        }

        if ($method === 'POST' && preg_match('#^/home/rooms/([a-z0-9_]+)/note/clear$#', $path, $matches)) {
            $account = Security::requireAccount();
            if (!$this->verifyRoomCsrf($matches[1])) {
                return true;
            }

            try {
                (new JourneyService($this->database))->clearRoomNote((string) $account['id'], $matches[1]);
                $_SESSION['flash'] = 'Room note cleared.';
            } catch (\RuntimeException $exception) {
                $_SESSION['flash'] = $exception->getMessage();
            }
            header('Location: /home/rooms/' . $matches[1], true, 303);
            return true;
        }

        if ($method === 'GET' && preg_match('#^/home/relationships/([a-z0-9-]+)$#', $path, $matches)) {
            $account = Security::requireAccount();
            $relationship = (new JourneyService($this->database))->relationshipForAccount(
                (string) $account['id'],
                $matches[1]
            );

            if (!$relationship) {
                http_response_code(404);
                echo $this->document(
                    'Relationship unavailable',
                    '<section class="panel"><h1>This relationship is unavailable.</h1><a href="/home">Return home</a></section>'
                );

                return true;
            }

            echo $this->renderRelationship($relationship);

            return true;
        }

        return false;
    }

    private function renderHome(array $account, array $journey): string
    {
        $focus = $journey['focus_quest'];
        $focusHtml = $focus
            ? '<article class="home-focus room-card room-quest-board" aria-labelledby="quest-board-title"><span class="room-glow" aria-hidden="true"></span><p class="eyebrow">Quest Board</p><h2 id="quest-board-title">' . self::e((string) $focus['title']) . '</h2><p>' . self::e((string) ($focus['purpose'] ?: 'A commitment you chose to carry.')) . '</p><div class="next-step"><span>Next meaningful step</span><strong>' . self::e((string) ($focus['next_step'] ?: 'Open the Quest and decide what comes next.')) . '</strong></div><p class="local-actions"><a class="button" href="/quests/' . self::e((string) $focus['id']) . '">Continue Quest</a><a href="/home/rooms/quest_board">Open room</a></p></article>'
            : '<article class="home-focus room-card room-quest-board empty-state" aria-labelledby="quest-board-title"><span class="room-glow" aria-hidden="true"></span><p class="eyebrow">Quest Board</p><h2 id="quest-board-title">Nothing is demanding your attention.</h2><p>You can begin something meaningful when you are ready.</p><p class="local-actions"><a class="button" href="/quests/create">Begin a Quest</a><a href="/home/rooms/quest_board">Open room</a></p></article>';

        $memory = $journey['chronicle'];
        $memoryHtml = $memory
            ? '<article class="home-place room-card room-journal-table" aria-labelledby="journal-table-title"><p class="eyebrow">Journal Table</p><h2 id="journal-table-title">' . self::e((string) $memory['title']) . '</h2><p>' . self::e((string) $memory['body']) . '</p><p class="local-actions"><a href="/chronicle">Open Chronicle</a><a href="/home/rooms/journal_table">Open room</a></p></article>'
            : '<article class="home-place room-card room-journal-table" aria-labelledby="journal-table-title"><p class="eyebrow">Journal Table</p><h2 id="journal-table-title">Your story has room to gather.</h2><p>Reflections and meaningful moments will rest here.</p><p class="local-actions"><a href="/chronicle">Open Chronicle</a><a href="/home/rooms/journal_table">Open room</a></p></article>';

        $changes = '';
        foreach ($journey['changes'] as $change) {
            $changes .= '<li><strong>' . self::e((string) $change['title']) . '</strong><span>' . self::e((string) $change['description']) . '</span><small>' . self::e(ucwords(str_replace('_', ' ', (string) $change['room_key']))) . '</small></li>';
        }
        $changeHtml = '<article class="home-place room-card room-fireplace fireplace" aria-labelledby="fireplace-title"><p class="eyebrow">Fireplace</p><h2 id="fireplace-title">The house noticed.</h2>' . ($changes ? '<ol class="home-change-list">' . $changes . '</ol>' : '<p>The World will reflect only what you deliberately allow it to notice.</p>') . '<p class="local-actions"><a href="/worlds/epic-ordinary/progress">See World history</a><a href="/home/rooms/fireplace">Open room</a></p></article>';

        $keepsakes = '';
        foreach ($journey['keepsakes'] as $keepsake) {
            $keepsakes .= '<li><strong>' . self::e((string) $keepsake['name']) . '</strong><span>' . self::e((string) $keepsake['meaning']) . '</span></li>';
        }
        $keepsakeHtml = '<article class="home-place room-card room-keepsakes" aria-labelledby="keepsake-title"><p class="eyebrow">Keepsake Shelf</p><h2 id="keepsake-title">Small things worth keeping.</h2>' . ($keepsakes ? '<ul class="keepsake-list">' . $keepsakes . '</ul>' : '<p>No keepsakes are displayed yet. They arrive through meaningful story and reflection, not grinding.</p>') . '<a href="/home/rooms/fireplace">Open room</a></article>';

        $relationships = '';
        foreach ($journey['relationships'] as $relationship) {
            $relationships .= '<li><a href="/home/relationships/' . self::e((string) $relationship['character_key']) . '"><strong>' . self::e((string) $relationship['character_name']) . '</strong><span>' . self::e(ucfirst((string) $relationship['relationship_state'])) . ' - remembers ' . (int) $relationship['familiarity'] . '%</span></a></li>';
        }
        $relationshipHtml = '<article class="home-place room-card room-relationships companion-seat" aria-labelledby="relationship-title"><p class="eyebrow">Guest and Resident Memory</p><h2 id="relationship-title">People remember what was shared.</h2>' . ($relationships ? '<ul class="relationship-list">' . $relationships . '</ul>' : '<p>No one has gathered here yet.</p>') . '<a href="/home/rooms/entry_hall">Open room</a></article>';

        $rooms = '';
        foreach ($journey['rooms'] as $room) {
            $locked = (string) $room['state'] !== 'open';
            $isCurrent = (string) ($journey['state']['current_room'] ?? 'entry_hall') === (string) $room['room_key'];
            $rooms .= '<li class="home-room ' . ($locked ? 'locked' : 'open') . ($isCurrent ? ' current-room' : '') . '"><a href="/home/rooms/' . self::e((string) $room['room_key']) . '"' . ($isCurrent ? ' aria-current="location"' : '') . '><span>' . self::e((string) $room['name']) . '</span><small>' . ($isCurrent ? 'Resting here' : ($locked ? 'Waiting to be discovered' : 'Open')) . '</small></a></li>';
        }

        $atmosphere = str_replace('_', ' ', (string) ($journey['state']['atmosphere'] ?? 'quiet morning'));
        $returned = $journey['state']['last_returned_at']
            ? '<p class="home-return-note">Last opened ' . self::e((string) $journey['state']['last_returned_at']) . ' UTC. Nothing was lost while you were away.</p>'
            : '<p class="home-return-note">The first door is open. The rest can wait.</p>';

        $body = '<section class="healing-home-hero" aria-labelledby="healing-home-title"><div class="home-sky" aria-hidden="true"><span></span><span></span><span></span></div><div class="healing-home-copy"><p class="eyebrow">Healing Home - ' . self::e(ucwords($atmosphere)) . '</p><h1 id="healing-home-title">Welcome home, ' . self::e((string) $account['display_name']) . '.</h1><p>You do not have to carry everything at once. One honest next step is enough.</p>' . $returned . '<div class="hero-actions"><a class="button" href="#home-room-scene">Step inside</a><a class="button secondary" href="/worlds/epic-ordinary/play">Continue the story</a></div></div><figure class="home-illustration" aria-label="A warm cutaway room with a lit fireplace, quest board, journal table, companion chair, and unopened doors."><div class="roof" aria-hidden="true"></div><div class="room-window" aria-hidden="true"></div><div class="room-fire" aria-hidden="true"></div><div class="room-board" aria-hidden="true"></div><div class="room-table" aria-hidden="true"></div><div class="room-chair" aria-hidden="true"></div><div class="room-door" aria-hidden="true"></div></figure></section><section id="home-room-scene" class="healing-home-grid" aria-label="Healing Home rooms">' . $focusHtml . $changeHtml . $memoryHtml . $keepsakeHtml . $relationshipHtml . '<article class="home-place room-card room-companion-chair"><p class="eyebrow">Companion Chair</p><h2>A place for thoughtful help.</h2><p>The Companion may help you clarify, reflect, or draft, but never choose for you.</p><p class="local-actions"><a href="/companion">Visit Companion</a><a href="/home/rooms/companion_chair">Open room</a></p></article></section><section class="home-rooms"><div class="section-heading"><div><p class="eyebrow">The house</p><h2>Familiar places and unopened doors</h2></div></div><ul>' . $rooms . '</ul></section>';

        return $this->document('Healing Home', $body);
    }

    private function renderRoom(array $data): string
    {
        $room = $data['room'];
        $roomKey = (string) $room['room_key'];
        $isOpen = (string) $room['state'] === 'open';
        if (!$isOpen) {
            return $this->document(
                (string) $room['name'],
                '<section class="room-detail-hero locked-room" aria-labelledby="room-title"><p class="eyebrow">Healing Home</p><h1 id="room-title">' . self::e((string) $room['name']) . '</h1><p>This doorway is visible, but it has not opened yet. Koravik will not invent a ritual or punishment to force it.</p><div class="hero-actions"><a class="button" href="/home">Return home</a></div></section>'
            );
        }

        $copy = $this->roomCopy($roomKey);
        $isCurrent = (string) ($data['current_room'] ?? '') === $roomKey;
        $restAction = $isCurrent
            ? '<p class="room-rest-status" role="status">You are resting here.</p>'
            : '<form class="room-rest-form" method="post" action="/home/rooms/' . self::e($roomKey) . '/rest"><input type="hidden" name="csrf" value="' . self::e(Security::csrfToken()) . '"><button class="button secondary" type="submit">Rest here</button></form>';
        $body = $this->flashHtml() . '<section class="room-detail-hero room-detail-' . self::e($roomKey) . '" aria-labelledby="room-title"><p class="eyebrow">Healing Home Room</p><h1 id="room-title">' . self::e((string) $room['name']) . '</h1><p>' . self::e($copy['summary']) . '</p><div class="hero-actions"><a class="button" href="' . self::e($copy['primary_href']) . '">' . self::e($copy['primary_label']) . '</a><a class="button secondary" href="/home">Return home</a>' . $restAction . '</div></section><section class="room-detail-grid" aria-label="Room contents">' . $this->roomPrimaryPanel($roomKey, $data, $copy) . $this->roomContextPanel($roomKey, $data) . '</section>' . $this->roomNotePanel($roomKey, $room) . '<section class="room-trust-panel"><p class="eyebrow">Ownership</p><h2>What this room can and cannot do</h2><p>' . self::e($copy['ownership']) . '</p></section>';

        return $this->document((string) $room['name'], $body);
    }

    private function roomPrimaryPanel(string $roomKey, array $data, array $copy): string
    {
        if ($roomKey === 'quest_board') {
            $quest = $data['focus_quest'];
            return '<article class="room-detail-panel"><p class="eyebrow">Current Quest</p>' . ($quest ? '<h2>' . self::e((string) $quest['title']) . '</h2><p>' . self::e((string) ($quest['purpose'] ?: 'A commitment you chose to carry.')) . '</p><div class="next-step"><span>Next meaningful step</span><strong>' . self::e((string) ($quest['next_step'] ?: 'Open the Quest and decide what comes next.')) . '</strong></div><a class="button" href="/quests/' . self::e((string) $quest['id']) . '">Open in Quests</a>' : '<h2>No active Quest is waiting here.</h2><p>The board can stay quiet. Begin a Quest when it would genuinely help.</p><a class="button" href="/quests/create">Begin a Quest</a>') . '</article>';
        }

        if ($roomKey === 'journal_table') {
            $entries = '';
            foreach ($data['chronicle'] as $entry) {
                $entries .= '<li><strong>' . self::e((string) $entry['title']) . '</strong><span>' . self::e((string) $entry['body']) . '</span><small>' . self::e((string) $entry['created_at']) . ' UTC</small></li>';
            }
            return '<article class="room-detail-panel"><p class="eyebrow">Chronicle</p><h2>Recent intentional memory</h2>' . ($entries ? '<ol class="room-memory-list">' . $entries . '</ol>' : '<p>No Chronicle entries are resting here yet.</p>') . '<a class="button" href="/chronicle">Open Chronicle</a></article>';
        }

        if ($roomKey === 'companion_chair') {
            return '<article class="room-detail-panel"><p class="eyebrow">Companion</p><h2>Help that waits for consent.</h2><p>The Companion can clarify, draft, summarize, and propose. Consequential action still belongs to you and to the owning District.</p><a class="button" href="/companion">Visit Companion</a></article>';
        }

        return '<article class="room-detail-panel"><p class="eyebrow">' . self::e($copy['panel_label']) . '</p><h2>' . self::e($copy['panel_title']) . '</h2><p>' . self::e($copy['panel_body']) . '</p><a class="button" href="' . self::e($copy['primary_href']) . '">' . self::e($copy['primary_label']) . '</a></article>';
    }

    private function roomContextPanel(string $roomKey, array $data): string
    {
        $items = '';
        foreach ($data['changes'] as $change) {
            $items .= '<li><strong>' . self::e((string) $change['title']) . '</strong><span>' . self::e((string) $change['description']) . '</span></li>';
        }
        foreach ($data['keepsakes'] as $keepsake) {
            $items .= '<li><strong>' . self::e((string) $keepsake['name']) . '</strong><span>' . self::e((string) $keepsake['meaning']) . '</span></li>';
        }
        if ($roomKey === 'entry_hall') {
            foreach ($data['relationships'] as $relationship) {
                $items .= '<li><strong>' . self::e((string) $relationship['character_name']) . '</strong><span>' . self::e(ucfirst((string) $relationship['relationship_state'])) . ' - remembers ' . (int) $relationship['familiarity'] . '%</span></li>';
            }
        }

        return '<aside class="room-detail-panel room-context-panel"><p class="eyebrow">Room memory</p><h2>What is gathered here</h2>' . ($items ? '<ol class="room-memory-list">' . $items . '</ol>' : '<p>This room is quiet right now.</p>') . '</aside>';
    }

    private function roomNotePanel(string $roomKey, array $room): string
    {
        $note = (string) ($room['note_text'] ?? '');
        $updated = $room['note_updated_at']
            ? '<p class="meta">Last saved ' . self::e((string) $room['note_updated_at']) . ' UTC</p>'
            : '<p class="meta">Private to this room. Not saved to Chronicle.</p>';
        $clear = $note !== ''
            ? '<button class="quiet-button" type="submit" formaction="/home/rooms/' . self::e($roomKey) . '/note/clear">Clear note</button>'
            : '';

        return '<section class="room-note-panel" aria-labelledby="room-note-title"><div><p class="eyebrow">Room Note</p><h2 id="room-note-title">Why rest here?</h2><p>A small private intention for this room. It stays in Healing Home and does not become a Quest, Chronicle entry, or World fact.</p></div><form method="post" action="/home/rooms/' . self::e($roomKey) . '/note"><input type="hidden" name="csrf" value="' . self::e(Security::csrfToken()) . '"><label for="room-note-text">Room note <span class="optional">Optional</span></label><textarea id="room-note-text" name="note_text" maxlength="600" rows="4">' . self::e($note) . '</textarea><div class="form-actions"><button class="button" type="submit">Save note</button>' . $clear . '</div></form>' . $updated . '</section>';
    }

    private function roomCopy(string $roomKey): array
    {
        return match ($roomKey) {
            'quest_board' => ['summary' => 'The board gathers one real-life commitment without turning your home into a backlog.', 'primary_href' => '/quests', 'primary_label' => 'Open Quests', 'panel_label' => 'Quest Board', 'panel_title' => 'A place for chosen action.', 'panel_body' => 'Quests owns the work. Healing Home only keeps it visible.', 'ownership' => 'Quests owns titles, steps, recurrence, progress, and completion. Healing Home only links to that source.'],
            'journal_table' => ['summary' => 'The table holds reflections and memories you intentionally preserved.', 'primary_href' => '/chronicle', 'primary_label' => 'Open Chronicle', 'panel_label' => 'Journal Table', 'panel_title' => 'A place for memory.', 'panel_body' => 'Chronicle owns saved entries and their privacy.', 'ownership' => 'Chronicle owns entries, provenance, archive, and deletion behavior. Healing Home only previews account-owned entries.'],
            'companion_chair' => ['summary' => 'The chair is a quiet place to ask for help without giving away authority.', 'primary_href' => '/companion', 'primary_label' => 'Visit Companion', 'panel_label' => 'Companion Chair', 'panel_title' => 'A place for thoughtful help.', 'panel_body' => 'The Companion proposes. You decide.', 'ownership' => 'Companion owns proposals and memory controls. Destination Districts execute approved consequential actions.'],
            'entry_hall' => ['summary' => 'The entry hall remembers who has met you here and lets you leave without penalty.', 'primary_href' => '/worlds/epic-ordinary/play', 'primary_label' => 'Continue story', 'panel_label' => 'Entry Hall', 'panel_title' => 'A place for arrival.', 'panel_body' => 'Return is orientation, not judgment.', 'ownership' => 'Relationship memory shown here is account-scoped Journey and World context. It is not a score or source-of-truth record about real life.'],
            default => ['summary' => 'This room gathers visible World and Journey context without taking ownership from source modules.', 'primary_href' => '/worlds/epic-ordinary/progress', 'primary_label' => 'Open World progress', 'panel_label' => 'Room', 'panel_title' => 'A place for continuity.', 'panel_body' => 'World changes remain explainable and fictional.', 'ownership' => 'Worlds own World State and reactions. Healing Home only composes permitted account-owned context.'],
        };
    }

    private function renderRelationship(array $relationship): string
    {
        $memories = '';
        foreach ($relationship['memories'] as $memory) {
            $memories .= '<li><p class="eyebrow">' . self::e(ucwords(str_replace('_', ' ', (string) $memory['memory_kind']))) . '</p><p>' . self::e((string) $memory['summary']) . '</p><small>' . self::e((string) $memory['created_at']) . ' UTC</small></li>';
        }

        $body = '<section class="relationship-hero"><p class="eyebrow">Relationship - ' . self::e(ucfirst((string) $relationship['relationship_state'])) . '</p><h1>' . self::e((string) $relationship['character_name']) . '</h1><p>This history records what shaped the relationship. It is not a score, affection meter, or judgment.</p><a class="button secondary" href="/home">Return home</a></section><section class="relationship-memory-panel"><h2>Shared history</h2>' . ($memories ? '<ol class="relationship-memory-list">' . $memories . '</ol>' : '<p>No shared moments have been recorded yet.</p>') . '</section>';

        return $this->document((string) $relationship['character_name'], $body);
    }

    private function document(string $title, string $body): string
    {
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . self::e($title) . ' - Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/journey.css"></head><body><main id="main" class="page">' . $body . '</main><footer>Koravik - Reality first. Story in service of life.</footer></body></html>';
    }

    private function flashHtml(): string
    {
        $message = (string) ($_SESSION['flash'] ?? '');
        unset($_SESSION['flash']);

        return $message === '' ? '' : '<div class="notice" role="status">' . self::e($message) . '</div>';
    }

    private function verifyRoomCsrf(string $roomKey): bool
    {
        if (Security::verifyCsrf((string) ($_POST['csrf'] ?? ''))) {
            return true;
        }

        http_response_code(419);
        echo $this->document(
            'Session changed',
            '<section class="panel"><h1>Your session changed.</h1><p>Please return to the room and try again.</p><a class="button" href="/home/rooms/' . self::e($roomKey) . '">Return to room</a></section>'
        );

        return false;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
