<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Koravik\Worlds\EpicOrdinary\EpicOrdinaryConsumer;

$consumer = new EpicOrdinaryConsumer(database());
$accountId = '00000000-0000-4000-8000-000000000001';
$completionEvents = [];
$pdo = database()->pdo();
$installationId = (string) $pdo->query('SELECT id FROM world_installations WHERE world_key = "epic-ordinary" LIMIT 1')->fetchColumn();

$pdo->prepare('DELETE FROM world_event_receipts WHERE event_id LIKE "10000000-%" OR event_id LIKE "30000000-%"')->execute();
$pdo->prepare('DELETE FROM world_story_moments WHERE installation_id = :installation_id')->execute(['installation_id' => $installationId]);
$pdo->prepare('UPDATE world_story_threads SET chapter = 1, progress_count = 0, updated_at = UTC_TIMESTAMP() WHERE installation_id = :installation_id')->execute(['installation_id' => $installationId]);
$pdo->prepare('UPDATE world_relationships SET relationship_stage = "new_acquaintance", trust_count = 0, updated_at = UTC_TIMESTAMP() WHERE installation_id = :installation_id AND character_key = "caretaker"')->execute(['installation_id' => $installationId]);

for ($index = 1; $index <= 4; $index++) {
    $eventId = sprintf('10000000-0000-4000-8000-%012d', $index);
    $completionEvents[] = $eventId;
    $consumer->consume([
        'id' => $eventId,
        'event_name' => 'Quests.QuestCompleted',
        'event_version' => 1,
        'account_id' => $accountId,
        'payload_json' => json_encode([
            'quest_id' => '00000000-0000-4000-8000-000000000101',
            'occurrence_id' => sprintf('20000000-0000-4000-8000-%012d', $index),
            'title' => 'Keep one ordinary promise',
        ], JSON_THROW_ON_ERROR),
    ]);
}

$thread = $pdo->prepare('SELECT chapter, progress_count FROM world_story_threads WHERE installation_id = :installation_id');
$thread->execute(['installation_id' => $installationId]);
$threadState = $thread->fetch();
$relationship = $pdo->prepare('SELECT relationship_stage, trust_count FROM world_relationships WHERE installation_id = :installation_id AND character_key = "caretaker"');
$relationship->execute(['installation_id' => $installationId]);
$relationshipState = $relationship->fetch();
$moments = $pdo->prepare('SELECT COUNT(*) FROM world_story_moments WHERE installation_id = :installation_id AND status = "active"');
$moments->execute(['installation_id' => $installationId]);

if ((int) $threadState['chapter'] !== 2 || (int) $threadState['progress_count'] !== 4) {
    throw new RuntimeException('Story thread did not advance into chapter 2.');
}
if ((string) $relationshipState['relationship_stage'] !== 'familiar_presence' || (int) $relationshipState['trust_count'] !== 4) {
    throw new RuntimeException('Caretaker relationship did not advance.');
}
if ((int) $moments->fetchColumn() !== 4) {
    throw new RuntimeException('Expected four durable story moments.');
}

$reversalId = '30000000-0000-4000-8000-000000000001';
$reversal = [
    'id' => $reversalId,
    'event_name' => 'Quests.QuestCompletionReversed',
    'event_version' => 1,
    'account_id' => $accountId,
    'payload_json' => json_encode(['completion_event_id' => $completionEvents[3]], JSON_THROW_ON_ERROR),
];
$consumer->consume($reversal);
$consumer->consume($reversal);

$thread->execute(['installation_id' => $installationId]);
$threadState = $thread->fetch();
$relationship->execute(['installation_id' => $installationId]);
$relationshipState = $relationship->fetch();
$reversed = $pdo->prepare('SELECT COUNT(*) FROM world_story_moments WHERE installation_id = :installation_id AND status = "reversed"');
$reversed->execute(['installation_id' => $installationId]);
$receipt = $pdo->prepare('SELECT COUNT(*) FROM world_event_receipts WHERE event_id = :event_id');
$receipt->execute(['event_id' => $reversalId]);

if ((int) $threadState['chapter'] !== 1 || (int) $threadState['progress_count'] !== 3) {
    throw new RuntimeException('Story reversal did not recalculate chapter progress.');
}
if ((string) $relationshipState['relationship_stage'] !== 'familiar_presence' || (int) $relationshipState['trust_count'] !== 3) {
    throw new RuntimeException('Relationship reversal did not recalculate trust.');
}
if ((int) $reversed->fetchColumn() !== 1 || (int) $receipt->fetchColumn() !== 1) {
    throw new RuntimeException('Reversal was not durable and idempotent.');
}

echo json_encode([
    'chapter' => (int) $threadState['chapter'],
    'progress_count' => (int) $threadState['progress_count'],
    'relationship_stage' => (string) $relationshipState['relationship_stage'],
    'trust_count' => (int) $relationshipState['trust_count'],
    'reversed_moments' => 1,
    'idempotent' => true,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
