<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\InputQuery\Attribute\Input;

final class MixedInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email,
        private TestService $service
    ) {}

    public function getService(): TestService
    {
        return $this->service;
    }
}