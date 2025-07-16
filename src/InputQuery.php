<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use ArrayObject;
use InvalidArgumentException;
use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use Override;
use Ray\Di\Di\Named;
use Ray\Di\Di\Qualifier;
use Ray\Di\Exception\Unbound;
use Ray\Di\InjectorInterface;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use Ray\InputQuery\Exception\InvalidFileUploadAttributeException;
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
 * @psalm-import-type Query from InputQueryInterface
 * @psalm-type FileData = array{name: string, type: string, size: int, tmp_name: string, error: int}
 * @psalm-type FileNameArray = array<int, string>
 * @psalm-type FileTypeArray = array<int, string>
 * @psalm-type FileSizeArray = array<int, int>
 * @psalm-type FileTmpNameArray = array<int, string>
 * @psalm-type FileErrorArray = array<int, int>
 * @psalm-type MultipleFileData = array{name: FileNameArray, type: FileTypeArray, size: FileSizeArray, tmp_name: FileTmpNameArray, error: FileErrorArray}
 * @psalm-type ValidationOptions = array{maxSize?: int<1, max>, allowedTypes?: list<string>, allowedExtensions?: list<string>}
 * @psalm-type FileUploadKey = int|string
 * @psalm-type FileUploadArray = array<FileUploadKey, FileUpload|ErrorFileUpload>
 * @psalm-type NestedQuery = array<string, mixed>
 * @psalm-type InputArray = array<int, mixed>
 * @psalm-type ParameterValue = scalar|array<array-key, mixed>|object|null
 * @psalm-type InputAttributes = array<ReflectionAttribute<Input>>
 * @psalm-type InputFileAttributes = array<ReflectionAttribute<InputFile>>
 */
final class InputQuery implements InputQueryInterface
{
    private FileUploadFactoryInterface $fileUploadFactory;

    public function __construct(
        private InjectorInterface $injector,
        FileUploadFactoryInterface|null $fileUploadFactory = null,
    ) {
        $this->fileUploadFactory = $fileUploadFactory ?? new NullUploadFactory();
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
     * @param class-string<T> $class
     * @param Query           $query
     *
     * @return T
     */
    #[Override]
    public function newInstance(string $class, array $query): object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return $reflection->newInstance();
        }

        $args = $this->getArguments($constructor, $query);

        return $reflection->newInstanceArgs($args);
    }

    /** @param Query $query */
    private function resolveParameter(ReflectionParameter $param, array $query): mixed
    {
        $inputAttributes = $param->getAttributes(Input::class);
        $inputFileAttributes = $param->getAttributes(InputFile::class);
        $hasInputAttribute = ! empty($inputAttributes);
        $hasInputFileAttribute = ! empty($inputFileAttributes);

        if ($hasInputAttribute && $hasInputFileAttribute) {
            throw new InvalidArgumentException(
                sprintf(
                    'Parameter $%s cannot have both #[Input] and #[InputFile] attributes at the same time.',
                    $param->getName(),
                ),
            );
        }

        if (! $hasInputAttribute && ! $hasInputFileAttribute) {
            // No #[Input] or #[InputFile] attribute - get from DI
            return $this->resolveFromDI($param);
        }

        if ($hasInputFileAttribute) {
            return $this->resolveInputFileParameter($param, $query, $inputFileAttributes);
        }

        return $this->resolveInputParameter($param, $query, $inputAttributes);
    }

    /**
     * @param Query               $query
     * @param InputFileAttributes $inputFileAttributes
     */
    private function resolveInputFileParameter(ReflectionParameter $param, array $query, array $inputFileAttributes): mixed
    {
        // Validate that only one InputFile attribute is present
        if (count($inputFileAttributes) > 1) {
            throw new InvalidArgumentException(
                'Only one #[InputFile] attribute is allowed per parameter',
            );
        }

        $type = $param->getType();
        $inputFileAttribute = $inputFileAttributes[0] ?? null;

        // Handle array type for multiple file uploads
        if ($type instanceof ReflectionNamedType && $type->getName() === 'array') {
            return $this->fileUploadFactory->createMultiple($param, $query, $inputFileAttribute);
        }

        // Handle single file upload
        $result = $this->fileUploadFactory->create($param, $query, $inputFileAttribute);

        // Handle ErrorFileUpload for missing files
        if ($result instanceof ErrorFileUpload && $result->error === UPLOAD_ERR_NO_FILE) {
            // For nullable parameters, return null
            if ($param->allowsNull()) {
                return null;
            }

            // For required parameters, throw exception
            throw new InvalidArgumentException(
                sprintf("Required file parameter '%s' is missing", $param->getName()),
            );
        }

        return $result;
    }

    /**
     * @param Query           $query
     * @param InputAttributes $inputAttributes
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
     * @param Query           $query
     * @param InputAttributes $inputAttributes
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
                if (FileUploadTypeChecker::isFileUploadType($itemClass)) {
                    throw new InvalidFileUploadAttributeException(
                        sprintf('FileUpload array parameter "%s" must use #[InputFile] attribute, not #[Input]', $paramName),
                    );
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
     * @param Query           $query
     * @param InputAttributes $inputAttributes
     */
    private function resolveObjectType(ReflectionParameter $param, array $query, array $inputAttributes, ReflectionNamedType $type): mixed
    {
        $paramName = $param->getName();
        $className = $type->getName();

        // Check for FileUpload types - must use #[InputFile] not #[Input]
        if (FileUploadTypeChecker::isFileUploadType($className)) {
            throw new InvalidFileUploadAttributeException(
                sprintf('FileUpload parameter "%s" must use #[InputFile] attribute, not #[Input]', $paramName),
            );
        }

        // Check for ArrayObject types with item specification
        $arrayObjectResult = $this->resolveArrayObjectType($paramName, $query, $inputAttributes, $className);
        if ($arrayObjectResult !== null) {
            return $arrayObjectResult;
        }

        // Check if the parameter name exists as a direct nested array in query
        if (array_key_exists($paramName, $query) && is_array($query[$paramName])) {
            assert(class_exists($className));

            /** @var Query $nestedQuery */
            $nestedQuery = $query[$paramName];

            /** @var class-string<T> $className */
            return $this->newInstance($className, $nestedQuery);
        }

        // Regular object type with #[Input] - create nested
        $nestedQuery = $this->extractNestedQuery($paramName, $query);

        // If no nested keys found, try using the entire query
        if (empty($nestedQuery)) {
            $nestedQuery = $query;
        }

        assert(class_exists($className));

        /** @var class-string<T> $className */
        return $this->newInstance($className, $nestedQuery);
    }

    /**
     * @param Query           $query
     * @param InputAttributes $inputAttributes
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
        return $this->getDefaultValueOrThrow(
            $param,
            sprintf('Required parameter "%s" is missing and has no default value', $param->getName()),
        );
    }

    /**
     * Get the default value of a parameter or throw an exception with a custom message
     */
    private function getDefaultValueOrThrow(ReflectionParameter $param, string $message): mixed
    {
        if ($param->allowsNull()) {
            return null;
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new InvalidArgumentException($message);
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
     * @param Query $query
     *
     * @return Query
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
     * @param Query           $query
     * @param class-string<T> $itemClass
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
            $result[$key] = $this->newInstance($itemClass, $itemData);
        }

        return $result;
    }

    /** @param Query $query */
    private function resolveUnionType(ReflectionParameter $param, array $query, ReflectionUnionType $type): mixed
    {
        // Check if this is a file upload union type
        if (FileUploadTypeChecker::isValidFileUploadUnion($type)) {
            // This is a valid FileUpload union, handle as file upload
            $inputFileAttrs = $param->getAttributes(InputFile::class);
            $inputFileAttribute = $inputFileAttrs[0] ?? null;

            return $this->fileUploadFactory->create($param, $query, $inputFileAttribute);
        }

        // Not a FileUpload union type, handle as regular parameter
        $paramName = $param->getName();

        return $query[$paramName] ?? $this->getDefaultValue($param);
    }
}
