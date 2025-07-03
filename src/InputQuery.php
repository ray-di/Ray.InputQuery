<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use ArrayObject;
use InvalidArgumentException;
use Override;
use Ray\Di\Di\Named;
use Ray\Di\Di\Qualifier;
use Ray\Di\Exception\Unbound;
use Ray\Di\InjectorInterface;
use Ray\InputQuery\Attribute\Input;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

use function array_key_exists;
use function assert;
use function class_exists;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_scalar;
use function is_string;
use function is_subclass_of;
use function lcfirst;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function ucwords;

/**
 * @template T of object
 * @implements InputQueryInterface<T>
 */
final class InputQuery implements InputQueryInterface
{
    public function __construct(
        private InjectorInterface $injector,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getArguments(ReflectionMethod $method, array $query): array
    {
        $args = [];
        foreach ($method->getParameters() as $param) {
            /** @psalm-suppress MixedAssignment */
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
    #[Override]
    public function create(string $class, array $query): object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return $reflection->newInstance();
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
            // Check if it's an array type with item specification
            if ($type->getName() === 'array') {
                $inputAttribute = $inputAttributes[0]->newInstance();
                if ($inputAttribute->item !== null) {
                    assert(class_exists($inputAttribute->item));
                    $itemClass = $inputAttribute->item;

                    /** @var class-string<T> $itemClass */
                    return $this->createArrayOfInputs($paramName, $query, $itemClass);
                }
            }

            // Scalar type with #[Input]
            /** @psalm-suppress MixedAssignment $value */

            $value = $query[$paramName] ?? $this->getDefaultValue($param);

            return $this->convertScalar($value, $type);
        }

        // Check if it's ArrayObject or its subclass with item specification
        $className = $type->getName();
        if (class_exists($className) && is_subclass_of($className, ArrayObject::class)) {
            $inputAttribute = $inputAttributes[0]->newInstance();
            if ($inputAttribute->item !== null) {
                assert(class_exists($inputAttribute->item));
                /** @var class-string<T> $itemClass */
                $itemClass = $inputAttribute->item;
                $array = $this->createArrayOfInputs($paramName, $query, $itemClass);
                $reflectionClass = new ReflectionClass($className);

                return $reflectionClass->newInstance($array);
            }
        }

        // Check if it's ArrayObject itself with item specification
        if ($className === ArrayObject::class) {
            $inputAttribute = $inputAttributes[0]->newInstance();
            if ($inputAttribute->item !== null) {
                assert(class_exists($inputAttribute->item));
                /** @var class-string<T> $itemClass */
                $itemClass = $inputAttribute->item;
                $array = $this->createArrayOfInputs($paramName, $query, $itemClass);

                return new ArrayObject($array);
            }
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

        /** @var class-string<T> $class */
        return $this->create($class, $nestedQuery);
    }

    private function resolveFromDI(ReflectionParameter $param): mixed
    {
        $interface = $this->getInterface($param);
        $qualifier = $this->getQualifier($param);
        try {
            return $this->injector->getInstance($interface, $qualifier);
        } catch (Unbound $e) {
            // If the type is not bound, we need to handle it
            // If it's a scalar type, return default value
            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            // If it's an object type, throw an exception
            throw new InvalidArgumentException(sprintf(
                'Parameter "%s" of type "%s:%s" is not bound in the injector.',
                $param->getName(),
                $interface,
                $qualifier,
            ), 0, $e);
        }
    }

    /** @return class-string|'' */
    private function getInterface(ReflectionParameter $param): string
    {
        $type = $param->getType();
        if ($type === null || ! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return '';
        }

        $class =  $type->getName();
        assert(class_exists($class));

        return $class;
    }

    private function getQualifier(ReflectionParameter $param): string
    {
        $maybeAttrs = $param->getAttributes();
        foreach ($maybeAttrs as $maybeAttr) {
            $attr = $maybeAttr->newInstance();
            if ($attr instanceof Named) {
                // If the attribute is Named, return its value
                return $attr->value;
            }

            $maybeQualifier = (new ReflectionClass($attr))->getAttributes(Qualifier::class);
            $isQualifier  = ! empty($maybeQualifier);
            if ($isQualifier) {
                // If the attribute is Qualifier, return its value
                return $attr::class;
            }
        }

        return '';
    }

    private function getDefaultValue(ReflectionParameter $param): mixed
    {
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
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
            'string' => is_string($value) ? $value : (is_scalar($value) ? (string) $value : ''),
            'int' => is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0),
            'float' => is_float($value) ? $value : (is_numeric($value) ? (float) $value : 0.0),
            'bool' => is_bool($value) ? $value : (bool) $value,
            default => $value
        };
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    private function extractNestedQuery(string $paramName, array $query): array
    {
        $prefix = $this->toCamelCase($paramName);
        $nestedQuery = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($query as $key => $value) {
            $normalizedKey = $this->toCamelCase($key);

            if (str_starts_with($normalizedKey, $prefix)) {
                $nestedKey = substr($normalizedKey, strlen($prefix));
                $nestedKey = lcfirst($nestedKey);
                if ($nestedKey !== '') {
                    /** @psalm-suppress MixedAssignment */
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

    /**
     * @param array<string, mixed> $query
     * @param class-string<T>      $itemClass
     *
     * @return array<mixed>
     */
    private function createArrayOfInputs(string $paramName, array $query, string $itemClass): array
    {
        if (! array_key_exists($paramName, $query)) {
            return [];
        }

        /** @var mixed $arrayData */
        $arrayData = $query[$paramName];

        if (! is_array($arrayData)) {
            return [];
        }

        $result = [];
        /** @var mixed $itemData */
        foreach ($arrayData as $key => $itemData) {
            if (is_array($itemData)) {
                // Query parameters from HTTP requests have string keys
                /** @psalm-var array<string, mixed> $itemData */
                /** @phpstan-var array<string, mixed> $itemData */
                $result[$key] = $this->create($itemClass, $itemData);
            }
        }

        return $result;
    }
}
