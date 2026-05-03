<?php

declare(strict_types=1);

namespace Waffle\Commons\EventDispatcher\Event;

use Psr\EventDispatcher\StoppableEventInterface;

abstract class AbstractStoppableEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
