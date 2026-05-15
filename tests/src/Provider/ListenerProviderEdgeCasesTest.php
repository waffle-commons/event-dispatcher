<?php

declare(strict_types=1);

namespace WaffleTests\Commons\EventDispatcher\Provider;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\EventDispatcher\Attribute\AsEventListener;
use Waffle\Commons\EventDispatcher\Provider\ListenerProvider;
use WaffleTests\Commons\EventDispatcher\AbstractTestCase;
use WaffleTests\Commons\EventDispatcher\Helper\BuiltinParamMethodListener;
use WaffleTests\Commons\EventDispatcher\Helper\ConstructorFirstListener;
use WaffleTests\Commons\EventDispatcher\Helper\NoParamMethodListener;
use WaffleTests\Commons\EventDispatcher\Helper\NullEventClassListener;
use WaffleTests\Commons\EventDispatcher\Helper\TestStoppableEvent;

#[CoversClass(ListenerProvider::class)]
#[CoversClass(AsEventListener::class)]
final class ListenerProviderEdgeCasesTest extends AbstractTestCase
{
    /**
     * A class-level #[AsEventListener] with `event = null` must be silently
     * skipped (line 49 `continue;`). The provider stays empty.
     */
    public function testRegisterSkipsClassLevelAttributeWithoutEvent(): void
    {
        $provider = new ListenerProvider();
        $provider->register(new NullEventClassListener());

        /** @var callable[] $listeners */
        $listeners = iterator_to_array($provider->getListenersForEvent(new TestStoppableEvent()));

        static::assertSame([], $listeners);
    }

    /**
     * When iterating the class's public methods to bind a class-level attribute,
     * the constructor must be skipped (line 55 `continue;`). The provider must
     * still attach the next public method (`handle`).
     */
    public function testRegisterSkipsConstructorWhenBindingClassLevelAttribute(): void
    {
        $provider = new ListenerProvider();
        $provider->register(new ConstructorFirstListener());

        /** @var callable[] $listeners */
        $listeners = iterator_to_array($provider->getListenersForEvent(new TestStoppableEvent()));

        static::assertCount(1, $listeners);
    }

    /**
     * Method-level #[AsEventListener] with `event = null` on a method with no
     * parameters cannot infer an event class — `resolveEventFromTypeHint`
     * returns `null` (line 121) and the method is not registered.
     */
    public function testRegisterDoesNotBindMethodWithNoParameters(): void
    {
        $provider = new ListenerProvider();
        $provider->register(new NoParamMethodListener());

        /** @var callable[] $listeners */
        $listeners = iterator_to_array($provider->getListenersForEvent(new TestStoppableEvent()));

        static::assertSame([], $listeners);
    }

    /**
     * Method-level #[AsEventListener] with `event = null` on a method whose
     * first parameter is a builtin type — `resolveEventFromTypeHint` returns
     * `null` (line 127) and the method is not registered.
     */
    public function testRegisterDoesNotBindMethodWithBuiltinParameterType(): void
    {
        $provider = new ListenerProvider();
        $provider->register(new BuiltinParamMethodListener());

        /** @var callable[] $listeners */
        $listeners = iterator_to_array($provider->getListenersForEvent(new TestStoppableEvent()));

        static::assertSame([], $listeners);
    }
}
