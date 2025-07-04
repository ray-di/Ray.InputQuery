<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use InvalidArgumentException;
use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use Ray\InputQuery\Attribute\InputFile;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

use function array_key_exists;
use function count;
use function is_array;
use function is_subclass_of;
use function sprintf;

use const UPLOAD_ERR_NO_FILE;

/**
 * Factory for creating FileUpload objects with validation
 *
 * This class follows SRP (Single Responsibility Principle) and is responsible
 * only for creating FileUpload objects from various data sources.
 * It can be used independently from InputQuery in other contexts like BEAR.Resource.
 *
 * Uses service locator pattern for testability:
 * - Production: Creates FileUpload from $_FILES data
 * - Testing: Uses pre-created FileUpload objects from $query parameter
 *
 * @psalm-import-type Query from InputQueryInterface
 * @psalm-import-type FileData from InputQuery
 * @psalm-import-type MultipleFileData from InputQuery
 * @psalm-import-type ValidationOptions from InputQuery
 * @psalm-import-type FileUploadArray from InputQuery
 * @psalm-import-type InputFileAttributes from InputQuery
 */
final class FileUploadFactory
{
    /**
     * Create FileUpload from InputFile attribute and query data
     *
     * Primary method for InputQuery integration
     *
     * @param ReflectionParameter $param               Parameter metadata for file upload
     * @param Query               $query               Service locator for pre-created FileUpload objects (testing) or empty array (production)
     * @param InputFileAttributes $inputFileAttributes InputFile attribute instances containing validation options
     */
    public function create(ReflectionParameter $param, array $query, array $inputFileAttributes): mixed
    {
        // Validate that only one InputFile attribute is present
        if (count($inputFileAttributes) > 1) {
            throw new InvalidArgumentException(
                'Only one #[InputFile] attribute is allowed per parameter',
            );
        }

        $type = $param->getType();

        // Handle union types (e.g., FileUpload|ErrorFileUpload|null)
        if ($type instanceof ReflectionUnionType) {
            if (! $this->isValidFileUploadUnion($type)) {
                throw new InvalidArgumentException(
                    'Unsupported union type for file upload parameter',
                );
            }

            return $this->createWithValidation($param, $query, $inputFileAttributes);
        }

        // Handle single FileUpload type
        if ($type instanceof ReflectionNamedType) {
            if ($this->isFileUploadType($type->getName())) {
                return $this->createWithValidation($param, $query, $inputFileAttributes);
            }

            // Handle array of FileUpload
            if ($type->getName() === 'array') {
                return $this->createArray($param->getName(), $query, $inputFileAttributes);
            }
        }

        // Handle mixed type (no type hint) with InputFile attribute
        if ($type === null) {
            return $this->createWithValidation($param, $query, $inputFileAttributes);
        }

        throw new InvalidArgumentException(
            sprintf('Parameter %s is not a valid file upload parameter', $param->getName()),
        );
    }

    /**
     * Create FileUpload directly from file data
     *
     * For use outside InputQuery context (e.g., BEAR.Resource, CLI tools)
     *
     * @param array<string, mixed> $filesData         $_FILES format data
     * @param ValidationOptions    $validationOptions
     */
    public function createFromFiles(ReflectionParameter $param, array $filesData, array $validationOptions = []): mixed
    {
        return $this->resolveFileUpload($param, [], $validationOptions, $filesData);
    }

    /**
     * Create array of FileUploads from InputFile attribute and query data
     *
     * @param Query               $query
     * @param InputFileAttributes $inputFileAttributes
     *
     * @return FileUploadArray
     */
    public function createArray(string $paramName, array $query, array $inputFileAttributes): array
    {
        $validationOptions = $this->extractValidationOptions($inputFileAttributes);

        return $this->createArrayOfFileUploads($paramName, $query, $validationOptions);
    }

    /**
     * Check if this is a valid FileUpload union type for resolveUnionType
     *
     * @param Query $query
     */
    public function resolveFileUploadUnionType(ReflectionParameter $param, array $query, ReflectionUnionType $type): mixed
    {
        if (! $this->isValidFileUploadUnion($type)) {
            return null; // Not a file upload union type
        }

        // This is a valid FileUpload union, handle as file upload
        $inputFileAttrs = $param->getAttributes(InputFile::class);
        $validationOptions = $this->extractValidationOptions($inputFileAttrs);

        return $this->resolveFileUpload($param, $query, $validationOptions);
    }

    /**
     * Check if a class name represents a FileUpload type
     */
    public function isFileUploadType(string $className): bool
    {
        if ($className === FileUpload::class || $className === ErrorFileUpload::class) {
            return true;
        }

        return is_subclass_of($className, FileUpload::class) || is_subclass_of($className, ErrorFileUpload::class);
    }

    /**
     * Check if union type is valid for file uploads
     */
    private function isValidFileUploadUnion(ReflectionUnionType $type): bool
    {
        $unionTypes = $type->getTypes();

        foreach ($unionTypes as $unionType) {
            if (! $unionType instanceof ReflectionNamedType) {
                // @codeCoverageIgnoreStart
                // This case occurs with PHP 8.2+ intersection types in union types like (A&B)|C
                // Cannot be tested in PHP < 8.2 due to syntax errors
                return false;
                // @codeCoverageIgnoreEnd
            }

            $typeName = $unionType->getName();
            // Allow FileUpload types (with or without namespace) and null
            if (! $this->isFileUploadType($typeName) && $typeName !== 'null') {
                return false;
            }
        }

        return true;
    }

    /**
     * Create FileUpload with validation from InputFile attributes
     *
     * @param Query               $query
     * @param InputFileAttributes $inputFileAttributes
     */
    private function createWithValidation(ReflectionParameter $param, array $query, array $inputFileAttributes): mixed
    {
        $validationOptions = $this->extractValidationOptions($inputFileAttributes);

        return $this->resolveFileUpload($param, $query, $validationOptions);
    }

    /**
     * Extract validation options from InputFile attributes
     *
     * @param InputFileAttributes $inputFileAttributes
     *
     * @return ValidationOptions
     */
    private function extractValidationOptions(array $inputFileAttributes): array
    {
        if (empty($inputFileAttributes)) {
            return [];
        }

        $inputFile = $inputFileAttributes[0]->newInstance();
        $options = [];

        if ($inputFile->maxSize !== null && $inputFile->maxSize > 0) {
            $options['maxSize'] = $inputFile->maxSize;
        }

        if ($inputFile->allowedTypes !== null) {
            $options['allowedTypes'] = $inputFile->allowedTypes;
        }

        if ($inputFile->allowedExtensions !== null) {
            $options['allowedExtensions'] = $inputFile->allowedExtensions;
        }

        return $options;
    }

    /**
     * Core file upload resolution logic
     *
     * Uses service locator pattern: checks $query first for pre-created FileUpload objects (testing),
     * then falls back to $_FILES or custom $filesData (production).
     *
     * @param ReflectionParameter       $param             Parameter metadata
     * @param Query                     $query             Service locator for pre-created FileUpload objects
     * @param ValidationOptions         $validationOptions Validation rules for file upload
     * @param array<string, mixed>|null $filesData         Custom files data (for createFromFiles)
     */
    private function resolveFileUpload(ReflectionParameter $param, array $query, array $validationOptions = [], array|null $filesData = null): mixed
    {
        $paramName = $param->getName();

        // Service locator: check if FileUpload is already provided (for testing/mocking)
        if (array_key_exists($paramName, $query)) {
            return $query[$paramName];
        }

        // Use provided files data or $_FILES
        $files = $filesData ?? $_FILES;

        // Try to create from file data
        if (isset($files[$paramName])) {
            /** @var FileData $fileData */
            $fileData = $files[$paramName];

            // Check if no file was uploaded (UPLOAD_ERR_NO_FILE)
            if ($fileData['error'] === UPLOAD_ERR_NO_FILE) {
                return $this->getDefaultValueOrThrow($param, "Required file parameter '{$paramName}' is missing");
            }

            return FileUpload::create($fileData, $validationOptions);
        }

        // No file found
        return $this->getDefaultValueOrThrow($param, "Required file parameter '{$paramName}' is missing");
    }

    /**
     * Create array of FileUploads from various data sources
     *
     * @param Query             $query
     * @param ValidationOptions $validationOptions
     *
     * @return FileUploadArray
     */
    private function createArrayOfFileUploads(string $paramName, array $query, array $validationOptions = []): array
    {
        // Check if FileUpload array is provided in query (for testing)
        if (array_key_exists($paramName, $query) && is_array($query[$paramName])) {
            /** @var FileUploadArray $result */
            $result = $query[$paramName];

            return $result;
        }

        // Try to create from $_FILES
        if (! isset($_FILES[$paramName])) {
            return [];
        }

        /** @var array<string, mixed> $arrayData */
        $arrayData = $_FILES[$paramName];

        // Check if this is HTML multiple file upload format
        if (isset($arrayData['name']) && is_array($arrayData['name'])) {
            /** @var MultipleFileData $arrayData */
            return $this->convertMultipleFileFormat($arrayData, $validationOptions);
        }

        // Handle regular array format (each element is a complete file array)
        $result = [];

        /** @var FileData $fileData */
        foreach ($arrayData as $key => $fileData) {
            // Skip files that weren't uploaded
            if ($fileData['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $result[$key] = FileUpload::create($fileData, $validationOptions);
        }

        return $result;
    }

    /**
     * Convert multiple file format to individual FileUpload objects
     *
     * @param MultipleFileData  $multipleFileData
     * @param ValidationOptions $validationOptions
     *
     * @return FileUploadArray
     */
    private function convertMultipleFileFormat(array $multipleFileData, array $validationOptions = []): array
    {
        /** @var MultipleFileData $multipleFileData */

        $result = [];
        $fileCount = count($multipleFileData['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            $fileData = [
                'name' => $multipleFileData['name'][$i] ?? '',
                'type' => $multipleFileData['type'][$i] ?? '',
                'size' => $multipleFileData['size'][$i] ?? 0,
                'tmp_name' => $multipleFileData['tmp_name'][$i] ?? '',
                'error' => $multipleFileData['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            ];

            // Skip files that weren't uploaded
            if ($fileData['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $result[$i] = FileUpload::create($fileData, $validationOptions);
        }

        return $result;
    }

    /**
     * Helper method to get default value or throw exception
     */
    private function getDefaultValueOrThrow(ReflectionParameter $param, string $message): mixed
    {
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($param->allowsNull()) {
            return null;
        }

        throw new InvalidArgumentException($message);
    }
}
