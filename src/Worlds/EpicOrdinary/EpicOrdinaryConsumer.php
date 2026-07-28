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
        $name = (string) ($event['event_name'] ?? '');
        $version = (int) ($event['event_version'] ?? 0);
        if ($version !== 1 || !in_array($name, ['Quests.QuestCompleted', 'Quests.QuestCompletionReversed'], true)) {
            return;
        }

        $this->database->transaction(function (PDO $pdo) use ($event, $name): void {
            $duplicate = $pdo->prepare('SELECT event_id FROM world_event_receipts WHERE event_id = :event_id LIMIT 1');
            $duplicate->execute(['event_id' => $event['id']]);
            if ($duplicate->fetch()) return;

            $installation = $pdo->prepare('SELECT id FROM world_installations WHERE account_id = :account_id AND world_key = "epic-ordinary" AND status = "active" LIMIT 1 FOR UPDATE');
            $installation->execute(['account_id' => $event['account_id']]);
            $installationId = $installation->fetchColumn();
            if (!$installationId) return;

            $payload = json_decode((string) $event['payload_json'], true, 512, JSON_THROW_ON_ERROR);
            if ($name === 'Quests.QuestCompletionReversed') {
                $this->reverse($pdo, (string) $installationId, $payload, (string) $event['id']);
            } else {
                $this->advance($pdo, (string) $installationId, $payload, (string) $event['id']);
            }

            $pdo->prepare('INSERT INTO world_event_receipts (event_id, installation_id, consumed_at) VALUES (:event_id, :installation_id, UTC_TIMESTAMP())')
                ->execute(['event_id' => $event['id'], 'installation_id' => $installationId]);
        });
    }

    private function advance(PDO $pdo, string $installationId, array $payload, string $eventId): void
    {
        $pdo->prepare('INSERT INTO world_story_threads (installation_id, story_key, chapter, progress_count, status, updated_at) VALUES (:installation_id, "caretaker-path", 1, 0, "active", UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)')->execute(['installation_id' => $installationId]);
        $pdo->prepare('INSERT INTO world_relationships (installation_id, character_key, relationship_stage, trust_count, updated_at) VALUES (:installation_id, "caretaker", "new_acquaintance", 0, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)')->execute(['installation_id' => $installationId]);

        $thread = $pdo->prepare('SELECT progress_count FROM world_story_threads WHERE installation_id = :installation_id FOR UPDATE');
        $thread->execute(['installation_id' => $installationId]);
        $progress = (int) $thread->fetchColumn() + 1;
        $chapter = min(4, 1 + intdiv(max(0, $progress - 1), 3));

        $relationship = $pdo->prepare('SELECT trust_count FROM world_relationships WHERE installation_id = :installation_id AND character_key = "caretaker" FOR UPDATE');
        $relationship->execute(['installation_id' => $installationId]);
        $trust = (int) $relationship->fetchColumn() + 1;
        $stage = match (true) {
            $trust >= 10 => 'trusted_companion',
            $trust >= 6 => 'steady_ally',
            $trust >= 3 => 'familiar_presence',
            default => 'new_acquaintance',
        };

        $pdo->prepare('UPDATE world_story_threads SET chapter = :chapter, progress_count = :progress_count, updated_at = UTC_TIMESTAMP() WHERE installation_id = :installation_id')->execute(['chapter' => $chapter, 'progress_count' => $progress, 'installation_id' => $installationId]);
        $pdo->prepare('UPDATE world_relationships SET relationship_stage = :stage, trust_count = :trust_count, last_interaction_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE installation_id = :installation_id AND character_key = "caretaker"')->execute(['stage' => $stage, 'trust_count' => $trust, 'installation_id' => $installationId]);

        [$title, $message] = $this->reactionFor($chapter, $stage);
        $questTitle = (string) ($payload['title'] ?? 'a meaningful action');
        $explanation = sprintf('Epic Ordinary continued the Caretaker path because you completed “%s.” This is story chapter %d, and the Caretaker now regards you as a %s.', $questTitle, $chapter, str_replace('_', ' ', $stage));

        $pdo->prepare('INSERT INTO world_story_moments (id, installation_id, source_event_id, source_completion_event_id, story_key, chapter, character_key, relationship_stage, title, body, status, created_at) VALUES (:id, :installation_id, :source_event_id, :completion_event_id, "caretaker-path", :chapter, "caretaker", :stage, :title, :body, "active", UTC_TIMESTAMP())')->execute(['id' => self::uuid(), 'installation_id' => $installationId, 'source_event_id' => $eventId, 'completion_event_id' => $eventId, 'chapter' => $chapter, 'stage' => $stage, 'title' => $title, 'body' => $message]);
        $pdo->prepare('INSERT INTO world_state (installation_id, state_key, state_json, updated_at) VALUES (:installation_id, "caretaker.continuity", :state_json, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE state_json = VALUES(state_json), updated_at = VALUES(updated_at)')->execute(['installation_id' => $installationId, 'state_json' => json_encode(['story_key' => 'caretaker-path', 'chapter' => $chapter, 'progress_count' => $progress, 'relationship_stage' => $stage, 'trust_count' => $trust, 'source_event_id' => $eventId], JSON_THROW_ON_ERROR)]);
        $pdo->prepare('INSERT INTO world_reactions (id, installation_id, source_event_id, title, message, explanation, created_at) VALUES (:id, :installation_id, :source_event_id, :title, :message, :explanation, UTC_TIMESTAMP())')->execute(['id' => self::uuid(), 'installation_id' => $installationId, 'source_event_id' => $eventId, 'title' => $title, 'message' => $message, 'explanation' => $explanation]);
    }

    private function reverse(PDO $pdo, string $installationId, array $payload, string $eventId): void
    {
        $completionEventId = (string) ($payload['completion_event_id'] ?? '');
        if ($completionEventId === '') return;

        $moment = $pdo->prepare('SELECT id FROM world_story_moments WHERE installation_id = :installation_id AND source_completion_event_id = :event_id AND status = "active" LIMIT 1 FOR UPDATE');
        $moment->execute(['installation_id' => $installationId, 'event_id' => $completionEventId]);
        if (!$moment->fetchColumn()) return;

        $pdo->prepare('UPDATE world_story_moments SET status = "reversed", reversed_at = UTC_TIMESTAMP() WHERE installation_id = :installation_id AND source_completion_event_id = :event_id')->execute(['installation_id' => $installationId, 'event_id' => $completionEventId]);
        $pdo->prepare('UPDATE world_story_threads SET progress_count = GREATEST(progress_count - 1, 0), chapter = LEAST(4, 1 + FLOOR(GREATEST(progress_count - 2, 0) / 3)), updated_at = UTC_TIMESTAMP() WHERE installation_id = :installation_id')->execute(['installation_id' => $installationId]);
        $pdo->prepare('UPDATE world_relationships SET trust_count = GREATEST(trust_count - 1, 0), relationship_stage = CASE WHEN trust_count - 1 >= 10 THEN "trusted_companion" WHEN trust_count - 1 >= 6 THEN "steady_ally" WHEN trust_count - 1 >= 3 THEN "familiar_presence" ELSE "new_acquaintance" END, updated_at = UTC_TIMESTAMP() WHERE installation_id = :installation_id AND character_key = "caretaker"')->execute(['installation_id' => $installationId]);
        $pdo->prepare('INSERT INTO world_reactions (id, installation_id, source_event_id, title, message, explanation, created_at) VALUES (:id, :installation_id, :source_event_id, "The thread was corrected", "The Caretaker lets the moment go without judgment. The path remains open.", "Epic Ordinary adjusted its independent story state because a previously completed Quest occurrence was reversed.", UTC_TIMESTAMP())')->execute(['id' => self::uuid(), 'installation_id' => $installationId, 'source_event_id' => $eventId]);
    }

    private function reactionFor(int $chapter, string $stage): array
    {
        return match ($chapter) {
            1 => ['The Caretaker noticed', 'A small promise kept can change the shape of a day.'],
            2 => ['A light remains in the window', 'The Caretaker is beginning to recognize the rhythm of your return.'],
            3 => ['The path remembers your steps', 'What once looked accidental now feels like a pattern you are choosing.'],
            default => ['The ordinary becomes a landmark', $stage === 'trusted_companion' ? 'The Caretaker no longer watches from a distance. You have become part of the keeping of this place.' : 'The Caretaker meets your effort with quiet recognition.'],
        };
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
