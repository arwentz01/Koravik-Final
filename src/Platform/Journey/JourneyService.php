<?php

declare(strict_types=1);

namespace Koravik\Platform\Journey;

use Koravik\Platform\Database\Database;
use PDO;

final class JourneyService
{
    public function __construct(private readonly Database $database) {}

    public function homeForAccount(string $accountId): array
    {
        $this->ensureHome($accountId);
        $this->materializeJourney($accountId);
        $pdo = $this->database->pdo();

        $home = $pdo->prepare('SELECT atmosphere, current_room, last_returned_at FROM healing_home_state WHERE account_id = :account_id LIMIT 1');
        $home->execute(['account_id' => $accountId]);
        $rooms = $pdo->prepare('SELECT room_key, name, state, sort_order FROM healing_home_rooms WHERE account_id = :account_id ORDER BY sort_order, name');
        $rooms->execute(['account_id' => $accountId]);
        $quest = $pdo->prepare('SELECT id, title, purpose, next_step, quest_type, origin_type FROM quests WHERE account_id = :account_id AND lifecycle_status = "active" ORDER BY updated_at DESC, created_at DESC LIMIT 1');
        $quest->execute(['account_id' => $accountId]);
        $chronicle = $pdo->prepare('SELECT title, body, created_at FROM chronicle_entries WHERE account_id = :account_id AND archived_at IS NULL ORDER BY created_at DESC LIMIT 1');
        $chronicle->execute(['account_id' => $accountId]);
        $changes = $pdo->prepare('SELECT title, description, room_key, created_at FROM healing_home_changes WHERE account_id = :account_id ORDER BY created_at DESC LIMIT 3');
        $changes->execute(['account_id' => $accountId]);
        $keepsakes = $pdo->prepare('SELECT name, meaning, room_key, created_at FROM healing_home_keepsakes WHERE account_id = :account_id AND displayed = 1 ORDER BY created_at DESC LIMIT 4');
        $keepsakes->execute(['account_id' => $accountId]);
        $relationships = $pdo->prepare('SELECT id, character_key, character_name, relationship_state, familiarity, last_met_at FROM journey_relationships WHERE account_id = :account_id ORDER BY updated_at DESC');
        $relationships->execute(['account_id' => $accountId]);
        $previousReturn = $home->fetch() ?: [];
        $pdo->prepare('UPDATE healing_home_state SET last_returned_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id')->execute(['account_id' => $accountId]);

        return ['state'=>$previousReturn,'rooms'=>$rooms->fetchAll(),'focus_quest'=>$quest->fetch() ?: null,'chronicle'=>$chronicle->fetch() ?: null,'changes'=>$changes->fetchAll(),'keepsakes'=>$keepsakes->fetchAll(),'relationships'=>$relationships->fetchAll()];
    }

    public function relationshipForAccount(string $accountId, string $characterKey): ?array
    {
        $this->ensureHome($accountId);
        $this->materializeJourney($accountId);
        $statement = $this->database->pdo()->prepare('SELECT id, character_key, character_name, relationship_state, familiarity, last_met_at FROM journey_relationships WHERE account_id = :account_id AND character_key = :character_key LIMIT 1');
        $statement->execute(['account_id'=>$accountId,'character_key'=>$characterKey]);
        $relationship = $statement->fetch();
        if (!$relationship) return null;
        $memories = $this->database->pdo()->prepare('SELECT memory_kind, summary, created_at FROM journey_relationship_memories WHERE relationship_id = :relationship_id AND account_id = :account_id ORDER BY created_at DESC');
        $memories->execute(['relationship_id'=>$relationship['id'],'account_id'=>$accountId]);
        $relationship['memories'] = $memories->fetchAll();
        $conversations = $this->database->pdo()->prepare('SELECT conversation_type, prompt_key, player_choice, character_response, remembered_context, created_at FROM relationship_conversations WHERE account_id = :account_id AND character_key = :character_key ORDER BY created_at DESC LIMIT 8');
        $conversations->execute(['account_id'=>$accountId,'character_key'=>$characterKey]);
        $relationship['conversations'] = $conversations->fetchAll();
        return $relationship;
    }

    public function keepsakesForAccount(string $accountId): array
    {
        $this->ensureHome($accountId);
        $this->materializeJourney($accountId);
        $statement = $this->database->pdo()->prepare('SELECT id, source_type, source_id, keepsake_key, name, meaning, room_key, displayed, created_at FROM healing_home_keepsakes WHERE account_id = :account_id ORDER BY created_at DESC, name');
        $statement->execute(['account_id'=>$accountId]);

        return $statement->fetchAll();
    }

    public function keepsakeForAccount(string $accountId, string $id): ?array
    {
        if (!preg_match('/^[a-f0-9-]{36}$/', $id)) {
            return null;
        }

        $this->ensureHome($accountId);
        $this->materializeJourney($accountId);
        $statement = $this->database->pdo()->prepare('SELECT id, source_type, source_id, keepsake_key, name, meaning, room_key, displayed, created_at FROM healing_home_keepsakes WHERE account_id = :account_id AND id = :id LIMIT 1');
        $statement->execute(['account_id'=>$accountId,'id'=>$id]);
        $keepsake = $statement->fetch();

        return $keepsake ?: null;
    }

    public function converseWithCaretaker(string $accountId, string $choice): void
    {
        $responses = [
            'gratitude' => 'The Caretaker rests a hand on the chair. "Then let us remember that something good was allowed to matter."',
            'repair' => 'The Caretaker nods. "We do not have to pretend every moment landed well. We can begin again without erasing it."',
            'disagree' => 'The Caretaker leans back. "You may disagree with me. Your judgment remains your own."',
            'quiet' => 'The fire settles into a softer glow. No answer is demanded of you.',
        ];
        if (!isset($responses[$choice])) {
            throw new \RuntimeException('Choose a valid conversation response.');
        }

        $this->ensureHome($accountId);
        $this->materializeJourney($accountId);
        $this->database->transaction(function (PDO $pdo) use ($accountId, $choice, $responses): void {
            $relationship = $pdo->prepare('SELECT id FROM journey_relationships WHERE account_id = :account_id AND world_key = "epic-ordinary" AND character_key = "caretaker" LIMIT 1');
            $relationship->execute(['account_id' => $accountId]);
            if (!$relationship->fetchColumn()) {
                throw new \RuntimeException('The Caretaker is not available yet.');
            }

            $memory = $pdo->prepare('SELECT summary FROM journey_relationship_memories WHERE account_id = :account_id AND source_type <> "relationship_conversation" ORDER BY created_at DESC LIMIT 1');
            $memory->execute(['account_id' => $accountId]);
            $pdo->prepare('INSERT INTO relationship_conversations (id, account_id, character_key, conversation_type, prompt_key, player_choice, character_response, remembered_context, created_at) VALUES (:id, :account_id, "caretaker", "check_in", "healing_home_relationship", :choice, :response, :memory, UTC_TIMESTAMP())')->execute(['id' => self::uuid(), 'account_id' => $accountId, 'choice' => $choice, 'response' => $responses[$choice], 'memory' => $memory->fetchColumn() ?: null]);
            $this->auditRoom($pdo, $accountId, 'healing_home.relationship.conversed', 'caretaker');
        });
    }

    public function tendGarden(string $accountId, string $choice): void
    {
        $choices = [
            'water' => 'You watered what is still becoming. Nothing had to bloom today.',
            'clear_space' => 'You cleared a little space without declaring the whole garden fixed.',
            'rest' => 'You rested near the green things. Rest counted as care.',
            'repair' => 'You tended one small repair and let it be enough.',
        ];
        if (!isset($choices[$choice])) {
            throw new \RuntimeException('Choose a valid Garden tending action.');
        }

        $this->ensureHome($accountId);
        $this->materializeJourney($accountId);
        $this->database->transaction(function (PDO $pdo) use ($accountId, $choice, $choices): void {
            $room = $pdo->prepare('SELECT state FROM healing_home_rooms WHERE account_id = :account_id AND room_key = "garden" LIMIT 1');
            $room->execute(['account_id'=>$accountId]);
            if ((string) $room->fetchColumn() !== 'open') {
                throw new \RuntimeException('The Garden is not open yet.');
            }
            $id = self::uuid();
            $pdo->prepare('INSERT INTO healing_home_changes (id, account_id, source_type, source_id, change_key, title, description, room_key, created_at) VALUES (:id,:account_id,"garden_tending",:source_id,:change_key,"Something was tended",:description,"garden",UTC_TIMESTAMP())')->execute(['id'=>$id,'account_id'=>$accountId,'source_id'=>$id,'change_key'=>'garden_'.$choice,'description'=>$choices[$choice]]);
            $pdo->prepare('UPDATE healing_home_state SET atmosphere = "green_dusk", updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id')->execute(['account_id'=>$accountId]);
            $this->auditRoom($pdo, $accountId, 'healing_home.garden.tended', $choice);
        });
    }

    public function timelineForAccount(string $accountId): array
    {
        $this->ensureHome($accountId);
        $this->materializeJourney($accountId);
        $pdo = $this->database->pdo();
        $items = [];
        $changes = $pdo->prepare('SELECT "change" AS item_type, id, source_type, source_id, title, description, room_key, created_at FROM healing_home_changes WHERE account_id = :account_id');
        $changes->execute(['account_id'=>$accountId]);
        foreach ($changes->fetchAll() as $row) $items[] = $row;
        $keepsakes = $pdo->prepare('SELECT "keepsake" AS item_type, id, source_type, source_id, name AS title, meaning AS description, room_key, created_at FROM healing_home_keepsakes WHERE account_id = :account_id AND displayed = 1');
        $keepsakes->execute(['account_id'=>$accountId]);
        foreach ($keepsakes->fetchAll() as $row) $items[] = $row;
        $conversations = $pdo->prepare('SELECT "conversation" AS item_type, id, "relationship_conversation" AS source_type, id AS source_id, "Caretaker conversation" AS title, character_response AS description, "entry_hall" AS room_key, created_at FROM relationship_conversations WHERE account_id = :account_id');
        $conversations->execute(['account_id'=>$accountId]);
        foreach ($conversations->fetchAll() as $row) $items[] = $row;
        usort($items, static fn(array $a, array $b): int => strcmp((string)$b['created_at'], (string)$a['created_at']));

        return array_slice($items, 0, 30);
    }

    public function roomForAccount(string $accountId, string $roomKey): ?array
    {
        if (!preg_match('/^[a-z0-9_]+$/', $roomKey)) {
            return null;
        }

        $this->ensureHome($accountId);
        $this->materializeJourney($accountId);
        $pdo = $this->database->pdo();
        $noteColumns = $this->roomNotesAvailable($pdo)
            ? 'note_text, note_updated_at'
            : 'NULL AS note_text, NULL AS note_updated_at';
        $room = $pdo->prepare('SELECT room_key, name, state, ' . $noteColumns . ', sort_order FROM healing_home_rooms WHERE account_id = :account_id AND room_key = :room_key LIMIT 1');
        $room->execute(['account_id' => $accountId, 'room_key' => $roomKey]);
        $result = $room->fetch();
        if (!$result) {
            return null;
        }

        $state = $pdo->prepare('SELECT current_room FROM healing_home_state WHERE account_id = :account_id LIMIT 1');
        $state->execute(['account_id' => $accountId]);
        $quest = $pdo->prepare('SELECT id, title, purpose, next_step, quest_type, origin_type FROM quests WHERE account_id = :account_id AND lifecycle_status = "active" ORDER BY updated_at DESC, created_at DESC LIMIT 1');
        $quest->execute(['account_id' => $accountId]);
        $chronicle = $pdo->prepare('SELECT title, body, created_at FROM chronicle_entries WHERE account_id = :account_id AND archived_at IS NULL ORDER BY created_at DESC LIMIT 3');
        $chronicle->execute(['account_id' => $accountId]);
        $changes = $pdo->prepare('SELECT id, source_type, source_id, change_key, title, description, room_key, created_at FROM healing_home_changes WHERE account_id = :account_id AND room_key = :room_key ORDER BY created_at DESC LIMIT 6');
        $changes->execute(['account_id' => $accountId, 'room_key' => $roomKey]);
        $keepsakes = $pdo->prepare('SELECT id, source_type, source_id, name, meaning, room_key, created_at FROM healing_home_keepsakes WHERE account_id = :account_id AND room_key = :room_key AND displayed = 1 ORDER BY created_at DESC LIMIT 6');
        $keepsakes->execute(['account_id' => $accountId, 'room_key' => $roomKey]);
        $relationships = $pdo->prepare('SELECT character_key, character_name, relationship_state, familiarity, last_met_at FROM journey_relationships WHERE account_id = :account_id ORDER BY updated_at DESC LIMIT 5');
        $relationships->execute(['account_id' => $accountId]);
        $reactions = $pdo->prepare('SELECT wr.id, wr.title, wr.message, wr.explanation, wr.source_fact_key, wr.source_fact_summary, wr.rule_key, COALESCE(wr.interpreted_at, wr.created_at) AS interpreted_at, wrr.reviewed_at
            FROM world_reactions wr
            JOIN world_installations wi ON wi.id = wr.installation_id
            LEFT JOIN world_reaction_reviews wrr ON wrr.reaction_id = wr.id AND wrr.account_id = wi.account_id
            WHERE wi.account_id = :account_id
            ORDER BY (wrr.reviewed_at IS NULL) DESC, wr.created_at DESC
            LIMIT 6');
        $reactions->execute(['account_id' => $accountId]);

        return [
            'room' => $result,
            'current_room' => (string) ($state->fetchColumn() ?: 'entry_hall'),
            'focus_quest' => $quest->fetch() ?: null,
            'chronicle' => $chronicle->fetchAll(),
            'changes' => $changes->fetchAll(),
            'keepsakes' => $keepsakes->fetchAll(),
            'relationships' => $relationships->fetchAll(),
            'world_reactions' => $reactions->fetchAll(),
        ];
    }

    public function sourceThreadForAccount(string $accountId, string $kind, string $id): ?array
    {
        if (!in_array($kind, ['change', 'keepsake', 'conversation'], true) || !preg_match('/^[a-f0-9-]{36}$/', $id)) {
            return null;
        }

        $this->ensureHome($accountId);
        $this->materializeJourney($accountId);
        $pdo = $this->database->pdo();
        if ($kind === 'change') {
            $statement = $pdo->prepare('SELECT id, "change" AS kind, source_type, source_id, change_key AS source_key, title, description, room_key, created_at FROM healing_home_changes WHERE account_id = :account_id AND id = :id LIMIT 1');
        } elseif ($kind === 'keepsake') {
            $statement = $pdo->prepare('SELECT id, "keepsake" AS kind, source_type, source_id, keepsake_key AS source_key, name AS title, meaning AS description, room_key, created_at FROM healing_home_keepsakes WHERE account_id = :account_id AND id = :id LIMIT 1');
        } else {
            $statement = $pdo->prepare('SELECT id, "conversation" AS kind, "relationship_conversation" AS source_type, id AS source_id, prompt_key AS source_key, "Caretaker conversation" AS title, character_response AS description, "entry_hall" AS room_key, created_at FROM relationship_conversations WHERE account_id = :account_id AND id = :id LIMIT 1');
        }
        $statement->execute(['account_id'=>$accountId,'id'=>$id]);
        $thread = $statement->fetch();

        return $thread ?: null;
    }

    public function restInRoom(string $accountId, string $roomKey): void
    {
        if (!preg_match('/^[a-z0-9_]+$/', $roomKey)) {
            throw new \RuntimeException('That room is unavailable.');
        }

        $this->ensureHome($accountId);
        $this->database->transaction(function (PDO $pdo) use ($accountId, $roomKey): void {
            $room = $pdo->prepare('SELECT state FROM healing_home_rooms WHERE account_id = :account_id AND room_key = :room_key LIMIT 1');
            $room->execute(['account_id' => $accountId, 'room_key' => $roomKey]);
            $state = $room->fetchColumn();
            if ($state === false || (string) $state !== 'open') {
                throw new \RuntimeException('That room is not open yet.');
            }

            $pdo->prepare('UPDATE healing_home_state SET current_room = :room_key, updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id')->execute(['account_id' => $accountId, 'room_key' => $roomKey]);
            $this->auditRoom($pdo, $accountId, 'healing_home.room.rested', $roomKey);
        });
    }

    public function saveRoomNote(string $accountId, string $roomKey, string $note): void
    {
        if (!preg_match('/^[a-z0-9_]+$/', $roomKey)) {
            throw new \RuntimeException('That room is unavailable.');
        }

        $note = trim($note);
        if ($note === '') {
            $this->clearRoomNote($accountId, $roomKey);
            return;
        }

        if (mb_strlen($note) > 600) {
            throw new \RuntimeException('Room notes must be 600 characters or fewer.');
        }

        $this->ensureHome($accountId);
        $this->database->transaction(function (PDO $pdo) use ($accountId, $roomKey, $note): void {
            if (!$this->roomNotesAvailable($pdo)) {
                throw new \RuntimeException('Room notes need the latest database migration before they can be saved.');
            }

            $room = $pdo->prepare('SELECT state FROM healing_home_rooms WHERE account_id = :account_id AND room_key = :room_key LIMIT 1');
            $room->execute(['account_id' => $accountId, 'room_key' => $roomKey]);
            $state = $room->fetchColumn();
            if ($state === false || (string) $state !== 'open') {
                throw new \RuntimeException('That room is not open yet.');
            }

            $pdo->prepare('UPDATE healing_home_rooms SET note_text = :note, note_updated_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id AND room_key = :room_key')->execute(['account_id' => $accountId, 'room_key' => $roomKey, 'note' => $note]);
            $this->auditRoom($pdo, $accountId, 'healing_home.room_note.saved', $roomKey);
        });
    }

    public function clearRoomNote(string $accountId, string $roomKey): void
    {
        if (!preg_match('/^[a-z0-9_]+$/', $roomKey)) {
            throw new \RuntimeException('That room is unavailable.');
        }

        $this->ensureHome($accountId);
        $this->database->transaction(function (PDO $pdo) use ($accountId, $roomKey): void {
            if (!$this->roomNotesAvailable($pdo)) {
                throw new \RuntimeException('Room notes need the latest database migration before they can be cleared.');
            }

            $room = $pdo->prepare('SELECT state FROM healing_home_rooms WHERE account_id = :account_id AND room_key = :room_key LIMIT 1');
            $room->execute(['account_id' => $accountId, 'room_key' => $roomKey]);
            $state = $room->fetchColumn();
            if ($state === false || (string) $state !== 'open') {
                throw new \RuntimeException('That room is not open yet.');
            }

            $pdo->prepare('UPDATE healing_home_rooms SET note_text = NULL, note_updated_at = NULL, updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id AND room_key = :room_key')->execute(['account_id' => $accountId, 'room_key' => $roomKey]);
            $this->auditRoom($pdo, $accountId, 'healing_home.room_note.cleared', $roomKey);
        });
    }

    private function materializeJourney(string $accountId): void
    {
        $this->database->transaction(function (PDO $pdo) use ($accountId): void {
            $relationshipId = $this->ensureCaretaker($pdo, $accountId);
            $reactions = $pdo->prepare('SELECT wr.id, wr.title, wr.message, wr.created_at FROM world_reactions wr JOIN world_installations wi ON wi.id = wr.installation_id WHERE wi.account_id = :account_id ORDER BY wr.created_at');
            $reactions->execute(['account_id'=>$accountId]);
            foreach ($reactions->fetchAll() as $reaction) {
                $pdo->prepare('INSERT IGNORE INTO healing_home_changes (id, account_id, source_type, source_id, change_key, title, description, room_key, created_at) VALUES (:id,:account_id,"world_reaction",:source_id,"fireplace_notice",:title,:description,"fireplace",:created_at)')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'source_id'=>$reaction['id'],'title'=>$reaction['title'],'description'=>$reaction['message'],'created_at'=>$reaction['created_at']]);
                $pdo->prepare('INSERT IGNORE INTO journey_relationship_memories (id, relationship_id, account_id, source_type, source_id, memory_kind, summary, created_at) VALUES (:id,:relationship_id,:account_id,"world_reaction",:source_id,"noticed_change",:summary,:created_at)')->execute(['id'=>self::uuid(),'relationship_id'=>$relationshipId,'account_id'=>$accountId,'source_id'=>$reaction['id'],'summary'=>$reaction['message'],'created_at'=>$reaction['created_at']]);
            }
            $easternRoom = $pdo->prepare('SELECT wch.id, wch.choice_label, wch.created_at, wk.name, wk.description
                FROM world_installations wi
                JOIN world_choice_history wch ON wch.installation_id = wi.id AND wch.scene_key = "eastern-room-purpose"
                LEFT JOIN world_keepsakes wk ON wk.installation_id = wi.id AND wk.source_scene = "eastern-room-purpose"
                WHERE wi.account_id = :account_id AND wi.world_key = "epic-ordinary"
                ORDER BY wch.created_at DESC
                LIMIT 1');
            $easternRoom->execute(['account_id' => $accountId]);
            $restoredRoom = $easternRoom->fetch();
            if ($restoredRoom) {
                $pdo->prepare('UPDATE healing_home_rooms SET state = "open", unlocked_at = COALESCE(unlocked_at, :unlocked_at), updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id AND room_key = "eastern_room"')->execute(['unlocked_at' => $restoredRoom['created_at'], 'account_id' => $accountId]);
                if (str_contains(mb_strtolower((string)$restoredRoom['choice_label']), 'making')) {
                    $pdo->prepare('UPDATE healing_home_rooms SET state = "open", unlocked_at = COALESCE(unlocked_at, :unlocked_at), updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id AND room_key = "workshop"')->execute(['unlocked_at' => $restoredRoom['created_at'], 'account_id' => $accountId]);
                    $pdo->prepare('UPDATE healing_home_state SET atmosphere = "workshop_lamplight", updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id AND atmosphere <> "green_dusk"')->execute(['account_id'=>$accountId]);
                }
                if (str_contains(mb_strtolower((string)$restoredRoom['choice_label']), 'welcome')) {
                    $pdo->prepare('UPDATE healing_home_rooms SET state = "open", unlocked_at = COALESCE(unlocked_at, :unlocked_at), updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id AND room_key = "guest_room"')->execute(['unlocked_at' => $restoredRoom['created_at'], 'account_id' => $accountId]);
                }
                $description = 'You chose ' . $restoredRoom['choice_label'] . '. The Eastern Room now has a purpose inside Epic Ordinary.';
                $pdo->prepare('INSERT IGNORE INTO healing_home_changes (id, account_id, source_type, source_id, change_key, title, description, room_key, created_at) VALUES (:id,:account_id,"world_choice",:source_id,"eastern_room_restored","The Eastern Room opened",:description,"eastern_room",:created_at)')->execute(['id' => self::uuid(), 'account_id' => $accountId, 'source_id' => $restoredRoom['id'], 'description' => $description, 'created_at' => $restoredRoom['created_at']]);
                if (!empty($restoredRoom['name'])) {
                    $pdo->prepare('INSERT IGNORE INTO healing_home_keepsakes (id, account_id, source_type, source_id, keepsake_key, name, meaning, room_key, displayed, created_at) VALUES (:id,:account_id,"world_choice",:source_id,"eastern_room_keepsake",:name,:meaning,"eastern_room",1,:created_at)')->execute(['id' => self::uuid(), 'account_id' => $accountId, 'source_id' => $restoredRoom['id'], 'name' => $restoredRoom['name'], 'meaning' => $restoredRoom['description'], 'created_at' => $restoredRoom['created_at']]);
                }
            }
            $gardenMoment = $pdo->prepare('SELECT id, player_choice, created_at FROM relationship_conversations WHERE account_id = :account_id AND character_key = "caretaker" ORDER BY created_at ASC LIMIT 1');
            $gardenMoment->execute(['account_id' => $accountId]);
            $garden = $gardenMoment->fetch();
            if ($garden) {
                $pdo->prepare('UPDATE healing_home_rooms SET state = "open", unlocked_at = COALESCE(unlocked_at, :unlocked_at), updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id AND room_key = "garden"')->execute(['unlocked_at' => $garden['created_at'], 'account_id' => $accountId]);
                $description = 'The Garden opened after you chose to meet the Caretaker honestly. It is a place for tending, not proving.';
                $pdo->prepare('INSERT IGNORE INTO healing_home_changes (id, account_id, source_type, source_id, change_key, title, description, room_key, created_at) VALUES (:id,:account_id,"relationship_conversation",:source_id,"garden_opened","The Garden gate opened",:description,"garden",:created_at)')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'source_id'=>$garden['id'],'description'=>$description,'created_at'=>$garden['created_at']]);
            }
            $reviewed = $pdo->prepare('SELECT reviewed_at FROM world_reaction_reviews WHERE account_id = :account_id ORDER BY reviewed_at ASC LIMIT 1');
            $reviewed->execute(['account_id'=>$accountId]);
            $reviewedAt = $reviewed->fetchColumn();
            if ($reviewedAt) {
                $pdo->prepare('UPDATE healing_home_rooms SET state = "open", unlocked_at = COALESCE(unlocked_at, :unlocked_at), updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id AND room_key = "library"')->execute(['unlocked_at'=>$reviewedAt,'account_id'=>$accountId]);
            }
            $resolutions = $pdo->prepare('SELECT qr.id, qr.outcome, qr.reflection, qr.resolved_at, q.title FROM quest_resolutions qr JOIN quests q ON q.id = qr.quest_id WHERE qr.account_id = :account_id ORDER BY qr.resolved_at');
            $resolutions->execute(['account_id'=>$accountId]);
            foreach ($resolutions->fetchAll() as $resolution) {
                $description = $resolution['reflection'] ?: 'You chose how this commitment should continue.';
                $pdo->prepare('INSERT IGNORE INTO healing_home_changes (id, account_id, source_type, source_id, change_key, title, description, room_key, created_at) VALUES (:id,:account_id,"quest_resolution",:source_id,"quest_board_mark",:title,:description,"quest_board",:created_at)')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'source_id'=>$resolution['id'],'title'=>'The Quest Board remembers '.$resolution['title'],'description'=>$description,'created_at'=>$resolution['resolved_at']]);
                if (in_array($resolution['outcome'], ['completed','partial','changed_direction'], true)) {
                    $pdo->prepare('INSERT IGNORE INTO healing_home_keepsakes (id, account_id, source_type, source_id, keepsake_key, name, meaning, room_key, displayed, created_at) VALUES (:id,:account_id,"quest_resolution",:source_id,"small_token",:name,:meaning,"fireplace",1,:created_at)')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'source_id'=>$resolution['id'],'name'=>'A small token from '.$resolution['title'],'meaning'=>$description,'created_at'=>$resolution['resolved_at']]);
                }
            }
            $memoryCount = (int)$pdo->query('SELECT COUNT(*) FROM journey_relationship_memories WHERE relationship_id = '.$pdo->quote($relationshipId))->fetchColumn();
            $state = $memoryCount >= 6 ? 'trusted' : ($memoryCount >= 2 ? 'familiar' : 'new');
            $pdo->prepare('UPDATE journey_relationships SET familiarity = :familiarity, relationship_state = :state, last_met_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = :id')->execute(['familiarity'=>min(100,$memoryCount * 10),'state'=>$state,'id'=>$relationshipId]);
            if ($memoryCount >= 3) $pdo->prepare('UPDATE healing_home_state SET atmosphere = "warm_firelight", updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id')->execute(['account_id'=>$accountId]);
        });
    }

    private function ensureCaretaker(PDO $pdo, string $accountId): string
    {
        $existing = $pdo->prepare('SELECT id FROM journey_relationships WHERE account_id = :account_id AND world_key = "epic-ordinary" AND character_key = "caretaker" LIMIT 1');
        $existing->execute(['account_id'=>$accountId]);
        $id = $existing->fetchColumn();
        if ($id) return (string)$id;
        $id = self::uuid(); $now = gmdate('Y-m-d H:i:s');
        $pdo->prepare('INSERT INTO journey_relationships (id, account_id, world_key, character_key, character_name, relationship_state, familiarity, created_at, updated_at) VALUES (:id,:account_id,"epic-ordinary","caretaker","The Caretaker","new",0,:created_at,:updated_at)')->execute(['id'=>$id,'account_id'=>$accountId,'created_at'=>$now,'updated_at'=>$now]);
        return $id;
    }

    private function ensureHome(string $accountId): void
    {
        $this->database->transaction(function (PDO $pdo) use ($accountId): void {
            $now = gmdate('Y-m-d H:i:s');
            $pdo->prepare('INSERT IGNORE INTO healing_home_state (account_id, atmosphere, current_room, created_at, updated_at) VALUES (:account_id,"quiet_morning","entry_hall",:created_at,:updated_at)')->execute(['account_id'=>$accountId,'created_at'=>$now,'updated_at'=>$now]);
            $rooms=[['entry_hall','Entry Hall','open',10],['fireplace','Fireplace','open',20],['quest_board','Quest Board','open',30],['journal_table','Journal Table','open',40],['companion_chair','Companion Chair','open',50],['library','Library','visible_locked',110],['garden','Garden','visible_locked',120],['workshop','Workshop','visible_locked',130],['guest_room','Guest Room','visible_locked',140],['eastern_room','Eastern Room','visible_locked',150]];
            $statement=$pdo->prepare('INSERT IGNORE INTO healing_home_rooms (id, account_id, room_key, name, state, sort_order, created_at, updated_at) VALUES (:id,:account_id,:room_key,:name,:state,:sort_order,:created_at,:updated_at)');
            foreach($rooms as [$key,$name,$state,$order]) $statement->execute(['id'=>self::uuid(),'account_id'=>$accountId,'room_key'=>$key,'name'=>$name,'state'=>$state,'sort_order'=>$order,'created_at'=>$now,'updated_at'=>$now]);
        });
    }

    private function auditRoom(PDO $pdo, string $accountId, string $action, string $roomKey): void
    {
        $pdo->prepare(
            'INSERT INTO audit_log (id, account_id, action, subject_type, subject_id, occurred_at)
             VALUES (:id, :account_id, :action, "healing_home_room", :subject_id, UTC_TIMESTAMP())'
        )->execute(['id' => self::uuid(), 'account_id' => $accountId, 'action' => $action, 'subject_id' => $roomKey]);
    }

    private function roomNotesAvailable(PDO $pdo): bool
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = "healing_home_rooms"
               AND column_name IN ("note_text", "note_updated_at")'
        );
        $statement->execute();

        return (int) $statement->fetchColumn() === 2;
    }

    private static function uuid(): string
    {
        $data=random_bytes(16);$data[6]=chr((ord($data[6])&0x0f)|0x40);$data[8]=chr((ord($data[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($data),4));
    }
}
