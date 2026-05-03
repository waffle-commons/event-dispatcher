<?php

declare(strict_types=1);

namespace WaffleTests\Commons\EventDispatcher\Helper;

use Waffle\Commons\EventDispatcher\Attribute\AsEventListener;
use Waffle\Commons\EventDispatcher\Event\AbstractStoppableEvent;

final readonly class TypeHintedTestListener
{
    #[AsEventListener(priority: 5)]
    public function handle(AbstractStoppableEvent $event): void {}
}
