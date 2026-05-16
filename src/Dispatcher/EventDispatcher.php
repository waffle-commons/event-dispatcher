<?php

declare(strict_types=1);

namespace Waffle\Commons\EventDispatcher\Dispatcher;

use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Waffle\Commons\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        // @mago-ignore analysis:unused-property -- read via $this->listenerProvider in dispatch(); mago miscounts constructor-promoted readonly reads
        private ListenerProviderInterface $listenerProvider,
    ) {}

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function dispatch(object $event): object
    {
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            $listener($event);

            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }
}
