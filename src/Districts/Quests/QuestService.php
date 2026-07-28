<?php

declare(strict_types=1);

namespace Koravik\Districts\Quests;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class QuestService
{
    public function __construct(private readonly Database $database)
    {
    }

    public function getForAccount(string $questId, string $accountId): ?array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT q.id, q.title, q.description, q.status,
                    CASE WHEN qc.id IS NULL THEN 0 ELSE 1 END AS completed
             FROM quests q
             LEFT JOIN quest_completions qc ON qc.quest_id = q.id AND qc.account_id = :account_id
             WHERE q.id = :quest_id AND q.account_id = :account_id
             LIMIT 1'
        );
        $statement->execute(['quest_id' => $questId, 'account_id' => $accountId]);
        $quest = $statement->fetch();
        return $quest ?: null;
    }

    public function listForAccount(string $accountId): array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT q.id, q.title, q.description,
                    CASE WHEN qc.id IS NULL THEN 0 ELSE 1 END AS completed
             FROM quests q
             LEFT JOIN quest_completions qc ON qc.quest_id = q.id AND qc.account_id = :account_id
             WHERE q.account_id = :account_id
             ORDER BY q.created_at ASC'
        );
        $statement->execute(['account_id' => $accountId]);
        return $statement->fetchAll();
    }

    public function complete(string $questId, string $accountId): string
    {
        return $this->database->transaction(function (PDO $pdo) use ($questId, $accountId): string {
            $questStatement = $pdo->prepare(
                'SELECT id, title FROM quests WHERE id = :quest_id AND account_id = :account_id AND status = "active" FOR UPDATE'
            );
            $questStatement->execute(['quest_id' => $questId, 'account_id' => $accountId]);
            $quest = $questStatement->fetch();
            if (!$quest) {
                throw new RuntimeException('Quest not found or unavailable.');
            }

            $existing = $pdo->prepare(
                'SELECT id FROM quest_completions WHERE quest_id = :quest_id AND account_id = :account_id LIMIT 1'
            );
            $existing->execute(['quest_id' => $questId, 'account_id' => $accountId]);
            if ($existing->fetch()) {
                throw new RuntimeException('This Quest is already complete.');
            }

            $completionId = self::uuid();
            $eventId = self::uuid();
            $now = gmdate('Y-m-d H:i:s');

            $completion = $pdo->prepare(
                'INSERT INTO quest_completions (id, quest_id, account_id, completed_at)
                 VALUES (:id, :quest_id, :account_id, :completed_at)'
            );
            $completion->execute([
                'id' => $completionId,
                'quest_id' => $questId,
                'account_id' => $accountId,
                'completed_at' => $now,
            ]);

            $payload = json_encode([
                'quest_id' => $questId,
                'completion_id' => $completionId,
                'title' => (string) $quest['title'],
            ], JSON_THROW_ON_ERROR);

            $outbox = $pdo->prepare(
                'INSERT INTO platform_outbox
                 (id, event_name, event_version, account_id, payload_json, status, attempts, available_at, occurred_at, created_at)
                 VALUES (:id, "Quests.QuestCompleted", 1, :account_id, :payload_json, "pending", 0, :available_at, :occurred_at, :created_at)'
            );
            $outbox->execute([
                'id' => $eventId,
                'account_id' => $accountId,
                'payload_json' => $payload,
                'available_at' => $now,
                'occurred_at' => $now,
                'created_at' => $now,
            ]);

            $audit = $pdo->prepare(
                'INSERT INTO audit_log (id, account_id, action, subject_type, subject_id, occurred_at)
                 VALUES (:id, :account_id, "quest.completed", "quest", :subject_id, :occurred_at)'
            );
            $audit->execute([
                'id' => self::uuid(),
                'account_id' => $accountId,
                'subject_id' => $questId,
                'occurred_at' => $now,
            ]);

            return $eventId;
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
