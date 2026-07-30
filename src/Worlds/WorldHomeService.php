<?php

declare(strict_types=1);

namespace Koravik\Worlds;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class WorldHomeService
{
    private readonly WorldHomeRepository $repository;

    public function __construct(private readonly Database $database)
    {
        $this->repository = new WorldHomeRepository($database);
    }

    public function dashboard(string $accountId): array
    {
        return [
            'active_world' => $this->repository->activeWorld($accountId),
            'reactions' => $this->repository->reactions($accountId),
            'catalog' => $this->repository->catalog($accountId),
        ];
    }

    public function markReactionReviewed(string $accountId, string $reactionId): void
    {
        if (!preg_match('/^[a-f0-9-]{36}$/', $reactionId)) {
            throw new RuntimeException('That World reaction is unavailable.');
        }

        $this->database->transaction(function (PDO $pdo) use ($accountId, $reactionId): void {
            if (!$this->repository->ownsReaction($accountId, $reactionId)) {
                throw new RuntimeException('That World reaction is unavailable.');
            }

            $this->repository->markReviewed($pdo, $accountId, $reactionId);
            $pdo->prepare(
                'INSERT INTO audit_log (id, account_id, action, subject_type, subject_id, occurred_at)
                 VALUES (:id, :account_id, "world.reaction.reviewed", "world_reaction", :subject_id, UTC_TIMESTAMP())'
            )->execute([
                'id' => self::uuid(),
                'account_id' => $accountId,
                'subject_id' => $reactionId,
            ]);
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
