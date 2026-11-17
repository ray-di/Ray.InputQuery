<?php

declare(strict_types=1);

namespace Ray\InputQuery\Attribute;

use Attribute;

/**
 * Fills the parameter from the input query, building an object when its type is a class
 *
 * @see https://bearsunday.github.io/manuals/1.0/en/resource_param.html#rayinputquery-integration
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class Input
{
    /** @param string|null $item Class name for array items */
    public function __construct(
        public readonly string|null $item = null,
    ) {
    }
}
