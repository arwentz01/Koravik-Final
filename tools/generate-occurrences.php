<?php

declare(strict_types=1);

use Koravik\Districts\Quests\RecurrenceService;

require dirname(__DIR__) . '/bootstrap.php';

$daysAhead = isset($argv[1]) ? max(1, min(730, (int) $argv[1])) : 90;
$limit = isset($argv[2]) ? max(1, min(500, (int) $argv[2])) : 100;

$generated = (new RecurrenceService(database()))->generateAll($daysAhead, $limit);
echo json_encode(['generated' => $generated, 'days_ahead' => $daysAhead, 'quest_limit' => $limit], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
