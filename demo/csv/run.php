<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/CsvDemo.php';
require_once __DIR__ . '/AgeGroup.php';
require_once __DIR__ . '/UserInput.php';
require_once __DIR__ . '/AgeInput.php';



use Koriym\FileUpload\FileUpload;
use Ray\Di\Injector;
use Ray\InputQuery\Demo\CsvDemo;
use Ray\InputQuery\Demo\CsvFileInput;
use Ray\InputQuery\Demo\CsvToInputConverter;
use Ray\InputQuery\Demo\CsvUsersImport;
use Ray\InputQuery\InputQuery;
use Ray\InputQuery\FileUploadFactory;

$inputQuery = new InputQuery(new Injector(), new FileUploadFactory());
$csvFile = FileUpload::fromFile(__DIR__ . '/users.csv',[
]);

$method = new ReflectionMethod(CsvDemo::class, 'process');
$args = $inputQuery->getArguments($method, [
    'csvFile' => $csvFile,
    'delimiter' => ','
]);
$method->invoke(new CsvDemo(), ...$args);

echo "✅ Key Principles Demonstrated:\n";
echo "================================\n";
echo "🔹 Input objects receive data, don't convert types\n";
echo "🔹 Ray.InputQuery handles ALL type conversion automatically\n";
echo "🔹 Separate utilities for CSV parsing (outside Ray.InputQuery)\n";
echo "🔹 Type safety guaranteed by the framework\n";
echo "🔹 Clean separation of concerns\n";
