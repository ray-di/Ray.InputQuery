<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\InputQuery\Attribute\Input;

final class DefaultValuesInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly ?string $email = null,
        #[Input] public readonly int $age = 25,
        #[Input] public readonly bool $active = true,
        #[Input] public readonly float $score = 0.0
    ) {}
}