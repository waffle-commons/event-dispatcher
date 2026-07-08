<?php

declare(strict_types=1);

namespace Waffle\Commons\EventDispatcher\Dispatcher;

use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Waffle\Commons\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private ListenerProviderInterface $listenerProvider,
    ) {}

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function dispatch(object $event): object
    {
        // POLICY-05 / inherent: Mago mis-resolves PSR-14's `@return iterable<callable>`
        // stub into the contradictory type `iterable<mixed,mixed>[(callable…)]`,
        // which it then reports as BOTH "not iterable" and "not callable". No source
        // narrowing can satisfy it (a typed @var is rejected as having "no overlap"),
        // so this is a documented, irreducible scoped ignore — not a baseline.
        // @mago-ignore analysis:invalid-iterator,mixed-assignment
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            // @mago-ignore analysis:invalid-callable
            $listener($event);

            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }
}
