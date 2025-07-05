<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Koriym\FileUpload\FileUpload;

/**
 * Interface for format processing strategies
 */
interface FormatProcessorInterface
{
    public function supports(string $format): bool;

    public function process(FileUpload $file, array $options = []): array;
}
