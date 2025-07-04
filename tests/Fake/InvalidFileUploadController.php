<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Koriym\FileUpload\FileUpload;
use Ray\InputQuery\Attribute\Input;

final class InvalidFileUploadController
{
    /**
     * Invalid usage: #[Input] with FileUpload type
     */
    public function uploadWithWrongAttribute(
        #[Input] FileUpload $file
    ): void {
        // This should throw InvalidFileUploadAttributeException
    }

    /**
     * Invalid usage: #[Input] with FileUpload array
     */
    public function uploadArrayWithWrongAttribute(
        #[Input(item: FileUpload::class)] array $files
    ): void {
        // This should throw InvalidFileUploadAttributeException
    }
}