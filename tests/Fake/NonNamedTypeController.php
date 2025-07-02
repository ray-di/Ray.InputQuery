<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

final class NonNamedTypeController
{
    public function processUnionType(
        string|int $unionParam = 'default',  // Union type - not ReflectionNamedType
        ?TestService $service = null
    ): string {
        return (string)$unionParam . ':' . ($service ? $service->getValue() : 'null');
    }
}