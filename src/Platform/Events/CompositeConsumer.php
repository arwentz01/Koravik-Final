<?php

declare(strict_types=1);

namespace Koravik\Platform\Events;

final class CompositeConsumer implements EventConsumer
{
    /** @param list<EventConsumer> $consumers */
    public function __construct(private readonly array $consumers)
    {
    }

    public function consume(array $event): void
    {
        foreach ($this->consumers as $consumer) {
            $consumer->consume($event);
        }
    }
}
