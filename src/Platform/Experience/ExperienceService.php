<?php

declare(strict_types=1);

namespace Koravik\Platform\Experience;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class ExperienceService
{
    public function __construct(private readonly Database $database)
    {
    }

    public function pillars(): array
    {
        return $this->database->pdo()->query('SELECT pillar_key, name, description FROM pillar_definitions WHERE active = 1 ORDER BY sort_order')->fetchAll();
    }

    public function linkQuest(string $questId, string $accountId, ?string $pillarKey): void
    {
        if ($pillarKey === null || $pillarKey === '') {
            return;
        }
        $statement = $this->database->pdo()->prepare(
            'INSERT INTO quest_pillar_links (quest_id, pillar_key, is_primary, created_at)
             SELECT q.id, d.pillar_key, 1, UTC_TIMESTAMP()
             FROM quests q JOIN pillar_definitions d ON d.pillar_key = :pillar_key AND d.active = 1
             WHERE q.id = :quest_id AND q.account_id = :account_id'
        );
        $statement->execute(['pillar_key' => $pillarKey, 'quest_id' => $questId, 'account_id' => $accountId]);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('Choose a valid Pillar.');
        }
    }

    public function dashboard(string $accountId): array
    {
        $contributions = $this->database->pdo()->prepare(
            'SELECT d.name, COUNT(*) AS contribution_count, MAX(pc.contributed_on) AS last_contributed_on
             FROM pillar_contributions pc JOIN pillar_definitions d ON d.pillar_key = pc.pillar_key
             WHERE pc.account_id = :account_id AND pc.status = "active"
             GROUP BY d.pillar_key, d.name, d.sort_order ORDER BY last_contributed_on DESC, d.sort_order LIMIT 6'
        );
        $contributions->execute(['account_id' => $accountId]);

        $chronicle = $this->database->pdo()->prepare(
            'SELECT id, entry_type, title, body, created_at FROM chronicle_entries
             WHERE account_id = :account_id AND status = "active" ORDER BY created_at DESC LIMIT 5'
        );
        $chronicle->execute(['account_id' => $accountId]);

        return ['pillars' => $contributions->fetchAll(), 'chronicle' => $chronicle->fetchAll()];
    }

    public function completionSummary(string $accountId, string $eventId): ?array
    {
        $event = $this->database->pdo()->prepare('SELECT payload_json, occurred_at FROM platform_outbox WHERE id = :id AND account_id = :account_id AND event_name = "Quests.QuestCompleted"');
        $event->execute(['id' => $eventId, 'account_id' => $accountId]);
        $row = $event->fetch();
        if (!$row) {
            return null;
        }
        $payload = json_decode((string) $row['payload_json'], true, 512, JSON_THROW_ON_ERROR);
        $pillars = $this->database->pdo()->prepare('SELECT d.name FROM pillar_contributions pc JOIN pillar_definitions d ON d.pillar_key = pc.pillar_key WHERE pc.source_event_id = :event_id AND pc.status = "active" ORDER BY d.sort_order');
        $pillars->execute(['event_id' => $eventId]);
        return [
            'event_id' => $eventId,
            'quest_id' => (string) ($payload['quest_id'] ?? ''),
            'occurrence_id' => (string) ($payload['occurrence_id'] ?? ''),
            'title' => (string) ($payload['title'] ?? 'Quest'),
            'pillars' => array_column($pillars->fetchAll(), 'name'),
            'occurred_at' => (string) $row['occurred_at'],
        ];
    }

    public function addReflection(string $accountId, string $eventId, string $body): void
    {
        $body = trim($body);
        if ($body === '') {
            throw new RuntimeException('Write a short reflection first.');
        }
        if (mb_strlen($body) > 2000) {
            throw new RuntimeException('Reflections must be 2,000 characters or fewer.');
        }
        $summary = $this->completionSummary($accountId, $eventId);
        if (!$summary) {
            throw new RuntimeException('That completion is unavailable.');
        }
        $statement = $this->database->pdo()->prepare(
            'INSERT INTO chronicle_entries (id, account_id, entry_type, title, body, source_event_id, quest_id, occurrence_id, status, created_at)
             VALUES (:id, :account_id, "reflection", "Reflection", :body, :source_event_id, :quest_id, :occurrence_id, "active", UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE body = VALUES(body), created_at = UTC_TIMESTAMP()'
        );
        $statement->execute([
            'id' => self::uuid(), 'account_id' => $accountId, 'body' => $body, 'source_event_id' => $eventId,
            'quest_id' => $summary['quest_id'], 'occurrence_id' => $summary['occurrence_id'],
        ]);
    }

    public function undoCompletion(string $accountId, string $eventId): string
    {
        return $this->database->transaction(function (PDO $pdo) use ($accountId, $eventId): string {
            $event = $pdo->prepare('SELECT payload_json, occurred_at FROM platform_outbox WHERE id = :id AND account_id = :account_id AND event_name = "Quests.QuestCompleted" FOR UPDATE');
            $event->execute(['id' => $eventId, 'account_id' => $accountId]);
            $row = $event->fetch();
            if (!$row || strtotime((string) $row['occurred_at']) < time() - 900) {
                throw new RuntimeException('The undo window has closed.');
            }
            $payload = json_decode((string) $row['payload_json'], true, 512, JSON_THROW_ON_ERROR);
            $occurrenceId = (string) ($payload['occurrence_id'] ?? '');
            $occurrence = $pdo->prepare('UPDATE quest_occurrences SET status = "available", completed_at = NULL, updated_at = UTC_TIMESTAMP() WHERE id = :id AND account_id = :account_id AND status = "completed"');
            $occurrence->execute(['id' => $occurrenceId, 'account_id' => $accountId]);
            if ($occurrence->rowCount() !== 1) {
                throw new RuntimeException('This completion has already been changed.');
            }
            $pdo->prepare('DELETE FROM quest_completions WHERE quest_id = :quest_id AND account_id = :account_id')->execute(['quest_id' => $payload['quest_id'], 'account_id' => $accountId]);
            $reversalId = self::uuid();
            $reversalPayload = json_encode([
                'quest_id' => (string) $payload['quest_id'],
                'occurrence_id' => $occurrenceId,
                'completion_event_id' => $eventId,
            ], JSON_THROW_ON_ERROR);
            $pdo->prepare('INSERT INTO platform_outbox (id, event_name, event_version, account_id, payload_json, status, attempts, available_at, occurred_at, created_at) VALUES (:id, "Quests.QuestCompletionReversed", 1, :account_id, :payload_json, "pending", 0, UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP())')->execute(['id' => $reversalId, 'account_id' => $accountId, 'payload_json' => $reversalPayload]);
            $pdo->prepare('INSERT INTO audit_log (id, account_id, action, subject_type, subject_id, occurred_at) VALUES (:id, :account_id, "quest.occurrence.completion_reversed", "quest_occurrence", :subject_id, UTC_TIMESTAMP())')->execute(['id' => self::uuid(), 'account_id' => $accountId, 'subject_id' => $occurrenceId]);
            return $reversalId;
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
