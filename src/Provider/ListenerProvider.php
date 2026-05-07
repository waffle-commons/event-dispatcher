<?php

declare(strict_types=1);

namespace Waffle\Commons\EventDispatcher\Provider;

use Psr\EventDispatcher\ListenerProviderInterface;
use Waffle\Commons\EventDispatcher\Attribute\AsEventListener;

final class ListenerProvider implements ListenerProviderInterface
{
    /**
     * @var array<string, array<int, callable>>
     */
    private array $listeners = [];

    /**
     * Registers a listener for a given event.
     *
     * @param class-string<object> $eventClass The fully qualified class name of the event.
     * @param callable $listener The callable to invoke when the event is dispatched.
     * @param int $priority Higher priority listeners are executed first.
     */
    public function addListener(string $eventClass, callable $listener, int $priority = 0): void
    {
        // @mago-ignore lint:no-isset
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }

        $this->listeners[$eventClass][] = [$priority, $listener];

        // @mago-ignore analysis:possibly-undefined-int-array-index,possibly-undefined-int-array-index
        usort($this->listeners[$eventClass], static fn(array $a, array $b): int => $b[0] <=> $a[0]);
    }

    /**
     * Registers a listener from an object that may have #[AsEventListener] attributes.
     */
    public function register(object $listener): void
    {
        $reflection = new \ReflectionObject($listener);

        // Check class-level attributes
        $classAttributes = $reflection->getAttributes(AsEventListener::class);
        foreach ($classAttributes as $attribute) {
            /** @var AsEventListener $instance */
            $instance = $attribute->newInstance();

            $eventClass = $instance->event;

            if ($eventClass === null) {
                continue;
            }

            // Use the first public method as the handler
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getName() === '__construct') {
                    continue;
                }
                $this->addListener($eventClass, [$listener, $method->getName()], $instance->priority);
                break;
            }
        }

        // Check method-level attributes
        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(AsEventListener::class);

            foreach ($attributes as $attribute) {
                /** @var AsEventListener $instance */
                $instance = $attribute->newInstance();

                $eventClass = $instance->event;

                if ($eventClass === null) {
                    $eventClass = $this->resolveEventFromTypeHint($method);
                }

                if ($eventClass !== null) {
                    $this->addListener($eventClass, [$listener, $method->getName()], $instance->priority);
                }
            }
        }
    }

    #[\Override]
    public function getListenersForEvent(object $event): iterable
    {
        $classes = [$event::class];
        $parent = get_parent_class($event);
        while ($parent !== false) {
            $classes[] = $parent;
            $parent = get_parent_class($parent);
        }

        foreach ($classes as $eventClass) {
            // @mago-ignore lint:no-isset
            if (!isset($this->listeners[$eventClass])) {
                continue;
            }

            // @mago-ignore analysis:possibly-undefined-string-array-index
            foreach ($this->listeners[$eventClass] as $entry) {
                yield $entry[1];
            }
        }
    }

    /**
     * Resolves the event class from the method's first type-hinted parameter.
     */
    private function resolveEventFromTypeHint(\ReflectionMethod $method): ?string
    {
        $params = $method->getParameters();

        if ($params === []) {
            return null;
        }

        // @mago-ignore analysis:possibly-undefined-int-array-index
        $type = $params[0]?->getType();

        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return $type->getName();
    }
}
