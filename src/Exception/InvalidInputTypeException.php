<?php

declare(strict_types=1);

namespace Ray\InputQuery\Exception;

use InvalidArgumentException;
use Throwable;

use function get_debug_type;

final class InvalidInputTypeException extends InvalidArgumentException
{
    public function __construct(
        public readonly string $paramName,
        public readonly string $expectedType,
        public readonly string $actualType,
        public readonly int|string|null $itemKey = null,
        int $code = 0,
        Throwable|null $previous = null,
    ) {
        parent::__construct('', $code, $previous);
    }

    public static function forParameter(string $paramName, mixed $actualValue): self
    {
        return new self($paramName, 'array', get_debug_type($actualValue));
    }

    public static function forItem(string $paramName, int|string $itemKey, mixed $actualValue): self
    {
        return new self($paramName, 'array', get_debug_type($actualValue), $itemKey);
    }
}
