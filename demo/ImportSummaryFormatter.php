<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use function array_slice;
use function count;
use function is_array;

/**
 * Shared Summary Formatter
 *
 * Provides consistent formatting for import summaries across CSV, JSON, and multi-format demos.
 */
final class ImportSummaryFormatter
{
    public static function formatFileInfo(array $fileInfo): string
    {
        $summary = "📊 Import Summary\n";
        $summary .= "=================\n";

        if (isset($fileInfo['name'])) {
            $summary .= "File: {$fileInfo['name']}";
            if (isset($fileInfo['size'])) {
                $summary .= " ({$fileInfo['size']} bytes)";
            }

            $summary .= "\n";
        }

        if (isset($fileInfo['delimiter'])) {
            $summary .= "Delimiter: '{$fileInfo['delimiter']}'\n";
        }

        if (isset($fileInfo['encoding'])) {
            $summary .= "Encoding: {$fileInfo['encoding']}\n";
        }

        if (isset($fileInfo['has_header'])) {
            $summary .= 'Has Header: ' . ($fileInfo['has_header'] ? 'Yes' : 'No') . "\n";
        }

        return $summary;
    }

    public static function formatProcessingStats(array $stats): string
    {
        $summary = '';

        if (isset($stats['processed'])) {
            $summary .= "Processed: {$stats['processed']} items\n";
        }

        if (isset($stats['errors']) && is_array($stats['errors'])) {
            $summary .= 'Errors: ' . count($stats['errors']) . "\n";
        }

        return $summary;
    }

    public static function formatUsersList(array $users, int $maxShow = 5): string
    {
        if (empty($users)) {
            return '';
        }

        $summary = "\n✅ Successfully imported users:\n";

        foreach (array_slice($users, 0, $maxShow) as $i => $user) {
            $age = isset($user['age']) && $user['age'] ? " (age: {$user['age']})" : '';
            $dept = isset($user['department']) && $user['department'] ? " - {$user['department']}" : '';
            $summary .= '  ' . ($i + 1) . ". {$user['name']} <{$user['email']}>{$age}{$dept}\n";
        }

        if (count($users) > $maxShow) {
            $remaining = count($users) - $maxShow;
            $summary .= "  ... and {$remaining} more users\n";
        }

        return $summary;
    }

    public static function formatErrorsList(array $errors, int $maxShow = 3): string
    {
        if (empty($errors)) {
            return '';
        }

        $summary = "\n❌ Errors encountered:\n";

        foreach (array_slice($errors, 0, $maxShow) as $error) {
            $summary .= "  • {$error}\n";
        }

        if (count($errors) > $maxShow) {
            $remaining = count($errors) - $maxShow;
            $summary .= "  ... and {$remaining} more errors\n";
        }

        return $summary;
    }

    public static function formatComplete(array $results): string
    {
        $summary = '';

        // File info
        if (isset($results['file_info'])) {
            $summary .= self::formatFileInfo($results['file_info']);
        }

        // Processing stats
        $summary .= self::formatProcessingStats($results);

        // Users list
        if (isset($results['users'])) {
            $summary .= self::formatUsersList($results['users']);
        }

        // Errors list
        if (isset($results['errors'])) {
            $summary .= self::formatErrorsList($results['errors']);
        }

        return $summary;
    }
}
