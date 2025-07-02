<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\InputQuery\Attribute\Input;

final class TodoController
{
    public function create(#[Input] TodoInput $todo): string
    {
        return 'Created: ' . $todo->title . ' by ' . $todo->author->name;
    }
}