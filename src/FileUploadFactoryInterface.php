<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use Ray\InputQuery\Attribute\InputFile;
use ReflectionAttribute;
use ReflectionParameter;

/** @psalm-import-type FileUploadKey from InputQuery */
interface FileUploadFactoryInterface
{
    /**
     * Create single FileUpload from InputFile attribute and query data
     *
     * @param ReflectionParameter                 $param              Parameter metadata for file upload
     * @param array<string, mixed>                $query              Service locator for pre-created FileUpload objects (testing) or empty array (production)
     * @param ReflectionAttribute<InputFile>|null $inputFileAttribute InputFile attribute instance containing validation options
     */
    public function create(ReflectionParameter $param, array $query, ReflectionAttribute|null $inputFileAttribute): FileUpload|ErrorFileUpload;

    /**
     * Create multiple FileUploads from InputFile attribute and query data
     *
     * For HTML <input type="file" name="files[]" multiple> cases
     *
     * @param ReflectionParameter                 $param              Parameter metadata for file upload array
     * @param array<string, mixed>                $query              Service locator for pre-created FileUpload objects (testing) or empty array (production)
     * @param ReflectionAttribute<InputFile>|null $inputFileAttribute InputFile attribute instance containing validation options
     *
     * @return array<FileUploadKey, FileUpload|ErrorFileUpload>
     */
    public function createMultiple(ReflectionParameter $param, array $query, ReflectionAttribute|null $inputFileAttribute): array;
}
