<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use InvalidArgumentException;
use Ray\Di\InjectorInterface;
use Ray\InputQuery\Attribute\Input;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

use function assert;
use function class_exists;
use function lcfirst;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function ucwords;

/** @template T of object */
final class InputQuery implements InputQueryInterface
{
    public function __construct(
        private InjectorInterface $injector,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function getArguments(ReflectionMethod $method, array $query): array
    {
        $args = [];
        foreach ($method->getParameters() as $param) {
            $args[] = $this->resolveParameter($param, $query);
        }

        return $args;
    }

    /**
     * @param class-string<T>      $class
     * @param array<string, mixed> $query
     *
     * @return T
     */
    public function create(string $class, array $query): object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return new $class();
        }

        $args = $this->getArguments($constructor, $query);

        return $reflection->newInstanceArgs($args);
    }

    /** @param array<string, mixed> $query */
    private function resolveParameter(ReflectionParameter $param, array $query): mixed
    {
        $inputAttributes = $param->getAttributes(Input::class);
        $hasInputAttribute = ! empty($inputAttributes);

        if (! $hasInputAttribute) {
            // No #[Input] attribute - get from DI
            return $this->resolveFromDI($param);
        }

        // Has #[Input] attribute - get from query
        $type = $param->getType();
        $paramName = $param->getName();

        if (! $type instanceof ReflectionNamedType) {
            return $query[$paramName] ?? $this->getDefaultValue($param);
        }

        if ($type->isBuiltin()) {
            // Scalar type with #[Input]
            $value = $query[$paramName] ?? $this->getDefaultValue($param);

            return $this->convertScalar($value, $type);
        }

        // Object type with #[Input] - create nested
        $nestedQuery = $this->extractNestedQuery($paramName, $query);

        // If no nested keys found, try using the entire query
        // This handles cases like controller method parameters
        if (empty($nestedQuery)) {
            $nestedQuery = $query;
        }

        $class = $type->getName();
        assert(class_exists($class));

        return $this->create($class, $nestedQuery);
    }

    private function resolveFromDI(ReflectionParameter $param): mixed
    {
        $type = $param->getType();

        if (! $type instanceof ReflectionNamedType) {
            return $this->getDefaultValue($param);
        }

        if ($type->isBuiltin()) {
            // Scalar type without #[Input] - should be from DI with #[Named]
            // For now, return default value
            return $this->getDefaultValue($param);
        }

        // Object type without #[Input] - get from DI
        return $this->injector->getInstance($type->getName());
    }

    private function getDefaultValue(ReflectionParameter $param): mixed
    {
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($param->allowsNull()) {
            return null;
        }

        // Required parameter without default value
        throw new InvalidArgumentException(sprintf(
            'Required parameter "%s" is missing and has no default value',
            $param->getName(),
        ));
    }

    private function convertScalar(mixed $value, ReflectionNamedType $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type->getName()) {
            'string' => (string) $value,
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            default => $value
        };
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<mixed>
     */
    private function extractNestedQuery(string $paramName, array $query): array
    {
        $prefix = $this->toCamelCase($paramName);
        $nestedQuery = [];

        foreach ($query as $key => $value) {
            $normalizedKey = $this->toCamelCase($key);

            if (str_starts_with($normalizedKey, $prefix)) {
                $nestedKey = substr($normalizedKey, strlen($prefix));
                $nestedKey = lcfirst($nestedKey);
                if ($nestedKey !== '') {
                    $nestedQuery[$nestedKey] = $value;
                }
            }
        }

        return $nestedQuery;
    }

    private function toCamelCase(string $string): string
    {
        // Convert snake_case and kebab-case to camelCase
        $string = str_replace(['-', '_'], ' ', strtolower($string));
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);

        return lcfirst($string);
    }
}
