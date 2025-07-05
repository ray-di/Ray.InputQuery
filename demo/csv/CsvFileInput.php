<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Koriym\FileUpload\FileUpload;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;

/**
 * Simple CSV File Input
 *
 * This Input object simply receives a CSV file and processing options.
 * It does NOT do any manual type conversion - that's Ray.InputQuery's job!
 */
final class CsvFileInput
{
    public function __construct(
        #[InputFile(
            allowedExtensions: ['csv', 'tsv'],
            allowedTypes: ['text/csv', 'text/plain', 'application/csv'],
            maxSize: 10 * 1024 * 1024, // 10MB
        )]
        public readonly FileUpload $csvFile,
        #[Input]
        public readonly string $delimiter = ',',
        #[Input]
        public readonly bool $hasHeader = true,
        #[Input]
        public readonly string $encoding = 'UTF-8',
        #[Input]
        public readonly string $importBatch = '',
    ) {
    }

    /**
     * Get file information
     */
    public function getFileInfo(): array
    {
        return [
            'name' => $this->csvFile->name,
            'size' => $this->csvFile->size,
            'type' => $this->csvFile->type,
            'delimiter' => $this->delimiter,
            'has_header' => $this->hasHeader,
            'encoding' => $this->encoding,
            'import_batch' => $this->importBatch,
        ];
    }
}
