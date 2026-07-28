<?php

declare(strict_types=1);

namespace Koravik\Districts\Quests;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class RecurrenceService
{
    private const FREQUENCIES = ['daily', 'weekly', 'monthly', 'yearly'];

    public function __construct(private readonly Database $database)
    {
    }

    public function saveRule(PDO $pdo, string $questId, array $input, string $accountId): void
    {
        $frequency = strtolower(trim((string) ($input['frequency'] ?? 'none')));
        if ($frequency === 'none') {
            return;
        }
        if (!in_array($frequency, self::FREQUENCIES, true)) {
            throw new RuntimeException('Choose a valid repeat pattern.');
        }

        $interval = max(1, min(365, (int) ($input['interval_count'] ?? 1)));
        $startsOn = trim((string) ($input['starts_on'] ?? gmdate('Y-m-d')));
        $timezone = trim((string) ($input['timezone'] ?? 'America/New_York'));
        $this->date($startsOn, $timezone);

        $endsOn = trim((string) ($input['ends_on'] ?? ''));
        if ($endsOn !== '') {
            $this->date($endsOn, $timezone);
            if ($endsOn < $startsOn) {
                throw new RuntimeException('The repeat end date cannot be before the start date.');
            }
        }

        $weekdays = array_values(array_unique(array_map('intval', (array) ($input['weekdays'] ?? []))));
        sort($weekdays);
        if ($frequency === 'weekly') {
            if ($weekdays === []) {
                $weekdays = [(int) $this->date($startsOn, $timezone)->format('N')];
            }
            foreach ($weekdays as $weekday) {
                if ($weekday < 1 || $weekday > 7) {
                    throw new RuntimeException('Choose valid weekdays for the repeating Quest.');
                }
            }
        } else {
            $weekdays = [];
        }

        $monthlyMode = $frequency === 'monthly' ? (string) ($input['monthly_mode'] ?? 'day_of_month') : null;
        $dayOfMonth = $frequency === 'monthly' && $monthlyMode === 'day_of_month'
            ? max(1, min(31, (int) ($input['day_of_month'] ?? $this->date($startsOn, $timezone)->format('j'))))
            : null;
        $ordinalWeek = $frequency === 'monthly' && $monthlyMode === 'ordinal_weekday'
            ? max(-1, min(4, (int) ($input['ordinal_week'] ?? 1)))
            : null;
        $ordinalWeekday = $frequency === 'monthly' && $monthlyMode === 'ordinal_weekday'
            ? max(1, min(7, (int) ($input['ordinal_weekday'] ?? $this->date($startsOn, $timezone)->format('N'))))
            : null;

        $now = gmdate('Y-m-d H:i:s');
        $statement = $pdo->prepare(
            'INSERT INTO quest_recurrence_rules
             (quest_id, frequency, interval_count, starts_on, ends_on, occurrence_limit, monthly_mode, day_of_month, ordinal_week, ordinal_weekday, timezone, generated_through, created_at, updated_at)
             VALUES (:quest_id, :frequency, :interval_count, :starts_on, :ends_on, :occurrence_limit, :monthly_mode, :day_of_month, :ordinal_week, :ordinal_weekday, :timezone, NULL, :created_at, :updated_at)'
        );
        $statement->execute([
            'quest_id' => $questId,
            'frequency' => $frequency,
            'interval_count' => $interval,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn !== '' ? $endsOn : null,
            'occurrence_limit' => ($input['occurrence_limit'] ?? '') !== '' ? max(1, (int) $input['occurrence_limit']) : null,
            'monthly_mode' => $monthlyMode,
            'day_of_month' => $dayOfMonth,
            'ordinal_week' => $ordinalWeek,
            'ordinal_weekday' => $ordinalWeekday,
            'timezone' => $timezone,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $weekdayInsert = $pdo->prepare('INSERT INTO quest_recurrence_weekdays (quest_id, weekday) VALUES (:quest_id, :weekday)');
        foreach ($weekdays as $weekday) {
            $weekdayInsert->execute(['quest_id' => $questId, 'weekday' => $weekday]);
        }

        $this->generateForQuest($pdo, $questId, $accountId, 90);
    }

    public function generateAll(int $daysAhead = 90, int $limit = 100): int
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT r.quest_id, q.account_id FROM quest_recurrence_rules r
             JOIN quests q ON q.id = r.quest_id
             WHERE q.lifecycle_status = "active"
             ORDER BY COALESCE(r.generated_through, r.starts_on) ASC LIMIT :limit'
        );
        $statement->bindValue(':limit', max(1, min(500, $limit)), PDO::PARAM_INT);
        $statement->execute();
        $count = 0;
        foreach ($statement->fetchAll() as $row) {
            $count += $this->database->transaction(fn (PDO $pdo): int => $this->generateForQuest($pdo, (string) $row['quest_id'], (string) $row['account_id'], $daysAhead));
        }
        return $count;
    }

    public function generateForQuest(PDO $pdo, string $questId, string $accountId, int $daysAhead): int
    {
        $ruleStatement = $pdo->prepare('SELECT * FROM quest_recurrence_rules WHERE quest_id = :quest_id');
        $ruleStatement->execute(['quest_id' => $questId]);
        $rule = $ruleStatement->fetch();
        if (!$rule) {
            return 0;
        }
        $weekdayStatement = $pdo->prepare('SELECT weekday FROM quest_recurrence_weekdays WHERE quest_id = :quest_id ORDER BY weekday');
        $weekdayStatement->execute(['quest_id' => $questId]);
        $weekdays = array_map('intval', $weekdayStatement->fetchAll(PDO::FETCH_COLUMN));

        $timezone = new DateTimeZone((string) $rule['timezone']);
        $start = new DateTimeImmutable((string) $rule['starts_on'], $timezone);
        $today = new DateTimeImmutable('today', $timezone);
        $until = $today->add(new DateInterval('P' . max(1, min(730, $daysAhead)) . 'D'));
        if ($rule['ends_on']) {
            $ruleEnd = new DateTimeImmutable((string) $rule['ends_on'], $timezone);
            if ($ruleEnd < $until) {
                $until = $ruleEnd;
            }
        }

        $insert = $pdo->prepare(
            'INSERT IGNORE INTO quest_occurrences
             (id, quest_id, account_id, scheduled_for, status, available_at, created_at, updated_at)
             VALUES (:id, :quest_id, :account_id, :scheduled_for, :status, :available_at, :created_at, :updated_at)'
        );
        $generated = 0;
        $cursor = $start;
        $occurrenceLimit = $rule['occurrence_limit'] !== null ? (int) $rule['occurrence_limit'] : null;
        $ordinal = 0;
        while ($cursor <= $until) {
            if ($this->matches($cursor, $start, $rule, $weekdays)) {
                $ordinal++;
                if ($occurrenceLimit !== null && $ordinal > $occurrenceLimit) {
                    break;
                }
                $scheduled = $cursor->format('Y-m-d');
                $available = $cursor->setTime(0, 0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                $now = gmdate('Y-m-d H:i:s');
                $insert->execute([
                    'id' => self::uuid(),
                    'quest_id' => $questId,
                    'account_id' => $accountId,
                    'scheduled_for' => $scheduled,
                    'status' => $cursor <= $today ? 'available' : 'scheduled',
                    'available_at' => $available,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $generated += $insert->rowCount();
            }
            $cursor = $cursor->add(new DateInterval('P1D'));
        }

        $update = $pdo->prepare('UPDATE quest_recurrence_rules SET generated_through = :through, updated_at = UTC_TIMESTAMP() WHERE quest_id = :quest_id');
        $update->execute(['through' => $until->format('Y-m-d'), 'quest_id' => $questId]);
        return $generated;
    }

    private function matches(DateTimeImmutable $date, DateTimeImmutable $start, array $rule, array $weekdays): bool
    {
        $interval = (int) $rule['interval_count'];
        $days = (int) $start->diff($date)->format('%a');
        return match ($rule['frequency']) {
            'daily' => $days % $interval === 0,
            'weekly' => intdiv($days, 7) % $interval === 0 && in_array((int) $date->format('N'), $weekdays, true),
            'monthly' => $this->matchesMonthly($date, $start, $rule, $interval),
            'yearly' => ((int) $date->format('Y') - (int) $start->format('Y')) % $interval === 0 && $date->format('m-d') === $start->format('m-d'),
            default => false,
        };
    }

    private function matchesMonthly(DateTimeImmutable $date, DateTimeImmutable $start, array $rule, int $interval): bool
    {
        $months = (((int) $date->format('Y') - (int) $start->format('Y')) * 12) + ((int) $date->format('n') - (int) $start->format('n'));
        if ($months < 0 || $months % $interval !== 0) {
            return false;
        }
        if ($rule['monthly_mode'] === 'ordinal_weekday') {
            $weekday = (int) $rule['ordinal_weekday'];
            if ((int) $date->format('N') !== $weekday) {
                return false;
            }
            $ordinal = (int) $rule['ordinal_week'];
            if ($ordinal === -1) {
                return $date->modify('+7 days')->format('n') !== $date->format('n');
            }
            return (int) ceil(((int) $date->format('j')) / 7) === $ordinal;
        }
        $target = (int) $rule['day_of_month'];
        $last = (int) $date->format('t');
        return (int) $date->format('j') === min($target, $last);
    }

    private function date(string $value, string $timezone): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone($timezone));
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new RuntimeException('Choose a valid date.');
        }
        return $date;
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
