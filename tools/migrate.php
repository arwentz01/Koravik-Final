<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';


$pdo = database()->pdo();
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(64) PRIMARY KEY, applied_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$migrationDirectory = dirname(__DIR__) . '/database/migrations';
$files = glob($migrationDirectory . '/*.sql') ?: [];
sort($files, SORT_STRING);

echo 'Migration directory: ' . $migrationDirectory . PHP_EOL;
echo 'Discovered migrations: ' . count($files) . PHP_EOL;

if ($files === []) {
    throw new RuntimeException('No SQL migration files were found. Confirm that database/migrations was included in the deployed release.');
}

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

    $statements = preg_split('/;\s*(?:\r?\n|$)/', trim($sql)) ?: [];
    $statementNumber = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }

        $statementNumber++;
        try {
            $pdo->exec($statement);
        } catch (PDOException $exception) {
            $driverCode = (int)($exception->errorInfo[1] ?? 0);

            // DDL can be committed before a later statement fails. These errors mean
            // the intended object already exists and allow a partially applied
            // migration to resume safely. All other errors remain fatal.
            if (in_array($driverCode, [1050, 1060, 1061], true)) {
                echo "Reconciled existing schema in {$version}, statement {$statementNumber}: {$exception->getMessage()}\n";
                continue;
            }

            $preview = preg_replace('/\s+/', ' ', substr($statement, 0, 240)) ?: $statement;
            throw new RuntimeException(
                "Migration {$version} failed at statement {$statementNumber}: {$preview}",
                0,
                $exception
            );
        }
    }

    $record = $pdo->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (:version, UTC_TIMESTAMP())');
    $record->execute(['version' => $version]);
    echo "Applied: {$version}\n";
}
