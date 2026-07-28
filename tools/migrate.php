<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$pdo = database()->pdo();
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(64) PRIMARY KEY, applied_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$files = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
sort($files);

foreach ($files as $file) {
    $version = basename($file, '.sql');
    $check = $pdo->prepare('SELECT version FROM schema_migrations WHERE version = :version');
    $check->execute(['version' => $version]);
    if ($check->fetchColumn()) {
        echo "Already applied: {$version}\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("Unable to read migration {$version}");
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec($sql);
        $record = $pdo->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (:version, UTC_TIMESTAMP())');
        $record->execute(['version' => $version]);
        $pdo->commit();
        echo "Applied: {$version}\n";
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $throwable;
    }
}
