<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Koravik\Worlds\EpicOrdinary\EpicOrdinaryConsumer;

$consumer = new EpicOrdinaryConsumer(database());
$accountId = '00000000-0000-4000-8000-000000000001';
$completionEvents = [];

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

$pdo = database()->pdo();
$thread = $pdo->query('SELECT chapter, progress_count FROM world_story_threads LIMIT 1')->fetch();
$relationship = $pdo->query('SELECT relationship_stage, trust_count FROM world_relationships WHERE character_key = "caretaker" LIMIT 1')->fetch();
$moments = (int) $pdo->query('SELECT COUNT(*) FROM world_story_moments WHERE status = "active"')->fetchColumn();

if ((int) $thread['chapter'] !== 2 || (int) $thread['progress_count'] !== 4) {
    throw new RuntimeException('Story thread did not advance into chapter 2.');
}
if ((string) $relationship['relationship_stage'] !== 'familiar_presence' || (int) $relationship['trust_count'] !== 4) {
    throw new RuntimeException('Caretaker relationship did not advance.');
}
if ($moments !== 4) {
    throw new RuntimeException('Expected four durable story moments.');
}

$reversalId = '30000000-0000-4000-8000-000000000001';
$consumer->consume([
    'id' => $reversalId,
    'event_name' => 'Quests.QuestCompletionReversed',
    'event_version' => 1,
    'account_id' => $accountId,
    'payload_json' => json_encode(['completion_event_id' => $completionEvents[3]], JSON_THROW_ON_ERROR),
]);
$consumer->consume([
    'id' => $reversalId,
    'event_name' => 'Quests.QuestCompletionReversed',
    'event_version' => 1,
    'account_id' => $accountId,
    'payload_json' => json_encode(['completion_event_id' => $completionEvents[3]], JSON_THROW_ON_ERROR),
]);

$thread = $pdo->query('SELECT chapter, progress_count FROM world_story_threads LIMIT 1')->fetch();
$relationship = $pdo->query('SELECT relationship_stage, trust_count FROM world_relationships WHERE character_key = "caretaker" LIMIT 1')->fetch();
$reversed = (int) $pdo->query('SELECT COUNT(*) FROM world_story_moments WHERE status = "reversed"')->fetchColumn();
$receiptCount = (int) $pdo->query('SELECT COUNT(*) FROM world_event_receipts WHERE event_id = "30000000-0000-4000-8000-000000000001"')->fetchColumn();

if ((int) $thread['chapter'] !== 1 || (int) $thread['progress_count'] !== 3) {
    throw new RuntimeException('Story reversal did not recalculate chapter progress.');
}
if ((string) $relationship['relationship_stage'] !== 'familiar_presence' || (int) $relationship['trust_count'] !== 3) {
    throw new RuntimeException('Relationship reversal did not recalculate trust.');
}
if ($reversed !== 1 || $receiptCount !== 1) {
    throw new RuntimeException('Reversal was not durable and idempotent.');
}

echo json_encode([
    'chapter' => (int) $thread['chapter'],
    'progress_count' => (int) $thread['progress_count'],
    'relationship_stage' => (string) $relationship['relationship_stage'],
    'trust_count' => (int) $relationship['trust_count'],
    'reversed_moments' => $reversed,
    'idempotent' => true,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
