<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use ArrayObject;

/**
 * Custom ArrayObject subclass for testing
 *
 * @extends ArrayObject<int, UserInput>
 */
final class UserArrayObject extends ArrayObject
{
    /**
     * @param array<UserInput> $array
     */
    public function __construct(array $array = [])
    {
        parent::__construct($array);
    }
}