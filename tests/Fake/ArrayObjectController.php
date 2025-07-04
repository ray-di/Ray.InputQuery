<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use ArrayObject;
use Ray\InputQuery\Attribute\Input;

final class ArrayObjectController
{
    /**
     * Test ArrayObject creation
     */
    public function processArrayObject(#[Input(item: UserInput::class)] ArrayObject $users): void
    {
        // Test ArrayObject creation with item type
    }

    /**
     * Test ArrayObject without item type
     */
    public function processArrayObjectNoItem(#[Input] ArrayObject $items): void
    {
        // Test ArrayObject without item type - should return null and fall back
    }

    /**
     * Test custom ArrayObject subclass
     */
    public function processCustomArrayObject(#[Input(item: UserInput::class)] UserArrayObject $users): void
    {
        // Test custom ArrayObject subclass
    }
}