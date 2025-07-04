<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use ArrayObject;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Fake\CustomArrayObject;
use Ray\InputQuery\Fake\DITestController;
use Ray\InputQuery\Fake\UnresolvableService;
use Ray\InputQuery\Fake\UserInputWithAttribute;
use ReflectionClass;
use ReflectionMethod;

final class DIResolverTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testResolveFromDIWithUnboundService(): void
    {
        // Test resolving an unbound service that should throw an exception
        $method = new ReflectionMethod(DITestController::class, 'withUnboundServiceWithoutDefault');
        $param = $method->getParameters()[0]; // UnresolvableService parameter

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "service" of type "Ray\InputQuery\Fake\UnresolvableService:" is not bound in the injector');

        $this->callPrivateMethod($this->inputQuery, 'resolveFromDI', [$param]);
    }

    public function testResolveFromDIWithUnboundServiceWithDefault(): void
    {
        // Test resolving an unbound service that has a default value
        $method = new ReflectionMethod($this, 'methodWithUnboundServiceWithDefault');
        $param = $method->getParameters()[0]; // UnresolvableService parameter with default

        $result = $this->callPrivateMethod($this->inputQuery, 'resolveFromDI', [$param]);
        $this->assertNull($result);
    }

    public function testResolveArrayObjectTypeWithReflectionClassNewInstance(): void
    {
        // Test the ReflectionClass->newInstance() path in resolveArrayObjectType
        $query = [
            'users' => [
                ['id' => '1', 'name' => 'test'],
            ],
        ];

        // Create a fake Input attribute
        $inputAttribute = new Input(item: UserInputWithAttribute::class);
        $customAttribute = new class ($inputAttribute) {
            public function __construct(private Input $inputAttribute)
            {
            }

            public function newInstance(): Input
            {
                return $this->inputAttribute;
            }
        };

        $result = $this->callPrivateMethod($this->inputQuery, 'resolveArrayObjectType', [
            'users',
            $query,
            [$customAttribute],
            CustomArrayObject::class,
        ]);

        $this->assertInstanceOf(CustomArrayObject::class, $result);
        $this->assertCount(1, $result);
    }

    public function testResolveArrayObjectTypeWithNoItemAttribute(): void
    {
        // Test when Input attribute has no item specified
        $query = [
            'users' => [
                ['id' => '1', 'name' => 'test'],
            ],
        ];

        $inputAttribute = new Input(item: null);
        $customAttribute = new class ($inputAttribute) {
            public function __construct(private Input $inputAttribute)
            {
            }

            public function newInstance(): Input
            {
                return $this->inputAttribute;
            }
        };

        $result = $this->callPrivateMethod($this->inputQuery, 'resolveArrayObjectType', [
            'users',
            $query,
            [$customAttribute],
            ArrayObject::class,
        ]);

        $this->assertNull($result);
    }

    // Helper methods
    private function methodWithUnboundServiceWithDefault(UnresolvableService|null $service = null): void
    {
        // Test method with default value
    }

    private function callPrivateMethod(object $object, string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
