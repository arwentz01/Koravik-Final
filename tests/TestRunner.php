<?php

declare(strict_types=1);

namespace Koravik\Tests;

use Throwable;

final class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;

    public function test(string $name, callable $test): void
    {
        try {
            $test();
            $this->passed++;
            echo "PASS {$name}\n";
        } catch (Throwable $error) {
            $this->failed++;
            echo "FAIL {$name}: {$error->getMessage()}\n";
        }
    }

    public function assert(bool $condition, string $message): void
    {
        if (!$condition) throw new \RuntimeException($message);
    }

    public function finish(): int
    {
        echo "\n{$this->passed} passed, {$this->failed} failed\n";
        return $this->failed === 0 ? 0 : 1;
    }
}
