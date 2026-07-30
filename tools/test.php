<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require KORAVIK_ROOT . '/tests/TestRunner.php';
require KORAVIK_ROOT . '/tests/ReleaseSuite.php';

use Koravik\Tests\ReleaseSuite;
use Koravik\Tests\TestRunner;

if (PHP_SAPI === 'cli') {
    session_save_path(sys_get_temp_dir());
}
\Koravik\Platform\Security\Security::startSession();

$runner = new TestRunner();
(new ReleaseSuite($runner, database()->pdo()))->register();
exit($runner->finish());
