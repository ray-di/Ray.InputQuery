<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\InputQuery\Attribute\Input;

final class UserInputWithAttribute
{
    public function __construct(
        #[Input]
        public readonly string $id,
        #[Input]
        public readonly string $name
    ) {
    }
}