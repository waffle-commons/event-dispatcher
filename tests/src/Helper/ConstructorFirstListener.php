<?php

declare(strict_types=1);

namespace WaffleTests\Commons\EventDispatcher\Helper;

use Waffle\Commons\EventDispatcher\Attribute\AsEventListener;

/**
 * Class-level AsEventListener attribute whose first public method is the
 * constructor.
 *
 * Reflection iterates public methods and must `continue` past `__construct`
 * (line 55 of ListenerProvider) before binding to the next public method.
 */
#[AsEventListener(event: TestStoppableEvent::class, priority: 3)]
final class ConstructorFirstListener
{
    public function __construct() {}

    public function handle(): void {}
}
