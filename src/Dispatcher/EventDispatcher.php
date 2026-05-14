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
        // @mago-ignore analysis:invalid-iterator -- PSR ListenerProviderInterface docblock (@return iterable[callable]) is malformed; runtime is correct
        // @mago-ignore analysis:mixed-assignment -- listener type is callable per PSR contract; vendor docblock is unparseable by Mago
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            // @mago-ignore analysis:invalid-callable -- $listener is callable per PSR-14 contract
            $listener($event);

            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }
}
