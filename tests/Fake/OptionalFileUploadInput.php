<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;

final class OptionalFileUploadInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[InputFile] public readonly FileUpload|ErrorFileUpload|null $banner = null,
    ) {
    }
}