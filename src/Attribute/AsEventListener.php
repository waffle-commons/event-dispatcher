<?php

declare(strict_types=1);

namespace Waffle\Commons\EventDispatcher\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class AsEventListener
{
    public function __construct(
        public ?string $event = null,
        public int $priority = 0,
    ) {}
}
