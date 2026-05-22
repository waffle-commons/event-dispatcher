<?php

declare(strict_types=1);

namespace Waffle\Commons\EventDispatcher\Provider;

use Psr\EventDispatcher\ListenerProviderInterface;
use Waffle\Commons\EventDispatcher\Attribute\AsEventListener;

final class ListenerProvider implements ListenerProviderInterface
{
    /**
     * @var array<string, list<array{0: int, 1: callable}>>
     */
    private array $listeners = [];

    /**
     * Registers a listener for a given event.
     *
     * @param string $eventClass The fully qualified class name of the event.
     * @param callable $listener The callable to invoke when the event is dispatched.
     * @param int $priority Higher priority listeners are executed first.
     */
    public function addListener(string $eventClass, callable $listener, int $priority = 0): void
    {
        if (!array_key_exists($eventClass, $this->listeners)) {
            $this->listeners[$eventClass] = [];
        }

        $this->listeners[$eventClass][] = [$priority, $listener];
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
                /** @var callable $classCallable */
                $classCallable = [$listener, $method->getName()];
                $this->addListener($eventClass, $classCallable, $instance->priority);
                break;
            }
        }

        // Check method-level attributes
        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(AsEventListener::class);

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();

                $eventClass = $instance->event;

                if ($eventClass === null) {
                    $eventClass = $this->resolveEventFromTypeHint($method);
                }

                if ($eventClass !== null) {
                    // Create a callable from the object and method name
                    /** @var callable $callable */
                    $callable = [$listener, $method->getName()];
                    $this->addListener($eventClass, $callable, $instance->priority);
                }
            }
        }
    }

    /**
     * Gets all listeners for the given event.
     *
     * @return list<callable>
     */
    #[\Override]
    // @mago-ignore analysis:incompatible-return-type
    public function getListenersForEvent(object $event): iterable
    {
        $classes = [$event::class];
        $parent = get_parent_class($event);
        while ($parent !== false) {
            $classes[] = $parent;
            $parent = get_parent_class($parent);
        }

        $listeners = [];
        foreach ($classes as $eventClass) {
            foreach ($this->listeners[$eventClass] ?? [] as $listenerPair) {
                $listeners[] = $listenerPair[1];
            }
        }

        return $listeners;
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

        $type = ($params[0] ?? null)?->getType();

        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return $type->getName();
    }
}
