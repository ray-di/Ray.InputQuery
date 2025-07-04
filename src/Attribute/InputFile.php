<?php

declare(strict_types=1);

namespace Ray\InputQuery\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class InputFile
{
    /**
     * @param int|null          $maxSize           Maximum file size in bytes
     * @param list<string>|null $allowedTypes      Allowed MIME types
     * @param list<string>|null $allowedExtensions Allowed file extensions
     * @param bool              $required          Whether the file is required
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
