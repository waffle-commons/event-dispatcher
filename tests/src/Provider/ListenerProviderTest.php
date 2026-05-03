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

        $listeners = iterator_to_array($provider->getListenersForEvent(new \stdClass()));

        $this->assertCount(3, $listeners);
        $this->assertSame('b', $listeners[0]());
        $this->assertSame('c', $listeners[1]());
        $this->assertSame('a', $listeners[2]());
    }

    public function testGetListenersForEventReturnsEmptyForUnknownEvent(): void
    {
        $provider = new ListenerProvider();
        $listeners = iterator_to_array($provider->getListenersForEvent(new \stdClass()));

        $this->assertSame([], $listeners);
    }

    public function testRegisterFromAttributeAnnotatedClass(): void
    {
        $provider = new ListenerProvider();
        $listener = new AnnotatedTestListener();

        $provider->register($listener);

        $listeners = iterator_to_array($provider->getListenersForEvent(new TestStoppableEvent()));

        $this->assertCount(1, $listeners);
    }

    public function testRegisterInfersEventClassFromTypeHint(): void
    {
        $provider = new ListenerProvider();
        $listener = new TypeHintedTestListener();

        $provider->register($listener);

        $listeners = iterator_to_array($provider->getListenersForEvent(new TestStoppableEvent()));

        $this->assertCount(1, $listeners);
    }

    public function testStoppableEventImplementation(): void
    {
        $event = new TestStoppableEvent();

        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }

    public function testAsEventListenerAttributeDefaults(): void
    {
        $attr = new AsEventListener();

        $this->assertNull($attr->event);
        $this->assertSame(0, $attr->priority);
    }

    public function testAsEventListenerAttributeWithValues(): void
    {
        $attr = new AsEventListener(event: TestStoppableEvent::class, priority: 42);

        $this->assertSame(TestStoppableEvent::class, $attr->event);
        $this->assertSame(42, $attr->priority);
    }
}
