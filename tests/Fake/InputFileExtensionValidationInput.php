<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Koriym\FileUpload\FileUpload;
use Koriym\FileUpload\ErrorFileUpload;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;

final class InputFileExtensionValidationInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[InputFile(
            maxSize: 5 * 1024 * 1024,
            allowedTypes: ['image/jpeg', 'image/png'],
            allowedExtensions: ['jpg', 'jpeg', 'png']
        )]
        public readonly FileUpload|ErrorFileUpload $avatar,
    ) {
    }
}