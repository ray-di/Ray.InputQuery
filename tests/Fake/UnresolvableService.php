<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

final class UnresolvableService
{
    public function __construct(
        private NotExistentService $dependency  // This service doesn't exist and can't be auto-wired
    ) {}

    public function getDependency(): NotExistentService
    {
        return $this->dependency;
    }
}