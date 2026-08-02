<?php

declare(strict_types=1);

namespace Koravik\Platform\Journey;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Moments\MomentService;
use Koravik\Platform\Security\Security;
use Koravik\Worlds\WorldHomeService;

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

        if ($method === 'GET' && $path === '/home/rooms/health_garden') {
            Security::requireAccount();
            echo $this->renderHealthGardenRoom();
            return true;
        }

        if ($method === 'GET' && $path === '/home/rooms/gather_table') {
            Security::requireAccount();
            echo $this->renderGatherTableRoom();
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

        if ($method === 'POST' && preg_match('#^/home/rooms/fireplace/reactions/([a-f0-9-]{36})/review$#', $path, $matches)) {
            $account = Security::requireAccount();
            if (!$this->verifyRoomCsrf('fireplace')) {
                return true;
            }

            try {
                (new WorldHomeService($this->database))->markReactionReviewed((string) $account['id'], $matches[1]);
                $_SESSION['flash'] = 'World change marked as reviewed.';
            } catch (\RuntimeException $exception) {
                $_SESSION['flash'] = $exception->getMessage();
            }
            header('Location: /home/rooms/fireplace', true, 303);
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

        if ($method === 'GET' && $path === '/home/keepsakes') {
            $account = Security::requireAccount();
            echo $this->renderKeepsakes((new JourneyService($this->database))->keepsakesForAccount((string) $account['id']));
            return true;
        }

        if ($method === 'GET' && $path === '/home/reclamation') {
            $account = Security::requireAccount();
            echo $this->renderReclamation((new JourneyService($this->database))->reclamationForAccount((string) $account['id']));
            return true;
        }

        if ($method === 'GET' && $path === '/home/discoveries') {
            $account = Security::requireAccount();
            echo $this->renderDiscoveries((new JourneyService($this->database))->reclamationForAccount((string) $account['id']));
            return true;
        }

        if ($method === 'GET' && $path === '/home/tiny-joys') {
            $account = Security::requireAccount();
            echo $this->renderTinyJoys((new JourneyService($this->database))->reclamationForAccount((string) $account['id']));
            return true;
        }

        if ($method === 'GET' && $path === '/home/seasons') {
            $account = Security::requireAccount();
            echo $this->renderSeasons((new JourneyService($this->database))->reclamationForAccount((string) $account['id']));
            return true;
        }

        if ($method === 'GET' && $path === '/home/moments') {
            $account = Security::requireAccount();
            echo $this->renderMomentsRemembered((new JourneyService($this->database))->reclamationForAccount((string) $account['id']));
            return true;
        }

        if ($method === 'GET' && preg_match('#^/home/keepsakes/([a-f0-9-]{36})$#', $path, $matches)) {
            $account = Security::requireAccount();
            $keepsake = (new JourneyService($this->database))->keepsakeForAccount((string) $account['id'], $matches[1]);
            if (!$keepsake) {
                http_response_code(404);
                echo $this->document(
                    'Keepsake unavailable',
                    '<section class="panel"><h1>This keepsake is unavailable.</h1><p>The shelf could not find that item for this account.</p><a class="button" href="/home/keepsakes">Return to keepsakes</a></section>'
                );

                return true;
            }

            echo $this->renderKeepsake($keepsake);
            return true;
        }

        if ($method === 'POST' && preg_match('#^/home/keepsakes/([a-f0-9-]{36})/moment$#', $path, $matches)) {
            $account = Security::requireAccount();
            if (!Security::verifyCsrf(isset($_POST['csrf']) ? (string) $_POST['csrf'] : null)) {
                $_SESSION['flash'] = 'Your session changed. Please try again.';
                header('Location: /home/keepsakes/' . $matches[1], true, 303);
                return true;
            }
            $keepsake = (new JourneyService($this->database))->keepsakeForAccount((string) $account['id'], $matches[1]);
            if (!$keepsake) {
                http_response_code(404);
                echo $this->document('Keepsake unavailable', '<section class="panel"><h1>This keepsake is unavailable.</h1><a class="button" href="/home/keepsakes">Return to keepsakes</a></section>');
                return true;
            }
            $momentId = (new MomentService($this->database))->submit((string) $account['id'], [
                'source_module' => 'Healing Home',
                'source_type' => 'keepsake_manual',
                'source_id' => (string) $keepsake['id'],
                'moment_key' => 'keepsake-detail-memory-scene',
                'scene_template' => 'memory',
                'primary_object' => (string) $keepsake['name'],
                'ambient_detail' => 'The object waits on the shelf as evidence, not reward.',
                'recommended_action_label' => 'Stay with the object',
                'title' => (string) $keepsake['name'],
                'body' => (string) $keepsake['meaning'],
                'room_key' => (string) $keepsake['room_key'],
                'visibility' => 'chronicle',
                'priority' => 'low',
                'provenance_summary' => 'You chose to prepare this displayed Healing Home keepsake as a memory-object Moment.',
                'excluded_summary' => 'Preparing this Moment did not read private room notes, Chronicle prose, Companion memory, Health records, or unrelated data.',
            ]);
            $_SESSION['flash'] = 'Keepsake prepared as a memory Moment.';
            header('Location: /moments/' . $momentId, true, 303);
            return true;
        }

        if ($method === 'GET' && $path === '/home/timeline') {
            $account = Security::requireAccount();
            echo $this->renderTimeline((new JourneyService($this->database))->timelineForAccount((string) $account['id']));
            return true;
        }

        if ($method === 'GET' && $path === '/home/privacy') {
            Security::requireAccount();
            echo $this->renderHomePrivacy();
            return true;
        }

        if ($method === 'GET' && $path === '/home/guide') {
            Security::requireAccount();
            echo $this->renderHomeGuide();
            return true;
        }

        if ($method === 'GET' && $path === '/home/today') {
            $account = Security::requireAccount();
            echo $this->renderHomeToday((new JourneyService($this->database))->homeForAccount((string) $account['id']));
            return true;
        }

        if ($method === 'GET' && $path === '/home/rooms') {
            $account = Security::requireAccount();
            echo $this->renderRoomDirectory((new JourneyService($this->database))->homeForAccount((string) $account['id']));
            return true;
        }

        if ($method === 'GET' && $path === '/home/sources') {
            Security::requireAccount();
            echo $this->renderSourceGlossary();
            return true;
        }

        if ($method === 'GET' && $path === '/home/invitations') {
            $account = Security::requireAccount();
            echo $this->renderHouseInvitations((new JourneyService($this->database))->homeForAccount((string) $account['id']));
            return true;
        }

        if ($method === 'GET' && $path === '/home/thresholds') {
            $account = Security::requireAccount();
            echo $this->renderHouseThresholds((new JourneyService($this->database))->homeForAccount((string) $account['id']));
            return true;
        }

        if ($method === 'GET' && $path === '/home/atlas') {
            Security::requireAccount();
            echo $this->renderHouseAtlas();
            return true;
        }

        if ($method === 'GET' && $path === '/home/lore') {
            Security::requireAccount();
            echo $this->renderRoomLore();
            return true;
        }

        if ($method === 'GET' && $path === '/home/constellations') {
            Security::requireAccount();
            echo $this->renderHouseConstellations();
            return true;
        }

        if ($method === 'GET' && $path === '/home/boundaries') {
            Security::requireAccount();
            echo $this->renderBoundaryLedger();
            return true;
        }

        if ($method === 'GET' && $path === '/home/wayfinding') {
            Security::requireAccount();
            echo $this->renderHouseWayfinding();
            return true;
        }

        if ($method === 'GET' && $path === '/home/compass') {
            Security::requireAccount();
            echo $this->renderHouseCompass();
            return true;
        }

        if ($method === 'GET' && $path === '/home/moods') {
            Security::requireAccount();
            echo $this->renderHouseMoods();
            return true;
        }

        if ($method === 'GET' && $path === '/home/by-need') {
            Security::requireAccount();
            echo $this->renderRoomsByNeed();
            return true;
        }

        if ($method === 'GET' && $path === '/home/consent-map') {
            Security::requireAccount();
            echo $this->renderConsentMap();
            return true;
        }

        if ($method === 'GET' && $path === '/home/changelog') {
            Security::requireAccount();
            echo $this->renderHouseChangelog();
            return true;
        }

        if ($method === 'GET' && preg_match('#^/home/source/(change|keepsake|conversation)/([a-f0-9-]{36})$#', $path, $matches)) {
            $account = Security::requireAccount();
            $thread = (new JourneyService($this->database))->sourceThreadForAccount((string) $account['id'], $matches[1], $matches[2]);
            if (!$thread) {
                http_response_code(404);
                echo $this->document('Source thread unavailable', '<section class="panel"><h1>Source thread unavailable.</h1><p>The house could not find that source thread for this account.</p><a class="button" href="/home/timeline">Return to timeline</a></section>');
                return true;
            }
            echo $this->renderSourceThread($thread);
            return true;
        }

        if ($method === 'POST' && preg_match('#^/home/relationships/([a-z0-9-]+)/converse$#', $path, $matches)) {
            $account = Security::requireAccount();
            if (!$this->verifyRelationshipCsrf($matches[1])) {
                return true;
            }

            try {
                if ($matches[1] !== 'caretaker') {
                    throw new \RuntimeException('That relationship is not ready for conversation yet.');
                }
                (new JourneyService($this->database))->converseWithCaretaker((string) $account['id'], (string) ($_POST['choice'] ?? ''));
                $_SESSION['flash'] = 'The Caretaker remembered the moment.';
            } catch (\RuntimeException $exception) {
                $_SESSION['flash'] = $exception->getMessage();
            }
            header('Location: /home/relationships/' . $matches[1], true, 303);
            return true;
        }

        if ($method === 'POST' && $path === '/home/rooms/garden/tend') {
            $account = Security::requireAccount();
            if (!$this->verifyRoomCsrf('garden')) {
                return true;
            }

            try {
                (new JourneyService($this->database))->tendGarden((string) $account['id'], (string) ($_POST['choice'] ?? ''));
                $_SESSION['flash'] = 'The Garden was tended.';
            } catch (\RuntimeException $exception) {
                $_SESSION['flash'] = $exception->getMessage();
            }
            header('Location: /home/rooms/garden', true, 303);
            return true;
        }

        return false;
    }

    private function renderHealthGardenRoom(): string
    {
        $body = '<section class="room-detail-hero healing-home-health-garden-room" aria-labelledby="room-title"><p class="eyebrow">Health Garden</p><h1 id="room-title"><span class="room-title-symbol" aria-hidden="true">❧</span>Health Garden</h1><p>A composed room for care patterns. Health owns observations, notes, revisions, and derived sharing consent; Healing Home only points toward private records and consent-safe summaries.</p><div class="hero-actions"><a class="button" href="/health">Open Health</a><a class="button secondary" href="/home">Return home</a></div></section><section class="room-detail-grid"><article class="room-detail-panel"><p class="eyebrow">What this room can show</p><h2>Care without diagnosis.</h2><p>The garden may show that a private Health surface exists and can link you back to it. It does not read feeling words, expose notes, or infer diagnosis.</p><p class="local-actions"><a class="button secondary" href="/health/checkins">Review check-ins</a><a class="button secondary" href="/privacy">Review consent</a></p></article><aside class="room-detail-panel room-context-panel"><p class="eyebrow">Boundary</p><h2>What stays in Health</h2><ul class="room-memory-list"><li><strong>Health source owner</strong><span>Observed date, private note, revision history, and sharing flags.</span></li><li><strong>Healing Home display</strong><span>Orientation language and a safe doorway back to Health.</span></li><li><strong>Never hidden scoring</strong><span>No diagnosis, no streak pressure, and no background emotional assessment.</span></li></ul></aside></section>';
        return $this->document('Health Garden', $body);
    }

    private function renderGatherTableRoom(): string
    {
        $body = '<section class="room-detail-hero healing-home-gather-table-room" aria-labelledby="room-title"><p class="eyebrow">Gather Table</p><h1 id="room-title"><span class="room-title-symbol" aria-hidden="true">◇</span>Gather Table</h1><p>A composed room for invitations and event continuity. Gather owns events, RSVP truth, signup commitments, attendance, communication preferences, and follow-up records.</p><div class="hero-actions"><a class="button" href="/gather">Open Gather</a><a class="button secondary" href="/home">Return home</a></div></section><section class="room-detail-grid"><article class="room-detail-panel"><p class="eyebrow">What this room can show</p><h2>Coordination without taking over.</h2><p>The table may orient you toward upcoming or recent gatherings, but event truth remains in Gather and public details remain behind Gather public preview safety.</p><p class="local-actions"><a class="button secondary" href="/gather">Open Gather dashboard</a><a class="button secondary" href="/home/boundaries">Review house boundaries</a></p></article><aside class="room-detail-panel room-context-panel"><p class="eyebrow">Boundary</p><h2>What stays in Gather</h2><ul class="room-memory-list"><li><strong>Gather source owner</strong><span>RSVP, signup, attendance, reminders, participant preferences, and follow-up.</span></li><li><strong>Healing Home display</strong><span>A calm table metaphor and safe links back to Gather.</span></li><li><strong>Never silently shared</strong><span>Beacon pages, public attendance, and guest communication require their own source-owned review.</span></li></ul></aside></section>';
        return $this->document('Gather Table', $body);
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
        $keepsakeHtml = '<article class="home-place room-card room-keepsakes" aria-labelledby="keepsake-title"><p class="eyebrow">Keepsake Shelf</p><h2 id="keepsake-title">Small things worth keeping.</h2>' . ($keepsakes ? '<ul class="keepsake-list">' . $keepsakes . '</ul>' : '<p>No keepsakes are displayed yet. They arrive through meaningful story and reflection, not grinding.</p>') . '<p class="local-actions"><a href="/home/keepsakes">Open shelf</a><a href="/home/rooms/fireplace">Open room</a></p></article>';

        $relationships = '';
        foreach ($journey['relationships'] as $relationship) {
            $relationships .= '<li><a href="/home/relationships/' . self::e((string) $relationship['character_key']) . '"><strong>' . self::e((string) $relationship['character_name']) . '</strong><span>' . self::e(ucfirst((string) $relationship['relationship_state'])) . ' - remembers ' . (int) $relationship['familiarity'] . '%</span></a></li>';
        }
        $relationshipHtml = '<article class="home-place room-card room-relationships companion-seat" aria-labelledby="relationship-title"><p class="eyebrow">Guest and Resident Memory</p><h2 id="relationship-title">People remember what was shared.</h2>' . ($relationships ? '<ul class="relationship-list">' . $relationships . '</ul>' : '<p>No one has gathered here yet.</p>') . '<a href="/home/rooms/entry_hall">Open room</a></article>';

        $rooms = '';
        foreach ($journey['rooms'] as $room) {
            $isCurrent = (string) ($journey['state']['current_room'] ?? 'entry_hall') === (string) $room['room_key'];
            $roomKey = (string) $room['room_key'];
            $rooms .= '<li class="' . self::e($this->roomMapClass($room, $isCurrent)) . '"><a href="/home/rooms/' . self::e($roomKey) . '"' . ($isCurrent ? ' aria-current="location"' : '') . '><span class="room-symbol" aria-hidden="true">' . self::e($this->roomSymbol($roomKey)) . '</span><span class="room-map-name">' . self::e((string) $room['name']) . '</span><span class="room-map-status">' . self::e($this->roomStatusLabel($room, $isCurrent)) . '</span><small>' . self::e($this->roomDoorState($roomKey, (string) $room['state'])) . ' ' . self::e($this->roomMapDescription($roomKey)) . '</small></a></li>';
        }

        $atmosphere = str_replace('_', ' ', (string) ($journey['state']['atmosphere'] ?? 'quiet morning'));
        $recentChange = $journey['changes'][0] ?? null;
        $sinceGone = $recentChange
            ? '<li><strong>Latest room change</strong><span>' . self::e((string) $recentChange['title']) . ' — ' . self::e(ucwords(str_replace('_', ' ', (string) $recentChange['room_key']))) . '</span></li>'
            : '<li><strong>Latest room change</strong><span>The house is quiet right now.</span></li>';
        $openRooms = 0;
        foreach ($journey['rooms'] as $room) {
            if ((string) $room['state'] === 'open') {
                $openRooms++;
            }
        }
        $returned = $journey['state']['last_returned_at']
            ? '<p class="home-return-note">Return scene: the ' . self::e(ucwords(str_replace('_', ' ', (string) ($journey['state']['current_room'] ?? 'entry_hall')))) . ' still held your place. Last opened ' . self::e((string) $journey['state']['last_returned_at']) . ' UTC. Nothing was lost while you were away.</p>'
            : '<p class="home-return-note">The first door is open. The rest can wait.</p>';
        $arrival = '<section class="home-arrival-scene" aria-labelledby="home-arrival-title"><div><p class="eyebrow">Arrival scene</p><h2 id="home-arrival-title">What changed since you were gone</h2><p>' . self::e($this->atmosphereDescription((string) ($journey['state']['atmosphere'] ?? 'quiet_morning'))) . '</p></div><ul class="arrival-list">' . $sinceGone . '<li><strong>Open rooms</strong><span>' . $openRooms . ' rooms are available; waiting doors stay visible without pressure.</span></li><li><strong>Next threshold</strong><span>Choose a room by meaning, not by obligation.</span></li></ul></section>';
        $pulse = '<section class="home-pulse-panel" aria-labelledby="home-pulse-title"><p class="eyebrow">House pulse</p><h2 id="home-pulse-title">' . self::e($this->housePulseLabel((string) ($journey['state']['atmosphere'] ?? 'quiet_morning'))) . '</h2><p>' . self::e($this->housePulseCopy((string) ($journey['state']['atmosphere'] ?? 'quiet_morning'))) . '</p><p class="meta">This is atmospheric presentation, not diagnosis, productivity scoring, or hidden emotional assessment.</p></section>';
        $routes = '<section class="home-resonance-routes" aria-labelledby="home-resonance-title"><div><p class="eyebrow">House resonance</p><h2 id="home-resonance-title">Choose a path by the kind of care you want.</h2><p>These are invitations, not assigned work. Each path keeps the source owner visible.</p></div><ol><li><strong>Understand what changed</strong><span>Fireplace → Library → Source thread</span><a href="/home/rooms/fireplace">Follow the echoes</a></li><li><strong>Make without proving</strong><span>Eastern Room → Workshop → Chronicle only if chosen</span><a href="/home/rooms/workshop">Enter the Workshop</a></li><li><strong>Recover gently</strong><span>Caretaker → Garden → Room note</span><a href="/home/rooms/garden">Visit the Garden</a></li></ol><p><a class="button secondary" href="/home/guide">Open the house guide</a></p></section>';
        $living = '<section class="home-living-house home-command-center healing-home-composition-depth epic-ordinary-reclamation-sprint" aria-labelledby="home-living-title"><div><p class="eyebrow">Epic Ordinary Reclamation Sprint</p><h2 id="home-living-title">The house offers invitations, not assignments.</h2><p>Recovered Epic-Ordinary charm now has visible places: evidence objects, Quiet Hearth whispers, tiny joys, seasonal ambience, remembered Moments, and source-aware discoveries. Healing Home is ready to move sections, and Health and Gather can appear as composed rooms without giving Healing Home ownership of their records. Reclaimed moods still include Green Dusk, quiet morning, and workshop lamplight as presentation language.</p></div><div class="home-command-grid"><article><h3>Start here</h3><p>Daily orientation and direct room access.</p><p class="local-actions"><a class="button secondary" href="/home/today">Today in the house</a><a class="button secondary" href="/home/rooms">Room directory</a><a class="button secondary" href="/home/guide">Open the house guide</a></p></article><article><h3>Reclaimed wonder</h3><p>Epic-Ordinary identity restored as player-facing exploration.</p><p class="local-actions"><a class="button secondary" href="/home/reclamation">Reclamation hearth</a><a class="button secondary" href="/home/discoveries">Discoveries</a><a class="button secondary" href="/home/tiny-joys">Tiny joys</a><a class="button secondary" href="/home/seasons">Seasonal life</a><a class="button secondary" href="/home/moments">Moments remembered</a></p></article><article><h3>Living house</h3><p>Invitations, thresholds, and the authored map of the house.</p><p class="local-actions"><a class="button secondary" href="/home/invitations">House invitations</a><a class="button secondary" href="/home/thresholds">Thresholds</a><a class="button secondary" href="/home/atlas">House atlas</a><a class="button secondary" href="/home/lore">Room lore</a><a class="button secondary" href="/home/constellations">House constellations</a></p></article><article><h3>Source-aware rooms</h3><p>Composed rooms point toward source modules without copying private payloads.</p><p class="local-actions"><a class="button secondary" href="/home/rooms/health_garden">Health Garden</a><a class="button secondary" href="/home/rooms/gather_table">Gather Table</a><a class="button secondary" href="/search">Search room notes</a><a class="button secondary" href="/companion/context">Companion room-note consent</a></p></article><article><h3>Trust and meaning</h3><p>Boundaries, provenance, and consent stay visible.</p><p class="local-actions"><a class="button secondary" href="/home/sources">Source glossary</a><a class="button secondary" href="/home/boundaries">Boundary ledger</a><a class="button secondary" href="/home/consent-map">Consent map</a><a class="button secondary" href="/home/privacy">What the house knows</a></p></article><article><h3>Compass</h3><p>Choose by need, mood, or orientation.</p><p class="local-actions"><a class="button secondary" href="/home/wayfinding">Wayfinding</a><a class="button secondary" href="/home/compass">House compass</a><a class="button secondary" href="/home/moods">Moods</a><a class="button secondary" href="/home/by-need">Rooms by need</a><a class="button secondary" href="/home/changelog">House changelog</a></p></article></div></section>';

        $body = '<section class="healing-home-hero home-atmosphere-' . self::e((string) ($journey['state']['atmosphere'] ?? 'quiet_morning')) . '" aria-labelledby="healing-home-title"><div class="home-sky" aria-hidden="true"><span></span><span></span><span></span></div><div class="healing-home-copy"><p class="eyebrow">Healing Home - ' . self::e(ucwords($atmosphere)) . '</p><h1 id="healing-home-title">Welcome home, ' . self::e((string) $account['display_name']) . '.</h1><p>You do not have to carry everything at once. One honest next step is enough.</p>' . $returned . '<div class="hero-actions"><a class="button" href="#home-room-scene">Step inside</a><a class="button secondary" href="/home/today">Today in the house</a><a class="button secondary" href="/home/rooms">Room directory</a><a class="button secondary" href="/home/sources">Source glossary</a><a class="button secondary" href="/home/privacy">What the house knows</a></div></div><figure class="home-illustration" aria-label="A warm cutaway room with a lit fireplace, quest board, journal table, companion chair, and unopened doors."><div class="roof" aria-hidden="true"></div><div class="room-window" aria-hidden="true"></div><div class="room-fire" aria-hidden="true"></div><div class="room-board" aria-hidden="true"></div><div class="room-table" aria-hidden="true"></div><div class="room-chair" aria-hidden="true"></div><div class="room-door" aria-hidden="true"></div></figure></section>' . $arrival . $pulse . $routes . $living . '<section id="home-room-scene" class="healing-home-grid" aria-label="Healing Home rooms">' . $focusHtml . $changeHtml . $memoryHtml . $keepsakeHtml . $relationshipHtml . '<article class="home-place room-card room-companion-chair"><p class="eyebrow">Companion Chair</p><h2>A place for thoughtful help.</h2><p>The Companion may help you clarify, reflect, or draft, but never choose for you.</p><p class="local-actions"><a href="/companion">Visit Companion</a><a href="/home/rooms/companion_chair">Open room</a></p></article></section><section class="home-rooms home-room-map home-blueprint-map" aria-labelledby="home-room-map-title"><div class="section-heading"><div><p class="eyebrow">Room map</p><h2 id="home-room-map-title">Familiar places and unopened doors</h2><p>Every room names what it holds, whether it is open, and where you are resting now. Each room also has a symbolic marker and a doorway you can choose without pressure.</p></div></div><ul>' . $rooms . '</ul></section>';

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
        $body = $this->flashHtml() . '<section class="room-detail-hero room-detail-' . self::e($roomKey) . '" aria-labelledby="room-title"><p class="eyebrow">Healing Home Room</p><h1 id="room-title"><span class="room-title-symbol" aria-hidden="true">' . self::e($this->roomSymbol($roomKey)) . '</span>' . self::e((string) $room['name']) . '</h1><p>' . self::e($copy['summary']) . '</p><div class="hero-actions"><a class="button" href="' . self::e($copy['primary_href']) . '">' . self::e($copy['primary_label']) . '</a><a class="button secondary" href="/home">Return home</a>' . $restAction . '</div></section>' . $this->roomWalkway($roomKey) . $this->roomPracticePanel($roomKey) . $this->roomInvitationPanel($roomKey) . '<section class="room-detail-grid" aria-label="Room contents">' . $this->roomPrimaryPanel($roomKey, $data, $copy) . $this->roomContextPanel($roomKey, $data) . '</section>' . $this->roomNotePanel($roomKey, $room) . '<section class="room-trust-panel"><p class="eyebrow">Ownership</p><h2>What this room can and cannot do</h2><p>' . self::e($copy['ownership']) . '</p></section>';

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
            return '<article class="room-detail-panel"><p class="eyebrow">Chronicle</p><h2>Recent intentional memory</h2>' . ($entries ? '<ol class="room-memory-list">' . $entries . '</ol>' : '<p>No Chronicle entries are resting here yet.</p>') . '<p class="local-actions"><a class="button" href="/chronicle/new?context=healing_home_journal_table&title=Journal%20Table%20reflection&tags=healing-home,journal-table">Start a reflection</a><a class="button secondary" href="/chronicle">Open Chronicle</a></p><p class="meta">Chronicle owns the saved entry. Healing Home only suggests the starting context.</p></article>';
        }

        if ($roomKey === 'companion_chair') {
            return '<article class="room-detail-panel"><p class="eyebrow">Companion</p><h2>Help that waits for consent.</h2><p>The Companion can clarify, draft, summarize, and propose. Consequential action still belongs to you and to the owning District.</p><a class="button" href="/companion">Visit Companion</a></article>';
        }

        if ($roomKey === 'fireplace') {
            $reactions = '';
            foreach ($data['world_reactions'] ?? [] as $reaction) {
                $review = $reaction['reviewed_at']
                    ? '<p class="reviewed-note">Reviewed ' . self::e((string) $reaction['reviewed_at']) . ' UTC</p>'
                    : '<form method="post" action="/home/rooms/fireplace/reactions/' . self::e((string) $reaction['id']) . '/review"><input type="hidden" name="csrf" value="' . self::e(Security::csrfToken()) . '"><button class="quiet-button" type="submit">Mark reviewed</button></form>';
                $reactions .= '<li><h3>' . self::e((string) $reaction['title']) . '</h3><p>' . self::e((string) $reaction['message']) . '</p><dl class="reaction-explain-list"><div><dt>What changed</dt><dd>' . self::e((string) $reaction['explanation']) . '</dd></div><div><dt>Approved fact</dt><dd>' . self::e((string) ($reaction['source_fact_summary'] ?: 'A minimized approved event fact.')) . '</dd></div><div><dt>World rule</dt><dd>' . self::e((string) ($reaction['rule_key'] ?: 'Epic Ordinary authored reaction')) . '</dd></div><div><dt>Deliberately excluded</dt><dd>Quest notes, Chronicle prose, Companion memory, Health records, and unrelated private data.</dd></div></dl><p class="meta">Interpreted ' . self::e((string) $reaction['interpreted_at']) . ' UTC</p>' . $review . '</li>';
            }
            return '<article class="room-detail-panel fireplace-reaction-panel"><p class="eyebrow">Narrative center</p><h2>The Fireplace reads the echoes.</h2><p class="room-cinematic-copy">Why the house noticed: Recent echoes gather here when an authorized World turns a minimized fact into fiction. Review makes the ember quieter; it never changes the original District record.</p>' . ($reactions ? '<ol class="room-memory-list">' . $reactions . '</ol>' : '<p class="ambient-empty-state">No approved World reaction has reached the fireplace yet. The hearth can stay dark until a World has something explainable to say.</p>') . '<a class="button secondary" href="/worlds/epic-ordinary/progress">Open World progress</a></article>';
        }

        if ($roomKey === 'garden') {
            $growth = $this->gardenGrowthState($data['changes'] ?? []);
            return '<article class="room-detail-panel garden-room-panel"><p class="eyebrow">Garden growth state</p><h2>' . self::e($growth['title']) . '</h2><p>' . self::e($growth['copy']) . ' It remains never streaks, punishment, or proof.</p><ul class="garden-growth-list"><li>Watered: ' . self::e($growth['watered']) . '</li><li>Cleared: ' . self::e($growth['cleared']) . '</li><li>Rested: ' . self::e($growth['rested']) . '</li><li>Repaired: ' . self::e($growth['repaired']) . '</li></ul><form method="post" action="/home/rooms/garden/tend"><input type="hidden" name="csrf" value="' . self::e(Security::csrfToken()) . '"><div class="resolution-grid"><button class="button secondary" name="choice" value="water" type="submit">Water gently</button><button class="button secondary" name="choice" value="clear_space" type="submit">Clear space</button><button class="button secondary" name="choice" value="rest" type="submit">Rest here</button><button class="button secondary" name="choice" value="repair" type="submit">Tend repair</button></div></form><p class="local-actions"><a class="button" href="/home/relationships/caretaker">Speak with the Caretaker</a><a class="button secondary" href="/chronicle/new?context=healing_home_journal_table&title=Garden%20reflection&tags=healing-home,garden">Reflect from the Garden</a></p></article>';
        }

        if ($roomKey === 'workshop') {
            $note = trim((string) ($data['room']['note_text'] ?? ''));
            $idea = $note !== '' ? mb_strimwidth(strtok($note, "\n") ?: $note, 0, 90, '…') : 'No unfinished idea is pinned here yet.';
            return '<article class="room-detail-panel workshop-room-panel"><p class="eyebrow">Unfinished ideas shelf</p><h2>A place for making and repair.</h2><p>The Workshop opens when the Eastern Room becomes a place for making. It holds unfinished ideas without converting them into obligations.</p><div class="unfinished-idea-card"><strong>Local shelf</strong><span>' . self::e($idea) . '</span><small>Use the private room note below to hold a seed here, or preserve it to Chronicle only when you choose.</small></div><a class="button" href="/chronicle/new?context=healing_home_journal_table&title=Workshop%20sketch&tags=healing-home,workshop">Preserve a sketch in Chronicle</a></article>';
        }

        if ($roomKey === 'library') {
            return '<article class="room-detail-panel library-room-panel"><p class="eyebrow">Explanation browser</p><h2>A place for explanations.</h2><p>The Library opens after you review a World reaction. It gathers meaning, source ownership, and privacy boundaries without exposing private records.</p><dl class="reaction-explain-list library-shelf-list"><div><dt>World reaction shelf</dt><dd>Fictional changes and why they appeared.</dd></div><div><dt>Room change shelf</dt><dd>Doors, atmosphere, keepsakes, and visible home state.</dd></div><div><dt>Privacy shelf</dt><dd>What the house composes and what it deliberately ignores.</dd></div></dl><p class="local-actions"><a class="button" href="/home/timeline">Open timeline</a><a class="button secondary" href="/home/privacy">What the house knows</a></p></article>';
        }

        if ($roomKey === 'guest_room') {
            return '<article class="room-detail-panel guest-room-panel"><p class="eyebrow">Consent preview</p><h2>A place prepared for welcome.</h2><p>The Guest Room opens when your Eastern Room choice centers welcome. It does not share anything publicly or invite anyone without explicit future consent.</p><dl class="reaction-explain-list guest-consent-list"><div><dt>Could later be shared</dt><dd>A chosen invitation, a selected room note excerpt, or a future explicit welcome message.</dd></div><div><dt>Never silently shared</dt><dd>Quest notes, Chronicle prose, Companion memory, Health records, or other people’s data.</dd></div><div><dt>Required first</dt><dd>A clear review screen and approval action from you.</dd></div></dl><a class="button" href="/home/privacy">Review sharing boundaries</a></article>';
        }

        if ($roomKey === 'eastern_room') {
            return '<article class="room-detail-panel eastern-room-panel"><p class="eyebrow">Purpose deepening</p><h2>' . self::e($this->easternPurposeTitle($data)) . '</h2><p>A room with a chosen purpose. ' . self::e($this->easternPurposeCopy($data)) . '</p><p class="local-actions"><a class="button" href="/worlds/epic-ordinary/play">Continue Epic Ordinary</a><a class="button secondary" href="/home/timeline">Trace the source thread</a></p></article>';
        }

        if ($roomKey === 'fireplace') {
            $reactions = '';
            foreach ($data['world_reactions'] ?? [] as $reaction) {
                $review = $reaction['reviewed_at']
                    ? '<p class="reviewed-note">Reviewed ' . self::e((string) $reaction['reviewed_at']) . ' UTC</p>'
                    : '<form method="post" action="/home/rooms/fireplace/reactions/' . self::e((string) $reaction['id']) . '/review"><input type="hidden" name="csrf" value="' . self::e(Security::csrfToken()) . '"><button class="quiet-button" type="submit">Mark reviewed</button></form>';
                $reactions .= '<li><h3>' . self::e((string) $reaction['title']) . '</h3><p>' . self::e((string) $reaction['message']) . '</p><dl class="reaction-explain-list"><div><dt>What changed</dt><dd>' . self::e((string) $reaction['explanation']) . '</dd></div><div><dt>Approved fact</dt><dd>' . self::e((string) ($reaction['source_fact_summary'] ?: 'A minimized approved event fact.')) . '</dd></div><div><dt>World rule</dt><dd>' . self::e((string) ($reaction['rule_key'] ?: 'Epic Ordinary authored reaction')) . '</dd></div><div><dt>Deliberately excluded</dt><dd>Quest notes, Chronicle prose, Companion memory, Health records, and unrelated private data.</dd></div></dl><p class="meta">Interpreted ' . self::e((string) $reaction['interpreted_at']) . ' UTC</p>' . $review . '</li>';
            }

            return '<article class="room-detail-panel fireplace-reaction-panel"><p class="eyebrow">World reactions</p><h2>Why the house noticed</h2>' . ($reactions ? '<ol class="room-memory-list">' . $reactions . '</ol>' : '<p>No approved World reaction has reached the fireplace yet.</p>') . '<a class="button secondary" href="/worlds/epic-ordinary/progress">Open World progress</a></article>';
        }

        if ($roomKey === 'garden') {
            return '<article class="room-detail-panel garden-room-panel"><p class="eyebrow">Garden</p><h2>A place for tending.</h2><p>The Garden opens after an honest Caretaker conversation. It is for recovery, repair, and small chosen care — never streaks, punishment, or proof.</p><form method="post" action="/home/rooms/garden/tend"><input type="hidden" name="csrf" value="' . self::e(Security::csrfToken()) . '"><div class="resolution-grid"><button class="button secondary" name="choice" value="water" type="submit">Water gently</button><button class="button secondary" name="choice" value="clear_space" type="submit">Clear space</button><button class="button secondary" name="choice" value="rest" type="submit">Rest here</button><button class="button secondary" name="choice" value="repair" type="submit">Tend repair</button></div></form><p class="local-actions"><a class="button" href="/home/relationships/caretaker">Speak with the Caretaker</a><a class="button secondary" href="/chronicle/new?context=healing_home_journal_table&title=Garden%20reflection&tags=healing-home,garden">Reflect from the Garden</a></p></article>';
        }

        if ($roomKey === 'workshop') {
            return '<article class="room-detail-panel workshop-room-panel"><p class="eyebrow">Workshop</p><h2>A place for making and repair.</h2><p>The Workshop opens when the Eastern Room becomes a place for making. It holds unfinished ideas without converting them into obligations.</p><a class="button" href="/chronicle/new?context=healing_home_journal_table&title=Workshop%20sketch&tags=healing-home,workshop">Sketch an idea in Chronicle</a></article>';
        }

        if ($roomKey === 'library') {
            return '<article class="room-detail-panel library-room-panel"><p class="eyebrow">Library</p><h2>A place for explanations.</h2><p>The Library opens after you review a World reaction. It gathers meaning, source ownership, and privacy boundaries without exposing private records.</p><p class="local-actions"><a class="button" href="/home/timeline">Open timeline</a><a class="button secondary" href="/home/privacy">What the house knows</a></p></article>';
        }

        if ($roomKey === 'guest_room') {
            return '<article class="room-detail-panel guest-room-panel"><p class="eyebrow">Guest Room</p><h2>A place prepared for welcome.</h2><p>The Guest Room opens when your Eastern Room choice centers welcome. It does not share anything publicly or invite anyone without explicit future consent.</p><a class="button" href="/home/privacy">Review sharing boundaries</a></article>';
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
        $intention = trim(strtok($note, "\n") ?: '');
        $intentionHtml = $intention !== ''
            ? '<p class="room-intention-label"><span>Intention label</span><strong>' . self::e(mb_strimwidth($intention, 0, 90, '…')) . '</strong></p>'
            : '<p class="meta">Add a first line to use it as this room’s private intention label.</p>';
        $updated = $room['note_updated_at']
            ? '<p class="meta">Last saved ' . self::e((string) $room['note_updated_at']) . ' UTC. Not saved to Chronicle.</p>'
            : '<p class="meta">Private to this room. Not saved to Chronicle.</p>';
        $clear = $note !== ''
            ? '<button class="quiet-button" type="submit" formaction="/home/rooms/' . self::e($roomKey) . '/note/clear">Clear note</button>'
            : '';

        return '<section class="room-note-panel" aria-labelledby="room-note-title"><div><p class="eyebrow">Room Note</p><h2 id="room-note-title">Why rest here?</h2><p>A small private intention for this room. It stays in Healing Home and does not become a Quest, Chronicle entry, or World fact.</p>' . $intentionHtml . '</div><form method="post" action="/home/rooms/' . self::e($roomKey) . '/note"><input type="hidden" name="csrf" value="' . self::e(Security::csrfToken()) . '"><label for="room-note-text">Room note <span class="optional">Optional</span></label><textarea id="room-note-text" name="note_text" maxlength="600" rows="4">' . self::e($note) . '</textarea><div class="form-actions"><button class="button" type="submit">Save note</button>' . $clear . '</div></form>' . $updated . '</section>';
    }

    private function roomCopy(string $roomKey): array
    {
        return match ($roomKey) {
            'quest_board' => ['summary' => 'The board gathers one real-life commitment without turning your home into a backlog.', 'primary_href' => '/quests', 'primary_label' => 'Open Quests', 'panel_label' => 'Quest Board', 'panel_title' => 'A place for chosen action.', 'panel_body' => 'Quests owns the work. Healing Home only keeps it visible.', 'ownership' => 'Quests owns titles, steps, recurrence, progress, and completion. Healing Home only links to that source.'],
            'journal_table' => ['summary' => 'The table holds reflections and memories you intentionally preserved.', 'primary_href' => '/chronicle', 'primary_label' => 'Open Chronicle', 'panel_label' => 'Journal Table', 'panel_title' => 'A place for memory.', 'panel_body' => 'Chronicle owns saved entries and their privacy.', 'ownership' => 'Chronicle owns entries, provenance, archive, and deletion behavior. Healing Home only previews account-owned entries.'],
            'companion_chair' => ['summary' => 'The chair is a quiet place to ask for help without giving away authority.', 'primary_href' => '/companion', 'primary_label' => 'Visit Companion', 'panel_label' => 'Companion Chair', 'panel_title' => 'A place for thoughtful help.', 'panel_body' => 'The Companion proposes. You decide.', 'ownership' => 'Companion owns proposals and memory controls. Destination Districts execute approved consequential actions.'],
            'entry_hall' => ['summary' => 'The entry hall remembers who has met you here and lets you leave without penalty.', 'primary_href' => '/worlds/epic-ordinary/play', 'primary_label' => 'Continue story', 'panel_label' => 'Entry Hall', 'panel_title' => 'A place for arrival.', 'panel_body' => 'Return is orientation, not judgment.', 'ownership' => 'Relationship memory shown here is account-scoped Journey and World context. It is not a score or source-of-truth record about real life.'],
            'eastern_room' => ['summary' => 'The restored Eastern Room holds the refuge you chose with the Caretaker.', 'primary_href' => '/worlds/epic-ordinary/play', 'primary_label' => 'Continue Epic Ordinary', 'panel_label' => 'Eastern Room', 'panel_title' => 'A room with a chosen purpose.', 'panel_body' => 'Its meaning comes from Chapter Two World State, not from a real-life obligation.', 'ownership' => 'Epic Ordinary owns the chapter choice, fictional objective, relationship moment, and keepsake. Healing Home only shows the room after that account-owned World choice exists.'],
            'garden' => ['summary' => 'The Garden is a quiet place for tending what can grow back gently.', 'primary_href' => '/home/relationships/caretaker', 'primary_label' => 'Speak with the Caretaker', 'panel_label' => 'Garden', 'panel_title' => 'A place for tending.', 'panel_body' => 'The Garden opens from relationship continuity, not productivity.', 'ownership' => 'Healing Home owns the Garden presentation. The relationship conversation remains account-scoped Journey continuity, and Chronicle owns any reflection you choose to save.'],
            'workshop' => ['summary' => 'The Workshop holds making, repair, and unfinished ideas without turning them into a backlog.', 'primary_href' => '/chronicle/new?context=healing_home_journal_table&title=Workshop%20sketch&tags=healing-home,workshop', 'primary_label' => 'Sketch an idea', 'panel_label' => 'Workshop', 'panel_title' => 'A place for making.', 'panel_body' => 'Unfinished work can remain welcome here.', 'ownership' => 'Healing Home presents the Workshop. Chronicle owns any sketch you choose to save.'],
            'library' => ['summary' => 'The Library gathers explanations about why the house changed and what stayed private.', 'primary_href' => '/home/timeline', 'primary_label' => 'Open timeline', 'panel_label' => 'Library', 'panel_title' => 'A place for meaning.', 'panel_body' => 'Explanation belongs here without exposing private source records.', 'ownership' => 'Worlds, Quests, Chronicle, and Journey keep ownership. The Library only explains composed state.'],
            'guest_room' => ['summary' => 'The Guest Room is prepared for welcome without silently sharing anything.', 'primary_href' => '/home/privacy', 'primary_label' => 'Review boundaries', 'panel_label' => 'Guest Room', 'panel_title' => 'A place for welcome.', 'panel_body' => 'Connection waits for explicit consent.', 'ownership' => 'Healing Home does not publish, invite, or share from this room. Future sharing must remain explicit.'],
            default => ['summary' => 'This room gathers visible World and Journey context without taking ownership from source modules.', 'primary_href' => '/worlds/epic-ordinary/progress', 'primary_label' => 'Open World progress', 'panel_label' => 'Room', 'panel_title' => 'A place for continuity.', 'panel_body' => 'World changes remain explainable and fictional.', 'ownership' => 'Worlds own World State and reactions. Healing Home only composes permitted account-owned context.'],
        };
    }

    private function roomMapClass(array $room, bool $isCurrent): string
    {
        $state = (string) $room['state'];
        $classes = ['home-room', $state === 'open' ? 'open' : 'locked', 'home-room-' . (string) $room['room_key']];
        if ($isCurrent) {
            $classes[] = 'current-room';
        }
        if ((string) $room['room_key'] === 'eastern_room' && $state === 'open') {
            $classes[] = 'restored-room';
        }

        return implode(' ', $classes);
    }

    private function roomStatusLabel(array $room, bool $isCurrent): string
    {
        if ($isCurrent) {
            return 'Resting here';
        }
        if ((string) $room['state'] !== 'open') {
            return 'Door waiting';
        }
        if ((string) $room['room_key'] === 'eastern_room') {
            return 'Restored room open';
        }

        return 'Open room';
    }

    private function roomMapDescription(string $roomKey): string
    {
        return match ($roomKey) {
            'entry_hall' => 'Arrival, return, and relationship memory.',
            'fireplace' => 'World changes and what the house noticed.',
            'quest_board' => 'Chosen real-life commitments from Quests.',
            'journal_table' => 'Recent intentional memory from Chronicle.',
            'companion_chair' => 'Thoughtful help that waits for consent.',
            'library' => 'Meaning, explanations, and privacy boundaries.',
            'garden' => 'Tending, recovery, and small chosen care.',
            'workshop' => 'Making, repair, and unfinished ideas.',
            'guest_room' => 'Welcome and connection with consent.',
            'eastern_room' => 'Epic Ordinary refuge, opened by Chapter Two.',
            default => 'A visible part of the house.',
        };
    }

    private function roomSymbol(string $roomKey): string
    {
        return match ($roomKey) {
            'entry_hall' => '⌂',
            'fireplace' => '✦',
            'quest_board' => '☰',
            'journal_table' => '✎',
            'companion_chair' => '◔',
            'library' => '▤',
            'garden' => '❧',
            'workshop' => '⚒',
            'guest_room' => '◇',
            'eastern_room' => '☼',
            default => '•',
        };
    }

    private function atmosphereDescription(string $atmosphere): string
    {
        return match ($atmosphere) {
            'green_dusk' => 'Green dusk gathers around the Garden. The house feels tended, quiet, and a little more alive.',
            'workshop_lamplight' => 'Workshop lamplight is on. The house is making space for repair, sketches, and unfinished things.',
            'reclaimed_warmth' => 'Reclaimed warmth moves through the rooms. The Caretaker’s lantern, Quiet Hearth whispers, and remembered objects make return visible.',
            default => 'Quiet morning holds the rooms steady. Nothing urgent is waiting behind the door.',
        };
    }

    private function roomDoorState(string $roomKey, string $state): string
    {
        if ($state === 'open') {
            return match ($roomKey) {
                'library' => 'A shelf has opened.',
                'garden' => 'The gate is unlatched.',
                'workshop' => 'Lamplight is under the door.',
                'guest_room' => 'The bed is made, but no guest is assumed.',
                'eastern_room' => 'The nameplate has changed.',
                default => 'The door is open.',
            };
        }

        return match ($roomKey) {
            'library' => 'A quiet shelf waits for reviewed meaning.',
            'garden' => 'There is green behind the door.',
            'workshop' => 'A thin line of lamplight is not ready yet.',
            'guest_room' => 'A room is prepared, but not opened.',
            'eastern_room' => 'The door is waiting for Chapter Two.',
            default => 'The door is waiting.',
        };
    }

    private function housePulseLabel(string $atmosphere): string
    {
        return match ($atmosphere) {
            'green_dusk' => 'Tended and listening',
            'workshop_lamplight' => 'Lit for making',
            'reclaimed_warmth' => 'Reclaimed and welcoming',
            default => 'Quiet and steady',
        };
    }

    private function housePulseCopy(string $atmosphere): string
    {
        return match ($atmosphere) {
            'green_dusk' => 'The Garden has been touched recently, so the house carries a green dusk mood.',
            'workshop_lamplight' => 'The Workshop is awake; unfinished things are allowed to stay unfinished.',
            'reclaimed_warmth' => 'Epic Ordinary’s older warmth has been reclaimed as evidence objects, soft room life, and source-aware Moments.',
            default => 'The home is holding its shape without asking you to perform.',
        };
    }

    private function gardenGrowthState(array $changes): array
    {
        $seen = ['water'=>false,'clear_space'=>false,'rest'=>false,'repair'=>false];
        foreach ($changes as $change) {
            $key = (string) ($change['change_key'] ?? '');
            foreach ($seen as $choice => $_) {
                if ($key === 'garden_' . $choice) {
                    $seen[$choice] = true;
                }
            }
        }
        $count = count(array_filter($seen));

        return [
            'title' => $count > 0 ? 'Something here has been tended.' : 'A place for tending.',
            'copy' => $count > 0 ? 'The Garden remembers kinds of care, not streaks or points.' : 'The Garden opens after an honest Caretaker conversation. It is for recovery, repair, and small chosen care.',
            'watered' => $seen['water'] ? 'yes' : 'not yet',
            'cleared' => $seen['clear_space'] ? 'yes' : 'not yet',
            'rested' => $seen['rest'] ? 'yes' : 'not yet',
            'repaired' => $seen['repair'] ? 'yes' : 'not yet',
        ];
    }

    private function easternPurposeTitle(array $data): string
    {
        $text = $this->easternPurposeText($data);
        if (str_contains($text, 'making')) return 'A room for making.';
        if (str_contains($text, 'welcome')) return 'A room for welcome.';
        if (str_contains($text, 'repair')) return 'A room for repair.';
        if (str_contains($text, 'rest')) return 'A room for rest.';

        return 'A room with a chosen purpose.';
    }

    private function easternPurposeCopy(array $data): string
    {
        $text = $this->easternPurposeText($data);
        if (str_contains($text, 'making')) return 'Tools, scraps, and lamplight answer the choice you made in Epic Ordinary.';
        if (str_contains($text, 'welcome')) return 'A guest chair waits here, but invitation still requires explicit consent.';
        if (str_contains($text, 'repair')) return 'The room keeps repair visible without pretending everything is fixed.';
        if (str_contains($text, 'rest')) return 'The room protects rest as a real purpose, not a reward for productivity.';

        return 'Its shape comes from the Chapter Two refuge choice, and the World remains the source owner.';
    }

    private function easternPurposeText(array $data): string
    {
        $combined = '';
        foreach ($data['changes'] ?? [] as $change) {
            $combined .= ' ' . mb_strtolower((string) ($change['description'] ?? ''));
        }

        return $combined;
    }

    private function roomWalkway(string $roomKey): string
    {
        $order = ['entry_hall','fireplace','quest_board','journal_table','companion_chair','library','garden','workshop','guest_room','eastern_room'];
        $index = array_search($roomKey, $order, true);
        if ($index === false) {
            return '';
        }
        $previous = $order[($index - 1 + count($order)) % count($order)];
        $next = $order[($index + 1) % count($order)];

        return '<nav class="room-walkway" aria-label="Move through the Healing Home"><a href="/home/rooms/' . self::e($previous) . '"><span>Previous room</span><strong>' . self::e(ucwords(str_replace('_', ' ', $previous))) . '</strong></a><a href="/home"><span>House map</span><strong>Return to the center</strong></a><a href="/home/rooms/' . self::e($next) . '"><span>Next room</span><strong>' . self::e(ucwords(str_replace('_', ' ', $next))) . '</strong></a></nav>';
    }

    private function roomPracticePanel(string $roomKey): string
    {
        $practice = match ($roomKey) {
            'fireplace' => ['Read the ember', 'Ask what changed, then check why it appeared.', '/home/timeline', 'Open timeline'],
            'library' => ['Name the source', 'Trace one explanation until the ownership boundary is clear.', '/home/privacy', 'Review privacy'],
            'garden' => ['Choose one kind of care', 'Water, clear, rest, or repair without making it a streak.', '/home/relationships/caretaker', 'Speak with the Caretaker'],
            'workshop' => ['Leave a seed unfinished', 'Pin one idea in the room note without promising to complete it.', '/chronicle/new?context=healing_home_journal_table&title=Workshop%20sketch&tags=healing-home,workshop', 'Preserve if ready'],
            'guest_room' => ['Check the boundary', 'Imagine welcome without sharing anything by default.', '/home/privacy', 'Review boundaries'],
            'eastern_room' => ['Remember the choice', 'Let the Chapter Two purpose shape the room without becoming a chore.', '/worlds/epic-ordinary/play', 'Continue Epic Ordinary'],
            'journal_table' => ['Preserve deliberately', 'Only Chronicle owns what you choose to save.', '/chronicle/new?context=healing_home_journal_table&title=Journal%20Table%20reflection&tags=healing-home,journal-table', 'Start reflection'],
            'quest_board' => ['Choose one real step', 'Quests owns commitments; the house only makes one visible.', '/quests', 'Open Quests'],
            default => ['Pause at the threshold', 'Notice what this room holds before choosing a next action.', '/home/guide', 'Open guide'],
        };

        return '<section class="room-practice-panel" aria-labelledby="room-practice-title"><p class="eyebrow">Room practice</p><h2 id="room-practice-title">' . self::e($practice[0]) . '</h2><p>' . self::e($practice[1]) . '</p><a class="button secondary" href="' . self::e($practice[2]) . '">' . self::e($practice[3]) . '</a></section>';
    }

    private function roomInvitationPanel(string $roomKey): string
    {
        $invitation = match ($roomKey) {
            'fireplace' => ['If this echo matters, trace it.', 'Move from reaction to source thread to privacy boundary.', '/home/sources'],
            'library' => ['If the meaning feels blurry, shelve it.', 'The Library can hold explanations without demanding immediate action.', '/home/thresholds'],
            'garden' => ['If repair feels too large, tend one small edge.', 'A small Garden action can be complete without becoming a streak.', '/home/today'],
            'workshop' => ['If an idea keeps knocking, give it a bench.', 'A room note can hold an unfinished seed until you decide whether Chronicle should preserve it.', '/home/rooms/workshop'],
            'guest_room' => ['If welcome is the question, start with boundaries.', 'No invitation, excerpt, or sharing exists until future explicit approval.', '/home/privacy'],
            'eastern_room' => ['If the purpose changed you, revisit the source.', 'Epic Ordinary owns the choice; Healing Home keeps the doorway visible.', '/worlds/epic-ordinary/play'],
            default => ['If you are not sure what to do here, pause.', 'The room can simply orient you; opening it creates no obligation.', '/home/guide'],
        };

        return '<section class="room-invitation-panel"><p class="eyebrow">Room invitation</p><h2>' . self::e($invitation[0]) . '</h2><p>' . self::e($invitation[1]) . '</p><a class="button secondary" href="' . self::e($invitation[2]) . '">Follow gently</a></section>';
    }

    private function renderRelationship(array $relationship): string
    {
        $memories = '';
        foreach ($relationship['memories'] as $memory) {
            $memories .= '<li><p class="eyebrow">' . self::e(ucwords(str_replace('_', ' ', (string) $memory['memory_kind']))) . '</p><p>' . self::e((string) $memory['summary']) . '</p><small>' . self::e((string) $memory['created_at']) . ' UTC</small></li>';
        }
        $conversations = '';
        foreach ($relationship['conversations'] ?? [] as $conversation) {
            $context = $conversation['remembered_context']
                ? '<p class="meta">Remembered context: ' . self::e((string) $conversation['remembered_context']) . '</p>'
                : '';
            $conversations .= '<li><p class="eyebrow">' . self::e(ucwords(str_replace('_', ' ', (string) $conversation['player_choice']))) . '</p><p>' . self::e((string) $conversation['character_response']) . '</p>' . $context . '<small>' . self::e((string) $conversation['created_at']) . ' UTC</small></li>';
        }
        $followUp = count($relationship['conversations'] ?? []) > 0
            ? '<p class="meta">Follow-up: the Caretaker can hold this shared history without turning it into a score, streak, or hidden affection meter.</p>'
            : '';
        $conversationPanel = (string) $relationship['character_key'] === 'caretaker'
            ? '<section class="relationship-conversation-panel" aria-labelledby="caretaker-conversation-title"><div><p class="eyebrow">By the fire</p><h2 id="caretaker-conversation-title">Speak with the Caretaker</h2><p>Choose the kind of moment you need. There is no correct dialogue path, and this does not create a Quest, Chronicle entry, Companion memory, or World fact.</p>' . $followUp . '</div><form method="post" action="/home/relationships/caretaker/converse"><input type="hidden" name="csrf" value="' . self::e(Security::csrfToken()) . '"><div class="resolution-grid"><button class="button secondary" name="choice" value="gratitude" type="submit">Share gratitude</button><button class="button secondary" name="choice" value="repair" type="submit">Ask to repair</button><button class="button secondary" name="choice" value="disagree" type="submit">Disagree honestly</button><button class="button secondary" name="choice" value="quiet" type="submit">Sit quietly</button></div></form><div><h3>Recent conversations</h3>' . ($conversations ? '<ol class="relationship-memory-list">' . $conversations . '</ol>' : '<p>No conversations have been held here yet.</p>') . '</div></section>'
            : '';

        $body = $this->flashHtml() . '<section class="relationship-hero"><p class="eyebrow">Relationship - ' . self::e(ucfirst((string) $relationship['relationship_state'])) . '</p><h1>' . self::e((string) $relationship['character_name']) . '</h1><p>This history records what shaped the relationship. It is not a score, affection meter, or judgment.</p><a class="button secondary" href="/home">Return home</a></section>' . $conversationPanel . '<section class="relationship-memory-panel"><h2>Shared history</h2>' . ($memories ? '<ol class="relationship-memory-list">' . $memories . '</ol>' : '<p>No shared moments have been recorded yet.</p>') . '</section>';

        return $this->document((string) $relationship['character_name'], $body);
    }

    private function renderReclamation(array $reclamation): string
    {
        $changes = '';
        foreach ($reclamation['changes'] as $change) {
            $changes .= '<li><strong>' . self::e((string) $change['title']) . '</strong><span>' . self::e((string) $change['description']) . '</span><small>' . self::e(ucwords(str_replace('_', ' ', (string) $change['room_key']))) . '</small></li>';
        }
        $artifacts = '';
        foreach ($reclamation['keepsakes'] as $keepsake) {
            $artifacts .= '<li><strong>' . self::e((string) $keepsake['name']) . '</strong><span>' . self::e((string) $keepsake['meaning']) . '</span><a href="/home/keepsakes/' . self::e((string) $keepsake['id']) . '">Inspect artifact</a></li>';
        }
        $body = '<section class="relationship-hero epic-reclamation-hero"><p class="eyebrow">Epic Ordinary Reclamation Sprint</p><h1>The ordinary is allowed to glow again.</h1><p>Recovered from the legacy Epic-Ordinary canon: the Healing Home is the flagship place, objects are evidence rather than rewards, nothing important happens off-screen, and the Caretaker notices without taking authority.</p><p class="local-actions"><a class="button secondary" href="/home">Return home</a><a class="button secondary" href="/home/discoveries">Discoveries</a><a class="button secondary" href="/home/tiny-joys">Tiny joys</a><a class="button secondary" href="/home/seasons">Seasonal life</a><a class="button secondary" href="/home/moments">Moments remembered</a></p></section><section class="house-threshold-columns"><article><h2>Reclaimed visible changes</h2>' . ($changes ? '<ol class="room-memory-list">' . $changes . '</ol>' : '<p>The reclamation materializer is waiting for Epic Ordinary to be installed for this account.</p>') . '</article><article><h2>Recovered evidence objects</h2>' . ($artifacts ? '<ol class="room-memory-list">' . $artifacts . '</ol>' : '<p>No reclaimed objects are displayed yet.</p>') . '</article></section><section class="room-trust-panel"><p class="eyebrow">Boundaries</p><h2>Reclaimed does not mean copied authority.</h2><p>These are fictional World/Journey surfaces. They do not import legacy schemas, admin tools, reward economies, streaks, household assumptions, or private source payloads. Chronicle entries still require an explicit Chronicle action.</p><p class="local-actions"><a class="button" href="/chronicle/new?context=epic_ordinary_reclamation&title=Epic%20Ordinary%20reclamation%20reflection&tags=epic-ordinary,healing-home,reclamation">Reflect in Chronicle</a><a class="button secondary" href="/home/privacy">What the house knows</a></p></section>';

        return $this->document('Epic Ordinary Reclamation', $body);
    }

    private function renderDiscoveries(array $reclamation): string
    {
        $cards = '';
        foreach ($reclamation['discoveries'] as $discovery) {
            $cards .= '<article><h2>' . self::e((string) $discovery['title']) . '</h2><p>' . self::e((string) $discovery['body']) . '</p></article>';
        }
        $body = '<section class="relationship-hero"><p class="eyebrow">Exploration and discovery</p><h1>Recovered discoveries</h1><p>Discoveries are source-aware pieces of world identity, not checklist goals.</p><p class="local-actions"><a class="button secondary" href="/home/reclamation">Reclamation hearth</a><a class="button secondary" href="/home">Return home</a></p></section><section class="source-glossary-grid">' . $cards . '</section>';

        return $this->document('Recovered discoveries', $body);
    }

    private function renderTinyJoys(array $reclamation): string
    {
        $cards = '';
        foreach ($reclamation['tiny_joys'] as $joy) {
            $cards .= '<article><p class="eyebrow">' . self::e((string) $joy['room']) . '</p><h2>' . self::e((string) $joy['title']) . '</h2><p>' . self::e((string) $joy['body']) . '</p><a href="/chronicle/new?context=epic_ordinary_tiny_joy&title=' . rawurlencode((string) $joy['title']) . '&tags=epic-ordinary,tiny-joy">Remember only if you choose</a></article>';
        }
        $body = '<section class="relationship-hero"><p class="eyebrow">Tiny joys</p><h1>Small interactions, no score attached.</h1><p>Recovered from Epic-Ordinary Tiny Joys and ambient props: noticing something small is already enough. No points, no progression credit, no obligation.</p><p class="local-actions"><a class="button secondary" href="/home/reclamation">Reclamation hearth</a><a class="button secondary" href="/home">Return home</a></p></section><section class="house-invitations-grid">' . $cards . '</section>';

        return $this->document('Tiny joys', $body);
    }

    private function renderSeasons(array $reclamation): string
    {
        $cards = '';
        foreach ($reclamation['seasons'] as $season) {
            $cards .= '<article><p class="eyebrow">' . self::e((string) $season['room']) . '</p><h2>' . self::e((string) $season['season']) . '</h2><p>' . self::e((string) $season['meaning']) . '</p></article>';
        }
        $body = '<section class="relationship-hero"><p class="eyebrow">Seasonal and ambient life</p><h1>Time passes softly.</h1><p>Seasonal storytelling makes the house feel alive without turning weather, absence, or mood into diagnosis or punishment.</p><p class="local-actions"><a class="button secondary" href="/home/reclamation">Reclamation hearth</a><a class="button secondary" href="/home">Return home</a></p></section><section class="house-moods-grid">' . $cards . '</section>';

        return $this->document('Seasonal life', $body);
    }

    private function renderMomentsRemembered(array $reclamation): string
    {
        $memories = '';
        foreach ($reclamation['memories'] as $memory) {
            $memories .= '<li><strong>' . self::e(ucwords(str_replace('_', ' ', (string) $memory['memory_kind']))) . '</strong><span>' . self::e((string) $memory['summary']) . '</span><small>' . self::e((string) $memory['created_at']) . ' UTC</small></li>';
        }
        $body = '<section class="relationship-hero"><p class="eyebrow">Moments remembered</p><h1>Nothing important happens off-screen.</h1><p>This page reclaims the legacy Moments Remembered idea as a replay-safe library of visible house continuity. It now hands off to the Moment Engine Foundation for queued arrival scenes, remembered moments, and Chronicle preservation review.</p><p class="local-actions"><a class="button" href="/moments">Open Moment Engine</a><a class="button secondary" href="/moments/next">Next arrival Moment</a><a class="button secondary" href="/moments/remembered">Remembered Moments</a><a class="button secondary" href="/home/reclamation">Reclamation hearth</a><a class="button secondary" href="/chronicle">Open Chronicle</a></p></section><section class="room-detail-panel"><h2>Remembered Caretaker continuity</h2>' . ($memories ? '<ol class="room-memory-list">' . $memories . '</ol>' : '<p>No reclaimed memories are available yet.</p>') . '</section><section class="room-trust-panel"><h2>Chronicle integration</h2><p>Meaningful Moments can be preserved in Chronicle, but Koravik-Final keeps that as an explicit player action.</p><a class="button" href="/chronicle/new?context=epic_ordinary_moment&title=Moment%20remembered&tags=epic-ordinary,moment">Preserve a Moment</a></section>';

        return $this->document('Moments remembered', $body);
    }

    private function renderKeepsakes(array $keepsakes): string
    {
        $items = '';
        foreach ($keepsakes as $keepsake) {
            $items .= '<li><a href="/home/keepsakes/' . self::e((string) $keepsake['id']) . '"><strong>' . self::e((string) $keepsake['name']) . '</strong><span>' . self::e((string) $keepsake['meaning']) . '</span><small>' . self::e($this->sourceLabel((string) $keepsake['source_type'])) . ' · ' . self::e(ucwords(str_replace('_', ' ', (string) $keepsake['room_key']))) . '</small></a></li>';
        }

        $body = '<section class="relationship-hero keepsake-shelf-hero"><p class="eyebrow">Healing Home</p><h1>Keepsake Shelf</h1><p>Fictional keepsakes and reflected home tokens live here with their source and room provenance. They are not currency, trophies, or proof that you performed correctly.</p><a class="button secondary" href="/home">Return home</a></section><section class="relationship-memory-panel keepsake-shelf-panel"><h2>Displayed keepsakes</h2>' . ($items ? '<ul class="keepsake-detail-list">' . $items . '</ul>' : '<p>No keepsakes are displayed yet. They arrive through meaningful story and reflection, not grinding.</p>') . '</section>';

        return $this->document('Keepsake Shelf', $body);
    }

    private function renderKeepsake(array $keepsake): string
    {
        $body = '<section class="relationship-hero keepsake-shelf-hero"><p class="eyebrow">Keepsake · ' . self::e($this->sourceLabel((string) $keepsake['source_type'])) . '</p><h1>' . self::e((string) $keepsake['name']) . '</h1><p>' . self::e((string) $keepsake['meaning']) . '</p><div class="hero-actions"><a class="button secondary" href="/home/keepsakes">Return to shelf</a><a class="button" href="/home/rooms/' . self::e((string) $keepsake['room_key']) . '">Open room</a><form method="post" action="/home/keepsakes/' . self::e((string) $keepsake['id']) . '/moment"><input type="hidden" name="csrf" value="' . self::e(Security::csrfToken()) . '"><button class="button secondary" type="submit">Prepare as memory Moment</button></form></div></section><section class="room-trust-panel"><p class="eyebrow">Provenance</p><h2>Where this came from</h2><dl class="reaction-explain-list"><div><dt>Source owner</dt><dd>' . self::e($this->sourceLabel((string) $keepsake['source_type'])) . '</dd></div><div><dt>Room</dt><dd>' . self::e(ucwords(str_replace('_', ' ', (string) $keepsake['room_key']))) . '</dd></div><div><dt>Created</dt><dd>' . self::e((string) $keepsake['created_at']) . ' UTC</dd></div><div><dt>Moment interaction</dt><dd>Prepare as memory Moment creates Moment Engine presentation state only. Chronicle still requires explicit review.</dd></div><div><dt>Boundary</dt><dd>Healing Home displays this keepsake. It does not create a Quest, Chronicle entry, Companion memory, or real-life achievement score.</dd></div></dl></section>';

        return $this->document((string) $keepsake['name'], $body);
    }

    private function renderTimeline(array $items): string
    {
        $rows = '';
        foreach ($items as $item) {
            $rows .= '<li><p class="eyebrow">' . self::e(ucwords((string)$item['item_type'])) . ' · ' . self::e(ucwords(str_replace('_', ' ', (string)$item['room_key']))) . '</p><h2>' . self::e((string)$item['title']) . '</h2><p>' . self::e((string)$item['description']) . '</p><small>' . self::e((string)$item['created_at']) . ' UTC</small></li>';
        }

        $body = '<section class="relationship-hero"><p class="eyebrow">Healing Home</p><h1>Room timeline</h1><p>A quiet history of room changes, keepsakes, and conversations. It is not a noisy activity feed or a scorecard.</p><a class="button secondary" href="/home">Return home</a></section><section class="relationship-memory-panel keepsake-shelf-panel"><h2>What the house has held</h2>' . ($rows ? '<ol class="relationship-memory-list">' . $rows . '</ol>' : '<p>No room history has gathered yet.</p>') . '</section>';

        return $this->document('Room timeline', $body);
    }

    private function renderHomePrivacy(): string
    {
        $body = '<section class="relationship-hero"><p class="eyebrow">Healing Home</p><h1>What the house knows</h1><p>Healing Home composes account-owned context so the house can feel continuous. It does not become the owner of your real-life records.</p><a class="button secondary" href="/home">Return home</a></section><section class="room-trust-panel"><h2>Composed sources</h2><dl class="reaction-explain-list"><div><dt>Quests</dt><dd>Active Quest title, purpose, next step, and resolved Quest summaries. Quests still owns commitments and completion.</dd></div><div><dt>Chronicle</dt><dd>Recent entries may appear at the Journal Table. Chronicle owns saved reflections, archive, deletion, and privacy.</dd></div><div><dt>Worlds</dt><dd>Epic Ordinary reactions, choices, and keepsakes may shape rooms. Worlds own fictional state and explanations.</dd></div><div><dt>Journey relationship</dt><dd>Caretaker continuity and bounded conversations may open rooms. It is not affection scoring.</dd></div></dl></section><section class="room-trust-panel"><h2>Deliberately not accessed</h2><p>Healing Home does not read Quest notes, Chronicle prose beyond previews you already own, Companion memory, Health records, Beacon attendance, Gather communication, account secrets, or other accounts’ data for hidden scoring.</p><p class="local-actions"><a class="button" href="/privacy">Open Privacy</a><a class="button secondary" href="/audit">Open Audit</a><a class="button secondary" href="/settings/data">Data controls</a></p></section>';

        return $this->document('What the house knows', $body);
    }

    private function renderHomeGuide(): string
    {
        $body = '<section class="relationship-hero home-guide-hero"><p class="eyebrow">Healing Home guide</p><h1>How to move through the house</h1><p>This guide explains the house as a place of orientation. It does not assign tasks, diagnose you, or decide what matters.</p><p class="local-actions"><a class="button secondary" href="/home">Return home</a><a class="button secondary" href="/home/today">Today in the house</a><a class="button secondary" href="/home/rooms">Room directory</a><a class="button secondary" href="/home/sources">Source glossary</a></p></section><section class="home-guide-grid" aria-label="Healing Home guide routes"><article><h2>When you want meaning</h2><p>Start at the Fireplace, then move to the Library, then open a source thread if you want provenance.</p><a href="/home/rooms/fireplace">Start with the Fireplace</a></article><article><h2>When you want making</h2><p>Start at the Eastern Room or Workshop. Keep a seed in a private room note until you choose Chronicle.</p><a href="/home/rooms/workshop">Start with the Workshop</a></article><article><h2>When you want recovery</h2><p>Start with the Caretaker or Garden. Small care is allowed to remain small.</p><a href="/home/rooms/garden">Start with the Garden</a></article><article><h2>When you want boundaries</h2><p>Start in the Guest Room or privacy panel. Sharing remains future explicit consent.</p><a href="/home/privacy">Review boundaries</a></article></section><section class="home-threshold-panel"><h2>Threshold reminders</h2><ul><li>Opening a page is not a commitment.</li><li>Saving a room note stays inside Healing Home.</li><li>Preserving to Chronicle is always a separate choice.</li></ul></section>';

        return $this->document('Healing Home guide', $body);
    }

    private function renderHomeToday(array $journey): string
    {
        $current = ucwords(str_replace('_', ' ', (string) ($journey['state']['current_room'] ?? 'entry_hall')));
        $atmosphere = (string) ($journey['state']['atmosphere'] ?? 'quiet_morning');
        $change = $journey['changes'][0] ?? null;
        $body = '<section class="relationship-hero home-today-hero"><p class="eyebrow">Healing Home</p><h1>Today in the house</h1><p>A one-page read of the home as it is right now. This is orientation, not a score.</p><a class="button secondary" href="/home">Return home</a></section><section class="home-today-grid"><article><p class="eyebrow">Current room</p><h2>' . self::e($current) . '</h2><p>The room holding your place remains available without asking you to prove anything.</p></article><article><p class="eyebrow">Atmosphere</p><h2>' . self::e($this->housePulseLabel($atmosphere)) . '</h2><p>' . self::e($this->housePulseCopy($atmosphere)) . '</p></article><article><p class="eyebrow">Latest threshold</p><h2>' . self::e($change ? (string) $change['title'] : 'No new threshold') . '</h2><p>' . self::e($change ? (string) $change['description'] : 'The house can simply be quiet today.') . '</p></article></section><section class="home-threshold-panel"><h2>Suggested gentle route</h2><p>Start with the room that matches your actual intent: Fireplace for meaning, Workshop for making, Garden for recovery, or Guest Room for boundaries.</p><p class="local-actions"><a class="button" href="/home/guide">Open guide</a><a class="button secondary" href="/home/rooms">Browse rooms</a></p></section>';

        return $this->document('Today in the house', $body);
    }

    private function renderRoomDirectory(array $journey): string
    {
        $items = '';
        foreach ($journey['rooms'] as $room) {
            $key = (string) $room['room_key'];
            $items .= '<li><a href="/home/rooms/' . self::e($key) . '"><span class="room-symbol" aria-hidden="true">' . self::e($this->roomSymbol($key)) . '</span><strong>' . self::e((string) $room['name']) . '</strong><span>' . self::e($this->roomStatusLabel($room, (string) ($journey['state']['current_room'] ?? 'entry_hall') === $key)) . '</span><small>' . self::e($this->roomDoorState($key, (string) $room['state'])) . ' ' . self::e($this->roomMapDescription($key)) . '</small></a></li>';
        }
        $body = '<section class="relationship-hero room-directory-hero"><p class="eyebrow">Healing Home</p><h1>Room directory</h1><p>All known rooms in one place, with their state and Source-aware purpose.</p><a class="button secondary" href="/home">Return home</a></section><section class="room-directory-panel"><ul>' . $items . '</ul></section>';

        return $this->document('Room directory', $body);
    }

    private function renderSourceGlossary(): string
    {
        $body = '<section class="relationship-hero source-glossary-hero healing-home-source-matrix"><p class="eyebrow">Healing Home source matrix</p><h1>Source glossary</h1><p>What the house can reference, who owns it, and what stays private.</p><a class="button secondary" href="/home">Return home</a></section><section class="source-glossary-grid"><article><h2>Quests</h2><p>Owns commitments, steps, recurrence, and completion. Healing Home may show a title or next step.</p></article><article><h2>Chronicle</h2><p>Owns saved reflections. Healing Home can suggest a starting context, but does not save prose for you.</p></article><article><h2>Worlds</h2><p>Owns fictional reactions, choices, and keepsakes. The house displays explainable fictional continuity.</p></article><article><h2>Health</h2><p>Owns check-ins, notes, revision history, and sharing consent. Health Garden is a doorway, not a diagnostic engine.</p></article><article><h2>Gather</h2><p>Owns events, RSVPs, signups, attendance, communication preferences, and follow-up truth. Gather Table does not publish or invite.</p></article><article><h2>Journey relationships</h2><p>Owns Caretaker continuity and conversations. It is not affection scoring.</p></article><article><h2>Companion</h2><p>May use Healing Home room notes only when the dedicated room-note permission is enabled.</p></article><article><h2>Deliberately excluded</h2><p>Health notes, Gather communication, Beacon attendance, account secrets, and other accounts’ data are not used for hidden scoring.</p></article></section>';

        return $this->document('Source glossary', $body);
    }

    private function renderHouseInvitations(array $journey): string
    {
        $cards = '';
        foreach (['fireplace','library','garden','workshop','guest_room','eastern_room'] as $roomKey) {
            $cards .= '<article><span class="room-symbol" aria-hidden="true">' . self::e($this->roomSymbol($roomKey)) . '</span><h2>' . self::e(ucwords(str_replace('_', ' ', $roomKey))) . '</h2><p>' . self::e($this->roomMapDescription($roomKey)) . '</p><a href="/home/rooms/' . self::e($roomKey) . '">Open invitation</a></article>';
        }
        $body = '<section class="relationship-hero house-invitations-hero"><p class="eyebrow">Living house</p><h1>House invitations</h1><p>Invitations are gentle doorways into existing rooms. They do not assign tasks or create records.</p><a class="button secondary" href="/home">Return home</a></section><section class="house-invitations-grid">' . $cards . '</section>';

        return $this->document('House invitations', $body);
    }

    private function renderHouseThresholds(array $journey): string
    {
        $open = '';
        $waiting = '';
        foreach ($journey['rooms'] as $room) {
            $key = (string) $room['room_key'];
            $item = '<li><strong>' . self::e((string) $room['name']) . '</strong><span>' . self::e($this->roomDoorState($key, (string) $room['state'])) . '</span><a href="/home/rooms/' . self::e($key) . '">Open doorway</a></li>';
            if ((string) $room['state'] === 'open') {
                $open .= $item;
            } else {
                $waiting .= $item;
            }
        }
        $body = '<section class="relationship-hero house-thresholds-hero"><p class="eyebrow">Living house</p><h1>Thresholds</h1><p>A threshold is a visible doorway and its source boundary. Open doors invite movement; waiting doors explain what they are waiting for.</p><a class="button secondary" href="/home">Return home</a></section><section class="house-threshold-columns"><article><h2>Open thresholds</h2><ol>' . $open . '</ol></article><article><h2>Waiting thresholds</h2>' . ($waiting ? '<ol>' . $waiting . '</ol>' : '<p>Every known doorway is open right now.</p>') . '</article></section>';

        return $this->document('Thresholds', $body);
    }

    private function renderHouseAtlas(): string
    {
        $body = '<section class="relationship-hero house-atlas-hero"><p class="eyebrow">Living house</p><h1>House atlas</h1><p>An atlas of the Healing Home as a source-aware place.</p><a class="button secondary" href="/home">Return home</a></section><section class="house-atlas-map"><article><h2>North: Meaning</h2><p>Fireplace, Library, source threads, and privacy boundaries.</p></article><article><h2>East: Story</h2><p>Eastern Room, Epic Ordinary, World choices, and keepsakes.</p></article><article><h2>South: Care</h2><p>Garden, Caretaker, room practices, and quiet recovery.</p></article><article><h2>West: Making</h2><p>Workshop, room notes, Journal Table, and optional Chronicle preservation.</p></article><article><h2>Center: Return</h2><p>Entry Hall, Today in the House, House Pulse, and room directory.</p></article></section>';

        return $this->document('House atlas', $body);
    }

    private function renderRoomLore(): string
    {
        $cards = '';
        foreach (['entry_hall','fireplace','library','garden','workshop','guest_room','eastern_room','journal_table','quest_board','companion_chair'] as $roomKey) {
            $cards .= '<article><span class="room-symbol" aria-hidden="true">' . self::e($this->roomSymbol($roomKey)) . '</span><h2>' . self::e(ucwords(str_replace('_', ' ', $roomKey))) . '</h2><p>' . self::e($this->roomLore($roomKey)) . '</p><a href="/home/rooms/' . self::e($roomKey) . '">Visit room</a></article>';
        }
        $body = '<section class="relationship-hero room-lore-hero"><p class="eyebrow">Healing Home deepening</p><h1>Room lore</h1><p>Lore gives each room a stronger identity without changing source ownership or inventing requirements.</p><a class="button secondary" href="/home">Return home</a></section><section class="room-lore-grid">' . $cards . '</section>';

        return $this->document('Room lore', $body);
    }

    private function renderHouseConstellations(): string
    {
        $body = '<section class="relationship-hero house-constellations-hero"><p class="eyebrow">Healing Home deepening</p><h1>House constellations</h1><p>Constellations group rooms by meaning so the house feels connected without creating new state.</p><a class="button secondary" href="/home">Return home</a></section><section class="house-constellation-grid"><article><h2>Meaning constellation</h2><p>Fireplace, Library, Timeline, Source threads, and Source Glossary.</p><p><a href="/home/rooms/library">Open Library</a></p></article><article><h2>Making constellation</h2><p>Eastern Room, Workshop, Journal Table, private room note, optional Chronicle preservation.</p><p><a href="/home/rooms/workshop">Open Workshop</a></p></article><article><h2>Care constellation</h2><p>Caretaker, Garden, Room Practice, Today in the House, and gentle thresholds.</p><p><a href="/home/rooms/garden">Open Garden</a></p></article><article><h2>Welcome constellation</h2><p>Guest Room, Boundary Ledger, Privacy, and future explicit consent.</p><p><a href="/home/rooms/guest_room">Open Guest Room</a></p></article></section>';

        return $this->document('House constellations', $body);
    }

    private function renderBoundaryLedger(): string
    {
        $body = '<section class="relationship-hero boundary-ledger-hero"><p class="eyebrow">Healing Home deepening</p><h1>Boundary ledger</h1><p>A plain-language ledger of what Healing Home may show, what it may suggest, and what it must not touch.</p><a class="button secondary" href="/home">Return home</a></section><section class="boundary-ledger-grid"><article><h2>May show</h2><p>Room state, room notes you saved here, displayed keepsakes, reviewed World reactions, and minimized continuity.</p></article><article><h2>May suggest</h2><p>Navigation, room practices, reflection starting context, and source-aware explanations.</p></article><article><h2>Must ask first</h2><p>Saving to Chronicle, acting in Quests, using Companion proposals, sharing anything from Guest Room, or changing real-world records.</p></article><article><h2>Must not touch</h2><p>Health records, account secrets, other accounts’ data, hidden diagnosis, hidden scoring, Beacon attendance, and Gather communications.</p></article></section>';

        return $this->document('Boundary ledger', $body);
    }

    private function renderHouseWayfinding(): string
    {
        $body = '<section class="relationship-hero house-wayfinding-hero"><p class="eyebrow">Healing Home deepening</p><h1>Wayfinding</h1><p>Choose a doorway based on what you are trying to understand, not on what the system wants from you.</p><a class="button secondary" href="/home">Return home</a></section><section class="house-wayfinding-grid"><article><h2>I want to understand why something appeared.</h2><p>Go to Fireplace, then Library, then Source Thread.</p><a href="/home/rooms/fireplace">Start with Fireplace</a></article><article><h2>I want to make something but keep it soft.</h2><p>Go to Workshop. Use a room note before choosing Chronicle.</p><a href="/home/rooms/workshop">Start with Workshop</a></article><article><h2>I want to rest or repair.</h2><p>Go to Garden or speak with the Caretaker.</p><a href="/home/rooms/garden">Start with Garden</a></article><article><h2>I want to know what is safe.</h2><p>Go to Boundary Ledger, Source Glossary, or Privacy.</p><a href="/home/boundaries">Start with boundaries</a></article></section>';

        return $this->document('Wayfinding', $body);
    }

    private function renderHouseCompass(): string
    {
        $body = '<section class="relationship-hero house-compass-hero"><p class="eyebrow">Healing Home compass</p><h1>Choose by direction, not pressure</h1><p>The compass gives you another way to enter the same house: meaning, story, care, making, welcome, or trust.</p><a class="button secondary" href="/home">Return home</a></section><section class="house-compass-grid"><article><h2>North / Meaning</h2><p>Fireplace, Library, Source Threads, Source Glossary.</p><a href="/home/rooms/library">Open meaning</a></article><article><h2>East / Story</h2><p>Eastern Room, Epic Ordinary, Keepsake Shelf.</p><a href="/home/rooms/eastern_room">Open story</a></article><article><h2>South / Care</h2><p>Garden, Caretaker, Today in the House.</p><a href="/home/rooms/garden">Open care</a></article><article><h2>West / Making</h2><p>Workshop, Journal Table, private room notes.</p><a href="/home/rooms/workshop">Open making</a></article><article><h2>Threshold / Trust</h2><p>Boundary Ledger, Consent Map, Privacy.</p><a href="/home/consent-map">Open trust</a></article></section>';

        return $this->document('House compass', $body);
    }

    private function renderHouseMoods(): string
    {
        $body = '<section class="relationship-hero house-moods-hero"><p class="eyebrow">Healing Home compass</p><h1>Moods of the house</h1><p>Moods are presentation language. They are not diagnosis, scoring, or emotional surveillance.</p><a class="button secondary" href="/home">Return home</a></section><section class="house-moods-grid"><article><h2>Quiet morning</h2><p>The default mood: the house is steady and asks nothing.</p></article><article><h2>Green dusk</h2><p>The Garden has recently been tended. Care is visible, not scored.</p></article><article><h2>Workshop lamplight</h2><p>The Workshop is awake for making, repair, and unfinished ideas.</p></article><article><h2>Future moods</h2><p>New moods must remain explainable and tied to visible account-owned state.</p></article></section>';

        return $this->document('Moods of the house', $body);
    }

    private function renderRoomsByNeed(): string
    {
        $body = '<section class="relationship-hero rooms-by-need-hero"><p class="eyebrow">Healing Home compass</p><h1>Rooms by need</h1><p>Start with what you need, then choose the room. No path is required.</p><a class="button secondary" href="/home">Return home</a></section><section class="rooms-by-need-grid"><article><h2>I need clarity</h2><p>Library, Source Glossary, Boundary Ledger.</p><a href="/home/rooms/library">Open Library</a></article><article><h2>I need continuity</h2><p>Today in the House, Timeline, Entry Hall.</p><a href="/home/today">Open Today</a></article><article><h2>I need repair</h2><p>Garden, Caretaker, Eastern Room.</p><a href="/home/rooms/garden">Open Garden</a></article><article><h2>I need expression</h2><p>Workshop, Journal Table, Chronicle only if chosen.</p><a href="/home/rooms/workshop">Open Workshop</a></article><article><h2>I need safety</h2><p>Consent Map, Guest Room, Privacy.</p><a href="/home/consent-map">Open Consent Map</a></article></section>';

        return $this->document('Rooms by need', $body);
    }

    private function renderConsentMap(): string
    {
        $body = '<section class="relationship-hero consent-map-hero"><p class="eyebrow">Healing Home compass</p><h1>Consent map</h1><p>A map of what can happen only after explicit approval.</p><a class="button secondary" href="/home">Return home</a></section><section class="consent-map-grid"><article><h2>Saving</h2><p>Chronicle entries are saved only when you submit them in Chronicle.</p></article><article><h2>Acting</h2><p>Quest actions remain in Quests and require their own forms and validation.</p></article><article><h2>Sharing</h2><p>Guest Room shares nothing now. Future sharing requires a clear review and approval flow.</p></article><article><h2>Companion</h2><p>Companion may propose. Consequential action still requires approval and owner execution.</p></article><article><h2>Excluded</h2><p>Health, Gather, Beacon, account secrets, and other accounts are not silently pulled into Healing Home.</p></article></section>';

        return $this->document('Consent map', $body);
    }

    private function renderHouseChangelog(): string
    {
        $body = '<section class="relationship-hero house-changelog-hero"><p class="eyebrow">Healing Home compass</p><h1>House changelog</h1><p>A human-readable guide to the house surfaces that now exist. This is documentation inside the product, not a developer log.</p><a class="button secondary" href="/home">Return home</a></section><section class="house-changelog-list"><article><h2>Foundation</h2><p>Home overview, room pages, rest state, room notes, and source ownership.</p></article><article><h2>Intrigue</h2><p>Atmosphere, room symbols, door states, room practices, invitations, and source threads.</p></article><article><h2>Presence</h2><p>Today in the House, Room Directory, Source Glossary, and guide thresholds.</p></article><article><h2>Living House</h2><p>Invitations, Thresholds, House Atlas, Room Lore, Constellations, Boundary Ledger, and Wayfinding.</p></article><article><h2>Compass</h2><p>Compass, moods, rooms by need, consent map, and this changelog.</p></article></section>';

        return $this->document('House changelog', $body);
    }

    private function roomLore(string $roomKey): string
    {
        return match ($roomKey) {
            'entry_hall' => 'The place of arrival. It keeps return gentle and refuses guilt as a navigation system.',
            'fireplace' => 'The interpretive hearth. It shows explainable World echoes without exposing private source material.',
            'library' => 'The shelves of meaning. It groups explanations, sources, and privacy boundaries.',
            'garden' => 'The place of small care. It remembers tending without points, streaks, or proof.',
            'workshop' => 'The bench for unfinished things. Ideas can remain seeds until you choose to preserve them.',
            'guest_room' => 'The room of welcome with consent. It prepares hospitality without silently sharing.',
            'eastern_room' => 'The restored refuge. Its purpose comes from Epic Ordinary and stays fictional.',
            'journal_table' => 'The threshold to Chronicle. Reflection begins here only when you choose to save it.',
            'quest_board' => 'The visible edge of chosen action. Quests owns the work; the house only points.',
            'companion_chair' => 'The seat for thoughtful help. Companion may propose, but you remain the actor.',
            default => 'A known room in the house.',
        };
    }

    private function renderSourceThread(array $thread): string
    {
        $body = '<section class="relationship-hero source-thread-hero"><p class="eyebrow">Healing Home source thread</p><h1>' . self::e((string) $thread['title']) . '</h1><p>' . self::e((string) $thread['description']) . '</p><div class="hero-actions"><a class="button secondary" href="/home/timeline">Return to timeline</a><a class="button" href="/home/rooms/' . self::e((string) $thread['room_key']) . '">Open room</a></div></section><section class="room-trust-panel source-thread-panel"><h2>Where this came from</h2><dl class="reaction-explain-list"><div><dt>Source owner</dt><dd>' . self::e($this->sourceLabel((string) $thread['source_type'])) . '</dd></div><div><dt>Source key</dt><dd>' . self::e((string) ($thread['source_key'] ?? 'direct room continuity')) . '</dd></div><div><dt>Room affected</dt><dd>' . self::e(ucwords(str_replace('_', ' ', (string) $thread['room_key']))) . '</dd></div><div><dt>Created</dt><dd>' . self::e((string) $thread['created_at']) . ' UTC</dd></div><div><dt>What stayed private</dt><dd>Quest notes, Chronicle prose, Companion memory, Health records, Gather communication, Beacon attendance, account secrets, and other accounts’ data were not pulled into this thread.</dd></div></dl></section><section class="source-thread-actions"><h2>Follow the thread</h2><p>Use these links to move without losing the provenance boundary.</p><p class="local-actions"><a class="button" href="/home/rooms/' . self::e((string) $thread['room_key']) . '">Return to affected room</a><a class="button secondary" href="/home/privacy">Review privacy boundary</a><a class="button secondary" href="/home/guide">Open house guide</a></p></section>';

        return $this->document('Source thread', $body);
    }

    private function sourceLabel(string $sourceType): string
    {
        return match ($sourceType) {
            'world_choice' => 'Epic Ordinary World choice',
            'world_reaction' => 'Epic Ordinary World reaction',
            'quest_resolution' => 'Quest resolution',
            default => ucwords(str_replace('_', ' ', $sourceType)),
        };
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

    private function verifyRelationshipCsrf(string $characterKey): bool
    {
        if (Security::verifyCsrf((string) ($_POST['csrf'] ?? ''))) {
            return true;
        }

        http_response_code(419);
        echo $this->document(
            'Session changed',
            '<section class="panel"><h1>Your session changed.</h1><p>Please return to the relationship and try again.</p><a class="button" href="/home/relationships/' . self::e($characterKey) . '">Return to relationship</a></section>'
        );

        return false;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
