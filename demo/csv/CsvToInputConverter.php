<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use DateTimeImmutable;
use Koriym\FileUpload\FileUpload;
use SplFileObject;

use function array_filter;
use function array_map;
use function date;
use function lcfirst;
use function str_replace;
use function strtolower;
use function trim;
use function ucwords;

/**
 * CSV to Input Object Converter
 *
 * Converts CSV file data into structured query data that Ray.InputQuery can process.
 * This bridges the gap between raw CSV files and type-safe Input objects.
 */
final class CsvToInputConverter
{
    public function __construct(
        private string $delimiter = ',',
        private bool $hasHeader = true,
        private string $encoding = 'UTF-8',
    ) {
    }

    /**
     * Convert CSV file to structured query data for Ray.InputQuery
     */
    public function convertCsvToQueryData(FileUpload $csvFile, string $importBatch = ''): array
    {
        $csvData = new SplFileObject($csvFile->tmpName);
        $csvData->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $csvData->setCsvControl($this->delimiter);

        $headers = [];
        $users = [];
        $rowNumber = 0;

        foreach ($csvData as $row) {
            $rowNumber++;

            if ($row === false || empty(array_filter((array) $row))) {
                continue;
            }

            // First row as headers
            if ($this->hasHeader && $rowNumber === 1) {
                $headers = array_map('trim', $row);
                continue;
            }

            // Process data row
            if ($this->hasHeader && ! empty($headers)) {
                $userData = [];
                foreach ($headers as $i => $header) {
                    $value = isset($row[$i]) ? trim($row[$i]) : '';
                    $userData[$this->normalizeKey($header)] = $value;
                }
            } else {
                // Default column mapping when no headers
                $userData = [
                    'name' => trim($row[0] ?? ''),
                    'email' => trim($row[1] ?? ''),
                    'age' => trim($row[2] ?? ''),
                    'department' => trim($row[3] ?? ''),
                    'isActive' => trim($row[4] ?? 'true'),
                ];
            }

            $users[] = $userData;
        }

        // Return structured data for Ray.InputQuery
        return [
            'users' => $users,
            'source' => 'csv_import',
            'importBatch' => $importBatch ?: 'batch_' . date('YmdHis'),
            'importedAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Normalize CSV header keys to match Input object property names
     */
    private function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));

        // Common CSV header mappings
        $mappings = [
            'user_name' => 'name',
            'username' => 'name',
            'full_name' => 'name',
            'email_address' => 'email',
            'e-mail' => 'email',
            'dept' => 'department',
            'division' => 'department',
            'active' => 'isActive',
            'status' => 'isActive',
            'enabled' => 'isActive',
        ];

        if (isset($mappings[$key])) {
            return $mappings[$key];
        }

        // Convert snake_case to camelCase
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $key))));
    }

    /**
     * Get sample query data for demonstration
     */
    public static function getSampleQueryData(): array
    {
        return [
            'users' => [
                [
                    'name' => 'Alice Johnson',
                    'email' => 'alice@example.com',
                    'age' => '28',
                    'department' => 'Engineering',
                    'isActive' => 'true',
                ],
                [
                    'name' => 'Bob Smith',
                    'email' => 'bob@example.com',
                    'age' => '35',
                    'department' => 'Marketing',
                    'isActive' => 'true',
                ],
                [
                    'name' => 'Carol Davis',
                    'email' => 'carol@example.com',
                    'age' => '31',
                    'department' => 'Sales',
                    'isActive' => 'false',
                ],
            ],
            'source' => 'sample_data',
            'importBatch' => 'demo_batch_001',
            'importedAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }
}
