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
        $pdo->prepare('UPDATE healing_home_state SET last_returned_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id')->execute(['account_id' => $accountId]);

        return ['state'=>$home->fetch() ?: [],'rooms'=>$rooms->fetchAll(),'focus_quest'=>$quest->fetch() ?: null,'chronicle'=>$chronicle->fetch() ?: null,'changes'=>$changes->fetchAll(),'keepsakes'=>$keepsakes->fetchAll(),'relationships'=>$relationships->fetchAll()];
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
        return $relationship;
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

    private static function uuid(): string
    {
        $data=random_bytes(16);$data[6]=chr((ord($data[6])&0x0f)|0x40);$data[8]=chr((ord($data[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($data),4));
    }
}
