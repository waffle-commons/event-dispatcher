<?php

declare(strict_types=1);

namespace WaffleTests\Commons\EventDispatcher\Helper;

use Waffle\Commons\EventDispatcher\Attribute\AsEventListener;
use Waffle\Commons\EventDispatcher\Event\AbstractStoppableEvent;

#[AsEventListener(event: TestStoppableEvent::class, priority: 10)]
final readonly class AnnotatedTestListener
{
    public function handle(): void {}
}
