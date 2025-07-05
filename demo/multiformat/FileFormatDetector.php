<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Koriym\FileUpload\FileUpload;

use function file_get_contents;
use function json_decode;
use function pathinfo;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function substr;
use function trim;

use const PATHINFO_EXTENSION;

/**
 * File Format Detection Strategy
 */
final class FileFormatDetector implements FormatDetectorInterface
{
    public function detectFormat(FileUpload $file): string
    {
        // First try extension-based detection
        $extension = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));
        $formatFromExtension = $this->getFormatFromExtension($extension);

        // If extension detection is uncertain, analyze content
        if ($formatFromExtension === 'unknown') {
            return $this->detectFromContent($file);
        }

        return $formatFromExtension;
    }

    private function getFormatFromExtension(string $extension): string
    {
        return match ($extension) {
            'csv' => 'csv',
            'tsv' => 'tsv',
            'json' => 'json',
            'xml' => 'xml',
            'txt' => 'txt',
            default => 'unknown'
        };
    }

    private function detectFromContent(FileUpload $file): string
    {
        $content = file_get_contents($file->tmp_name);
        $trimmedContent = trim($content);

        // JSON detection
        if ($this->looksLikeJson($trimmedContent)) {
            return 'json';
        }

        // XML detection
        if ($this->looksLikeXml($trimmedContent)) {
            return 'xml';
        }

        // CSV detection (with tabs = TSV)
        if ($this->looksLikeCsv($trimmedContent)) {
            return str_contains($content, "\t") ? 'tsv' : 'csv';
        }

        return 'txt';
    }

    private function looksLikeJson(string $content): bool
    {
        return (str_starts_with($content, '{') || str_starts_with($content, '['))
            && json_decode($content) !== null;
    }

    private function looksLikeXml(string $content): bool
    {
        return str_starts_with($content, '<?xml') || str_starts_with($content, '<');
    }

    private function looksLikeCsv(string $content): bool
    {
        $firstLine = substr($content, 0, 1000);

        return str_contains($firstLine, ',') || str_contains($firstLine, "\t");
    }
}
