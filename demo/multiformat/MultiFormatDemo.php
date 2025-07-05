<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use InvalidArgumentException;
use Koriym\FileUpload\FileUpload;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use Throwable;

use function array_merge;
use function array_slice;
use function count;
use function is_array;
use function json_encode;
use function strlen;
use function substr;

use const JSON_UNESCAPED_UNICODE;

/**
 * Multi-Format File Processing Demo
 *
 * Demonstrates how Ray.InputQuery can handle multiple file formats
 * (CSV, JSON, XML, TXT) with unified processing using strategy pattern.
 */
final class MultiFormatDemo
{
    private FormatDetectorInterface $formatDetector;

    /** @var array<FormatProcessorInterface> */
    private array $processors;

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
        $this->formatDetector = new FileFormatDetector();
        $this->processors = [
            new CsvFormatProcessor(),
            new JsonFormatProcessor(),
        ];
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
            $detectedFormat = $this->autoDetect ? $this->formatDetector->detectFormat($this->dataFile) : $this->format;
            $results['detected_format'] = $detectedFormat;

            // Find appropriate processor
            $processor = $this->findProcessor($detectedFormat);
            if ($processor === null) {
                throw new InvalidArgumentException("Unsupported format: {$detectedFormat}");
            }

            // Process using strategy
            $processingOptions = array_merge($this->options, ['format' => $detectedFormat]);
            $results['data'] = $processor->process($this->dataFile, $processingOptions);

            $results['metadata'] = [
                'file_name' => $this->dataFile->name,
                'file_size' => $this->dataFile->size,
                'processed_format' => $detectedFormat,
                'records_count' => $results['data']['total_rows'] ?? $results['data']['total_items'] ?? 0,
                'processing_options' => $this->options,
            ];
        } catch (Throwable $e) {
            $results['success'] = false;
            $results['errors'][] = 'File processing error: ' . $e->getMessage();
        }

        return $results;
    }

    private function findProcessor(string $format): FormatProcessorInterface|null
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($format)) {
                return $processor;
            }
        }

        return null;
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
            $data = $results['data'];
            $items = $data['items'] ?? [];
            $recordCount = count($items);
            $summary .= "📊 Processing Results:\n";
            $summary .= "  Records Found: {$recordCount}\n";

            if ($recordCount > 0) {
                $summary .= "  Sample Data:\n";

                // Show first few records
                $sampleData = array_slice($items, 0, 3);
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
