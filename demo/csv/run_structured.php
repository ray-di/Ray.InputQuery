<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/UserInput.php';
require_once __DIR__ . '/CsvUsersImport.php';
require_once __DIR__ . '/CsvToInputConverter.php';

use Koriym\FileUpload\FileUpload;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\InputQuery\Demo\CsvToInputConverter;
use Ray\InputQuery\Demo\CsvUsersImport;
use Ray\InputQuery\FileUploadFactory;
use Ray\InputQuery\FileUploadFactoryInterface;
use Ray\InputQuery\InputQuery;

echo "=== Ray.InputQuery Structured CSV Demo ===\n\n";
echo "🎯 Demonstrating hierarchical Input objects from CSV data\n\n";

// Setup dependency injection
$injector = new Injector(new class extends AbstractModule {
    protected function configure(): void
    {
        $this->bind(FileUploadFactoryInterface::class)->to(FileUploadFactory::class);
    }
});

$inputQuery = new InputQuery($injector, new FileUploadFactory());

// Demo 1: Create structured Input objects from sample data
echo "1. Direct Input Object Creation (No CSV file)\n";
echo "==============================================\n";

$sampleData = CsvToInputConverter::getSampleQueryData();
$usersImport = $inputQuery->create(CsvUsersImport::class, $sampleData);

echo $usersImport->getImportSummary();
echo "\n";

echo "👥 Sample Users:\n";
foreach ($usersImport->getValidUsers() as $i => $user) {
    echo '  ' . ($i + 1) . '. ' . $user->getDisplayName() . ' (Active: ' . ($user->isActive ? 'Yes' : 'No') . ")\n";
}

echo "\n";

// Demo 2: Convert CSV file to structured data
echo "2. CSV File to Structured Input Objects\n";
echo "========================================\n";

$csvFile = FileUpload::create([
    'name' => 'users.csv',
    'type' => 'text/csv',
    'size' => filesize(__DIR__ . '/users.csv'),
    'tmp_name' => __DIR__ . '/users.csv',
    'error' => 0,
]);

$converter = new CsvToInputConverter(
    delimiter: ',',
    hasHeader: true,
    encoding: 'UTF-8',
);

$queryData = $converter->convertCsvToQueryData($csvFile, 'csv_demo_batch');
$csvImport = $inputQuery->create(CsvUsersImport::class, $queryData);

echo $csvImport->getImportSummary();
echo "\n";

// Demo 3: Individual User Input validation
echo "3. Individual User Input Objects\n";
echo "=================================\n";

foreach (array_slice($csvImport->getValidUsers(), 0, 3) as $i => $user) {
    echo 'User ' . ($i + 1) . ":\n";
    echo "  Name: {$user->name}\n";
    echo "  Email: {$user->email}\n";
    echo '  Age: ' . ($user->age ?? 'Not specified') . "\n";
    echo "  Department: {$user->department}\n";
    echo '  Active: ' . ($user->isActive ? 'Yes' : 'No') . "\n";
    echo '  Valid: ' . ($user->isValid() ? '✅ Yes' : '❌ No') . "\n";

    if (! $user->isValid()) {
        echo '  Errors: ' . implode(', ', $user->getValidationErrors()) . "\n";
    }

    echo "\n";
}

// Demo 4: Validation and Error Handling
echo "4. Validation and Error Handling\n";
echo "=================================\n";

// Create CSV with validation errors
$invalidCsv = "name,email,age,department,isActive\n";
$invalidCsv .= "Alice Johnson,invalid-email,abc,Engineering,true\n";
$invalidCsv .= ",bob@example.com,35,Marketing,true\n";
$invalidCsv .= "Carol Davis,carol@example.com,999,Sales,true\n";
file_put_contents(__DIR__ . '/invalid_users.csv', $invalidCsv);

$invalidFile = FileUpload::create([
    'name' => 'invalid_users.csv',
    'type' => 'text/csv',
    'size' => strlen($invalidCsv),
    'tmp_name' => __DIR__ . '/invalid_users.csv',
    'error' => 0,
]);

$invalidQueryData = $converter->convertCsvToQueryData($invalidFile, 'validation_test');
$invalidImport = $inputQuery->create(CsvUsersImport::class, $invalidQueryData);

echo $invalidImport->getImportSummary();
echo "\n";
echo $invalidImport->getValidationReport();

// Demo 5: Business Logic with Structured Data
echo "5. Business Logic with Structured Data\n";
echo "=======================================\n";

$validUsers = $csvImport->getValidUsers();
$engineeringUsers = array_filter($validUsers, static fn ($user) => $user->department === 'Engineering');
$activeUsers = array_filter($validUsers, static fn ($user) => $user->isActive);
$youngUsers = array_filter($validUsers, static fn ($user) => $user->age !== null && $user->age < 30);

echo "📊 Business Intelligence:\n";
echo '  Total Engineering Users: ' . count($engineeringUsers) . "\n";
echo '  Active Users: ' . count($activeUsers) . "\n";
echo '  Young Users (< 30): ' . count($youngUsers) . "\n\n";

echo "🏢 Engineering Team:\n";
foreach ($engineeringUsers as $user) {
    echo '  • ' . $user->getDisplayName() . "\n";
}

if (! empty($youngUsers)) {
    echo "\n👶 Young Talent:\n";
    foreach ($youngUsers as $user) {
        echo '  • ' . $user->getDisplayName() . "\n";
    }
}

// Demo 6: Type Safety Demonstration
echo "\n6. Type Safety Demonstration\n";
echo "=============================\n";

echo "🔒 Type Safety Benefits:\n";
echo "  ✅ Each user is a strongly-typed UserInput object\n";
echo "  ✅ Properties are immutable (readonly)\n";
echo "  ✅ Built-in validation methods\n";
echo "  ✅ IDE autocompletion and type checking\n";
echo "  ✅ No need to check if array keys exist\n\n";

// Example of type-safe operations
$firstUser = $csvImport->getValidUsers()[0] ?? null;
if ($firstUser) {
    echo "📝 Type-safe operations example:\n";
    echo "  User name: {$firstUser->name} (string)\n";
    echo '  User age: ' . ($firstUser->age ?? 'null') . " (int|null)\n";
    echo '  Is active: ' . ($firstUser->isActive ? 'true' : 'false') . " (bool)\n";
    echo '  Email domain: ' . substr(strrchr($firstUser->email, '@'), 1) . "\n";
}

// Cleanup
@unlink(__DIR__ . '/invalid_users.csv');

echo "\n🎉 Structured CSV Demo completed successfully!\n";
echo "\n💡 Key Advantages of Structured Input Objects:\n";
echo "  ✅ Type safety - No more array key checking\n";
echo "  ✅ Immutability - Data integrity guaranteed\n";
echo "  ✅ Validation - Built-in validation per object\n";
echo "  ✅ IDE Support - Full autocompletion and refactoring\n";
echo "  ✅ Business Logic - Clean, object-oriented operations\n";
echo "  ✅ Hierarchical Data - Complex nested structures supported\n";
echo "  ✅ Reusability - Input objects can be used across the application\n";
