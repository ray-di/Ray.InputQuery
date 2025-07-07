<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Override;
use ReflectionClass;
use ReflectionProperty;

use function is_object;

final class ToArray implements ToArrayInterface
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function __invoke(object $input): array
    {
        return $this->extractProperties($input);
    }

    /** @return array<string, mixed> */
    private function extractProperties(object $object): array
    {
        $result = [];
        $reflection = new ReflectionClass($object);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            /** @var mixed $value */
            $value = $property->getValue($object);
            $name = $property->getName();

            if (is_object($value)) {
                // Recursively extract nested objects
                $nestedProperties = $this->extractProperties($value);
                /** @var mixed $nestedValue */
                foreach ($nestedProperties as $nestedName => $nestedValue) {
                    /** @psalm-suppress MixedAssignment */
                    $result[$nestedName] = $nestedValue;
                }

                continue;
            }

            // Keep arrays and scalar values as-is
            /** @psalm-suppress MixedAssignment */
            $result[$name] = $value;
        }

        return $result;
    }
}
