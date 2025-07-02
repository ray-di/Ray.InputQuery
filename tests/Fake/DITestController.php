<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\Di\Di\Named;
use Ray\InputQuery\Attribute\Input;

final class DITestController
{
    public function withNamed(
        #[Input] UserInput $user,
        #[Named('database.host')] string $host,
        #[Named('database.port')] int $port
    ): string {
        return $user->name . '@' . $host . ':' . $port;
    }

    public function withCustomQualifier(
        #[Input] UserInput $user,
        #[Primary] DatabaseService $primaryDb,
        #[Secondary] DatabaseService $secondaryDb
    ): string {
        return $user->name . ' uses ' . $primaryDb->getConnectionString() . ' and ' . $secondaryDb->getConnectionString();
    }

    public function withMixedDI(
        #[Input] string $message,
        TestService $defaultService,  // No qualifier - default DI
        #[Named('service.name')] string $serviceName
    ): string {
        return $message . ' from ' . $defaultService->getValue() . ' (' . $serviceName . ')';
    }

    public function withUnboundServiceWithoutDefault(
        UnresolvableService $service  // This service cannot be auto-wired
    ): string {
        return 'test';
    }
}
