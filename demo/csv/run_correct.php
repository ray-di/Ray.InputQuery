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

echo "=== Ray.InputQuery Correct CSV Pattern Demo ===\n\n";
echo "🎯 Demonstrating the CORRECT pattern: No manual type conversion!\n\n";

// Setup dependency injection
$injector = new Injector(new class extends AbstractModule {
    protected function configure(): void
    {
        $this->bind(FileUploadFactoryInterface::class)->to(FileUploadFactory::class);
    }
});

$inputQuery = new InputQuery($injector, new FileUploadFactory());

// Demo 1: Simple file input object
echo "1. Simple CSV File Input Object\n";
echo "================================\n";

$csvFile = FileUpload::create([
    'name' => 'users.csv',
    'type' => 'text/csv',
    'size' => filesize(__DIR__ . '/users.csv'),
    'tmp_name' => __DIR__ . '/users.csv',
    'error' => 0,
]);

// Create simple input object for the CSV file
$csvFileInput = $inputQuery->create(CsvFileInput::class, [
    'csvFile' => $csvFile,
    'delimiter' => ',',
    'hasHeader' => true,
    'encoding' => 'UTF-8',
    'importBatch' => 'correct_demo_001',
]);

echo "✅ Created CsvFileInput object!\n";
echo '📋 File Info: ' . json_encode($csvFileInput->getFileInfo(), JSON_PRETTY_PRINT) . "\n\n";

// Demo 2: Separate CSV processing (outside Ray.InputQuery)
echo "2. CSV Processing (Separate from Ray.InputQuery)\n";
echo "=================================================\n";

// Use separate utility for CSV parsing - NOT part of Ray.InputQuery
$converter = new CsvToInputConverter(
    delimiter: $csvFileInput->delimiter,
    hasHeader: $csvFileInput->hasHeader,
    encoding: $csvFileInput->encoding,
);

$queryData = $converter->convertCsvToQueryData($csvFileInput->csvFile, $csvFileInput->importBatch);

echo "✅ CSV parsed into query data structure!\n";
echo '📊 Raw data: ' . count($queryData['users']) . " users found\n\n";

// Demo 3: Ray.InputQuery handles ALL type conversion
echo "3. Ray.InputQuery Handles Type Conversion\n";
echo "==========================================\n";

// Ray.InputQuery automatically converts string data to proper types
$usersImport = $inputQuery->create(CsvUsersImport::class, $queryData);

echo "✅ Ray.InputQuery created CsvUsersImport with proper types!\n";
echo $usersImport->getImportSummary();
echo "\n";

// Demo 4: Verify type conversion worked correctly
echo "4. Type Conversion Verification\n";
echo "===============================\n";

$validUsers = $usersImport->getValidUsers();
foreach (array_slice($validUsers, 0, 2) as $i => $user) {
    echo '👤 User ' . ($i + 1) . ":\n";
    echo "  Name: '{$user->name}' (" . gettype($user->name) . ")\n";
    echo "  Email: '{$user->email}' (" . gettype($user->email) . ")\n";
    echo '  Age: ' . ($user->age ?? 'null') . ' (' . gettype($user->age) . ")\n";
    echo "  Department: '{$user->department}' (" . gettype($user->department) . ")\n";
    echo '  Active: ' . ($user->isActive ? 'true' : 'false') . ' (' . gettype($user->isActive) . ")\n";
    echo '  Valid: ' . ($user->isValid() ? '✅ Yes' : '❌ No') . "\n\n";
}

// Demo 5: Error handling demo
echo "5. Error Handling with Invalid Data\n";
echo "====================================\n";

$invalidCsv = "name,email,age,department,isActive\n";
$invalidCsv .= "Alice,invalid-email,not-a-number,Engineering,maybe\n";
$invalidCsv .= ",bob@example.com,35,Marketing,true\n";
file_put_contents(__DIR__ . '/invalid_test.csv', $invalidCsv);

$invalidFile = FileUpload::create([
    'name' => 'invalid_test.csv',
    'type' => 'text/csv',
    'size' => strlen($invalidCsv),
    'tmp_name' => __DIR__ . '/invalid_test.csv',
    'error' => 0,
]);

$invalidQueryData = $converter->convertCsvToQueryData($invalidFile, 'error_test');
$invalidImport = $inputQuery->create(CsvUsersImport::class, $invalidQueryData);

echo $invalidImport->getImportSummary();
echo "\n";
echo $invalidImport->getValidationReport();

// Demo 6: Sample data without CSV file
echo "6. Sample Data Processing\n";
echo "=========================\n";

$sampleData = CsvToInputConverter::getSampleQueryData();
$sampleImport = $inputQuery->create(CsvUsersImport::class, $sampleData);

echo $sampleImport->getImportSummary();

// Cleanup
@unlink(__DIR__ . '/invalid_test.csv');

echo "\n🎉 Correct CSV Pattern Demo completed!\n\n";

echo "✅ KEY PRINCIPLE: Separation of Concerns\n";
echo "=========================================\n";
echo "🔹 CsvFileInput: Simple Input object, just receives file + options\n";
echo "🔹 CsvToInputConverter: Pure utility, only parses CSV to arrays\n";
echo "🔹 Ray.InputQuery: Handles ALL type conversion automatically\n";
echo "🔹 UserInput/CsvUsersImport: Type-safe objects with validation\n\n";

echo "❌ WRONG: Manual type conversion inside Input objects\n";
echo "✅ RIGHT: Ray.InputQuery handles all type conversion\n\n";

echo "💡 This pattern ensures:\n";
echo "  • Clean separation of file parsing vs type conversion\n";
echo "  • Ray.InputQuery maintains control over type conversion\n";
echo "  • Input objects remain simple and focused\n";
echo "  • Type safety guaranteed by the framework\n";
