<?php

declare(strict_types=1);

namespace Koravik\Platform\Events;

interface EventConsumer
{
    public function consume(array $event): void;
}
