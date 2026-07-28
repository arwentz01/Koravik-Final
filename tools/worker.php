<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Koravik\Platform\Events\OutboxWorker;
use Koravik\Worlds\EpicOrdinary\EpicOrdinaryConsumer;

$limit = isset($argv[1]) ? (int) $argv[1] : 10;
$result = (new OutboxWorker(database(), new EpicOrdinaryConsumer(database())))->run($limit);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
