<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Koriym\FileUpload\FileUpload;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\Scope;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use Ray\InputQuery\InputQuery;
use SplFileObject;
use Throwable;

use function array_combine;
use function array_filter;
use function array_map;
use function array_unique;
use function assert;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filter_var;
use function implode;
use function is_numeric;
use function json_encode;
use function mb_convert_encoding;
use function mb_detect_encoding;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

use const FILTER_VALIDATE_EMAIL;

/**
 * CSV Import Demo - Core Ray.InputQuery Learning Example
 *
 * This demo illustrates the fundamental concepts of Ray.InputQuery:
 * 1. Raw data transformation to type-safe domain objects
 * 2. Automatic type conversion without manual effort
 * 3. Hierarchical Input object composition
 * 4. Service injection into Input objects
 * 5. Separation of data parsing from domain object creation
 */
class CsvDemo
{
    /**
     * Process CSV file using Ray.InputQuery
     *
     * Key Learning Points:
     * - CSV parsing is done OUTSIDE Ray.InputQuery (separation of concerns)
     * - Ray.InputQuery focuses on type-safe object creation from raw data
     * - Services are configured via DI container
     * - Multiple processing approaches can coexist
     */
    public function process(
        #[InputFile(
            allowedExtensions: ['csv'],
            allowedTypes: ['text/csv', 'text/plain', 'application/csv'],
            maxSize: 10 * 1024 * 1024, // 10MB
        )]
        FileUpload $csvFile,
        #[Input] string $delimiter = ',',
        #[Input] bool $hasHeader = true,
        #[Input] string $encoding = 'UTF-8',
        #[Input] bool $skipEmptyRows = true
    ): array {
        // Step 1: CSV parsing (OUTSIDE Ray.InputQuery scope)
        $saveFile = __DIR__ . '/tmp/saved.csv';
        $csvFile->move($saveFile);
        $csvData = new SplFileObject($saveFile);
        $csvData->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $csvData->setCsvControl($delimiter);
        
        // Step 2: DI Container setup with Singleton service
        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                // AgeGroup as Singleton - same instance shared across all Input objects
                $this->bind(AgeGroup::class)->in(Scope::SINGLETON);
            }
        });
        
        // Step 3: Ray.InputQuery setup
        $inputQuery = new InputQuery($injector);
        $method1 = new \ReflectionMethod(self::class, 'dump');
        $method2 = new \ReflectionMethod(self::class, 'dump2');
        
        // Step 4: Process each CSV row with Ray.InputQuery
        foreach ($csvData as $row) {
           // Raw CSV data transformed to associative array
           $query = ['name' => $row[0], 'email' => $row[1], 'age' => $row[2], 'role' => $row[3]];
           
           // Ray.InputQuery creates arguments with automatic type conversion
           $args1 = $inputQuery->getArguments($method1,$query);  // Primitive types approach
           $args2 = $inputQuery->getArguments($method2,$query);  // Input object approach
           
           // Invoke methods with type-safe arguments
           $method1->invokeArgs($this, $args1);
           $method2->invokeArgs($this, $args2);
        }
        
        // Step 5: Retrieve accumulated data from Singleton service
        $addGroup = $injector->getInstance(AgeGroup::class);
        assert($addGroup instanceof AgeGroup);
        $count = $addGroup->getTotalCount();
        echo "Total count: $count\n";
        $ageGroup = $addGroup->getGroups();
        $ageGroup = array_unique($ageGroup);
        echo "Age group: " . json_encode($ageGroup) . "\n";
        return;

    }

    public function dump(
        #[Input] string $name,  // Injected primitive type
        #[Input] string $email,
        #[Input] int $age,
        #[Input] string $role,
        AgeGroup $ageGroup      // Injected service for age grouping
    ): void
    {
        $ageGroup->addAge($age);
        echo "dump1: Name: $name Email: $email Age: $age Role: $role\n";
    }

    public function dump2(
        #[Input] UserInput $user, // Construct Input object with validation
        #[Input] int $email,       // You can mix Input objects with primitive types
        AgeGroup $ageGroup        // Injected service for age grouping
    ): void
    {
        $ageGroup->addAge($user->age);

        echo "dump2: Name: $user->name Email: $user->email Age: {$user->ageInput->age}:{$user->age}\n";
    }
}
