<?php

declare(strict_types=1);

namespace Koravik\Platform\Database;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class Database
{
    private function __construct(private readonly PDO $pdo)
    {
    }

    public static function connect(string $dsn, string $user, string $password): self
    {
        if ($dsn === '') {
            throw new RuntimeException('DB_DSN is required.');
        }

        try {
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed.', 0, $exception);
        }

        return new self($pdo);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this->pdo);
            $this->pdo->commit();
            return $result;
        } catch (Throwable $throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $throwable;
        }
    }
}
