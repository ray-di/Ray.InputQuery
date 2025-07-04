<?php

declare(strict_types=1);

namespace Ray\InputQuery\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class InputFile
{
    /**
     * @param int|null          $maxSize      Maximum file size in bytes
     * @param list<string>|null $allowedTypes Allowed MIME types
     * @param bool              $required     Whether the file is required
     */
    public function __construct(
        public readonly int|null $maxSize = null,
        public readonly array|null $allowedTypes = null,
        public readonly bool $required = true,
    ) {
    }
}
