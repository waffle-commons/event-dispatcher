<?php

declare(strict_types=1);

namespace WaffleTests\Commons\EventDispatcher;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\EventDispatcher\Dispatcher\EventDispatcher;
use Waffle\Commons\EventDispatcher\Event\AbstractStoppableEvent;
use Waffle\Commons\EventDispatcher\Provider\ListenerProvider;
use WaffleTests\Commons\EventDispatcher\Helper\TestStoppableEvent;

#[CoversClass(EventDispatcher::class)]
#[CoversClass(ListenerProvider::class)]
final class EventDispatcherTest extends AbstractTestCase
{
    public function testDispatchWithNoListeners(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new \stdClass();
        $result = $dispatcher->dispatch($event);

        static::assertSame($event, $result);
    }

    public function testDispatchInvokesListener(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new \stdClass();
        $called = false;

        $provider->addListener(\stdClass::class, static function () use (&$called): void {
            $called = true;
        });

        $dispatcher->dispatch($event);

        static::assertTrue($called);
    }

    public function testDispatchRespectsPriorityOrder(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $executionOrder = [];

        $provider->addListener(
            \stdClass::class,
            static function () use (&$executionOrder): void {
                $executionOrder[] = 'low';
            },
            priority: 0,
        );

        $provider->addListener(
            \stdClass::class,
            static function () use (&$executionOrder): void {
                $executionOrder[] = 'high';
            },
            priority: 10,
        );

        $provider->addListener(
            \stdClass::class,
            static function () use (&$executionOrder): void {
                $executionOrder[] = 'medium';
            },
            priority: 5,
        );

        $dispatcher->dispatch(new \stdClass());

        static::assertSame(['high', 'medium', 'low'], $executionOrder);
    }

    public function testStoppableEventHaltsPropagation(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new TestStoppableEvent();
        $executionOrder = [];

        $provider->addListener(
            TestStoppableEvent::class,
            static function ($e) use (&$executionOrder): void {
                $executionOrder[] = 'first';
                /** @var AbstractStoppableEvent $e */
                $e->stopPropagation();
            },
            priority: 10,
        );

        $provider->addListener(
            TestStoppableEvent::class,
            static function () use (&$executionOrder): void {
                $executionOrder[] = 'second';
            },
            priority: 5,
        );

        $provider->addListener(
            TestStoppableEvent::class,
            static function () use (&$executionOrder): void {
                $executionOrder[] = 'third';
            },
            priority: 0,
        );

        $dispatcher->dispatch($event);

        static::assertSame(['first'], $executionOrder);
        static::assertTrue($event->isPropagationStopped());
    }

    public function testMultipleListenersForSameEvent(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $count = 0;

        $provider->addListener(\stdClass::class, static function () use (&$count): void {
            $count++;
        });

        $provider->addListener(\stdClass::class, static function () use (&$count): void {
            $count++;
        });

        $provider->addListener(\stdClass::class, static function () use (&$count): void {
            $count++;
        });

        $dispatcher->dispatch(new \stdClass());

        static::assertSame(3, $count);
    }

    public function testListenerCanMutateEvent(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new class {
            public string $value = 'original';
        };

        $provider->addListener($event::class, static function ($e): void {
            $e->value = 'modified';
        });

        $result = $dispatcher->dispatch($event);

        // @mago-ignore analysis:ambiguous-object-property-access
        static::assertSame('modified', $result->value);
    }

    public function testDispatcherIsWorkerSafeNoMemoryLeak(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $provider->addListener(\stdClass::class, static function (): void {});

        // Simulate 1000 request cycles
        for ($i = 0; $i < 1000; $i++) {
            $event = new \stdClass();
            $dispatcher->dispatch($event);
            unset($event);
        }

        // Provider should not have accumulated any state from dispatched events
        /** @var callable[] $listeners */
        $listeners = iterator_to_array($provider->getListenersForEvent(new \stdClass()));
        static::assertCount(1, $listeners);
    }

    public function testDifferentEventTypesAreIsolated(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $eventACalled = false;
        $eventBCalled = false;

        $provider->addListener(\stdClass::class, static function () use (&$eventACalled): void {
            $eventACalled = true;
        });

        $provider->addListener(TestStoppableEvent::class, static function () use (&$eventBCalled): void {
            $eventBCalled = true;
        });

        $dispatcher->dispatch(new \stdClass());

        static::assertTrue($eventACalled);
        static::assertFalse($eventBCalled);
    }
}
