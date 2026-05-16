<?php

declare(strict_types=1);

namespace WaffleTests\Commons\EventDispatcher\Helper;

use Waffle\Commons\EventDispatcher\Attribute\AsEventListener;

/**
 * Class-level AsEventListener attribute with `event = null`.
 *
 * Triggers the `continue` branch (line 49 of ListenerProvider) where a
 * class-level attribute without an event class is skipped silently.
 */
#[AsEventListener]
final readonly class NullEventClassListener
{
    public function handle(): void {}
}
