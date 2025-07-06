<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Koriym\FileUpload\AbstractFileUpload;
use Override;
use ReflectionAttribute;
use ReflectionParameter;
use RuntimeException;

/**
 * Null implementation of FileUploadFactoryInterface
 *
 * This factory is used when koriym/file-upload package is not installed.
 * It throws a RuntimeException with a helpful message when file upload
 * functionality is attempted to be used.
 */
final class NullUploadFactory implements FileUploadFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function create(ReflectionParameter $param, array $query, ReflectionAttribute|null $inputFileAttribute): AbstractFileUpload
    {
        throw new RuntimeException(
            'File upload functionality requires koriym/file-upload package. ' .
            'Please install it via: composer require koriym/file-upload',
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function createMultiple(ReflectionParameter $param, array $query, ReflectionAttribute|null $inputFileAttribute): array
    {
        throw new RuntimeException(
            'File upload functionality requires koriym/file-upload package. ' .
            'Please install it via: composer require koriym/file-upload',
        );
    }
}
