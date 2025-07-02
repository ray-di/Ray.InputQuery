<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\InputQuery\Attribute\Input;

final class CustomTypeInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly mixed $customValue = null  // This will trigger default case in convertScalar
    ) {}
}