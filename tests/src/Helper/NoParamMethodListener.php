<?php

declare(strict_types=1);

namespace WaffleTests\Commons\EventDispatcher\Helper;

use Waffle\Commons\EventDispatcher\Attribute\AsEventListener;

/**
 * Method-level AsEventListener with `event = null` on a method that has no
 * parameters.
 *
 * Exercises `resolveEventFromTypeHint` returning `null` because the parameter
 * list is empty (line 121 of ListenerProvider).
 */
final readonly class NoParamMethodListener
{
    #[AsEventListener]
    public function handle(): void {}
}
