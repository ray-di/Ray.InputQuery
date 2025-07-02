<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

final class TestService
{
    public function __construct(
        private string $value = 'default'
    ) {}

    public function getValue(): string
    {
        return $this->value;
    }
}