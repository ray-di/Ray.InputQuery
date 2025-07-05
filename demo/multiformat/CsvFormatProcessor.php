<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Koriym\FileUpload\FileUpload;
use SplFileObject;

use function array_combine;
use function array_filter;
use function array_map;
use function count;

/**
 * CSV Format Processing Strategy
 */
final class CsvFormatProcessor implements FormatProcessorInterface
{
    public function supports(string $format): bool
    {
        return $format === 'csv' || $format === 'tsv';
    }

    public function process(FileUpload $file, array $options = []): array
    {
        $delimiter = $options['delimiter'] ?? ($options['format'] === 'tsv' ? "\t" : ',');
        $hasHeader = $options['has_header'] ?? true;

        $csvData = new SplFileObject($file->tmp_name);
        $csvData->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $csvData->setCsvControl($delimiter);

        $headers = [];
        $data = [];
        $rowNumber = 0;

        foreach ($csvData as $row) {
            $rowNumber++;

            if (empty(array_filter($row))) {
                continue;
            }

            if ($hasHeader && $rowNumber === 1) {
                $headers = array_map('trim', $row);
                continue;
            }

            if ($hasHeader && ! empty($headers)) {
                if (count($row) !== count($headers)) {
                    continue; // Skip malformed rows
                }

                $data[] = array_combine($headers, array_map('trim', $row));
            } else {
                $data[] = array_map('trim', $row);
            }
        }

        return [
            'items' => $data,
            'headers' => $headers,
            'total_rows' => count($data),
            'delimiter' => $delimiter,
        ];
    }
}
