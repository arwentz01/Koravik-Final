<?php

declare(strict_types=1);

namespace Koravik\Platform\Events;

use Koravik\Platform\Database\Database;
use Koravik\Worlds\EpicOrdinary\EpicOrdinaryConsumer;
use PDO;
use Throwable;

final class OutboxWorker
{
    public function __construct(
        private readonly Database $database,
        private readonly EpicOrdinaryConsumer $consumer,
    ) {
    }

    public function run(int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));
        $processed = 0;
        $failed = 0;

        for ($index = 0; $index < $limit; $index++) {
            $event = $this->claimNext();
            if ($event === null) {
                break;
            }

            try {
                $this->consumer->consume($event);
                $this->markDelivered((string) $event['id']);
                $processed++;
            } catch (Throwable $throwable) {
                $this->markFailed((string) $event['id'], $throwable->getMessage(), (int) $event['attempts']);
                $failed++;
            }
        }

        return ['processed' => $processed, 'failed' => $failed];
    }

    private function claimNext(): ?array
    {
        return $this->database->transaction(function (PDO $pdo): ?array {
            $statement = $pdo->query(
                'SELECT * FROM platform_outbox
                 WHERE status IN ("pending", "retry") AND available_at <= UTC_TIMESTAMP()
                 ORDER BY created_at ASC
                 LIMIT 1
                 FOR UPDATE'
            );
            $event = $statement->fetch();
            if (!$event) {
                return null;
            }

            $update = $pdo->prepare(
                'UPDATE platform_outbox
                 SET status = "processing", attempts = attempts + 1, locked_at = UTC_TIMESTAMP()
                 WHERE id = :id'
            );
            $update->execute(['id' => $event['id']]);
            $event['attempts'] = (int) $event['attempts'] + 1;
            return $event;
        });
    }

    private function markDelivered(string $eventId): void
    {
        $statement = $this->database->pdo()->prepare(
            'UPDATE platform_outbox
             SET status = "delivered", delivered_at = UTC_TIMESTAMP(), locked_at = NULL, last_error = NULL
             WHERE id = :id'
        );
        $statement->execute(['id' => $eventId]);
    }

    private function markFailed(string $eventId, string $message, int $attempts): void
    {
        $dead = $attempts >= 5;
        $statement = $this->database->pdo()->prepare(
            'UPDATE platform_outbox
             SET status = :status,
                 available_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL :delay SECOND),
                 locked_at = NULL,
                 last_error = :last_error
             WHERE id = :id'
        );
        $statement->bindValue(':status', $dead ? 'dead' : 'retry');
        $statement->bindValue(':delay', min(300, 2 ** max(1, $attempts)), PDO::PARAM_INT);
        $statement->bindValue(':last_error', mb_substr($message, 0, 500));
        $statement->bindValue(':id', $eventId);
        $statement->execute();
    }
}
