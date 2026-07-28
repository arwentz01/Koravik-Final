<?php

declare(strict_types=1);

namespace Koravik\Worlds\EpicOrdinary;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class EpicOrdinaryService
{
    public function __construct(private readonly Database $database)
    {
    }

    public function dashboard(string $accountId): array
    {
        $installation = $this->installationForAccount($accountId);
        if (!$installation) {
            return ['scene' => null, 'relationship' => ['name' => 'The Caretaker', 'state' => 'Unknown', 'value' => 0]];
        }

        $sceneStatement = $this->database->pdo()->prepare(
            'SELECT ws.id, ws.title, ws.body, ws.status, ws.chosen_choice_key, ws.created_at, ws.chosen_at,
                    wsc.label AS chosen_label, wsc.response_text AS chosen_response
             FROM world_scenes ws
             LEFT JOIN world_scene_choices wsc ON wsc.scene_id = ws.id AND wsc.choice_key = ws.chosen_choice_key
             WHERE ws.installation_id = :installation_id AND ws.status <> "reversed"
             ORDER BY CASE WHEN ws.status = "open" THEN 0 ELSE 1 END, ws.created_at DESC
             LIMIT 1'
        );
        $sceneStatement->execute(['installation_id' => $installation]);
        $scene = $sceneStatement->fetch() ?: null;
        if ($scene && $scene['status'] === 'open') {
            $choiceStatement = $this->database->pdo()->prepare(
                'SELECT choice_key, label, response_text, relationship_delta
                 FROM world_scene_choices WHERE scene_id = :scene_id ORDER BY sort_order'
            );
            $choiceStatement->execute(['scene_id' => $scene['id']]);
            $scene['choices'] = $choiceStatement->fetchAll();
        } elseif ($scene) {
            $scene['choices'] = [];
        }

        $relationshipStatement = $this->database->pdo()->prepare(
            'SELECT COALESCE(SUM(relationship_delta), 0)
             FROM world_relationship_entries
             WHERE installation_id = :installation_id AND npc_key = "caretaker" AND status = "active"'
        );
        $relationshipStatement->execute(['installation_id' => $installation]);
        $value = (int) $relationshipStatement->fetchColumn();

        return [
            'scene' => $scene,
            'relationship' => [
                'name' => 'The Caretaker',
                'value' => $value,
                'state' => match (true) {
                    $value >= 8 => 'Trusted companion',
                    $value >= 4 => 'Growing trust',
                    $value >= 1 => 'Familiar presence',
                    default => 'New acquaintance',
                },
            ],
        ];
    }

    public function choose(string $accountId, string $sceneId, string $choiceKey): void
    {
        $this->database->transaction(function (PDO $pdo) use ($accountId, $sceneId, $choiceKey): void {
            $statement = $pdo->prepare(
                'SELECT ws.id, ws.installation_id, ws.source_event_id, ws.status, wsc.label, wsc.response_text, wsc.relationship_delta
                 FROM world_scenes ws
                 JOIN world_installations wi ON wi.id = ws.installation_id
                 JOIN world_scene_choices wsc ON wsc.scene_id = ws.id AND wsc.choice_key = :choice_key
                 WHERE ws.id = :scene_id AND wi.account_id = :account_id AND wi.world_key = "epic-ordinary"
                 LIMIT 1 FOR UPDATE'
            );
            $statement->execute(['choice_key' => $choiceKey, 'scene_id' => $sceneId, 'account_id' => $accountId]);
            $scene = $statement->fetch();
            if (!$scene || $scene['status'] !== 'open') {
                throw new RuntimeException('That World choice is no longer available.');
            }

            $update = $pdo->prepare(
                'UPDATE world_scenes SET status = "chosen", chosen_choice_key = :choice_key, chosen_at = UTC_TIMESTAMP()
                 WHERE id = :scene_id'
            );
            $update->execute(['choice_key' => $choiceKey, 'scene_id' => $sceneId]);

            $relationship = $pdo->prepare(
                'INSERT INTO world_relationship_entries
                 (id, installation_id, npc_key, scene_id, source_event_id, choice_key, relationship_delta, reason, status, created_at)
                 VALUES (:id, :installation_id, "caretaker", :scene_id, :source_event_id, :choice_key, :relationship_delta, :reason, "active", UTC_TIMESTAMP())'
            );
            $relationship->execute([
                'id' => self::uuid(),
                'installation_id' => $scene['installation_id'],
                'scene_id' => $sceneId,
                'source_event_id' => $scene['source_event_id'],
                'choice_key' => $choiceKey,
                'relationship_delta' => (int) $scene['relationship_delta'],
                'reason' => 'The player chose: ' . (string) $scene['label'],
            ]);

            $chronicle = $pdo->prepare(
                'INSERT INTO chronicle_entries
                 (id, account_id, entry_type, title, body, source_event_id, status, created_at)
                 VALUES (:id, :account_id, "world", "A choice in Epic Ordinary", :body, :source_event_id, "active", UTC_TIMESTAMP())'
            );
            $chronicle->execute([
                'id' => self::uuid(),
                'account_id' => $accountId,
                'body' => (string) $scene['response_text'],
                'source_event_id' => $sceneId,
            ]);
        });
    }

    private function installationForAccount(string $accountId): ?string
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT id FROM world_installations
             WHERE account_id = :account_id AND world_key = "epic-ordinary" AND status = "active" LIMIT 1'
        );
        $statement->execute(['account_id' => $accountId]);
        $id = $statement->fetchColumn();
        return $id ? (string) $id : null;
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
