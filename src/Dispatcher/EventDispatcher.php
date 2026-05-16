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
