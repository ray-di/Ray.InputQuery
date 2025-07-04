<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use Override;
use Ray\InputQuery\Attribute\InputFile;
use ReflectionAttribute;
use ReflectionParameter;

use function array_key_exists;
use function count;
use function is_array;

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
 * @psalm-import-type FileUploadKey from InputQuery
 * @psalm-import-type FileUploadArray from InputQuery
 */
final class FileUploadFactory implements FileUploadFactoryInterface
{
    /**
     * Create single FileUpload from InputFile attribute and query data
     *
     * @param ReflectionParameter                 $param              Parameter metadata for file upload
     * @param Query                               $query              Service locator for pre-created FileUpload objects (testing) or empty array (production)
     * @param ReflectionAttribute<InputFile>|null $inputFileAttribute InputFile attribute instance containing validation options
     */
    #[Override]
    public function create(ReflectionParameter $param, array $query, ReflectionAttribute|null $inputFileAttribute): FileUpload|ErrorFileUpload
    {
        $validationOptions = $this->extractValidationOptions($inputFileAttribute);

        return $this->resolveFileUpload($param, $query, $validationOptions);
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
     * Create multiple FileUploads from InputFile attribute and query data
     *
     * For HTML <input type="file" name="files[]" multiple> cases
     *
     * @param string                              $paramName          Parameter name for file upload array
     * @param Query                               $query              Service locator for pre-created FileUpload objects (testing) or empty array (production)
     * @param ReflectionAttribute<InputFile>|null $inputFileAttribute InputFile attribute instance containing validation options
     *
     * @return array<FileUploadKey, FileUpload|ErrorFileUpload>
     */
    #[Override]
    public function createMultiple(string $paramName, array $query, ReflectionAttribute|null $inputFileAttribute): array
    {
        $validationOptions = $this->extractValidationOptions($inputFileAttribute);

        return $this->createArrayOfFileUploads($paramName, $query, $validationOptions);
    }

    /**
     * Extract validation options from InputFile attribute
     *
     * @param ReflectionAttribute<InputFile>|null $inputFileAttribute
     *
     * @return ValidationOptions
     */
    private function extractValidationOptions(ReflectionAttribute|null $inputFileAttribute): array
    {
        if ($inputFileAttribute === null) {
            return [];
        }

        $inputFile = $inputFileAttribute->newInstance();
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
    private function resolveFileUpload(ReflectionParameter $param, array $query, array $validationOptions = [], array|null $filesData = null): FileUpload|ErrorFileUpload
    {
        $paramName = $param->getName();

        // Service locator: check if FileUpload is already provided (for testing/mocking)
        if (array_key_exists($paramName, $query)) {
            /** @var mixed $value */
            $value = $query[$paramName];
            if ($value instanceof FileUpload || $value instanceof ErrorFileUpload) {
                return $value;
            }
        }

        // Use provided files data or $_FILES
        $files = $filesData ?? $_FILES;

        // Try to create from file data
        if (isset($files[$paramName])) {
            /** @var FileData $fileData */
            $fileData = $files[$paramName];

            // Check if no file was uploaded (UPLOAD_ERR_NO_FILE)
            if ($fileData['error'] === UPLOAD_ERR_NO_FILE) {
                // Return ErrorFileUpload for no file case
                return new ErrorFileUpload($fileData);
            }

            return FileUpload::create($fileData, $validationOptions);
        }

        // No file found - create ErrorFileUpload with UPLOAD_ERR_NO_FILE
        $noFileData = [
            'name' => '',
            'type' => '',
            'size' => 0,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        return new ErrorFileUpload($noFileData);
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
}
