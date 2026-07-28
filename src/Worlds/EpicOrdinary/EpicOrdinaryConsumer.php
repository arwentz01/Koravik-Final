<?php

declare(strict_types=1);

namespace Koravik\Worlds\EpicOrdinary;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Events\EventConsumer;
use PDO;

final class EpicOrdinaryConsumer implements EventConsumer
{
    public function __construct(private readonly Database $database)
    {
    }

    public function consume(array $event): void
    {
        if (($event['event_name'] ?? '') !== 'Quests.QuestCompleted' || (int) ($event['event_version'] ?? 0) !== 1) {
            return;
        }

        $this->database->transaction(function (PDO $pdo) use ($event): void {
            $duplicate = $pdo->prepare('SELECT event_id FROM world_event_receipts WHERE event_id = :event_id LIMIT 1');
            $duplicate->execute(['event_id' => $event['id']]);
            if ($duplicate->fetch()) return;

            $installation = $pdo->prepare('SELECT id FROM world_installations WHERE account_id = :account_id AND world_key = "epic-ordinary" AND status = "active" LIMIT 1 FOR UPDATE');
            $installation->execute(['account_id' => $event['account_id']]);
            $installationId = $installation->fetchColumn();
            if (!$installationId) return;

            $payload = json_decode((string) $event['payload_json'], true, 512, JSON_THROW_ON_ERROR);
            $title = (string) ($payload['title'] ?? 'a meaningful action');
            $now = gmdate('Y-m-d H:i:s');

            $pdo->prepare('INSERT INTO world_state (installation_id, state_key, state_json, updated_at) VALUES (:installation_id, "caretaker.encouragement", :state_json, :updated_at) ON DUPLICATE KEY UPDATE state_json = VALUES(state_json), updated_at = VALUES(updated_at)')
                ->execute(['installation_id' => $installationId, 'state_json' => json_encode(['status' => 'available', 'quest_title' => $title, 'source_event_id' => (string) $event['id']], JSON_THROW_ON_ERROR), 'updated_at' => $now]);

            $pdo->prepare('INSERT INTO world_reactions (id, installation_id, source_event_id, title, message, explanation, created_at) VALUES (:id, :installation_id, :source_event_id, "The Caretaker noticed", "A small promise kept can change the shape of a day.", :explanation, :created_at)')
                ->execute(['id' => self::uuid(), 'installation_id' => $installationId, 'source_event_id' => $event['id'], 'explanation' => 'Epic Ordinary responded because you completed the Quest “' . $title . '.”', 'created_at' => $now]);

            $pdo->prepare('INSERT INTO world_relationships (installation_id, npc_key, trust_score, relationship_stage, updated_at) VALUES (:installation_id, "caretaker", 1, "known", :updated_at) ON DUPLICATE KEY UPDATE trust_score = trust_score + 1, relationship_stage = CASE WHEN trust_score + 1 >= 5 THEN "trusted" ELSE "known" END, updated_at = VALUES(updated_at)')
                ->execute(['installation_id' => $installationId, 'updated_at' => $now]);

            $pdo->prepare('INSERT INTO world_relationship_history (id, installation_id, npc_key, delta_value, reason_code, source_event_id, explanation, created_at) VALUES (:id, :installation_id, "caretaker", 1, "quest.completed", :source_event_id, :explanation, :created_at)')
                ->execute(['id' => self::uuid(), 'installation_id' => $installationId, 'source_event_id' => $event['id'], 'explanation' => 'The Caretaker remembered that you followed through on “' . $title . '.”', 'created_at' => $now]);

            $pdo->prepare('INSERT INTO world_event_receipts (event_id, installation_id, consumed_at) VALUES (:event_id, :installation_id, :consumed_at)')
                ->execute(['event_id' => $event['id'], 'installation_id' => $installationId, 'consumed_at' => $now]);
        });
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
