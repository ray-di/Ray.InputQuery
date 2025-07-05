<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use InvalidArgumentException;
use Koriym\FileUpload\FileUpload;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use SimpleXMLElement;
use SplFileObject;
use Throwable;

use function array_combine;
use function array_filter;
use function array_map;
use function array_pad;
use function array_slice;
use function count;
use function explode;
use function file_get_contents;
use function is_array;
use function json_decode;
use function json_encode;
use function json_last_error;
use function pathinfo;
use function str_contains;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function substr_count;
use function trim;

use const JSON_ERROR_NONE;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;

/**
 * Multi-Format File Processing Demo
 *
 * Demonstrates how Ray.InputQuery can handle multiple file formats
 * (CSV, JSON, XML, TXT) with unified processing.
 */
final class MultiFormatDemo
{
    public function __construct(
        #[InputFile(
            allowedExtensions: ['csv', 'json', 'xml', 'txt', 'tsv'],
            allowedTypes: [
                'text/csv',
                'application/csv',
                'application/json',
                'text/json',
                'application/xml',
                'text/xml',
                'text/plain',
            ],
            maxSize: 20 * 1024 * 1024, // 20MB
        )]
        public readonly FileUpload $dataFile,
        #[Input]
        public readonly string $format, // auto, csv, json, xml, txt
        #[Input]
        public readonly bool $autoDetect = true,
        #[Input]
        public readonly array $options = [],
    ) {
    }

    public function processFile(): array
    {
        $results = [
            'success' => true,
            'format' => $this->format,
            'detected_format' => null,
            'data' => [],
            'metadata' => [],
            'errors' => [],
        ];

        try {
            // Auto-detect format if requested
            $detectedFormat = $this->autoDetect ? $this->detectFileFormat() : $this->format;
            $results['detected_format'] = $detectedFormat;

            // Process based on format
            switch ($detectedFormat) {
                case 'csv':
                case 'tsv':
                    $results['data'] = $this->processCsvFile($detectedFormat === 'tsv' ? "\t" : ',');
                    break;
                case 'json':
                    $results['data'] = $this->processJsonFile();
                    break;
                case 'xml':
                    $results['data'] = $this->processXmlFile();
                    break;
                case 'txt':
                    $results['data'] = $this->processTextFile();
                    break;
                default:
                    throw new InvalidArgumentException("Unsupported format: {$detectedFormat}");
            }

            $results['metadata'] = [
                'file_name' => $this->dataFile->name,
                'file_size' => $this->dataFile->size,
                'processed_format' => $detectedFormat,
                'records_count' => count($results['data']),
                'processing_options' => $this->options,
            ];
        } catch (Throwable $e) {
            $results['success'] = false;
            $results['errors'][] = 'File processing error: ' . $e->getMessage();
        }

        return $results;
    }

    private function detectFileFormat(): string
    {
        $extension = strtolower(pathinfo($this->dataFile->name, PATHINFO_EXTENSION));

        // If format is explicitly set and not 'auto', use it
        if ($this->format !== 'auto') {
            return $this->format;
        }

        // Detect by extension
        switch ($extension) {
            case 'csv':
                return 'csv';

            case 'tsv':
                return 'tsv';

            case 'json':
                return 'json';

            case 'xml':
                return 'xml';

            case 'txt':
                return 'txt';

            default:
                // Try to detect by content
                return $this->detectFormatByContent();
        }
    }

    private function detectFormatByContent(): string
    {
        $content = file_get_contents($this->dataFile->tmp_name, false, null, 0, 1024);

        // Try JSON
        if ($this->isValidJson($content)) {
            return 'json';
        }

        // Try XML
        if (str_starts_with(trim($content), '<?xml') || str_starts_with(trim($content), '<')) {
            return 'xml';
        }

        // Check for CSV patterns
        if (str_contains($content, ',') && substr_count($content, ',') > substr_count($content, "\t")) {
            return 'csv';
        }

        // Check for TSV patterns
        if (str_contains($content, "\t")) {
            return 'tsv';
        }

        // Default to text
        return 'txt';
    }

    private function isValidJson(string $content): bool
    {
        json_decode($content);

        return json_last_error() === JSON_ERROR_NONE;
    }

    private function processCsvFile(string $delimiter = ','): array
    {
        $data = [];
        $csvData = new SplFileObject($this->dataFile->tmp_name);
        $csvData->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $csvData->setCsvControl($delimiter);

        $headers = null;
        $hasHeader = $this->options['has_header'] ?? true;

        foreach ($csvData as $rowNumber => $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            if ($hasHeader && $rowNumber === 0) {
                $headers = $row;
                continue;
            }

            if ($headers) {
                $data[] = array_combine($headers, array_pad($row, count($headers), ''));
            } else {
                $data[] = $row;
            }
        }

        return $data;
    }

    private function processJsonFile(): array
    {
        $content = file_get_contents($this->dataFile->tmp_name);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        // Ensure we return an array of records
        if (! is_array($data)) {
            return [$data];
        }

        // If it's an associative array (single object), wrap it
        if (! isset($data[0]) && ! empty($data)) {
            return [$data];
        }

        return $data;
    }

    private function processXmlFile(): array
    {
        $content = file_get_contents($this->dataFile->tmp_name);
        $xml = new SimpleXMLElement($content);

        // Convert XML to array
        return $this->xmlToArray($xml);
    }

    private function xmlToArray(SimpleXMLElement $xml): array
    {
        $data = [];

        foreach ($xml->children() as $child) {
            if ($child->count() > 0) {
                $data[] = $this->xmlElementToArray($child);
            } else {
                $data[] = (string) $child;
            }
        }

        return $data;
    }

    private function xmlElementToArray(SimpleXMLElement $element): array
    {
        $array = [];

        foreach ($element->children() as $child) {
            $name = $child->getName();
            if ($child->count() > 0) {
                $array[$name] = $this->xmlElementToArray($child);
            } else {
                $array[$name] = (string) $child;
            }
        }

        return $array;
    }

    private function processTextFile(): array
    {
        $content = file_get_contents($this->dataFile->tmp_name);
        $lines = explode("\n", $content);

        return array_map('trim', array_filter($lines));
    }

    public function getSummary(): string
    {
        $results = $this->processFile();

        $summary = "🔄 Multi-Format Processing Summary\n";
        $summary .= "==================================\n";
        $summary .= "File: {$this->dataFile->name} ({$this->dataFile->size} bytes)\n";
        $summary .= "Requested Format: {$this->format}\n";
        $summary .= 'Detected Format: ' . ($results['detected_format'] ?? 'Unknown') . "\n";
        $summary .= 'Auto-Detection: ' . ($this->autoDetect ? 'Enabled' : 'Disabled') . "\n";
        $summary .= 'Status: ' . ($results['success'] ? '✅ Success' : '❌ Failed') . "\n\n";

        if ($results['success']) {
            $recordCount = count($results['data']);
            $summary .= "📊 Processing Results:\n";
            $summary .= "  Records Found: {$recordCount}\n";

            if ($recordCount > 0) {
                $summary .= "  Sample Data:\n";

                // Show first few records
                $sampleData = array_slice($results['data'], 0, 3);
                foreach ($sampleData as $i => $record) {
                    $recordStr = is_array($record) ? json_encode($record, JSON_UNESCAPED_UNICODE) : $record;
                    $preview = strlen($recordStr) > 80 ? substr($recordStr, 0, 77) . '...' : $recordStr;
                    $summary .= '    ' . ($i + 1) . ". {$preview}\n";
                }

                if ($recordCount > 3) {
                    $remaining = $recordCount - 3;
                    $summary .= "    ... and {$remaining} more records\n";
                }
            }
        }

        if (! empty($results['errors'])) {
            $summary .= "\n❌ Errors:\n";
            foreach ($results['errors'] as $error) {
                $summary .= "  • {$error}\n";
            }
        }

        return $summary;
    }
}
