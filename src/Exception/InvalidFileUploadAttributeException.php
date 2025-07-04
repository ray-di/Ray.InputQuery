<?php

declare(strict_types=1);

namespace Ray\InputQuery\Exception;

use InvalidArgumentException;

/**
 * Exception thrown when FileUpload type parameters are incorrectly decorated with #[Input] instead of #[InputFile].
 *
 * File upload parameters must use the #[InputFile] attribute to properly handle validation options
 * such as maxSize, allowedTypes, and allowedExtensions. Using #[Input] with FileUpload types
 * bypasses these important validation features and is not allowed.
 *
 * Correct usage:
 * - #[InputFile(maxSize: 5242880, allowedTypes: ['image/jpeg', 'image/png'])] FileUpload $avatar
 * - #[InputFile] FileUpload $document
 * - #[InputFile(item: FileUpload::class)] array $files
 *
 * Incorrect usage (will throw this exception):
 * - #[Input] FileUpload $file
 * - #[Input(item: FileUpload::class)] array $uploads
 */
final class InvalidFileUploadAttributeException extends InvalidArgumentException
{
}
