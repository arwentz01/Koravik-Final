<?php

declare(strict_types=1);

namespace Koravik\Platform\Experience;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Events\EventConsumer;
use PDO;

final class ExperienceConsumer implements EventConsumer
{
    public function __construct(private readonly Database $database)
    {
    }

    public function consume(array $event): void
    {
        $name = (string) ($event['event_name'] ?? '');
        if (!in_array($name, ['Quests.QuestCompleted', 'Quests.QuestCompletionReversed'], true) || (int) ($event['event_version'] ?? 0) !== 1) {
            return;
        }

        $this->database->transaction(function (PDO $pdo) use ($event, $name): void {
            $receipt = $pdo->prepare('SELECT event_id FROM platform_consumer_receipts WHERE consumer_key = "platform.experience" AND event_id = :event_id');
            $receipt->execute(['event_id' => $event['id']]);
            if ($receipt->fetchColumn()) {
                return;
            }

            $payload = json_decode((string) $event['payload_json'], true, 512, JSON_THROW_ON_ERROR);
            $questId = (string) ($payload['quest_id'] ?? '');
            $occurrenceId = (string) ($payload['occurrence_id'] ?? '');

            if ($name === 'Quests.QuestCompleted') {
                $pillars = $pdo->prepare('SELECT l.pillar_key, d.name FROM quest_pillar_links l JOIN pillar_definitions d ON d.pillar_key = l.pillar_key WHERE l.quest_id = :quest_id ORDER BY l.is_primary DESC, d.sort_order');
                $pillars->execute(['quest_id' => $questId]);
                $rows = $pillars->fetchAll();
                foreach ($rows as $row) {
                    $insert = $pdo->prepare('INSERT INTO pillar_contributions (id, account_id, pillar_key, quest_id, occurrence_id, source_event_id, status, contributed_on, created_at) VALUES (:id, :account_id, :pillar_key, :quest_id, :occurrence_id, :source_event_id, "active", :contributed_on, UTC_TIMESTAMP())');
                    $insert->execute([
                        'id' => self::uuid(), 'account_id' => $event['account_id'], 'pillar_key' => $row['pillar_key'],
                        'quest_id' => $questId, 'occurrence_id' => $occurrenceId, 'source_event_id' => $event['id'],
                        'contributed_on' => (string) ($payload['scheduled_date'] ?? gmdate('Y-m-d')),
                    ]);
                }

                $pillarText = $rows ? ' This supported ' . implode(', ', array_column($rows, 'name')) . '.' : '';
                $entry = $pdo->prepare('INSERT INTO chronicle_entries (id, account_id, entry_type, title, body, source_event_id, quest_id, occurrence_id, status, created_at) VALUES (:id, :account_id, "system", :title, :body, :source_event_id, :quest_id, :occurrence_id, "active", UTC_TIMESTAMP())');
                $entry->execute([
                    'id' => self::uuid(), 'account_id' => $event['account_id'], 'title' => 'A promise kept',
                    'body' => 'You completed “' . (string) ($payload['title'] ?? 'a Quest') . '.”' . $pillarText,
                    'source_event_id' => $event['id'], 'quest_id' => $questId, 'occurrence_id' => $occurrenceId,
                ]);
            } else {
                $sourceEventId = (string) ($payload['completion_event_id'] ?? '');
                $pdo->prepare('UPDATE pillar_contributions SET status = "reversed", reversed_at = UTC_TIMESTAMP() WHERE source_event_id = :source_event_id AND status = "active"')->execute(['source_event_id' => $sourceEventId]);
                $pdo->prepare('UPDATE chronicle_entries SET status = "reversed", reversed_at = UTC_TIMESTAMP() WHERE source_event_id = :source_event_id AND entry_type = "system" AND status = "active"')->execute(['source_event_id' => $sourceEventId]);
            }

            $pdo->prepare('INSERT INTO platform_consumer_receipts (consumer_key, event_id, consumed_at) VALUES ("platform.experience", :event_id, UTC_TIMESTAMP())')->execute(['event_id' => $event['id']]);
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
