<?php

declare(strict_types=1);

namespace Ray\InputQuery\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Input
{
    public function __construct(
        public readonly string|null $item = null,
    ) {
    }
}
