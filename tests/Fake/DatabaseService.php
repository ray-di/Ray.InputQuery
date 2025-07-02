<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

final class DatabaseService
{
    public function __construct(
        private string $connectionString
    ) {}

    public function getConnectionString(): string
    {
        return $this->connectionString;
    }
}