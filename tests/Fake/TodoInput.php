<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\InputQuery\Attribute\Input;

final class TodoInput
{
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly AuthorInput $author
    ) {}
}