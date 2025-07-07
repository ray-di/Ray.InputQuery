<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use PHPUnit\Framework\TestCase;
use Ray\InputQuery\Attribute\Input;

final class ToArrayTest extends TestCase
{
    private ToArrayInterface $toArray;

    protected function setUp(): void
    {
        $this->toArray = new ToArray();
    }

    public function testSimpleObject(): void
    {
        $input = new class {
            public function __construct(
                #[Input]
                public readonly string $name = 'John',
                #[Input]
                public readonly int $age = 30,
            ) {
            }
        };

        $result = ($this->toArray)($input);

        $this->assertSame(['name' => 'John', 'age' => 30], $result);
    }

    public function testNestedObject(): void
    {
        $article = new class {
            #[Input]
            public readonly string $title;

            #[Input]
            public readonly object $author;

            public function __construct()
            {
                $this->title = 'Hello World';
                $this->author = new class {
                    public function __construct(
                        #[Input]
                        public readonly string $name = 'John',
                        #[Input]
                        public readonly string $email = 'john@example.com',
                    ) {
                    }
                };
            }
        };

        $result = ($this->toArray)($article);

        $this->assertSame([
            'title' => 'Hello World',
            'name' => 'John',
            'email' => 'john@example.com',
        ], $result);
    }

    public function testArrayProperty(): void
    {
        $input = new class {
            /** @param array<int> $userIds */
            public function __construct(
                #[Input]
                public readonly string $status = 'active',
                #[Input]
                public readonly array $userIds = [1, 2, 3],
            ) {
            }
        };

        $result = ($this->toArray)($input);

        $this->assertSame([
            'status' => 'active',
            'userIds' => [1, 2, 3],
        ], $result);
    }

    public function testPropertyNameConflict(): void
    {
        $order = new class {
            #[Input]
            public readonly string $id;

            #[Input]
            public readonly object $customer;

            public function __construct()
            {
                $this->id = 'order-456';
                $this->customer = new class {
                    public function __construct(
                        #[Input]
                        public readonly string $id = 'customer-123',
                        #[Input]
                        public readonly string $name = 'John',
                    ) {
                    }
                };
            }
        };

        $result = ($this->toArray)($order);

        // Later property overwrites earlier one
        $this->assertSame([
            'id' => 'customer-123',
            'name' => 'John',
        ], $result);
    }

    public function testPrivatePropertiesIgnored(): void
    {
        $input = new class {
            public function __construct(
                #[Input]
                public readonly string $public = 'visible',
                #[Input]
                private readonly string $private = 'hidden',
            ) {
            }
        };

        $result = ($this->toArray)($input);

        $this->assertSame(['public' => 'visible'], $result);
        $this->assertArrayNotHasKey('private', $result);
    }

    public function testNullValues(): void
    {
        $input = new class {
            public function __construct(
                #[Input]
                public readonly string $name = 'John',
                #[Input]
                public readonly string|null $email = null,
                #[Input]
                public readonly object|null $address = null,
            ) {
            }
        };

        $result = ($this->toArray)($input);

        $this->assertSame([
            'name' => 'John',
            'email' => null,
            'address' => null,
        ], $result);
    }
}
