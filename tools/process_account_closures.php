<?php

declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

$limit=max(1,min(25,(int)($argv[1]??5)));
$count=(new Koravik\Platform\AccountData\AccountDataService(database()))->processDue($limit);
echo json_encode(['processed'=>$count,'limit'=>$limit],JSON_THROW_ON_ERROR).PHP_EOL;
