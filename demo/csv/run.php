<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/UserInput.php';
require_once __DIR__ . '/CsvUsersImport.php';
require_once __DIR__ . '/CsvFileInput.php';
require_once __DIR__ . '/CsvToInputConverter.php';

use Koriym\FileUpload\FileUpload;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\InputQuery\Demo\CsvFileInput;
use Ray\InputQuery\Demo\CsvToInputConverter;
use Ray\InputQuery\Demo\CsvUsersImport;
use Ray\InputQuery\FileUploadFactory;
use Ray\InputQuery\FileUploadFactoryInterface;
use Ray\InputQuery\InputQuery;

echo "=== Ray.InputQuery CSV Demo ===\n\n";

// Setup dependency injection
$injector = new Injector(new class extends AbstractModule {
    protected function configure(): void
    {
        $this->bind(FileUploadFactoryInterface::class)->to(FileUploadFactory::class);
    }
});

$inputQuery = new InputQuery($injector, new FileUploadFactory());

// Demo 1: Simple CSV file input
echo "1. CSV File Input Object Creation\n";
echo "==================================\n";

$csvFile = FileUpload::create([
    'name' => 'users.csv',
    'type' => 'text/csv',
    'size' => filesize(__DIR__ . '/users.csv'),
    'tmp_name' => __DIR__ . '/users.csv',
    'error' => 0,
]);

$csvFileInput = $inputQuery->create(CsvFileInput::class, [
    'csvFile' => $csvFile,
    'delimiter' => ',',
    'hasHeader' => true,
    'encoding' => 'UTF-8',
    'importBatch' => 'demo_batch_001',
]);

echo "✅ Created CsvFileInput object!\n";
echo '📋 File Info: ' . json_encode($csvFileInput->getFileInfo(), JSON_PRETTY_PRINT) . "\n\n";

// Demo 2: Convert CSV to structured data
echo "2. CSV Processing & Type Conversion\n";
echo "===================================\n";

$converter = new CsvToInputConverter(
    delimiter: $csvFileInput->delimiter,
    hasHeader: $csvFileInput->hasHeader,
    encoding: $csvFileInput->encoding,
);

$queryData = $converter->convertCsvToQueryData($csvFileInput->csvFile, $csvFileInput->importBatch);
$usersImport = $inputQuery->create(CsvUsersImport::class, $queryData);

echo $usersImport->getImportSummary();

// Demo 3: Working with type-safe user objects
echo "3. Type-Safe User Objects\n";
echo "=========================\n";

$validUsers = $usersImport->getValidUsers();
foreach (array_slice($validUsers, 0, 3) as $i => $user) {
    echo '👤 User ' . ($i + 1) . ": {$user->getDisplayName()}\n";
    echo '  Age: ' . ($user->age ?? 'Not specified') . ' (' . gettype($user->age) . ")\n";
    echo '  Active: ' . ($user->isActive ? 'Yes' : 'No') . ' (' . gettype($user->isActive) . ")\n";
    echo '  Valid: ' . ($user->isValid() ? '✅ Yes' : '❌ No') . "\n\n";
}

// Demo 4: Error handling
echo "4. Error Handling with Invalid Data\n";
echo "====================================\n";

$invalidCsv = "name,email,age,department,isActive\n";
$invalidCsv .= "Alice,invalid-email,not-a-number,Engineering,maybe\n";
$invalidCsv .= ",bob@example.com,35,Marketing,true\n";
file_put_contents(__DIR__ . '/test_invalid.csv', $invalidCsv);

$invalidFile = FileUpload::create([
    'name' => 'test_invalid.csv',
    'type' => 'text/csv',
    'size' => strlen($invalidCsv),
    'tmp_name' => __DIR__ . '/test_invalid.csv',
    'error' => 0,
]);

$invalidQueryData = $converter->convertCsvToQueryData($invalidFile, 'error_test');
$invalidImport = $inputQuery->create(CsvUsersImport::class, $invalidQueryData);

echo $invalidImport->getImportSummary();
echo "\n";
echo $invalidImport->getValidationReport();

// Demo 5: TSV format
echo "5. TSV Format Support\n";
echo "=====================\n";

$tsvContent = str_replace(',', "\t", file_get_contents(__DIR__ . '/users.csv'));
file_put_contents(__DIR__ . '/test_users.tsv', $tsvContent);

$tsvFile = FileUpload::create([
    'name' => 'test_users.tsv',
    'type' => 'text/tab-separated-values',
    'size' => strlen($tsvContent),
    'tmp_name' => __DIR__ . '/test_users.tsv',
    'error' => 0,
]);

$tsvFileInput = $inputQuery->create(CsvFileInput::class, [
    'csvFile' => $tsvFile,
    'delimiter' => "\t",
    'hasHeader' => true,
    'importBatch' => 'tsv_demo',
]);

$tsvConverter = new CsvToInputConverter(delimiter: "\t", hasHeader: true);
$tsvQueryData = $tsvConverter->convertCsvToQueryData($tsvFile, 'tsv_batch');
$tsvImport = $inputQuery->create(CsvUsersImport::class, $tsvQueryData);

echo $tsvImport->getImportSummary();

// Cleanup
@unlink(__DIR__ . '/test_invalid.csv');
@unlink(__DIR__ . '/test_users.tsv');

echo "\n🎉 CSV Demo completed successfully!\n\n";

echo "✅ Key Principles Demonstrated:\n";
echo "================================\n";
echo "🔹 Input objects receive data, don't convert types\n";
echo "🔹 Ray.InputQuery handles ALL type conversion automatically\n";
echo "🔹 Separate utilities for CSV parsing (outside Ray.InputQuery)\n";
echo "🔹 Type safety guaranteed by the framework\n";
echo "🔹 Clean separation of concerns\n";
