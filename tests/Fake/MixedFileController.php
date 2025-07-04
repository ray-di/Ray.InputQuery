<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Ray\InputQuery\Attribute\InputFile;

final class MixedFileController
{
    /**
     * Test file upload with no type hint (mixed)
     */
    public function uploadMixed(#[InputFile] $file): void
    {
        // Mixed type parameter with InputFile attribute
    }
}