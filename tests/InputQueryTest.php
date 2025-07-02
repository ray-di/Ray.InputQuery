<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\InputQuery\Fake\AuthorInput;
use Ray\InputQuery\Fake\DefaultValuesInput;
use Ray\InputQuery\Fake\MixedInput;
use Ray\InputQuery\Fake\NoConstructorInput;
use Ray\InputQuery\Fake\NonInputParameterController;
use Ray\InputQuery\Fake\NonNamedTypeController;
use Ray\InputQuery\Fake\NullableInput;
use Ray\InputQuery\Fake\ScalarInput;
use Ray\InputQuery\Fake\TestService;
use Ray\InputQuery\Fake\TodoController;
use Ray\InputQuery\Fake\TodoInput;
use Ray\InputQuery\Fake\UnionTypeInput;
use Ray\InputQuery\Fake\UserInput;
use ReflectionClass;
use ReflectionMethod;

final class InputQueryTest extends TestCase
{
    private InputQueryInterface $inputQuery;

    protected function setUp(): void
    {
        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(TestService::class)->toInstance(new TestService('injected'));
            }
        });
        $this->inputQuery = new InputQuery($injector);
    }

    public function testCreateSimpleObject(): void
    {
        $query = [
            'name' => 'John',
            'email' => 'john@example.com',
        ];

        $user = $this->inputQuery->create(UserInput::class, $query);

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

        $todo = $this->inputQuery->create(TodoInput::class, $query);

        $this->assertInstanceOf(TodoInput::class, $todo);
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

        $mixed = $this->inputQuery->create(MixedInput::class, $query);

        $this->assertInstanceOf(MixedInput::class, $mixed);
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

        $todo = $this->inputQuery->create(TodoInput::class, [
            'title' => 'Test',
            'author_name' => 'Author',
            'author_email' => 'author@example.com',
        ]);

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
        $this->inputQuery->create(UserInput::class, $query);
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

        $scalar = $this->inputQuery->create(ScalarInput::class, $query);

        $this->assertInstanceOf(ScalarInput::class, $scalar);
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

            $scalar = $this->inputQuery->create(ScalarInput::class, $query);
            $this->assertSame($case['expected'], $scalar->active, "Failed for active='{$case['active']}'.");
        }
    }

    public function testDefaultValues(): void
    {
        $query = ['name' => 'John']; // Other parameters should use defaults

        $defaultInput = $this->inputQuery->create(DefaultValuesInput::class, $query);

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

        $defaultInput = $this->inputQuery->create(DefaultValuesInput::class, $query);

        $this->assertSame('Jane', $defaultInput->name);
        $this->assertSame('jane@example.com', $defaultInput->email);
        $this->assertSame(35, $defaultInput->age);
        $this->assertTrue($defaultInput->active);
        $this->assertSame(0.0, $defaultInput->score);
    }

    public function testNullableValues(): void
    {
        $query = [];

        $nullable = $this->inputQuery->create(NullableInput::class, $query);

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

        $nullable = $this->inputQuery->create(NullableInput::class, $query);

        $this->assertNull($nullable->name);
        $this->assertNull($nullable->age);
        $this->assertNull($nullable->active);
    }

    public function testNoConstructorClass(): void
    {
        $query = ['name' => 'test'];

        $noConstructor = $this->inputQuery->create(NoConstructorInput::class, $query);

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
        $todo = $this->inputQuery->create(TodoInput::class, $query);

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

        $todo = $this->inputQuery->create(TodoInput::class, $query);

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

        $union = $this->inputQuery->create(UnionTypeInput::class, $query);

        $this->assertInstanceOf(UnionTypeInput::class, $union);
        $this->assertSame('test', $union->value);
        $this->assertSame('union', $union->name);
    }

    public function testUnionTypesWithDefaults(): void
    {
        $query = []; // Use defaults

        $union = $this->inputQuery->create(UnionTypeInput::class, $query);

        $this->assertInstanceOf(UnionTypeInput::class, $union);
        $this->assertSame('default', $union->value);
        $this->assertNull($union->name);
    }

    public function testNonInputParameters(): void
    {
        $method = new ReflectionMethod(NonInputParameterController::class, 'process');
        $query = []; // No query parameters for non-Input params

        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertCount(2, $args);
        $this->assertNull($args[0]); // scalar without #[Input] gets null default
        $this->assertInstanceOf(TestService::class, $args[1]); // object from DI
        $this->assertSame('injected', $args[1]->getValue());
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

        $scalar = $this->inputQuery->create(ScalarInput::class, $query);

        // Ensure all types are converted correctly
        $this->assertIsString($scalar->name);
        $this->assertIsInt($scalar->age);
        $this->assertIsFloat($scalar->price);
        $this->assertIsBool($scalar->active);
    }

    public function testExtractNestedQueryWithEmptyKey(): void
    {
        // Test edge case where nested key would be empty after prefix removal
        $query = [
            'author' => 'should_not_match',  // This key exactly matches prefix
            'authorName' => 'John',
            'authorEmail' => 'john@example.com',
        ];

        $todo = $this->inputQuery->create(TodoInput::class, [
            'title' => 'Test',
            ...$query,
        ]);

        $this->assertSame('Test', $todo->title);
        $this->assertSame('John', $todo->author->name);
        $this->assertSame('john@example.com', $todo->author->email);
    }

    public function testGetDefaultValueWithoutDefault(): void
    {
        // This should trigger the InvalidArgumentException path
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required parameter "name" is missing and has no default value');

        $this->inputQuery->create(UserInput::class, ['email' => 'test@example.com']); // missing required 'name'
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
}
