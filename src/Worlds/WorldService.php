<?php

declare(strict_types=1);

namespace Koravik\Worlds;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class WorldService
{
    public function __construct(private readonly Database $database)
    {
    }

    public function catalog(string $accountId): array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT c.world_key, c.name, c.tagline, c.description, c.package_version,
                    i.id AS installation_id, i.status AS installation_status,
                    p.granted AS quest_completed_granted
             FROM world_catalog c
             LEFT JOIN world_installations i ON i.world_key = c.world_key AND i.account_id = :account_id
             LEFT JOIN world_fact_permissions p ON p.installation_id = i.id AND p.fact_key = "quest.completed"
             WHERE c.status = "available"
             ORDER BY c.name'
        );
        $statement->execute(['account_id' => $accountId]);
        return $statement->fetchAll();
    }

    public function detail(string $worldKey, string $accountId): ?array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT c.*, i.id AS installation_id, i.status AS installation_status, i.installed_at,
                    p.granted AS quest_completed_granted, p.explanation AS permission_explanation,
                    p.granted_at, p.revoked_at
             FROM world_catalog c
             LEFT JOIN world_installations i ON i.world_key = c.world_key AND i.account_id = :account_id
             LEFT JOIN world_fact_permissions p ON p.installation_id = i.id AND p.fact_key = "quest.completed"
             WHERE c.world_key = :world_key AND c.status = "available" LIMIT 1'
        );
        $statement->execute(['account_id' => $accountId, 'world_key' => $worldKey]);
        $world = $statement->fetch();
        return $world ?: null;
    }

    public function install(string $worldKey, string $accountId): void
    {
        $this->database->transaction(function (PDO $pdo) use ($worldKey, $accountId): void {
            $catalog = $pdo->prepare('SELECT world_key FROM world_catalog WHERE world_key = :world_key AND status = "available" LIMIT 1');
            $catalog->execute(['world_key' => $worldKey]);
            if (!$catalog->fetchColumn()) {
                throw new RuntimeException('That World is unavailable.');
            }

            $existing = $pdo->prepare('SELECT id, status FROM world_installations WHERE account_id = :account_id AND world_key = :world_key LIMIT 1 FOR UPDATE');
            $existing->execute(['account_id' => $accountId, 'world_key' => $worldKey]);
            $installation = $existing->fetch();
            $now = gmdate('Y-m-d H:i:s');
            if ($installation) {
                $pdo->prepare('UPDATE world_installations SET status = "active" WHERE id = :id')->execute(['id' => $installation['id']]);
                $installationId = (string) $installation['id'];
            } else {
                $installationId = self::uuid();
                $pdo->prepare('INSERT INTO world_installations (id, account_id, world_key, status, installed_at) VALUES (:id, :account_id, :world_key, "active", :installed_at)')
                    ->execute(['id' => $installationId, 'account_id' => $accountId, 'world_key' => $worldKey, 'installed_at' => $now]);
            }

            $pdo->prepare('INSERT INTO world_fact_permissions (installation_id, fact_key, granted, explanation, granted_at, revoked_at, updated_at) VALUES (:installation_id, "quest.completed", 1, :explanation, :granted_at, NULL, :updated_at) ON DUPLICATE KEY UPDATE granted = 1, granted_at = VALUES(granted_at), revoked_at = NULL, updated_at = VALUES(updated_at)')
                ->execute([
                    'installation_id' => $installationId,
                    'explanation' => 'Allows Epic Ordinary to receive a minimized fact when a Quest occurrence is completed. Quest notes and full private records are not shared.',
                    'granted_at' => $now,
                    'updated_at' => $now,
                ]);
            $this->audit($pdo, $accountId, 'world.installed', $installationId, $now);
        });
    }

    public function setStatus(string $worldKey, string $accountId, string $status): void
    {
        if (!in_array($status, ['active', 'suspended', 'uninstalled'], true)) {
            throw new RuntimeException('Choose a valid World status.');
        }
        $this->database->transaction(function (PDO $pdo) use ($worldKey, $accountId, $status): void {
            $statement = $pdo->prepare('UPDATE world_installations SET status = :status WHERE account_id = :account_id AND world_key = :world_key');
            $statement->execute(['status' => $status, 'account_id' => $accountId, 'world_key' => $worldKey]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('That World is not installed.');
            }
            $this->audit($pdo, $accountId, 'world.' . $status, $worldKey, gmdate('Y-m-d H:i:s'));
        });
    }

    public function setPermission(string $worldKey, string $accountId, bool $granted): void
    {
        $this->database->transaction(function (PDO $pdo) use ($worldKey, $accountId, $granted): void {
            $installation = $pdo->prepare('SELECT id FROM world_installations WHERE account_id = :account_id AND world_key = :world_key LIMIT 1 FOR UPDATE');
            $installation->execute(['account_id' => $accountId, 'world_key' => $worldKey]);
            $installationId = $installation->fetchColumn();
            if (!$installationId) {
                throw new RuntimeException('Install the World before changing its permissions.');
            }
            $now = gmdate('Y-m-d H:i:s');
            $pdo->prepare('INSERT INTO world_fact_permissions (installation_id, fact_key, granted, explanation, granted_at, revoked_at, updated_at) VALUES (:installation_id, "quest.completed", :granted, :explanation, :granted_at, :revoked_at, :updated_at) ON DUPLICATE KEY UPDATE granted = VALUES(granted), granted_at = VALUES(granted_at), revoked_at = VALUES(revoked_at), updated_at = VALUES(updated_at)')
                ->execute([
                    'installation_id' => $installationId,
                    'granted' => $granted ? 1 : 0,
                    'explanation' => 'Allows Epic Ordinary to receive a minimized fact when a Quest occurrence is completed. Quest notes and full private records are not shared.',
                    'granted_at' => $granted ? $now : null,
                    'revoked_at' => $granted ? null : $now,
                    'updated_at' => $now,
                ]);
            $this->audit($pdo, $accountId, $granted ? 'world.permission.granted' : 'world.permission.revoked', (string) $installationId, $now);
        });
    }

    private function audit(PDO $pdo, string $accountId, string $action, string $subjectId, string $now): void
    {
        $pdo->prepare('INSERT INTO audit_log (id, account_id, action, subject_type, subject_id, occurred_at) VALUES (:id, :account_id, :action, "world", :subject_id, :occurred_at)')
            ->execute(['id' => self::uuid(), 'account_id' => $accountId, 'action' => $action, 'subject_id' => $subjectId, 'occurred_at' => $now]);
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
