<?php

declare(strict_types=1);

namespace Koravik\Platform\Journey;

use Koravik\Platform\Database\Database;
use PDO;

final class JourneyService
{
    public function __construct(private readonly Database $database)
    {
    }

    public function homeForAccount(string $accountId): array
    {
        $this->ensureHome($accountId);
        $pdo = $this->database->pdo();

        $home = $pdo->prepare('SELECT atmosphere, current_room, last_returned_at FROM healing_home_state WHERE account_id = :account_id LIMIT 1');
        $home->execute(['account_id' => $accountId]);
        $state = $home->fetch() ?: [];

        $rooms = $pdo->prepare('SELECT room_key, name, state, sort_order FROM healing_home_rooms WHERE account_id = :account_id ORDER BY sort_order, name');
        $rooms->execute(['account_id' => $accountId]);

        $quest = $pdo->prepare('SELECT id, title, purpose, next_step, quest_type, origin_type FROM quests WHERE account_id = :account_id AND lifecycle_status = "active" ORDER BY updated_at DESC, created_at DESC LIMIT 1');
        $quest->execute(['account_id' => $accountId]);

        $chronicle = $pdo->prepare('SELECT title, body, created_at FROM chronicle_entries WHERE account_id = :account_id AND archived_at IS NULL ORDER BY created_at DESC LIMIT 1');
        $chronicle->execute(['account_id' => $accountId]);

        $reaction = $pdo->prepare('SELECT wr.title, wr.message, wr.created_at FROM world_reactions wr JOIN world_installations wi ON wi.id = wr.installation_id WHERE wi.account_id = :account_id ORDER BY wr.created_at DESC LIMIT 1');
        $reaction->execute(['account_id' => $accountId]);

        $pdo->prepare('UPDATE healing_home_state SET last_returned_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id')->execute(['account_id' => $accountId]);

        return [
            'state' => $state,
            'rooms' => $rooms->fetchAll(),
            'focus_quest' => $quest->fetch() ?: null,
            'chronicle' => $chronicle->fetch() ?: null,
            'reaction' => $reaction->fetch() ?: null,
        ];
    }

    private function ensureHome(string $accountId): void
    {
        $this->database->transaction(function (PDO $pdo) use ($accountId): void {
            $now = gmdate('Y-m-d H:i:s');
            $pdo->prepare('INSERT IGNORE INTO healing_home_state (account_id, atmosphere, current_room, created_at, updated_at) VALUES (:account_id, "quiet_morning", "entry_hall", :created_at, :updated_at)')->execute(['account_id' => $accountId, 'created_at' => $now, 'updated_at' => $now]);

            $rooms = [
                ['entry_hall', 'Entry Hall', 'open', 10],
                ['fireplace', 'Fireplace', 'open', 20],
                ['quest_board', 'Quest Board', 'open', 30],
                ['journal_table', 'Journal Table', 'open', 40],
                ['companion_chair', 'Companion Chair', 'open', 50],
                ['library', 'Library', 'visible_locked', 110],
                ['garden', 'Garden', 'visible_locked', 120],
                ['workshop', 'Workshop', 'visible_locked', 130],
                ['guest_room', 'Guest Room', 'visible_locked', 140],
                ['eastern_room', 'Eastern Room', 'visible_locked', 150],
            ];
            $statement = $pdo->prepare('INSERT IGNORE INTO healing_home_rooms (id, account_id, room_key, name, state, sort_order, created_at, updated_at) VALUES (:id, :account_id, :room_key, :name, :state, :sort_order, :created_at, :updated_at)');
            foreach ($rooms as [$key, $name, $state, $order]) {
                $statement->execute(['id' => self::uuid(), 'account_id' => $accountId, 'room_key' => $key, 'name' => $name, 'state' => $state, 'sort_order' => $order, 'created_at' => $now, 'updated_at' => $now]);
            }
        });
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
