<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\InputQuery\Attribute\InputFile;

final class MultipleInputFileAttributesInput
{
    public function __construct(
        #[InputFile(maxSize: 1024)]
        #[InputFile(allowedTypes: ['image/jpeg'])]
        public mixed $file = null,
    ) {
    }
}