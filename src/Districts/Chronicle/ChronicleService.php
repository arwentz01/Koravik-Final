<?php

declare(strict_types=1);

namespace Koravik\Districts\Chronicle;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class ChronicleService
{
    public function __construct(private readonly Database $database)
    {
    }

    public function saveQuestReflection(string $accountId, string $occurrenceId, string $body, string $mood = ''): string
    {
        $body = trim($body);
        $mood = trim($mood);
        if ($body === '') {
            throw new RuntimeException('Write a few words before saving this reflection.');
        }
        if (mb_strlen($body) > 8000) {
            throw new RuntimeException('Reflections must be 8,000 characters or fewer.');
        }
        if ($mood !== '' && !in_array($mood, ['lighter', 'steady', 'proud', 'thoughtful', 'tired', 'mixed'], true)) {
            throw new RuntimeException('Choose a valid reflection tone.');
        }

        return $this->database->transaction(function (PDO $pdo) use ($accountId, $occurrenceId, $body, $mood): string {
            $source = $pdo->prepare(
                'SELECT qo.id AS occurrence_id, qo.quest_id, qo.scheduled_for, q.title, q.pillar_key
                 FROM quest_occurrences qo
                 JOIN quests q ON q.id = qo.quest_id
                 WHERE qo.id = :occurrence_id AND qo.account_id = :account_id AND qo.status = "completed"
                 LIMIT 1 FOR UPDATE'
            );
            $source->execute(['occurrence_id' => $occurrenceId, 'account_id' => $accountId]);
            $occurrence = $source->fetch();
            if (!$occurrence) {
                throw new RuntimeException('That completed Quest occurrence is unavailable.');
            }

            $existing = $pdo->prepare('SELECT chronicle_entry_id FROM quest_reflections WHERE occurrence_id = :occurrence_id LIMIT 1');
            $existing->execute(['occurrence_id' => $occurrenceId]);
            $entryId = $existing->fetchColumn();
            if ($entryId) {
                return (string) $entryId;
            }

            $now = gmdate('Y-m-d H:i:s');
            $entryId = self::uuid();
            $reflectionId = self::uuid();
            $title = 'Reflection: ' . (string) $occurrence['title'];

            $entry = $pdo->prepare(
                'INSERT INTO chronicle_entries
                 (id, account_id, title, body, visibility, entry_type, occurred_on, created_at, updated_at)
                 VALUES (:id, :account_id, :title, :body, "private", "quest_reflection", :occurred_on, :created_at, :updated_at)'
            );
            $entry->execute([
                'id' => $entryId,
                'account_id' => $accountId,
                'title' => $title,
                'body' => $body,
                'occurred_on' => $occurrence['scheduled_for'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $reference = $pdo->prepare(
                'INSERT INTO chronicle_source_references
                 (id, entry_id, source_module, source_type, source_id, created_at)
                 VALUES (:id, :entry_id, "Quests", "QuestOccurrence", :source_id, :created_at)'
            );
            $reference->execute([
                'id' => self::uuid(),
                'entry_id' => $entryId,
                'source_id' => $occurrenceId,
                'created_at' => $now,
            ]);

            $reflection = $pdo->prepare(
                'INSERT INTO quest_reflections
                 (id, occurrence_id, quest_id, account_id, reflection_text, mood, chronicle_entry_id, created_at, updated_at)
                 VALUES (:id, :occurrence_id, :quest_id, :account_id, :reflection_text, :mood, :chronicle_entry_id, :created_at, :updated_at)'
            );
            $reflection->execute([
                'id' => $reflectionId,
                'occurrence_id' => $occurrenceId,
                'quest_id' => $occurrence['quest_id'],
                'account_id' => $accountId,
                'reflection_text' => $body,
                'mood' => $mood !== '' ? $mood : null,
                'chronicle_entry_id' => $entryId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $audit = $pdo->prepare(
                'INSERT INTO audit_log (id, account_id, action, subject_type, subject_id, occurred_at)
                 VALUES (:id, :account_id, "chronicle.entry.created", "chronicle_entry", :subject_id, :occurred_at)'
            );
            $audit->execute([
                'id' => self::uuid(),
                'account_id' => $accountId,
                'subject_id' => $entryId,
                'occurred_at' => $now,
            ]);

            return $entryId;
        });
    }

    public function listForAccount(string $accountId): array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT ce.id, ce.title, ce.body, ce.visibility, ce.entry_type, ce.occurred_on, ce.created_at,
                    q.pillar_key, q.title AS quest_title
             FROM chronicle_entries ce
             LEFT JOIN chronicle_source_references csr ON csr.entry_id = ce.id AND csr.source_module = "Quests" AND csr.source_type = "QuestOccurrence"
             LEFT JOIN quest_occurrences qo ON qo.id = csr.source_id
             LEFT JOIN quests q ON q.id = qo.quest_id
             WHERE ce.account_id = :account_id
             ORDER BY ce.occurred_on DESC, ce.created_at DESC'
        );
        $statement->execute(['account_id' => $accountId]);
        return $statement->fetchAll();
    }

    public function getForAccount(string $entryId, string $accountId): ?array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT ce.id, ce.title, ce.body, ce.visibility, ce.entry_type, ce.occurred_on, ce.created_at,
                    q.pillar_key, q.title AS quest_title, qo.scheduled_for
             FROM chronicle_entries ce
             LEFT JOIN chronicle_source_references csr ON csr.entry_id = ce.id AND csr.source_module = "Quests" AND csr.source_type = "QuestOccurrence"
             LEFT JOIN quest_occurrences qo ON qo.id = csr.source_id
             LEFT JOIN quests q ON q.id = qo.quest_id
             WHERE ce.id = :entry_id AND ce.account_id = :account_id LIMIT 1'
        );
        $statement->execute(['entry_id' => $entryId, 'account_id' => $accountId]);
        $entry = $statement->fetch();
        return $entry ?: null;
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
