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
use function array_slice;
use function count;
use function filter_var;
use function implode;
use function is_numeric;
use function trim;

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
            $csvData = new SplFileObject($this->csvFile->tmp_name);
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
                    $headers = $row;
                    continue;
                }

                // Process data row
                if ($this->hasHeader && ! empty($headers)) {
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
        }

        return $results;
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

        $summary = "📊 CSV Import Summary\n";
        $summary .= "====================\n";
        $summary .= "File: {$this->csvFile->name} ({$this->csvFile->size} bytes)\n";
        $summary .= "Delimiter: '{$this->delimiter}'\n";
        $summary .= "Encoding: {$this->encoding}\n";
        $summary .= 'Has Header: ' . ($this->hasHeader ? 'Yes' : 'No') . "\n";
        $summary .= "Processed: {$results['processed']} users\n";
        $summary .= 'Errors: ' . count($results['errors']) . "\n\n";

        if (! empty($results['users'])) {
            $summary .= "✅ Successfully imported users:\n";
            foreach (array_slice($results['users'], 0, 5) as $i => $user) {
                $age = $user['age'] ? " (age: {$user['age']})" : '';
                $dept = $user['department'] ? " - {$user['department']}" : '';
                $summary .= '  ' . ($i + 1) . ". {$user['name']} <{$user['email']}>{$age}{$dept}\n";
            }

            if (count($results['users']) > 5) {
                $remaining = count($results['users']) - 5;
                $summary .= "  ... and {$remaining} more users\n";
            }
        }

        if (! empty($results['errors'])) {
            $summary .= "\n❌ Errors encountered:\n";
            foreach (array_slice($results['errors'], 0, 3) as $error) {
                $summary .= "  • {$error}\n";
            }

            if (count($results['errors']) > 3) {
                $remaining = count($results['errors']) - 3;
                $summary .= "  ... and {$remaining} more errors\n";
            }
        }

        return $summary;
    }
}
