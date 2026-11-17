<?php

declare(strict_types=1);

namespace Ray\InputQuery\Attribute;

use Attribute;

/**
 * Builds a FileUpload from the uploaded file named after the parameter
 * A file that fails validation gives an ErrorFileUpload, not an exception
 *
 * @see https://bearsunday.github.io/manuals/1.0/en/resource_param.html#file-upload
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class InputFile
{
    /**
     * @param int|null          $maxSize           Maximum file size in bytes
     * @param list<string>|null $allowedTypes      Allowed MIME types
     * @param list<string>|null $allowedExtensions Without the leading dot, compared case-sensitively
     * @param bool              $required          Not read; a nullable parameter makes the file optional
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag") - Boolean flags are acceptable in attribute/configuration classes,
     *                                                      as they represent configuration options, not behavioral flags
     */
    public function __construct(
        public readonly int|null $maxSize = null,
        public readonly array|null $allowedTypes = null,
        public readonly array|null $allowedExtensions = null,
        public readonly bool $required = true,
    ) {
    }
}
