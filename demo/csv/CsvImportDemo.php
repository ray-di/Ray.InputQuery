<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Koriym\FileUpload\FileUpload;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use SplFileObject;
use Throwable;

use function array_combine;
use function array_filter;
use function array_map;
use function array_unique;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filter_var;
use function implode;
use function is_numeric;
use function mb_convert_encoding;
use function mb_detect_encoding;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

use const FILTER_VALIDATE_EMAIL;

/**
 * CSV Import Demo
 *
 * Demonstrates how Ray.InputQuery can handle CSV file uploads
 * with validation and processing options.
 */
final class CsvImportDemo
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
        public readonly bool $skipEmptyRows = true,
    ) {
    }

    public function processUsers(): array
    {
        $results = [
            'success' => true,
            'processed' => 0,
            'errors' => [],
            'users' => [],
        ];

        try {
            // Handle encoding conversion if needed
            $tempFile = null;
            $fileContent = file_get_contents($this->csvFile->tmp_name);
            if ($this->encoding !== 'UTF-8') {
                $detectedEncoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'Shift_JIS'], true);
                if ($detectedEncoding && $detectedEncoding !== 'UTF-8') {
                    $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $detectedEncoding);
                    $tempFile = tempnam(sys_get_temp_dir(), 'csv_utf8_');
                    file_put_contents($tempFile, $fileContent);
                    $csvData = new SplFileObject($tempFile);
                } else {
                    $csvData = new SplFileObject($this->csvFile->tmp_name);
                }
            } else {
                $csvData = new SplFileObject($this->csvFile->tmp_name);
            }

            $csvData->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
            $csvData->setCsvControl($this->delimiter);

            $headers = [];
            $rowNumber = 0;

            foreach ($csvData as $row) {
                $rowNumber++;

                if ($this->skipEmptyRows && empty(array_filter($row))) {
                    continue;
                }

                // First row as headers
                if ($this->hasHeader && $rowNumber === 1) {
                    $headers = array_map('trim', $row);

                    // Validate headers
                    $headerValidation = $this->validateHeaders($headers);
                    if (! $headerValidation['valid']) {
                        $results['success'] = false;
                        $results['errors'][] = 'Header validation failed: ' . $headerValidation['error'];

                        return $results;
                    }

                    continue;
                }

                // Process data row
                if ($this->hasHeader && ! empty($headers)) {
                    // Validate row length matches header count
                    if (count($row) !== count($headers)) {
                        $results['errors'][] = "Row {$rowNumber}: Column count mismatch. Expected " . count($headers) . ' columns, got ' . count($row);
                        continue;
                    }

                    $userData = array_combine($headers, $row);
                } else {
                    $userData = [
                        'name' => $row[0] ?? '',
                        'email' => $row[1] ?? '',
                        'age' => $row[2] ?? '',
                        'department' => $row[3] ?? '',
                    ];
                }

                // Validate user data
                $validation = $this->validateUserData($userData);
                if ($validation['valid']) {
                    $results['users'][] = $validation['user'];
                    $results['processed']++;
                } else {
                    $results['errors'][] = "Row {$rowNumber}: " . $validation['error'];
                }
            }

            $results['file_info'] = [
                'name' => $this->csvFile->name,
                'size' => $this->csvFile->size,
                'delimiter' => $this->delimiter,
                'encoding' => $this->encoding,
                'has_header' => $this->hasHeader,
            ];
        } catch (Throwable $e) {
            $results['success'] = false;
            $results['errors'][] = 'File processing error: ' . $e->getMessage();
        } finally {
            // Clean up temporary file if created
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }
        }

        return $results;
    }

    private function validateHeaders(array $headers): array
    {
        $errors = [];

        // Check for empty headers
        foreach ($headers as $i => $header) {
            if (empty(trim($header))) {
                $errors[] = 'Empty header at column ' . ($i + 1);
            }
        }

        // Check for duplicate headers
        $uniqueHeaders = array_unique($headers);
        if (count($uniqueHeaders) !== count($headers)) {
            $errors[] = 'Duplicate headers found';
        }

        if (! empty($errors)) {
            return [
                'valid' => false,
                'error' => implode(', ', $errors),
            ];
        }

        return ['valid' => true];
    }

    private function validateUserData(array $data): array
    {
        $errors = [];

        // Validate name
        if (empty($data['name'] ?? '')) {
            $errors[] = 'Name is required';
        }

        // Validate email
        $email = $data['email'] ?? '';
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        // Validate age
        $age = $data['age'] ?? '';
        if (! empty($age) && (! is_numeric($age) || (int) $age < 0 || (int) $age > 150)) {
            $errors[] = 'Invalid age';
        }

        if (! empty($errors)) {
            return [
                'valid' => false,
                'error' => implode(', ', $errors),
            ];
        }

        return [
            'valid' => true,
            'user' => [
                'name' => trim($data['name'] ?? ''),
                'email' => trim($data['email'] ?? ''),
                'age' => ! empty($data['age']) ? (int) $data['age'] : null,
                'department' => trim($data['department'] ?? ''),
            ],
        ];
    }

    public function getSummary(): string
    {
        $results = $this->processUsers();

        return ImportSummaryFormatter::formatComplete($results);
    }
}
