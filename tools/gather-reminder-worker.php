<?php

declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';

use Koravik\Districts\Gather\GatherLifecycleService;

$limit=max(1,min(500,(int)($argv[1]??100)));
$count=(new GatherLifecycleService(database()))->queueDueReminders($limit);
echo 'Queued '.$count.' Gather agenda reminder(s).'.PHP_EOL;