<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\InputQuery\Attribute\Input;

final class ScalarInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly int $age,
        #[Input] public readonly float $price,
        #[Input] public readonly bool $active
    ) {}
}