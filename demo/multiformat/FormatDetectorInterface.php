<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Koriym\FileUpload\FileUpload;

/**
 * Interface for format detection strategies
 */
interface FormatDetectorInterface
{
    public function detectFormat(FileUpload $file): string;
}
