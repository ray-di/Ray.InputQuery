<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use DateTimeImmutable;
use Ray\InputQuery\Attribute\Input;

use function array_filter;
use function array_sum;
use function arsort;
use function count;
use function round;

/**
 * CSV Users Import Input Object
 *
 * Represents a complete CSV import operation with structured user data.
 * This demonstrates Ray.InputQuery's power to create hierarchical input objects.
 */
final class CsvUsersImport
{
    /** @param array<UserInput> $users */
    public function __construct(
        #[Input(item: UserInput::class)]
        public readonly array $users,
        #[Input]
        public readonly string $source = 'csv_import',
        #[Input]
        public readonly string $importBatch = '',
        #[Input]
        public readonly DateTimeImmutable $importedAt = new DateTimeImmutable(),
    ) {
    }

    public function getValidUsers(): array
    {
        return array_filter($this->users, static fn (UserInput $user) => $user->isValid());
    }

    public function getInvalidUsers(): array
    {
        return array_filter($this->users, static fn (UserInput $user) => ! $user->isValid());
    }

    public function getStatistics(): array
    {
        $validUsers = $this->getValidUsers();
        $invalidUsers = $this->getInvalidUsers();

        $departments = [];
        $ageGroups = ['under_25' => 0, '25_35' => 0, '36_50' => 0, 'over_50' => 0];

        foreach ($validUsers as $user) {
            // Department statistics
            if (! empty($user->department)) {
                $departments[$user->department] = ($departments[$user->department] ?? 0) + 1;
            }

            // Age group statistics
            if ($user->age !== null) {
                if ($user->age < 25) {
                    $ageGroups['under_25']++;
                } elseif ($user->age <= 35) {
                    $ageGroups['25_35']++;
                } elseif ($user->age <= 50) {
                    $ageGroups['36_50']++;
                } else {
                    $ageGroups['over_50']++;
                }
            }
        }

        return [
            'total_users' => count($this->users),
            'valid_users' => count($validUsers),
            'invalid_users' => count($invalidUsers),
            'success_rate' => count($this->users) > 0 ? round(count($validUsers) / count($this->users) * 100, 2) : 0,
            'departments' => $departments,
            'age_groups' => $ageGroups,
            'import_info' => [
                'source' => $this->source,
                'batch' => $this->importBatch,
                'imported_at' => $this->importedAt->format('Y-m-d H:i:s'),
            ],
        ];
    }

    public function getImportSummary(): string
    {
        $stats = $this->getStatistics();

        $summary = "📊 CSV Users Import Summary\n";
        $summary .= "===========================\n";
        $summary .= "Import Source: {$this->source}\n";
        $summary .= 'Import Batch: ' . ($this->importBatch ?: 'Default') . "\n";
        $summary .= 'Import Time: ' . $this->importedAt->format('Y-m-d H:i:s') . "\n\n";

        $summary .= "📈 Statistics:\n";
        $summary .= "  Total Users: {$stats['total_users']}\n";
        $summary .= "  Valid Users: {$stats['valid_users']}\n";
        $summary .= "  Invalid Users: {$stats['invalid_users']}\n";
        $summary .= "  Success Rate: {$stats['success_rate']}%\n\n";

        if (! empty($stats['departments'])) {
            $summary .= "🏢 Department Breakdown:\n";
            arsort($stats['departments']);
            foreach ($stats['departments'] as $dept => $count) {
                $summary .= "  {$dept}: {$count} users\n";
            }

            $summary .= "\n";
        }

        $ageGroups = $stats['age_groups'];
        if (array_sum($ageGroups) > 0) {
            $summary .= "👥 Age Group Distribution:\n";
            $summary .= "  Under 25: {$ageGroups['under_25']} users\n";
            $summary .= "  25-35: {$ageGroups['25_35']} users\n";
            $summary .= "  36-50: {$ageGroups['36_50']} users\n";
            $summary .= "  Over 50: {$ageGroups['over_50']} users\n";
        }

        return $summary;
    }

    public function getValidationReport(): string
    {
        $invalidUsers = $this->getInvalidUsers();

        if (empty($invalidUsers)) {
            return "✅ All users passed validation!\n";
        }

        $report = "❌ Validation Errors Found:\n";
        $report .= "============================\n";

        foreach ($invalidUsers as $i => $user) {
            $errors = $user->getValidationErrors();
            $report .= 'User ' . ($i + 1) . ": {$user->name} <{$user->email}>\n";
            foreach ($errors as $error) {
                $report .= "  • {$error}\n";
            }

            $report .= "\n";
        }

        return $report;
    }
}
