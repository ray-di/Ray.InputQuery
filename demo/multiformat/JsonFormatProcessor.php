<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Koriym\FileUpload\FileUpload;

use function count;
use function file_get_contents;
use function is_array;
use function json_decode;
use function reset;

use const JSON_THROW_ON_ERROR;

/**
 * JSON Format Processing Strategy
 */
final class JsonFormatProcessor implements FormatProcessorInterface
{
    public function supports(string $format): bool
    {
        return $format === 'json';
    }

    public function process(FileUpload $file, array $options = []): array
    {
        // Check file size to prevent memory issues
        if ($file->size > 50 * 1024 * 1024) { // 50MB limit for JSON
            throw new \RuntimeException('JSON file too large for processing');
        }

        $content = file_get_contents($file->tmp_name);
        if ($content === false) {
            throw new \RuntimeException('Failed to read file content');
        }

        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $items = is_array($data) ? $data : [$data];

        return [
            'items' => $items,
            'total_items' => count($items),
            'structure' => $this->analyzeStructure($data),
        ];
    }

    private function analyzeStructure(mixed $data): array
    {
        if (! is_array($data)) {
            return ['type' => 'single_object'];
        }

        if (empty($data)) {
            return ['type' => 'empty_array'];
        }

        $firstItem = reset($data);
        if (is_array($firstItem)) {
            return ['type' => 'array_of_objects'];
        }

        return ['type' => 'flat_array'];
    }
}
