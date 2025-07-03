<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use ArrayObject;

final class CustomArrayObject extends ArrayObject
{
    public function getFirst(): mixed
    {
        return $this[0] ?? null;
    }
}