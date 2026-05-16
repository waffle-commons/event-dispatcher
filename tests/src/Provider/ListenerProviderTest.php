<?php

declare(strict_types=1);

namespace WaffleTests\Commons\EventDispatcher;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\EventDispatcher\Attribute\AsEventListener;
use Waffle\Commons\EventDispatcher\Event\AbstractStoppableEvent;
use Waffle\Commons\EventDispatcher\Provider\ListenerProvider;
use WaffleTests\Commons\EventDispatcher\Helper\AnnotatedTestListener;
use WaffleTests\Commons\EventDispatcher\Helper\TestStoppableEvent;
use WaffleTests\Commons\EventDispatcher\Helper\TypeHintedTestListener;

#[CoversClass(ListenerProvider::class)]
#[CoversClass(AsEventListener::class)]
#[CoversClass(AbstractStoppableEvent::class)]
final class ListenerProviderTest extends AbstractTestCase
{
    public function testAddListenerWithPriority(): void
    {
        $provider = new ListenerProvider();
        $provider->addListener(\stdClass::class, static fn() => 'a', priority: 0);
        $provider->addListener(\stdClass::class, static fn() => 'b', priority: 10);
        $provider->addListener(\stdClass::class, static fn() => 'c', priority: 5);

        /** @var callable[] $listeners */
        $listeners = iterator_to_array($provider->getListenersForEvent(new \stdClass()));

        static::assertCount(3, $listeners);
        // @mago-ignore analysis:possibly-undefined-int-array-index
        // @mago-ignore analysis:invalid-callable
        static::assertSame('b', $listeners[0]());
        // @mago-ignore analysis:possibly-undefined-int-array-index
        // @mago-ignore analysis:invalid-callable
        static::assertSame('c', $listeners[1]());
        // @mago-ignore analysis:possibly-undefined-int-array-index
        // @mago-ignore analysis:invalid-callable
        static::assertSame('a', $listeners[2]());
    }

    public function testGetListenersForEventReturnsEmptyForUnknownEvent(): void
    {
        $provider = new ListenerProvider();
        /** @var callable[] $listeners */
        $listeners = iterator_to_array($provider->getListenersForEvent(new \stdClass()));

        static::assertSame([], $listeners);
    }

    public function testRegisterFromAttributeAnnotatedClass(): void
    {
        $provider = new ListenerProvider();
        $listener = new AnnotatedTestListener();

        $provider->register($listener);

        /** @var callable[] $listeners */
        $listeners = iterator_to_array($provider->getListenersForEvent(new TestStoppableEvent()));

        static::assertCount(1, $listeners);
    }

    public function testRegisterInfersEventClassFromTypeHint(): void
    {
        $provider = new ListenerProvider();
        $listener = new TypeHintedTestListener();

        $provider->register($listener);

        /** @var callable[] $listeners */
        $listeners = iterator_to_array($provider->getListenersForEvent(new TestStoppableEvent()));

        static::assertCount(1, $listeners);
    }

    public function testStoppableEventImplementation(): void
    {
        $event = new TestStoppableEvent();

        static::assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        static::assertTrue($event->isPropagationStopped());
    }

    public function testAsEventListenerAttributeDefaults(): void
    {
        $attr = new AsEventListener();

        static::assertNull($attr->event);
        static::assertSame(0, $attr->priority);
    }

    public function testAsEventListenerAttributeWithValues(): void
    {
        $attr = new AsEventListener(event: TestStoppableEvent::class, priority: 42);

        static::assertSame(TestStoppableEvent::class, $attr->event);
        static::assertSame(42, $attr->priority);
    }
}
