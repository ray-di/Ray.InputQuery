<?php

declare(strict_types=1);

namespace Ray\InputQuery\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Input
{
    /** @param string|null $item Class name for array items */
    public function __construct(
        public readonly string|null $item = null,
    ) {
    }
}
