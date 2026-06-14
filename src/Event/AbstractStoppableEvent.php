<?php

declare(strict_types=1);

namespace Waffle\Commons\EventDispatcher\Event;

use IgorPhp\IgorBundle\Attribute\WorkerSafe;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Events are per-dispatch value objects (PSR-14): a fresh instance is created
 * for every dispatch and discarded with the request, so the propagation flag
 * never survives a FrankenPHP worker iteration — it is not shared service state.
 */
abstract class AbstractStoppableEvent implements StoppableEventInterface
{
    #[WorkerSafe(
        reason: 'per-dispatch event value object; instantiated per dispatch, never a shared service (PSR-14 StoppableEventInterface)',
    )]
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
