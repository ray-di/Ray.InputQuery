<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use ArrayObject;
use InvalidArgumentException;
use Koriym\FileUpload\FileUpload;
use Override;
use Ray\Di\Di\Named;
use Ray\Di\Di\Qualifier;
use Ray\Di\Exception\Unbound;
use Ray\Di\InjectorInterface;
use Ray\InputQuery\Attribute\Input;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

use function array_key_exists;
use function assert;
use function class_exists;
use function count;
use function gettype;
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

use const UPLOAD_ERR_NO_FILE;

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

        return $this->resolveInputParameter($param, $query, $inputAttributes);
    }

    /**
     * @param array<string, mixed>              $query
     * @param array<ReflectionAttribute<Input>> $inputAttributes
     */
    private function resolveInputParameter(ReflectionParameter $param, array $query, array $inputAttributes): mixed
    {
        $type = $param->getType();
        $paramName = $param->getName();

        // Handle union types (e.g., FileUpload|ErrorFileUpload)
        if ($type instanceof ReflectionUnionType) {
            return $this->resolveUnionType($param, $query, $type);
        }

        if (! $type instanceof ReflectionNamedType) {
            return $query[$paramName] ?? $this->getDefaultValue($param);
        }

        if ($type->isBuiltin()) {
            return $this->resolveBuiltinType($param, $query, $inputAttributes, $type);
        }

        return $this->resolveObjectType($param, $query, $inputAttributes, $type);
    }

    /**
     * @param array<string, mixed>              $query
     * @param array<ReflectionAttribute<Input>> $inputAttributes
     */
    private function resolveBuiltinType(ReflectionParameter $param, array $query, array $inputAttributes, ReflectionNamedType $type): mixed
    {
        $paramName = $param->getName();

        if ($type->getName() === 'array') {
            $inputAttribute = $inputAttributes[0]->newInstance();
            if ($inputAttribute->item !== null) {
                assert(class_exists($inputAttribute->item));
                $itemClass = $inputAttribute->item;

                // Check if array items are FileUpload types
                if ($this->isFileUploadType($itemClass)) {
                    return $this->createArrayOfFileUploads($paramName, $query);
                }

                /** @var class-string<T> $itemClass */
                return $this->createArrayOfInputs($paramName, $query, $itemClass);
            }
        }

        // Scalar type with #[Input]
        /** @psalm-suppress MixedAssignment $value */
        $value = $query[$paramName] ?? $this->getDefaultValue($param);

        return $this->convertScalar($value, $type);
    }

    /**
     * @param array<string, mixed>              $query
     * @param array<ReflectionAttribute<Input>> $inputAttributes
     */
    private function resolveObjectType(ReflectionParameter $param, array $query, array $inputAttributes, ReflectionNamedType $type): mixed
    {
        $paramName = $param->getName();
        $className = $type->getName();

        // Check for FileUpload types
        if ($this->isFileUploadType($className)) {
            return $this->resolveFileUpload($param, $query);
        }

        // Check for ArrayObject types with item specification
        $arrayObjectResult = $this->resolveArrayObjectType($paramName, $query, $inputAttributes, $className);
        if ($arrayObjectResult !== null) {
            return $arrayObjectResult;
        }

        // Regular object type with #[Input] - create nested
        $nestedQuery = $this->extractNestedQuery($paramName, $query);

        // If no nested keys found, try using the entire query
        if (empty($nestedQuery)) {
            $nestedQuery = $query;
        }

        assert(class_exists($className));

        /** @var class-string<T> $className */
        return $this->create($className, $nestedQuery);
    }

    /**
     * @param array<string, mixed>              $query
     * @param array<ReflectionAttribute<Input>> $inputAttributes
     */
    private function resolveArrayObjectType(string $paramName, array $query, array $inputAttributes, string $className): mixed
    {
        $isArrayObjectSubclass = class_exists($className) && is_subclass_of($className, ArrayObject::class);
        $isArrayObject = $className === ArrayObject::class;

        if (! $isArrayObjectSubclass && ! $isArrayObject) {
            return null;
        }

        $inputAttribute = $inputAttributes[0]->newInstance();
        if ($inputAttribute->item === null) {
            return null;
        }

        assert(class_exists($inputAttribute->item));
        /** @var class-string<T> $itemClass */
        $itemClass = $inputAttribute->item;
        $array = $this->createArrayOfInputs($paramName, $query, $itemClass);

        if ($isArrayObject) {
            return new ArrayObject($array);
        }

        assert(class_exists($className));
        /** @var class-string $className */
        $reflectionClass = new ReflectionClass($className);

        return $reflectionClass->newInstance($array);
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
     * @return array<array-key, T>
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
            if (! is_array($itemData)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Expected array for item at key "%s", got %s.',
                        $key,
                        gettype($itemData),
                    ),
                );
            }

            // Query parameters from HTTP requests have string keys
            /** @psalm-var array<string, mixed> $itemData */
            /** @phpstan-var array<string, mixed> $itemData */
            $result[$key] = $this->create($itemClass, $itemData);
        }

        return $result;
    }

    private function isFileUploadType(string $className): bool
    {
        if (! class_exists('Koriym\FileUpload\FileUpload')) {
            return false;
        }

        return $className === 'Koriym\FileUpload\FileUpload'
            || $className === 'Koriym\FileUpload\ErrorFileUpload'
            || is_subclass_of($className, FileUpload::class);
    }

    /** @param array<string, mixed> $query */
    private function resolveFileUpload(ReflectionParameter $param, array $query): mixed
    {
        $paramName = $param->getName();

        // Check if FileUpload is provided in query (for testing)
        if (array_key_exists($paramName, $query)) {
            return $query[$paramName];
        }

        // Try to create from $_FILES
        if (isset($_FILES[$paramName])) {
            /** @var array<string, mixed> $fileData */
            $fileData = $_FILES[$paramName];

            // Check if no file was uploaded (UPLOAD_ERR_NO_FILE)
            if (isset($fileData['error']) && $fileData['error'] === UPLOAD_ERR_NO_FILE) {
                if ($param->allowsNull() || $param->isDefaultValueAvailable()) {
                    return $param->getDefaultValue();
                }

                throw new InvalidArgumentException("Required file parameter '{$paramName}' is missing");
            }

            return FileUpload::create($fileData);
        }

        // No file found
        if ($param->allowsNull() || $param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new InvalidArgumentException("Required file parameter '{$paramName}' is missing");
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<array-key, mixed>
     */
    private function createArrayOfFileUploads(string $paramName, array $query): array
    {
        // Check if FileUpload array is provided in query (for testing)
        if (array_key_exists($paramName, $query) && is_array($query[$paramName])) {
            return $query[$paramName];
        }

        // Try to create from $_FILES
        if (! isset($_FILES[$paramName])) {
            return [];
        }

        /** @var array<string, mixed> $arrayData */
        $arrayData = $_FILES[$paramName];

        // Check if this is HTML multiple file upload format
        if (isset($arrayData['name']) && is_array($arrayData['name'])) {
            return $this->convertMultipleFileFormat($arrayData);
        }

        // Handle regular array format (each element is a complete file array)
        $result = [];

        /** @var array<string, mixed> $fileData */
        foreach ($arrayData as $key => $fileData) {

            // Skip files that weren't uploaded
            if (isset($fileData['error']) && $fileData['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $result[$key] = FileUpload::create($fileData);
        }

        return $result;
    }

    /**
     * Convert HTML multiple file upload format to individual file arrays
     *
     * @param array<string, mixed> $multipleFileData
     *
     * @return array<array-key, mixed>
     */
    private function convertMultipleFileFormat(array $multipleFileData): array
    {
        if (! isset($multipleFileData['name']) || ! is_array($multipleFileData['name'])) {
            return [];
        }

        $result = [];
        $fileCount = count($multipleFileData['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            $fileData = [
                'name' => $multipleFileData['name'][$i] ?? '',
                'type' => isset($multipleFileData['type']) && is_array($multipleFileData['type']) ? ($multipleFileData['type'][$i] ?? '') : '',
                'size' => isset($multipleFileData['size']) && is_array($multipleFileData['size']) ? ($multipleFileData['size'][$i] ?? 0) : 0,
                'tmp_name' => isset($multipleFileData['tmp_name']) && is_array($multipleFileData['tmp_name']) ? ($multipleFileData['tmp_name'][$i] ?? '') : '',
                'error' => isset($multipleFileData['error']) && is_array($multipleFileData['error']) ? ($multipleFileData['error'][$i] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE,
            ];

            // Skip files that weren't uploaded
            if ($fileData['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            /** @var array<string, mixed> $fileData */
            $result[$i] = FileUpload::create($fileData);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function resolveUnionType(ReflectionParameter $param, array $query, ReflectionUnionType $type): mixed
    {
        // Check if any of the union types is a FileUpload type
        foreach ($type->getTypes() as $unionType) {
            /** @var ReflectionNamedType $unionType */
            if ($this->isFileUploadType($unionType->getName())) {
                // This is a FileUpload union, handle as file upload
                return $this->resolveFileUpload($param, $query);
            }
        }

        // Not a FileUpload union type, handle as regular parameter
        $paramName = $param->getName();

        return $query[$paramName] ?? $this->getDefaultValue($param);
    }
}
