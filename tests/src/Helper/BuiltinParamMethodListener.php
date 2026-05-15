<?php

declare(strict_types=1);

namespace WaffleTests\Commons\EventDispatcher\Helper;

use Waffle\Commons\EventDispatcher\Attribute\AsEventListener;

/**
 * Method-level AsEventListener with `event = null` on a method whose first
 * parameter is a builtin type.
 *
 * Exercises `resolveEventFromTypeHint` returning `null` because the type is
 * builtin / not a named class (line 127 of ListenerProvider).
 */
final readonly class BuiltinParamMethodListener
{
    #[AsEventListener]
    public function handle(string $event): void {}
}
