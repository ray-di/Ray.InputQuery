<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Exception\InvalidInputTypeException;
use Ray\InputQuery\Fake\CustomArrayObject;
use Ray\InputQuery\Fake\UserInputWithAttribute;
use ReflectionMethod;

final class ArrayInputTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $injector = new Injector();
        $this->inputQuery = new InputQuery($injector, new FileUploadFactory());
    }

    public function testArrayOfInputObjects(): void
    {
        $query = [
            'users' => [
                ['id' => '1', 'name' => 'jingu'],
                ['id' => '2', 'name' => 'horikawa'],
            ],
        ];

        $controller = new class {
            /**
             * @param array<mixed> $users
             *
             * @return array<mixed>
             */
            public function listUsers(
                #[Input(item: UserInputWithAttribute::class)]
                array $users,
            ): array {
                return $users;
            }
        };

        $method = new ReflectionMethod($controller, 'listUsers');
        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertIsArray($args[0]);
        $this->assertCount(2, $args[0]);

        $this->assertInstanceOf(UserInputWithAttribute::class, $args[0][0]);
        $this->assertSame('1', $args[0][0]->id);
        $this->assertSame('jingu', $args[0][0]->name);

        $this->assertInstanceOf(UserInputWithAttribute::class, $args[0][1]);
        $this->assertSame('2', $args[0][1]->id);
        $this->assertSame('horikawa', $args[0][1]->name);
    }

    public function testArrayObjectOfInputObjects(): void
    {
        $query = [
            'users' => [
                ['id' => '3', 'name' => 'tanaka'],
                ['id' => '4', 'name' => 'suzuki'],
            ],
        ];

        $controller = new class {
            public function listUsers(
                #[Input(item: UserInputWithAttribute::class)]
                ArrayObject $users,
            ): ArrayObject {
                return $users;
            }
        };

        $method = new ReflectionMethod($controller, 'listUsers');
        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertInstanceOf(ArrayObject::class, $args[0]);
        $this->assertCount(2, $args[0]);

        $this->assertInstanceOf(UserInputWithAttribute::class, $args[0][0]);
        $this->assertSame('3', $args[0][0]->id);
        $this->assertSame('tanaka', $args[0][0]->name);

        $this->assertInstanceOf(UserInputWithAttribute::class, $args[0][1]);
        $this->assertSame('4', $args[0][1]->id);
        $this->assertSame('suzuki', $args[0][1]->name);
    }

    public function testEmptyArray(): void
    {
        $query = ['users' => []];

        $controller = new class {
            /**
             * @param array<mixed> $users
             *
             * @return array<mixed>
             */
            public function listUsers(
                #[Input(item: UserInputWithAttribute::class)]
                array $users,
            ): array {
                return $users;
            }
        };

        $method = new ReflectionMethod($controller, 'listUsers');
        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertIsArray($args[0]);
        $this->assertCount(0, $args[0]);
    }

    public function testPlainArrayInput(): void
    {
        $query = ['ids' => [1, 2, 3]];

        $controller = new class {
            /**
             * @param list<int> $ids
             *
             * @return list<int>
             */
            public function listIds(
                #[Input]
                array $ids,
            ): array {
                return $ids;
            }
        };

        $method = new ReflectionMethod($controller, 'listIds');
        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertSame([1, 2, 3], $args[0]);
    }

    public function testPlainArrayInputRejectsScalarValue(): void
    {
        $query = ['ids' => '1,2,3'];

        $controller = new class {
            /**
             * @param list<int> $ids
             *
             * @return list<int>
             */
            public function listIds(
                #[Input]
                array $ids,
            ): array {
                return $ids;
            }
        };

        $method = new ReflectionMethod($controller, 'listIds');

        $this->assertInvalidInputTypeContext(
            fn () => $this->inputQuery->getArguments($method, $query),
            'ids',
            'string',
        );
    }

    public function testNullableArrayInputRejectsScalarValue(): void
    {
        $query = ['ids' => '1,2,3'];

        $controller = new class {
            /**
             * @param list<int>|null $ids
             *
             * @return list<int>|null
             */
            public function listIds(
                #[Input]
                array|null $ids = null,
            ): array|null {
                return $ids;
            }
        };

        $method = new ReflectionMethod($controller, 'listIds');

        $this->assertInvalidInputTypeContext(
            fn () => $this->inputQuery->getArguments($method, $query),
            'ids',
            'string',
        );
    }

    public function testNativeArrayConstructorInputRejectsScalarBeforeTypeError(): void
    {
        $input = new class {
            /** @param list<int> $tagIds */
            public function __construct(
                #[Input]
                public readonly array $tagIds = [],
            ) {
            }
        };

        $this->assertInvalidInputTypeContext(
            fn () => $this->inputQuery->newInstance($input::class, ['tagIds' => 1]),
            'tagIds',
            'int',
        );
    }

    public function testMissingArrayParameter(): void
    {
        $query = [];

        $controller = new class {
            /**
             * @param array<mixed> $users
             *
             * @return array<mixed>
             */
            public function listUsers(
                #[Input(item: UserInputWithAttribute::class)]
                array $users,
            ): array {
                return $users;
            }
        };

        $method = new ReflectionMethod($controller, 'listUsers');
        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertIsArray($args[0]);
        $this->assertCount(0, $args[0]);
    }

    public function testNonArrayValueForArrayParameter(): void
    {
        /**
         * @param array<mixed> $users
         *
         * @return array<mixed>
         */
        $query = ['users' => 'not-an-array'];

        $controller = new class {
            /**
             * @param array<mixed> $users
             *
             * @return array<mixed>
             */
            public function listUsers(
                #[Input(item: UserInputWithAttribute::class)]
                array $users,
            ): array {
                return $users;
            }
        };

        $method = new ReflectionMethod($controller, 'listUsers');

        $this->assertInvalidInputTypeContext(
            fn () => $this->inputQuery->getArguments($method, $query),
            'users',
            'string',
        );
    }

    public function testArrayWithNonArrayElements(): void
    {
        $query = [
            'users' => [
                'string-value', // Non-array element at index 0
                ['id' => '1', 'name' => 'valid'],
            ],
        ];

        $controller = new class {
            /**
             * @param array<mixed> $users
             *
             * @return array<mixed>
             */
            public function listUsers(
                #[Input(item: UserInputWithAttribute::class)]
                array $users,
            ): array {
                return $users;
            }
        };

        $method = new ReflectionMethod($controller, 'listUsers');

        $this->assertInvalidInputTypeContext(
            fn () => $this->inputQuery->getArguments($method, $query),
            'users',
            'string',
            0,
        );
    }

    public function testCustomArrayObjectOfInputObjects(): void
    {
        $query = [
            'users' => [
                ['id' => '5', 'name' => 'yamada'],
                ['id' => '6', 'name' => 'sato'],
            ],
        ];

        /**
         * @param array<mixed> $users
         *
         * @return array<mixed>
         */
        $controller = new class {
            public function listUsers(
                #[Input(item: UserInputWithAttribute::class)]
                CustomArrayObject $users,
            ): CustomArrayObject {
                return $users;
            }
        };

        $method = new ReflectionMethod($controller, 'listUsers');
        $args = $this->inputQuery->getArguments($method, $query);

        $this->assertInstanceOf(CustomArrayObject::class, $args[0]);
        $this->assertCount(2, $args[0]);

        $this->assertInstanceOf(UserInputWithAttribute::class, $args[0][0]);
        $this->assertSame('5', $args[0][0]->id);
        $this->assertSame('yamada', $args[0][0]->name);

        $this->assertInstanceOf(UserInputWithAttribute::class, $args[0][1]);
        $this->assertSame('6', $args[0][1]->id);
        $this->assertSame('sato', $args[0][1]->name);

        // Test custom method
        $firstUser = $args[0]->getFirst();
        $this->assertInstanceOf(UserInputWithAttribute::class, $firstUser);
        $this->assertSame('5', $firstUser->id);
    }

    public function testArrayObjectWithoutItemAttribute(): void
    {
        $query = [
            'users' => [
                ['id' => '1', 'name' => 'test'],
            ],
        ];

        $controller = new class {
            public function listUsers(
                #[Input] // No item parameter specified
                ArrayObject $users,
            ): ArrayObject {
                return $users;
            }
        };

        $method = new ReflectionMethod($controller, 'listUsers');
        $args = $this->inputQuery->getArguments($method, $query);

        // Should create a regular ArrayObject with the nested query data
        $this->assertInstanceOf(ArrayObject::class, $args[0]);
    }

    /** @param callable(): mixed $callback */
    private function assertInvalidInputTypeContext(
        callable $callback,
        string $paramName,
        string $actualType,
        int|string|null $itemKey = null,
    ): void {
        try {
            $callback();
            $this->fail('Expected InvalidInputTypeException.');
        } catch (InvalidInputTypeException $e) {
            $this->assertSame('', $e->getMessage());
            $this->assertSame($paramName, $e->paramName);
            $this->assertSame('array', $e->expectedType);
            $this->assertSame($actualType, $e->actualType);
            $this->assertSame($itemKey, $e->itemKey);
        }
    }
}
