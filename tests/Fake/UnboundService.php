<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

final class UnboundService
{
    public function __construct(
        private string $config = 'default'
    ) {}

    public function getConfig(): string
    {
        return $this->config;
    }
}