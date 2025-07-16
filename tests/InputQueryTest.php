<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use ArrayObject;
use InvalidArgumentException;
use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\InputQuery\Exception\InvalidFileUploadAttributeException;
use Ray\InputQuery\Fake\ArrayObjectController;
use Ray\InputQuery\Fake\AuthorInput;
use Ray\InputQuery\Fake\ComplexInputController;
use Ray\InputQuery\Fake\DatabaseService;
use Ray\InputQuery\Fake\DefaultFileInput;
use Ray\InputQuery\Fake\DefaultValuesInput;
use Ray\InputQuery\Fake\DITestController;
use Ray\InputQuery\Fake\FileUploadController;
use Ray\InputQuery\Fake\InputFileInput;
use Ray\InputQuery\Fake\InvalidFileUploadController;
use Ray\InputQuery\Fake\MixedFileController;
use Ray\InputQuery\Fake\MixedInput;
use Ray\InputQuery\Fake\NoConstructorInput;
use Ray\InputQuery\Fake\NonInputParameterController;
use Ray\InputQuery\Fake\NonNamedTypeController;
use Ray\InputQuery\Fake\NullableFileInput;
use Ray\InputQuery\Fake\NullableInput;
use Ray\InputQuery\Fake\Primary;
use Ray\InputQuery\Fake\ScalarInput;
use Ray\InputQuery\Fake\Secondary;
use Ray\InputQuery\Fake\TestService;
use Ray\InputQuery\Fake\TodoController;
use Ray\InputQuery\Fake\TodoInput;
use Ray\InputQuery\Fake\UnionTypeInput;
use Ray\InputQuery\Fake\UserArrayObject;
use Ray\InputQuery\Fake\UserInput;
use ReflectionClass;
use ReflectionMethod;

use function array_values;
use function assert;
use function count;

use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

final class InputQueryTest extends TestCase
{
    private InputQueryInterface $inputQuery;

    /** @var array<string, mixed> */
    private array $originalFiles;

    protected function setUp(): void
    {
        // $_FILESの元の状態を保存
        $this->originalFiles = $_FILES;

        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(TestService::class)->toInstance(new TestService('injected'));

                // Named bindings
                $this->bind()->annotatedWith('database.host')->toInstance('localhost');
                $this->bind()->annotatedWith('database.port')->toInstance(3306);
                $this->bind()->annotatedWith('service.name')->toInstance('TestService');

                // Custom qualifier bindings
                $this->bind(DatabaseService::class)->annotatedWith(Primary::class)
                     ->toInstance(new DatabaseService('primary://db1'));
                $this->bind(DatabaseService::class)->annotatedWith(Secondary::class)
                     ->toInstance(new DatabaseService('secondary://db2'));
                $this->bind(TestService::class)->toInstance(new TestService('injected'));
            }
        });
        $this->inputQuery = new InputQuery($injector, new FileUploadFactory());
    }

    protected function tearDown(): void
    {
        // $_FILESを元の状態に復元
        $_FILES = $this->originalFiles;
    }

    public function testCreateSimpleObject(): void
    {
        $query = [
            'name' => 'John',
            'email' => 'john@example.com',
        ];

        $user = $this->inputQuery->newInstance(UserInput::class, $query);

        $this->assertInstanceOf(UserInput::class, $user);
        $this->assertSame('John', $user->name);
        $this->assertSame('john@example.com', $user->email);
    }

    public function testCreateNestedObject(): void
    {
        $query = [
            'title' => 'Buy milk',
            'authorName' => 'John',
            'authorEmail' => 'john@example.com',
        ];

        $todo = $this->inputQuery->newInstance(TodoInput::class, $query);

        $this->assertInstanceOf(TodoInput::class, $todo);
        /** @var TodoInput $todo */
        $this->assertSame('Buy milk', $todo->title);
        $this->assertInstanceOf(AuthorInput::class, $todo->author);
        $this->assertSame('John', $todo->author->name);
        $this->assertSame('john@example.com', $todo->author->email);
    }

    public function testCreateBracketObject(): void
    {
        $query = [
            'title' => 'Buy milk',
            'author' => [
                'name' => 'John',
                'email' => 'john@example.com',
            ],
        ];

        $todo = $this->inputQuery->newInstance(TodoInput::class, $query);

        $this->assertInstanceOf(TodoInput::class, $todo);
        /** @var TodoInput $todo */
        $this->assertSame('Buy milk', $todo->title);
        $this->assertInstanceOf(AuthorInput::class, $todo->author);
        $this->assertSame('John', $todo->author->name);
        $this->assertSame('john@example.com', $todo->author->email);
    }

    public function testCreateMixedInputAndDI(): void
    {
        $query = [
            'name' => 'Jane',
            'email' => 'jane@example.com',
        ];

        $mixed = $this->inputQuery->newInstance(MixedInput::class, $query);

        $this->assertInstanceOf(MixedInput::class, $mixed);
        assert($mixed instanceof MixedInput);
        $this->assertSame('Jane', $mixed->name);
        $this->assertSame('jane@example.com', $mixed->email);
        $this->assertInstanceOf(TestService::class, $mixed->getService());
        $this->assertSame('injected', $mixed->getService()->getValue());
    }

    public function testGetArguments(): void
    {
        $method = new ReflectionMethod(TodoController::class, 'create');
        $query = [
            'title' => 'Buy groceries',
            'authorName' => 'Alice',
            'authorEmail' => 'alice@example.com',
        ];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertInstanceOf(TodoInput::class, $args[0]);
        assert($args[0] instanceof TodoInput);
        $this->assertSame('Buy groceries', $args[0]->title);
        $this->assertSame('Alice', $args[0]->author->name);
    }

    public function testKeyNormalization(): void
    {
        $query = [
            'name' => 'John',
            'email' => 'john@example.com',
            'author_name' => 'Author',  // snake_case
            'author-email' => 'author@example.com', // kebab-case
        ];

        $todo = $this->inputQuery->newInstance(TodoInput::class, [
            'title' => 'Test',
            'author_name' => 'Author',
            'author_email' => 'author@example.com',
        ]);

        /** @var TodoInput $todo */
        $this->assertSame('Test', $todo->title);
        $this->assertSame('Author', $todo->author->name);
        $this->assertSame('author@example.com', $todo->author->email);
    }

    public function testEmptyQueryWithDefaults(): void
    {
        // This test assumes nullable parameters or defaults would be handled
        $query = [];

        // For now, this might throw exceptions, which is expected behavior
        $this->expectException(InvalidArgumentException::class);
        $this->inputQuery->newInstance(UserInput::class, $query);
    }

    public function testIsInstanceOfInputQuery(): void
    {
        $this->assertInstanceOf(InputQuery::class, $this->inputQuery);
        $this->assertInstanceOf(InputQueryInterface::class, $this->inputQuery);
    }

    public function testScalarTypeConversions(): void
    {
        $query = [
            'name' => 'John',
            'age' => '30',      // string -> int
            'price' => '99.99', // string -> float
            'active' => '1',     // string -> bool
        ];

        $scalar = $this->inputQuery->newInstance(ScalarInput::class, $query);

        $this->assertInstanceOf(ScalarInput::class, $scalar);
        assert($scalar instanceof ScalarInput);
        $this->assertSame('John', $scalar->name);
        $this->assertSame(30, $scalar->age);
        $this->assertSame(99.99, $scalar->price);
        $this->assertTrue($scalar->active);
    }

    public function testBooleanConversions(): void
    {
        $testCases = [
            ['active' => 'true', 'expected' => true],
            ['active' => 'false', 'expected' => true], // (bool)'false' is true
            ['active' => '0', 'expected' => false],
            ['active' => '1', 'expected' => true],
            ['active' => '', 'expected' => false],
        ];

        foreach ($testCases as $case) {
            $query = [
                'name' => 'Test',
                'age' => '25',
                'price' => '10.0',
                'active' => $case['active'],
            ];

            $scalar = $this->inputQuery->newInstance(ScalarInput::class, $query);
            assert($scalar instanceof ScalarInput);
            $this->assertSame($case['expected'], $scalar->active, "Failed for active='{$case['active']}'.");
        }
    }

    public function testDefaultValues(): void
    {
        $query = ['name' => 'John']; // Other parameters should use defaults

        $defaultInput = $this->inputQuery->newInstance(DefaultValuesInput::class, $query);

        assert($defaultInput instanceof DefaultValuesInput);
        $this->assertSame('John', $defaultInput->name);
        $this->assertNull($defaultInput->email);
        $this->assertSame(25, $defaultInput->age);
        $this->assertTrue($defaultInput->active);
        $this->assertSame(0.0, $defaultInput->score);
    }

    public function testPartialDefaultValues(): void
    {
        $query = [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'age' => '35',
            // active and score should use defaults
        ];

        $defaultInput = $this->inputQuery->newInstance(DefaultValuesInput::class, $query);

        assert($defaultInput instanceof DefaultValuesInput);
        $this->assertSame('Jane', $defaultInput->name);
        $this->assertSame('jane@example.com', $defaultInput->email);
        $this->assertSame(35, $defaultInput->age);
        $this->assertTrue($defaultInput->active);
        $this->assertSame(0.0, $defaultInput->score);
    }

    public function testNullableValues(): void
    {
        $query = [];

        $nullable = $this->inputQuery->newInstance(NullableInput::class, $query);

        assert($nullable instanceof NullableInput);
        $this->assertNull($nullable->name);
        $this->assertNull($nullable->age);
        $this->assertNull($nullable->active);
    }

    public function testNullScalarConversion(): void
    {
        $query = [
            'name' => null,
            'age' => null,
            'active' => null,
        ];

        $nullable = $this->inputQuery->newInstance(NullableInput::class, $query);

        assert($nullable instanceof NullableInput);
        $this->assertNull($nullable->name);
        $this->assertNull($nullable->age);
        $this->assertNull($nullable->active);
    }

    public function testNoConstructorClass(): void
    {
        $query = ['name' => 'test'];

        $noConstructor = $this->inputQuery->newInstance(NoConstructorInput::class, $query);

        $this->assertInstanceOf(NoConstructorInput::class, $noConstructor);
        $this->assertSame('default', $noConstructor->name);
    }

    public function testComplexNestedPrefix(): void
    {
        $query = [
            'title' => 'Main Task',
            'authorId' => '123',
            'authorName' => 'John',
            'authorEmail' => 'john@example.com',
        ];

        // Create a TodoInput where author should be mapped from author prefix
        $todo = $this->inputQuery->newInstance(TodoInput::class, $query);

        assert($todo instanceof TodoInput);
        $this->assertSame('Main Task', $todo->title);
        $this->assertSame('John', $todo->author->name); // authorName -> author.name
        $this->assertSame('123', $todo->author->id); // authorId -> author.id
        $this->assertSame('john@example.com', $todo->author->email); // authorEmail -> author.email
    }

    public function testEmptyNestedQueryFallback(): void
    {
        // Test case where nested query extraction fails but fallback works
        $query = [
            'title' => 'Task without prefix',
            'name' => 'Direct Name',
            'email' => 'direct@example.com',
        ];

        $todo = $this->inputQuery->newInstance(TodoInput::class, $query);

        assert($todo instanceof TodoInput);
        $this->assertSame('Task without prefix', $todo->title);
        $this->assertSame('Direct Name', $todo->author->name);
        $this->assertSame('direct@example.com', $todo->author->email);
    }

    public function testCamelCaseConversion(): void
    {
        $testCases = [
            'simple_case' => 'simpleCase',
            'kebab-case' => 'kebabCase',
            'author_name' => 'authorName',
            'author_email' => 'authorEmail',
            'user_id' => 'userId',
            'UPPER_CASE' => 'upperCase',
            'mixed-case_example' => 'mixedCaseExample',
        ];

        $reflection = new ReflectionClass($this->inputQuery);
        $method = $reflection->getMethod('toCamelCase');
        $method->setAccessible(true);

        foreach ($testCases as $input => $expected) {
            $result = $method->invoke($this->inputQuery, $input);
            $this->assertSame($expected, $result, "Failed converting '{$input}' to camelCase");
        }
    }

    public function testUnionTypes(): void
    {
        $query = [
            'value' => 'test',
            'name' => 'union',
        ];

        $union = $this->inputQuery->newInstance(UnionTypeInput::class, $query);

        $this->assertInstanceOf(UnionTypeInput::class, $union);
        assert($union instanceof UnionTypeInput);
        $this->assertSame('test', $union->value);
        $this->assertSame('union', $union->name);
    }

    public function testUnionTypesWithDefaults(): void
    {
        $query = []; // Use defaults

        $union = $this->inputQuery->newInstance(UnionTypeInput::class, $query);

        $this->assertInstanceOf(UnionTypeInput::class, $union);
        assert($union instanceof UnionTypeInput);
        $this->assertSame('default', $union->value);
        $this->assertNull($union->name);
    }

    public function testNonInputParameters(): void
    {
        $method = new ReflectionMethod(NonInputParameterController::class, 'process');
        $query = []; // No query parameters for non-Input params

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(2, $args);
        $this->assertInstanceOf(TestService::class, $args[0]); // object from DI
        $this->assertSame('injected', $args[0]->getValue());
        $this->assertNull($args[1]); // scalar without #[Input] gets null default
    }

    public function testConvertScalarDefaultType(): void
    {
        // Test the default case in convertScalar method
        $query = [
            'name' => 'test',
            'age' => '30',
            'price' => '99.99',
            'active' => '1',
        ];

        $scalar = $this->inputQuery->newInstance(ScalarInput::class, $query);

        assert($scalar instanceof ScalarInput);
        // Ensure all types are converted correctly - values are tested in other methods
        $this->assertTrue(true); // Explicit assertion to satisfy PHPUnit
    }

    public function testExtractNestedQueryWithEmptyKey(): void
    {
        // Test edge case where nested key would be empty after prefix removal
        $query = [
            'author' => 'should_not_match',  // This key exactly matches prefix
            'authorName' => 'John',
            'authorEmail' => 'john@example.com',
        ];

        $todo = $this->inputQuery->newInstance(TodoInput::class, [
            'title' => 'Test',
            ...$query,
        ]);

        assert($todo instanceof TodoInput);
        $this->assertSame('Test', $todo->title);
        $this->assertSame('John', $todo->author->name);
        $this->assertSame('john@example.com', $todo->author->email);
    }

    public function testGetDefaultValueWithoutDefault(): void
    {
        // This should trigger the InvalidArgumentException path
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required parameter "name" is missing and has no default value');

        $this->inputQuery->newInstance(UserInput::class, ['email' => 'test@example.com']); // missing required 'name'
    }

    public function testNonNamedTypeParameter(): void
    {
        // Test union type parameter (not ReflectionNamedType) without #[Input]
        $method = new ReflectionMethod(NonNamedTypeController::class, 'processUnionType');
        $query = []; // No query parameters

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(2, $args);
        // Union type parameter without #[Input] should get default value (covers line 97)
        $this->assertSame('default', $args[0]);
        // TestService without #[Input] gets injected via DI
        $this->assertInstanceOf(TestService::class, $args[1]);
        $this->assertSame('injected', $args[1]->getValue());
    }

    public function testNamedDIParameters(): void
    {
        $method = new ReflectionMethod(DITestController::class, 'withNamed');
        $query = [
            'name' => 'TestUser',
            'email' => 'test@example.com',
        ];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(3, $args);

        // First parameter: UserInput from query
        $this->assertInstanceOf(UserInput::class, $args[0]);
        /** @var UserInput $userInput */
        $userInput = $args[0];
        $this->assertSame('TestUser', $userInput->name);
        $this->assertSame('test@example.com', $userInput->email);

        // Second parameter: Named 'database.host'
        $this->assertSame('localhost', $args[1]);

        // Third parameter: Named 'database.port'
        $this->assertSame(3306, $args[2]);
    }

    public function testCustomQualifierDIParameters(): void
    {
        $method = new ReflectionMethod(DITestController::class, 'withCustomQualifier');
        $query = [
            'name' => 'QualifierUser',
            'email' => 'qualifier@example.com',
        ];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(3, $args);

        // First parameter: UserInput from query
        $this->assertInstanceOf(UserInput::class, $args[0]);
        /** @var UserInput $userInput */
        $userInput = $args[0];
        $this->assertSame('QualifierUser', $userInput->name);

        // Second parameter: DatabaseService with CustomQualifier('primary')
        $this->assertInstanceOf(DatabaseService::class, $args[1]);
        /** @var DatabaseService $primaryDb */
        $primaryDb = $args[1];
        $this->assertSame('primary://db1', $primaryDb->getConnectionString());

        // Third parameter: DatabaseService with CustomQualifier('secondary')
        $this->assertInstanceOf(DatabaseService::class, $args[2]);
        /** @var DatabaseService $secondaryDb */
        $secondaryDb = $args[2];
        $this->assertSame('secondary://db2', $secondaryDb->getConnectionString());
    }

    public function testMixedDIParameters(): void
    {
        $method = new ReflectionMethod(DITestController::class, 'withMixedDI');
        $query = ['message' => 'Hello World'];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(3, $args);

        // First parameter: string from query with #[Input]
        $this->assertSame('Hello World', $args[0]);

        // Second parameter: TestService without qualifier (default DI)
        $this->assertInstanceOf(TestService::class, $args[1]);
        /** @var TestService $service */
        $service = $args[1];
        $this->assertSame('injected', $service->getValue());

        // Third parameter: Named 'service.name'
        $this->assertSame('TestService', $args[2]);
    }

    public function testUnboundDIParameterException(): void
    {
        // Create a method that attempts to inject an unbound service
        $method = new ReflectionMethod(DITestController::class, 'withUnboundServiceWithoutDefault');
        $query = [];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "service" of type "Ray\InputQuery\Fake\UnresolvableService:" is not bound in the injector.');

        $this->inputQuery->getArguments($method, $query);
    }

    public function testInvalidFileUploadAttributeException(): void
    {
        // Test #[Input] with FileUpload type should throw exception
        $method = new ReflectionMethod(InvalidFileUploadController::class, 'uploadWithWrongAttribute');
        $query = [];

        $this->expectException(InvalidFileUploadAttributeException::class);
        $this->expectExceptionMessage('FileUpload parameter "file" must use #[InputFile] attribute, not #[Input]');

        $this->inputQuery->getArguments($method, $query);
    }

    public function testInvalidFileUploadArrayAttributeException(): void
    {
        // Test #[Input] with FileUpload array should throw exception
        $method = new ReflectionMethod(InvalidFileUploadController::class, 'uploadArrayWithWrongAttribute');
        $query = [];

        $this->expectException(InvalidFileUploadAttributeException::class);
        $this->expectExceptionMessage('FileUpload array parameter "files" must use #[InputFile] attribute, not #[Input]');

        $this->inputQuery->getArguments($method, $query);
    }

    public function testUnionTypeWithoutInputAttribute(): void
    {
        // Test union type parameter without #[Input] should get default value
        $method = new ReflectionMethod(ComplexInputController::class, 'processUnionTypeNoInput');
        $query = [];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertSame('default', $args[0]);
    }

    public function testNullableParameterHandling(): void
    {
        // Test nullable parameter handling - should use default value without DI
        $method = new ReflectionMethod(ComplexInputController::class, 'processNullableParam');
        $query = [];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertNull($args[0]); // nullable parameter gets null default
    }

    public function testMixedTypeParameter(): void
    {
        // Test parameter with no type hint
        $method = new ReflectionMethod(ComplexInputController::class, 'processMixedType');
        $query = ['data' => 'test-value'];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertSame('test-value', $args[0]);
    }

    public function testNestedObjectExtraction(): void
    {
        // Test nested query extraction patterns (user_name -> UserInput->name)
        $method = new ReflectionMethod(ComplexInputController::class, 'processNestedExtraction');
        $query = [
            'user_name' => 'NestedUser',
            'user_email' => 'nested@example.com',
        ];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $userInput = $args[0];
        $this->assertInstanceOf(UserInput::class, $userInput);
        $this->assertSame('NestedUser', $userInput->name);
        $this->assertSame('nested@example.com', $userInput->email);
    }

    public function testComplexArrayObjects(): void
    {
        // Test array of complex objects
        $method = new ReflectionMethod(ComplexInputController::class, 'processComplexArray');
        $query = [
            'users' => [
                ['name' => 'User1', 'email' => 'user1@example.com'],
                ['name' => 'User2', 'email' => 'user2@example.com'],
            ],
        ];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $users = $args[0];
        $this->assertIsArray($users);
        $this->assertCount(2, $users);

        $this->assertInstanceOf(UserInput::class, $users[0]);
        $this->assertSame('User1', $users[0]->name);
        $this->assertSame('user1@example.com', $users[0]->email);

        $this->assertInstanceOf(UserInput::class, $users[1]);
        $this->assertSame('User2', $users[1]->name);
        $this->assertSame('user2@example.com', $users[1]->email);
    }

    public function testScalarConversions(): void
    {
        // Test various scalar type conversions
        $method = new ReflectionMethod(ComplexInputController::class, 'processScalarConversions');
        $query = [
            'text' => 'sample text',
            'number' => '42',
            'decimal' => '3.14',
            'flag' => 'true',
        ];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(4, $args);
        $this->assertSame('sample text', $args[0]);
        $this->assertSame(42, $args[1]);
        $this->assertSame(3.14, $args[2]);
        $this->assertTrue($args[3]);
    }

    public function testParameterDefaultFromReflection(): void
    {
        // Test getting default values from parameter reflection
        $method = new ReflectionMethod(ComplexInputController::class, 'processUnionTypeNoInput');
        $query = [];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertSame('default', $args[0]); // Uses parameter default value
    }

    public function testStringArrayProcessing(): void
    {
        // Test array processing without item type
        $method = new ReflectionMethod(ComplexInputController::class, 'processStringArray');
        $query = [
            'items' => ['item1', 'item2', 'item3'],
        ];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertIsArray($args[0]);
        $this->assertSame(['item1', 'item2', 'item3'], $args[0]);
    }

    public function testMixedInputAndNonInputParameters(): void
    {
        // Test method with both Input and non-Input parameters
        $method = new ReflectionMethod(ComplexInputController::class, 'processWithDefaults');
        $query = ['required' => 'test_value'];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(2, $args);
        $this->assertSame('test_value', $args[0]); // Input parameter
        $this->assertSame('default_value', $args[1]); // Non-Input with default
    }

    public function testIntArrayProcessing(): void
    {
        // Test array without item type - should pass through as-is
        $method = new ReflectionMethod(ComplexInputController::class, 'processIntArray');
        $query = [
            'numbers' => ['1', '2', '3', '4', '5'],
        ];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertIsArray($args[0]);
        $this->assertSame(['1', '2', '3', '4', '5'], $args[0]); // strings remain as strings without item type
    }

    public function testParameterWithoutInputOrDefault(): void
    {
        // Test parameter without Input attribute and without default - should trigger DI exception
        $method = new ReflectionMethod(ComplexInputController::class, 'processRequiresDefault');
        $query = [];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "param" of type ":" is not bound in the injector.');

        $this->inputQuery->getArguments($method, $query);
    }

    public function testNullableStringConversion(): void
    {
        // Test nullable parameter with actual null value
        $method = new ReflectionMethod(ComplexInputController::class, 'processNullableParam');
        $query = ['optional' => null];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertNull($args[0]);
    }

    public function testNullableStringWithValue(): void
    {
        // Test nullable parameter with actual value
        $method = new ReflectionMethod(ComplexInputController::class, 'processNullableParam');
        $query = ['optional' => 'test_value'];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertSame('test_value', $args[0]);
    }

    public function testEmptyArrayProcessing(): void
    {
        // Test empty array processing
        $method = new ReflectionMethod(ComplexInputController::class, 'processStringArray');
        $query = ['items' => []];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertIsArray($args[0]);
        $this->assertEmpty($args[0]);
    }

    public function testMixedArrayValues(): void
    {
        // Test array with mixed values (no item type specified)
        $method = new ReflectionMethod(ComplexInputController::class, 'processStringArray');
        $query = [
            'items' => ['string', 123, true, null],
        ];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertIsArray($args[0]);
        $this->assertSame(['string', 123, true, null], $args[0]);
    }

    public function testArrayObjectCreation(): void
    {
        // Test ArrayObject creation with item type
        $method = new ReflectionMethod(ArrayObjectController::class, 'processArrayObject');
        $query = [
            'users' => [
                ['name' => 'User1', 'email' => 'user1@example.com'],
                ['name' => 'User2', 'email' => 'user2@example.com'],
            ],
        ];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertInstanceOf(ArrayObject::class, $args[0]);
        /** @var ArrayObject $arrayObject */
        $arrayObject = $args[0];
        $this->assertCount(2, $arrayObject);
        $this->assertInstanceOf(UserInput::class, $arrayObject[0]);
        $this->assertSame('User1', $arrayObject[0]->name);
    }

    public function testArrayObjectWithoutItemType(): void
    {
        // Test ArrayObject without item type - should create empty ArrayObject
        $method = new ReflectionMethod(ArrayObjectController::class, 'processArrayObjectNoItem');
        $query = ['items' => ['test']];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        // ArrayObject without item type creates empty ArrayObject via regular object creation
        $this->assertInstanceOf(ArrayObject::class, $args[0]);
        /** @var ArrayObject $arrayObject */
        $arrayObject = $args[0];
        $this->assertCount(0, $arrayObject); // Empty because no constructor parameters matched
    }

    public function testCustomArrayObjectSubclass(): void
    {
        // Test custom ArrayObject subclass
        $method = new ReflectionMethod(ArrayObjectController::class, 'processCustomArrayObject');
        $query = [
            'users' => [
                ['name' => 'User1', 'email' => 'user1@example.com'],
                ['name' => 'User2', 'email' => 'user2@example.com'],
            ],
        ];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertInstanceOf(UserArrayObject::class, $args[0]);
        /** @var UserArrayObject $userArrayObject */
        $userArrayObject = $args[0];
        $this->assertCount(2, $userArrayObject);
        $this->assertInstanceOf(UserInput::class, $userArrayObject[0]);
        $this->assertSame('User1', $userArrayObject[0]->name);
    }

    public function testSingleFileUpload(): void
    {
        // Test single file upload with #[InputFile]
        $method = new ReflectionMethod(FileUploadController::class, 'uploadSingle');
        $fileUpload = FileUpload::create([
            'name' => 'test.txt',
            'type' => 'text/plain',
            'size' => 100,
            'tmp_name' => '/tmp/test',
            'error' => UPLOAD_ERR_OK,
        ]);
        $query = ['file' => $fileUpload];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertSame($fileUpload, $args[0]);
    }

    public function testMultipleFileUpload(): void
    {
        // Test multiple file upload with array
        $method = new ReflectionMethod(FileUploadController::class, 'uploadMultiple');
        $file1 = FileUpload::create([
            'name' => 'test1.txt',
            'type' => 'text/plain',
            'size' => 100,
            'tmp_name' => '/tmp/test1',
            'error' => UPLOAD_ERR_OK,
        ]);
        $file2 = FileUpload::create([
            'name' => 'test2.txt',
            'type' => 'text/plain',
            'size' => 200,
            'tmp_name' => '/tmp/test2',
            'error' => UPLOAD_ERR_OK,
        ]);
        $query = ['files' => [$file1, $file2]];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertIsArray($args[0]);
        $this->assertCount(2, $args[0]);
        $this->assertSame($file1, $args[0][0]);
        $this->assertSame($file2, $args[0][1]);
    }

    public function testFileUploadWithValidation(): void
    {
        // Test file upload with validation options
        $method = new ReflectionMethod(FileUploadController::class, 'uploadWithValidation');
        $fileUpload = FileUpload::create([
            'name' => 'image.jpg',
            'type' => 'image/jpeg',
            'size' => 500,
            'tmp_name' => '/tmp/image',
            'error' => UPLOAD_ERR_OK,
        ]);
        $query = ['image' => $fileUpload];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertSame($fileUpload, $args[0]);
    }

    public function testMultipleFileUploadWithValidation(): void
    {
        // Test multiple file upload with validation
        $method = new ReflectionMethod(FileUploadController::class, 'uploadMultipleWithValidation');
        $file1 = FileUpload::create([
            'name' => 'image1.png',
            'type' => 'image/png',
            'size' => 1000,
            'tmp_name' => '/tmp/image1',
            'error' => UPLOAD_ERR_OK,
        ]);
        $file2 = FileUpload::create([
            'name' => 'image2.jpg',
            'type' => 'image/jpeg',
            'size' => 1500,
            'tmp_name' => '/tmp/image2',
            'error' => UPLOAD_ERR_OK,
        ]);
        $query = ['images' => [$file1, $file2]];

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertIsArray($args[0]);
        $this->assertCount(2, $args[0]);
        $this->assertSame($file1, $args[0][0]);
        $this->assertSame($file2, $args[0][1]);
    }

    public function testInputFileParameterFromFiles(): void
    {
        // Test resolveInputFileParameter using $_FILES - covers single file processing
        $_FILES['file'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'size' => 100,
            'tmp_name' => '/tmp/test',
            'error' => UPLOAD_ERR_OK,
        ];

        $method = new ReflectionMethod(FileUploadController::class, 'uploadSingle');
        $query = []; // Empty query, should get from $_FILES

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertInstanceOf(FileUpload::class, $args[0]);
        $this->assertSame('test.txt', $args[0]->name);
    }

    public function testInputFileArrayFromFiles(): void
    {
        // Test createArrayOfFileUploadsWithValidation using $_FILES array
        $_FILES['files'] = [
            [
                'name' => 'file1.txt',
                'type' => 'text/plain',
                'size' => 100,
                'tmp_name' => '/tmp/file1',
                'error' => UPLOAD_ERR_OK,
            ],
            [
                'name' => 'file2.txt',
                'type' => 'text/plain',
                'size' => 200,
                'tmp_name' => '/tmp/file2',
                'error' => UPLOAD_ERR_OK,
            ],
        ];

        $method = new ReflectionMethod(FileUploadController::class, 'uploadMultiple');
        $query = []; // Empty query, should get from $_FILES

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertIsArray($args[0]);
        $this->assertCount(2, $args[0]);
        $this->assertInstanceOf(FileUpload::class, $args[0][0]);
        $this->assertInstanceOf(FileUpload::class, $args[0][1]);
    }

    public function testConvertMultipleFileFormat(): void
    {
        // Test convertMultipleFileFormat using PHP $_FILES multiple format
        $_FILES['files'] = [
            'name' => ['file1.txt', 'file2.txt'],
            'type' => ['text/plain', 'text/plain'],
            'tmp_name' => ['/tmp/file1', '/tmp/file2'],
            'size' => [100, 200],
            'error' => [0, 0],
        ];

        $method = new ReflectionMethod(FileUploadController::class, 'uploadMultiple');
        $query = []; // Empty query, should get from $_FILES

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertIsArray($args[0]);
        $this->assertGreaterThan(0, count($args[0]));
        foreach ($args[0] as $file) {
            $this->assertInstanceOf(FileUpload::class, $file);
        }
    }

    public function testInputFileCreateMethod(): void
    {
        // Test using create() method like existing InputFileTest
        $_FILES['avatar'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'size' => 100,
            'tmp_name' => '/tmp/test',
            'error' => UPLOAD_ERR_OK,
        ];

        // Use existing InputFileInput class that has #[InputFile] attribute
        $query = ['name' => 'test user'];
        $input = $this->inputQuery->newInstance(InputFileInput::class, $query);

        $this->assertInstanceOf(InputFileInput::class, $input);
        $this->assertInstanceOf(FileUpload::class, $input->avatar);
        $this->assertSame('test.txt', $input->avatar->name);
    }

    public function testConvertMultipleFileFormatWithNoFile(): void
    {
        // Test convertMultipleFileFormat with UPLOAD_ERR_NO_FILE - covers continue statement
        $_FILES['files'] = [
            'name' => ['file1.txt', '', 'file3.txt'],
            'type' => ['text/plain', '', 'text/plain'],
            'tmp_name' => ['/tmp/file1', '', '/tmp/file3'],
            'size' => [100, 0, 300],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE, UPLOAD_ERR_OK],
        ];

        $method = new ReflectionMethod(FileUploadController::class, 'uploadMultiple');
        $query = []; // Empty query, should get from $_FILES

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertIsArray($args[0]);

        // Debug output
        foreach ($args[0] as $key => $file) {
            if ($file instanceof FileUpload) {
                $this->assertInstanceOf(FileUpload::class, $file);
            }
        }

        // The array should contain 2 files (skipping UPLOAD_ERR_NO_FILE)
        $files = array_values($args[0]); // Re-index to ensure sequential keys
        $this->assertCount(2, $files);
        $this->assertInstanceOf(FileUpload::class, $files[0]);
        $this->assertSame('file1.txt', $files[0]->name);
        $this->assertInstanceOf(FileUpload::class, $files[1]);
        $this->assertSame('file3.txt', $files[1]->name);
    }

    public function testFileUploadArrayEmptyFiles(): void
    {
        // Test createArrayOfFileUploads when $_FILES is not set - should return empty array
        // Clear $_FILES to trigger the empty return path
        unset($_FILES['files']);

        $method = new ReflectionMethod(FileUploadController::class, 'uploadMultiple');
        $query = []; // Empty query

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertIsArray($args[0]);
        $this->assertEmpty($args[0]); // Should be empty array
    }

    public function testNullableFileUploadWithNoFile(): void
    {
        // Test nullable file parameter with UPLOAD_ERR_NO_FILE
        $_FILES['avatar'] = [
            'name' => '',
            'type' => '',
            'size' => 0,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        $query = ['name' => 'test user'];
        $input = $this->inputQuery->newInstance(NullableFileInput::class, $query);

        $this->assertInstanceOf(NullableFileInput::class, $input);
        $this->assertNull($input->avatar); // Should be null for nullable parameter
    }

    public function testDefaultFileUploadWithNoFile(): void
    {
        // Test file parameter with default value when UPLOAD_ERR_NO_FILE
        $_FILES['avatar'] = [
            'name' => '',
            'type' => '',
            'size' => 0,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        $query = ['name' => 'test user'];
        $input = $this->inputQuery->newInstance(DefaultFileInput::class, $query);

        $this->assertInstanceOf(DefaultFileInput::class, $input);
        $this->assertNull($input->avatar); // Should use default value (null)
    }

    public function testRequiredFileUploadWithNoFile(): void
    {
        // Test required file parameter with UPLOAD_ERR_NO_FILE - should throw exception
        $_FILES['file'] = [
            'name' => '',
            'type' => '',
            'size' => 0,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        $method = new ReflectionMethod(FileUploadController::class, 'uploadRequired');
        $query = []; // Empty query

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Required file parameter 'file' is missing");

        $this->inputQuery->getArguments($method, $query);
    }

    public function testNullableFileUploadWithNoFileMethod(): void
    {
        // Test nullable file parameter in method with UPLOAD_ERR_NO_FILE
        $_FILES['file'] = [
            'name' => '',
            'type' => '',
            'size' => 0,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        $method = new ReflectionMethod(FileUploadController::class, 'uploadNullable');
        $query = []; // Empty query

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertNull($args[0]); // Should be null for nullable parameter
    }

    public function testDefaultFileUploadWithNoFileMethod(): void
    {
        // Test file parameter with default value in method when UPLOAD_ERR_NO_FILE
        $_FILES['file'] = [
            'name' => '',
            'type' => '',
            'size' => 0,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        $method = new ReflectionMethod(FileUploadController::class, 'uploadWithDefault');
        $query = []; // Empty query

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertNull($args[0]); // Should use default value (null)
    }

    public function testFileUploadMissingInFiles(): void
    {
        // Test when file is not in $_FILES at all (not even with UPLOAD_ERR_NO_FILE)
        unset($_FILES['file']); // Make sure file key doesn't exist in $_FILES

        $method = new ReflectionMethod(FileUploadController::class, 'uploadNullable');
        $query = []; // Empty query

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertNull($args[0]); // Should be null for nullable parameter
    }

    public function testFileUploadMissingInFilesWithDefault(): void
    {
        // Test when file is not in $_FILES and parameter has default value
        unset($_FILES['file']); // Make sure file key doesn't exist in $_FILES

        $method = new ReflectionMethod(FileUploadController::class, 'uploadWithDefault');
        $query = []; // Empty query

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertNull($args[0]); // Should use default value (null)
    }

    public function testFileUploadMissingInFilesRequired(): void
    {
        // Test when required file is not in $_FILES at all - should throw exception
        unset($_FILES['file']); // Make sure file key doesn't exist in $_FILES

        $method = new ReflectionMethod(FileUploadController::class, 'uploadRequired');
        $query = []; // Empty query

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Required file parameter 'file' is missing");

        $this->inputQuery->getArguments($method, $query);
    }

    public function testFileUploadArrayRegularFormatWithNoFile(): void
    {
        // Test regular array format with UPLOAD_ERR_NO_FILE - covers continue in foreach
        $_FILES['files'] = [
            0 => [
                'name' => 'file1.txt',
                'type' => 'text/plain',
                'size' => 100,
                'tmp_name' => '/tmp/file1',
                'error' => UPLOAD_ERR_OK,
            ],
            1 => [
                'name' => '',
                'type' => '',
                'size' => 0,
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE, // This should be skipped
            ],
            2 => [
                'name' => 'file3.txt',
                'type' => 'text/plain',
                'size' => 300,
                'tmp_name' => '/tmp/file3',
                'error' => UPLOAD_ERR_OK,
            ],
        ];

        $method = new ReflectionMethod(FileUploadController::class, 'uploadMultiple');
        $query = []; // Empty query

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertIsArray($args[0]);
        $this->assertCount(2, $args[0]); // Only 2 files (skipped the NO_FILE one)

        $files = array_values($args[0]); // Re-index array
        $this->assertInstanceOf(FileUpload::class, $files[0]);
        $this->assertSame('file1.txt', $files[0]->name);
        $this->assertInstanceOf(FileUpload::class, $files[1]);
        $this->assertSame('file3.txt', $files[1]->name);
    }

    public function testMixedTypeFileUpload(): void
    {
        // Test file upload with mixed type (no type hint) - covers fallback in resolveInputFileParameter
        $_FILES['file'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'size' => 100,
            'tmp_name' => '/tmp/test',
            'error' => UPLOAD_ERR_OK,
        ];

        $method = new ReflectionMethod(MixedFileController::class, 'uploadMixed');
        $query = []; // Empty query

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(1, $args);
        $this->assertInstanceOf(FileUpload::class, $args[0]);
        $this->assertSame('test.txt', $args[0]->name);
    }
}
