<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

final class NonInputParameterController
{
    public function process(
        TestService $service,           // No #[Input] - object from DI
        ?string $nonInputParam = null   // No #[Input] - scalar without DI
    ): string {
        return $nonInputParam . ':' . $service->getValue();
    }
}