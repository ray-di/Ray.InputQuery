<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;

final class ConflictingAttributesInput
{
    public function __construct(
        #[Input]
        #[InputFile]
        public mixed $conflictingParam = null,
    ) {
    }
}