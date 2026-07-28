<?php

declare(strict_types=1);

namespace Koravik\Districts\Quests;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class QuestService
{
    private const TYPES = ['action', 'habit', 'goal', 'project', 'journey', 'responsibility'];

    public function __construct(private readonly Database $database)
    {
    }

    public function create(string $accountId, string $title, string $description = '', array $options = []): string
    {
        $title = trim($title);
        $description = trim($description);
        $purpose = trim((string) ($options['purpose'] ?? ''));
        $questType = strtolower(trim((string) ($options['quest_type'] ?? 'action')));
        $pillarKey = trim((string) ($options['pillar_key'] ?? ''));

        if ($title === '') throw new RuntimeException('Give this Quest a title.');
        if (mb_strlen($title) > 180) throw new RuntimeException('Quest titles must be 180 characters or fewer.');
        if (mb_strlen($description) > 4000) throw new RuntimeException('Quest notes must be 4,000 characters or fewer.');
        if (mb_strlen($purpose) > 500) throw new RuntimeException('Quest purpose must be 500 characters or fewer.');
        if (!in_array($questType, self::TYPES, true)) throw new RuntimeException('Choose a valid Quest type.');
        if ($pillarKey !== '' && !array_key_exists($pillarKey, self::pillarNames())) throw new RuntimeException('Choose a valid Pillar.');

        return $this->database->transaction(function (PDO $pdo) use ($accountId, $title, $description, $purpose, $questType, $pillarKey, $options): string {
            $questId = self::uuid();
            $now = gmdate('Y-m-d H:i:s');
            $statement = $pdo->prepare(
                'INSERT INTO quests (id, account_id, title, description, purpose, quest_type, pillar_key, status, lifecycle_status, created_at, updated_at)
                 VALUES (:id, :account_id, :title, :description, :purpose, :quest_type, :pillar_key, "active", "active", :created_at, :updated_at)'
            );
            $statement->execute([
                'id' => $questId, 'account_id' => $accountId, 'title' => $title, 'description' => $description,
                'purpose' => $purpose !== '' ? $purpose : null, 'quest_type' => $questType,
                'pillar_key' => $pillarKey !== '' ? $pillarKey : null, 'created_at' => $now, 'updated_at' => $now,
            ]);

            $frequency = strtolower((string) ($options['frequency'] ?? 'none'));
            if ($frequency === 'none') {
                $scheduledFor = trim((string) ($options['starts_on'] ?? gmdate('Y-m-d')));
                $occurrence = $pdo->prepare(
                    'INSERT INTO quest_occurrences (id, quest_id, account_id, scheduled_for, status, available_at, created_at, updated_at)
                     VALUES (:id, :quest_id, :account_id, :scheduled_for, "available", :available_at, :created_at, :updated_at)'
                );
                $occurrence->execute([
                    'id' => self::uuid(), 'quest_id' => $questId, 'account_id' => $accountId,
                    'scheduled_for' => $scheduledFor, 'available_at' => $now, 'created_at' => $now, 'updated_at' => $now,
                ]);
            } else {
                (new RecurrenceService($this->database))->saveRule($pdo, $questId, $options, $accountId);
            }

            $this->audit($pdo, $accountId, 'quest.created', $questId, $now);
            return $questId;
        });
    }

    public function getForAccount(string $questId, string $accountId): ?array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT q.id, q.title, q.description, q.purpose, q.quest_type, q.pillar_key, p.name AS pillar_name,
                    q.status, q.lifecycle_status, r.frequency, r.interval_count, r.starts_on, r.ends_on,
                    (SELECT GROUP_CONCAT(w.weekday ORDER BY w.weekday) FROM quest_recurrence_weekdays w WHERE w.quest_id = q.id) AS weekdays,
                    (SELECT qo.id FROM quest_occurrences qo WHERE qo.quest_id = q.id AND qo.account_id = :occurrence_account
                     AND qo.status IN ("available", "scheduled") ORDER BY qo.scheduled_for ASC LIMIT 1) AS next_occurrence_id,
                    (SELECT qo.scheduled_for FROM quest_occurrences qo WHERE qo.quest_id = q.id AND qo.account_id = :date_account
                     AND qo.status IN ("available", "scheduled") ORDER BY qo.scheduled_for ASC LIMIT 1) AS next_scheduled_for,
                    CASE WHEN EXISTS (SELECT 1 FROM quest_occurrences qo WHERE qo.quest_id = q.id AND qo.status IN ("available", "scheduled")) THEN 0 ELSE 1 END AS completed
             FROM quests q LEFT JOIN pillars p ON p.`key` = q.pillar_key
             LEFT JOIN quest_recurrence_rules r ON r.quest_id = q.id
             WHERE q.id = :quest_id AND q.account_id = :quest_account LIMIT 1'
        );
        $statement->execute(['quest_id' => $questId, 'occurrence_account' => $accountId, 'date_account' => $accountId, 'quest_account' => $accountId]);
        $quest = $statement->fetch();
        return $quest ?: null;
    }

    public function listForAccount(string $accountId): array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT q.id, q.title, q.description, q.purpose, q.quest_type, q.pillar_key, p.name AS pillar_name, q.lifecycle_status,
                    r.frequency, r.interval_count,
                    MIN(CASE WHEN qo.status IN ("available", "scheduled") THEN qo.scheduled_for END) AS next_scheduled_for,
                    CASE WHEN SUM(CASE WHEN qo.status IN ("available", "scheduled") THEN 1 ELSE 0 END) = 0 THEN 1 ELSE 0 END AS completed
             FROM quests q LEFT JOIN pillars p ON p.`key` = q.pillar_key
             LEFT JOIN quest_recurrence_rules r ON r.quest_id = q.id
             LEFT JOIN quest_occurrences qo ON qo.quest_id = q.id AND qo.account_id = :occurrence_account
             WHERE q.account_id = :quest_account AND q.lifecycle_status <> "archived"
             GROUP BY q.id, q.title, q.description, q.purpose, q.quest_type, q.pillar_key, p.name, q.lifecycle_status, r.frequency, r.interval_count, q.created_at
             ORDER BY completed ASC, next_scheduled_for ASC, q.created_at DESC'
        );
        $statement->execute(['occurrence_account' => $accountId, 'quest_account' => $accountId]);
        return $statement->fetchAll();
    }

    public function complete(string $questId, string $accountId): array
    {
        return $this->database->transaction(function (PDO $pdo) use ($questId, $accountId): array {
            $questStatement = $pdo->prepare(
                'SELECT q.id, q.title, q.pillar_key, r.quest_id AS recurring
                 FROM quests q LEFT JOIN quest_recurrence_rules r ON r.quest_id = q.id
                 WHERE q.id = :quest_id AND q.account_id = :account_id AND q.lifecycle_status = "active" FOR UPDATE'
            );
            $questStatement->execute(['quest_id' => $questId, 'account_id' => $accountId]);
            $quest = $questStatement->fetch();
            if (!$quest) throw new RuntimeException('Quest not found or unavailable.');

            $occurrenceStatement = $pdo->prepare(
                'SELECT id, scheduled_for FROM quest_occurrences
                 WHERE quest_id = :quest_id AND account_id = :account_id AND status IN ("available", "scheduled")
                 ORDER BY scheduled_for ASC LIMIT 1 FOR UPDATE'
            );
            $occurrenceStatement->execute(['quest_id' => $questId, 'account_id' => $accountId]);
            $occurrence = $occurrenceStatement->fetch();
            if (!$occurrence) throw new RuntimeException('There is no available occurrence to complete.');

            $now = gmdate('Y-m-d H:i:s');
            $update = $pdo->prepare('UPDATE quest_occurrences SET status = "completed", completed_at = :completed_at, updated_at = :updated_at WHERE id = :id');
            $update->execute(['completed_at' => $now, 'updated_at' => $now, 'id' => $occurrence['id']]);

            if (!$quest['recurring']) {
                $completion = $pdo->prepare('INSERT IGNORE INTO quest_completions (id, quest_id, account_id, completed_at) VALUES (:id, :quest_id, :account_id, :completed_at)');
                $completion->execute(['id' => self::uuid(), 'quest_id' => $questId, 'account_id' => $accountId, 'completed_at' => $now]);
            }

            $eventId = self::uuid();
            $payload = json_encode([
                'quest_id' => $questId, 'occurrence_id' => (string) $occurrence['id'],
                'scheduled_date' => (string) $occurrence['scheduled_for'], 'recurring' => (bool) $quest['recurring'],
                'pillar' => $quest['pillar_key'] ?: null, 'title' => (string) $quest['title'],
            ], JSON_THROW_ON_ERROR);
            $outbox = $pdo->prepare(
                'INSERT INTO platform_outbox
                 (id, event_name, event_version, account_id, payload_json, status, attempts, available_at, occurred_at, created_at)
                 VALUES (:id, "Quests.QuestCompleted", 1, :account_id, :payload_json, "pending", 0, :available_at, :occurred_at, :created_at)'
            );
            $outbox->execute([
                'id' => $eventId, 'account_id' => $accountId, 'payload_json' => $payload,
                'available_at' => $now, 'occurred_at' => $now, 'created_at' => $now,
            ]);
            $this->audit($pdo, $accountId, 'quest.occurrence.completed', (string) $occurrence['id'], $now);
            return ['event_id' => $eventId, 'occurrence_id' => (string) $occurrence['id'], 'quest_id' => $questId];
        });
    }

    public function setLifecycle(string $questId, string $accountId, string $status): void
    {
        if (!in_array($status, ['active', 'paused', 'archived'], true)) throw new RuntimeException('Choose a valid Quest status.');
        $this->database->transaction(function (PDO $pdo) use ($questId, $accountId, $status): void {
            $statement = $pdo->prepare('UPDATE quests SET lifecycle_status = :status, archived_at = :archived_at, updated_at = UTC_TIMESTAMP() WHERE id = :id AND account_id = :account_id');
            $statement->execute(['status' => $status, 'archived_at' => $status === 'archived' ? gmdate('Y-m-d H:i:s') : null, 'id' => $questId, 'account_id' => $accountId]);
            if ($statement->rowCount() !== 1) throw new RuntimeException('Quest not found or unavailable.');
            $this->audit($pdo, $accountId, 'quest.' . $status, $questId, gmdate('Y-m-d H:i:s'));
        });
    }

    public function pillarSummary(string $accountId, int $days = 30): array
    {
        $days = max(1, min(365, $days));
        $statement = $this->database->pdo()->prepare(
            'SELECT p.`key`, p.name, p.description, COUNT(qo.id) AS completed_count
             FROM pillars p
             LEFT JOIN quests q ON q.pillar_key = p.`key` AND q.account_id = :account_id
             LEFT JOIN quest_occurrences qo ON qo.quest_id = q.id AND qo.account_id = :occurrence_account
                  AND qo.status = "completed" AND qo.completed_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL ' . $days . ' DAY)
             WHERE p.is_active = 1
             GROUP BY p.`key`, p.name, p.description, p.sort_order
             ORDER BY p.sort_order'
        );
        $statement->execute(['account_id' => $accountId, 'occurrence_account' => $accountId]);
        return $statement->fetchAll();
    }

    public static function pillarNames(): array
    {
        return [
            'well-being' => 'Well-being', 'growth' => 'Growth', 'connection' => 'Connection', 'home' => 'Home',
            'stability' => 'Stability', 'purpose' => 'Purpose', 'creativity' => 'Creativity', 'adventure' => 'Adventure',
        ];
    }

    public static function typeNames(): array
    {
        return ['action' => 'Action', 'habit' => 'Habit', 'goal' => 'Goal', 'project' => 'Project', 'journey' => 'Journey', 'responsibility' => 'Responsibility'];
    }

    public static function recurrenceLabel(array $quest): ?string
    {
        if (empty($quest['frequency'])) return null;
        $interval = (int) ($quest['interval_count'] ?? 1);
        $prefix = $interval === 1 ? 'Every' : 'Every ' . $interval;
        $unit = match ($quest['frequency']) {
            'daily' => $interval === 1 ? 'day' : 'days', 'weekly' => $interval === 1 ? 'week' : 'weeks',
            'monthly' => $interval === 1 ? 'month' : 'months', 'yearly' => $interval === 1 ? 'year' : 'years',
            default => (string) $quest['frequency'],
        };
        if ($quest['frequency'] === 'weekly' && !empty($quest['weekdays'])) {
            $names = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
            $days = array_map(static fn (string $day): string => $names[(int) $day], explode(',', (string) $quest['weekdays']));
            return $prefix . ' ' . $unit . ' on ' . implode(', ', $days);
        }
        return $prefix . ' ' . $unit;
    }

    private function audit(PDO $pdo, string $accountId, string $action, string $subjectId, string $now): void
    {
        $audit = $pdo->prepare('INSERT INTO audit_log (id, account_id, action, subject_type, subject_id, occurred_at) VALUES (:id, :account_id, :action, "quest", :subject_id, :occurred_at)');
        $audit->execute(['id' => self::uuid(), 'account_id' => $accountId, 'action' => $action, 'subject_id' => $subjectId, 'occurred_at' => $now]);
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
