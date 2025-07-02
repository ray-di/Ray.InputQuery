<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\InputQuery\Attribute\Input;

final class UnionTypeInput
{
    public function __construct(
        #[Input] public readonly string|int $value = 'default',
        #[Input] public readonly ?string $name = null
    ) {}
}