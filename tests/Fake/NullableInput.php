<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\InputQuery\Attribute\Input;

final class NullableInput
{
    public function __construct(
        #[Input] public readonly ?string $name = null,
        #[Input] public readonly ?int $age = null,
        #[Input] public readonly ?bool $active = null
    ) {}
}