<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Koravik\Platform\Events\CompositeConsumer;
use Koravik\Platform\Events\OutboxWorker;
use Koravik\Platform\Experience\ExperienceConsumer;
use Koravik\Platform\Notifications\NotificationConsumer;
use Koravik\Worlds\EpicOrdinary\EpicOrdinaryConsumer;

$limit = min(100, max(1, isset($argv[1]) ? (int) $argv[1] : 10));
$consumer = new CompositeConsumer([
    new ExperienceConsumer(database()),
    new EpicOrdinaryConsumer(database()),
    new NotificationConsumer(database()),
]);
$result = (new OutboxWorker(database(), $consumer))->run($limit);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
