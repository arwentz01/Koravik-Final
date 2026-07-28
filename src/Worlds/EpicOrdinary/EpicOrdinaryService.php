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

    public function stateForAccount(string $accountId): array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT wi.id AS installation_id, np.current_arc, np.current_chapter, np.current_scene,
                    wr.trust_score, wr.relationship_stage,
                    (SELECT choice_key FROM world_choice_history wc WHERE wc.installation_id = wi.id AND wc.scene_key = "caretaker-welcome" LIMIT 1) AS support_choice,
                    (SELECT choice_label FROM world_choice_history wc WHERE wc.installation_id = wi.id AND wc.scene_key = "caretaker-welcome" LIMIT 1) AS support_choice_label
             FROM world_installations wi
             LEFT JOIN world_narrative_progress np ON np.installation_id = wi.id
             LEFT JOIN world_relationships wr ON wr.installation_id = wi.id AND wr.npc_key = "caretaker"
             WHERE wi.account_id = :account_id AND wi.world_key = "epic-ordinary" AND wi.status = "active"
             LIMIT 1'
        );
        $statement->execute(['account_id' => $accountId]);
        $state = $statement->fetch();
        if (!$state) {
            throw new RuntimeException('Epic Ordinary is not available for this account.');
        }

        $history = $this->database->pdo()->prepare(
            'SELECT delta_value, reason_code, explanation, source_event_id, created_at
             FROM world_relationship_history
             WHERE installation_id = :installation_id AND npc_key = "caretaker"
             ORDER BY created_at DESC LIMIT 8'
        );
        $history->execute(['installation_id' => $state['installation_id']]);
        $state['relationship_history'] = $history->fetchAll();
        return $state;
    }

    public function chooseSupportStyle(string $accountId, string $choiceKey): void
    {
        $choices = [
            'gentle' => ['label' => 'Remind me gently', 'delta' => 2],
            'direct' => ['label' => 'Tell me plainly', 'delta' => 1],
            'quiet' => ['label' => 'Give me room, but remember', 'delta' => 2],
        ];
        if (!isset($choices[$choiceKey])) {
            throw new RuntimeException('Choose one of the available responses.');
        }

        $this->database->transaction(function (PDO $pdo) use ($accountId, $choiceKey, $choices): void {
            $installation = $pdo->prepare('SELECT id FROM world_installations WHERE account_id = :account_id AND world_key = "epic-ordinary" AND status = "active" LIMIT 1 FOR UPDATE');
            $installation->execute(['account_id' => $accountId]);
            $installationId = $installation->fetchColumn();
            if (!$installationId) {
                throw new RuntimeException('Epic Ordinary is not available.');
            }

            $existing = $pdo->prepare('SELECT id FROM world_choice_history WHERE installation_id = :installation_id AND scene_key = "caretaker-welcome" LIMIT 1');
            $existing->execute(['installation_id' => $installationId]);
            if ($existing->fetch()) {
                throw new RuntimeException('That choice has already become part of this World.');
            }

            $now = gmdate('Y-m-d H:i:s');
            $choice = $choices[$choiceKey];
            $insert = $pdo->prepare(
                'INSERT INTO world_choice_history (id, installation_id, scene_key, choice_key, choice_label, created_at)
                 VALUES (:id, :installation_id, "caretaker-welcome", :choice_key, :choice_label, :created_at)'
            );
            $insert->execute([
                'id' => self::uuid(),
                'installation_id' => $installationId,
                'choice_key' => $choiceKey,
                'choice_label' => $choice['label'],
                'created_at' => $now,
            ]);

            $pdo->prepare(
                'INSERT INTO world_relationships (installation_id, npc_key, trust_score, relationship_stage, updated_at)
                 VALUES (:installation_id, "caretaker", :delta, "known", :updated_at)
                 ON DUPLICATE KEY UPDATE trust_score = trust_score + VALUES(trust_score), relationship_stage = "known", updated_at = VALUES(updated_at)'
            )->execute(['installation_id' => $installationId, 'delta' => $choice['delta'], 'updated_at' => $now]);

            $pdo->prepare(
                'INSERT INTO world_relationship_history (id, installation_id, npc_key, delta_value, reason_code, source_event_id, explanation, created_at)
                 VALUES (:id, :installation_id, "caretaker", :delta, "welcome.choice", NULL, :explanation, :created_at)'
            )->execute([
                'id' => self::uuid(),
                'installation_id' => $installationId,
                'delta' => $choice['delta'],
                'explanation' => 'The Caretaker remembers how you asked to be supported: ' . $choice['label'] . '.',
                'created_at' => $now,
            ]);

            $pdo->prepare('UPDATE world_narrative_progress SET current_scene = "hearth-after-choice", updated_at = :updated_at WHERE installation_id = :installation_id')
                ->execute(['updated_at' => $now, 'installation_id' => $installationId]);
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
