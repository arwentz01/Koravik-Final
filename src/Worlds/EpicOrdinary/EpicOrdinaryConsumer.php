<?php

declare(strict_types=1);

namespace Koravik\Worlds\EpicOrdinary;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Events\EventConsumer;
use PDO;

final class EpicOrdinaryConsumer implements EventConsumer
{
    public function __construct(private readonly Database $database)
    {
    }

    public function consume(array $event): void
    {
        if ((int) ($event['event_version'] ?? 0) !== 1) {
            return;
        }

        $name = (string) ($event['event_name'] ?? '');
        if (!in_array($name, ['Quests.QuestCompleted', 'Quests.QuestCompletionReversed'], true)) {
            return;
        }

        $this->database->transaction(function (PDO $pdo) use ($event, $name): void {
            $duplicate = $pdo->prepare('SELECT event_id FROM world_event_receipts WHERE event_id = :event_id LIMIT 1');
            $duplicate->execute(['event_id' => $event['id']]);
            if ($duplicate->fetch()) {
                return;
            }

            $installation = $pdo->prepare('SELECT id FROM world_installations WHERE account_id = :account_id AND world_key = "epic-ordinary" AND status = "active" LIMIT 1 FOR UPDATE');
            $installation->execute(['account_id' => $event['account_id']]);
            $installationId = $installation->fetchColumn();
            if (!$installationId) {
                return;
            }

            $payload = json_decode((string) $event['payload_json'], true, 512, JSON_THROW_ON_ERROR);
            if ($name === 'Quests.QuestCompleted') {
                $this->recordCompletion($pdo, (string) $installationId, $event, $payload);
            } else {
                $this->recordReversal($pdo, (string) $installationId, $payload);
            }

            $receipt = $pdo->prepare('INSERT INTO world_event_receipts (event_id, installation_id, consumed_at) VALUES (:event_id, :installation_id, UTC_TIMESTAMP())');
            $receipt->execute(['event_id' => $event['id'], 'installation_id' => $installationId]);
        });
    }

    private function recordCompletion(PDO $pdo, string $installationId, array $event, array $payload): void
    {
        $title = (string) ($payload['title'] ?? 'a meaningful action');
        $state = $pdo->prepare('INSERT INTO world_state (installation_id, state_key, state_json, updated_at) VALUES (:installation_id, "caretaker.encouragement", :state_json, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE state_json = VALUES(state_json), updated_at = VALUES(updated_at)');
        $state->execute([
            'installation_id' => $installationId,
            'state_json' => json_encode(['status' => 'available', 'quest_title' => $title, 'source_event_id' => (string) $event['id']], JSON_THROW_ON_ERROR),
        ]);

        $reaction = $pdo->prepare('INSERT INTO world_reactions (id, installation_id, source_event_id, title, message, explanation, created_at) VALUES (:id, :installation_id, :source_event_id, :title, :message, :explanation, UTC_TIMESTAMP())');
        $reaction->execute([
            'id' => self::uuid(),
            'installation_id' => $installationId,
            'source_event_id' => $event['id'],
            'title' => 'The Caretaker noticed',
            'message' => 'A small promise kept can change the shape of a day.',
            'explanation' => 'Epic Ordinary responded because you completed the Quest “' . $title . '.”',
        ]);

        $sceneId = self::uuid();
        $scene = $pdo->prepare('INSERT INTO world_scenes (id, installation_id, source_event_id, scene_key, title, body, status, created_at) VALUES (:id, :installation_id, :source_event_id, "caretaker-lantern-path", :title, :body, "open", UTC_TIMESTAMP())');
        $scene->execute([
            'id' => $sceneId,
            'installation_id' => $installationId,
            'source_event_id' => $event['id'],
            'title' => 'A lantern wakes along the path',
            'body' => 'The Caretaker finds you beside a newly lit lantern. “Something you did beyond this place reached us,” they say. “What should we do with the light?”',
        ]);

        $choice = $pdo->prepare('INSERT INTO world_scene_choices (scene_id, choice_key, label, response_text, relationship_delta, sort_order) VALUES (:scene_id, :choice_key, :label, :response_text, :relationship_delta, :sort_order)');
        $choices = [
            ['restore', 'Offer to help restore the path.', 'The Caretaker passes you a weathered lantern hook. Together, you make the next stretch safer for whoever comes after.', 2, 10],
            ['ask', 'Ask what the path remembers.', 'The Caretaker smiles and tells you the path remembers consistency more clearly than spectacle.', 1, 20],
            ['pause', 'Sit quietly with the light.', 'You share a quiet minute. The Caretaker does not rush to fill it, and the silence becomes companionable.', 1, 30],
        ];
        foreach ($choices as [$key, $label, $response, $delta, $order]) {
            $choice->execute(['scene_id' => $sceneId, 'choice_key' => $key, 'label' => $label, 'response_text' => $response, 'relationship_delta' => $delta, 'sort_order' => $order]);
        }
    }

    private function recordReversal(PDO $pdo, string $installationId, array $payload): void
    {
        $sourceEventId = (string) ($payload['completion_event_id'] ?? '');
        if ($sourceEventId === '') {
            return;
        }

        $scene = $pdo->prepare('SELECT id FROM world_scenes WHERE installation_id = :installation_id AND source_event_id = :source_event_id LIMIT 1 FOR UPDATE');
        $scene->execute(['installation_id' => $installationId, 'source_event_id' => $sourceEventId]);
        $sceneId = $scene->fetchColumn();
        if (!$sceneId) {
            return;
        }

        $pdo->prepare('UPDATE world_scenes SET status = "reversed", reversed_at = UTC_TIMESTAMP() WHERE id = :id')->execute(['id' => $sceneId]);
        $pdo->prepare('UPDATE world_relationship_entries SET status = "reversed", reversed_at = UTC_TIMESTAMP() WHERE scene_id = :scene_id AND status = "active"')->execute(['scene_id' => $sceneId]);
        $pdo->prepare('UPDATE chronicle_entries SET status = "reversed", reversed_at = UTC_TIMESTAMP() WHERE source_event_id = :scene_id AND entry_type = "world" AND status = "active"')->execute(['scene_id' => $sceneId]);
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
