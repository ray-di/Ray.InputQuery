<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Countable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionUnionType;
use Traversable;

/**
 * @requires PHP >= 8.2
 */
final class FileUploadFactoryPhp82Test extends TestCase
{
    private FileUploadFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new FileUploadFactory();
    }

    public function testIsValidFileUploadUnionWithIntersectionType(): void
    {
        // Test case for PHP 8.2+ intersection types in union types
        // This should reach the 'return false' when a non-ReflectionNamedType is encountered
        $method = new ReflectionMethod($this, 'dummyMethodForComplexUnionType');
        $param = $method->getParameters()[0];
        $unionType = $param->getType();

        $this->assertInstanceOf(ReflectionUnionType::class, $unionType);

        // Call the private method to test the return false case
        $reflection = new ReflectionClass($this->factory);
        $isValidMethod = $reflection->getMethod('isValidFileUploadUnion');
        $isValidMethod->setAccessible(true);

        $result = $isValidMethod->invoke($this->factory, $unionType);

        // Should return false because ReflectionIntersectionType is not ReflectionNamedType
        $this->assertFalse($result);

        // Verify that this triggers the expected behavior in resolveFileUploadUnionType
        $resolveResult = $this->factory->resolveFileUploadUnionType($param, [], $unionType);
        $this->assertNull($resolveResult);
    }

    /**
     * Try to create a case with intersection types (PHP 8.2+)
     */
    public function dummyMethodForComplexUnionType((Traversable&Countable)|string|null $param): void
    {
    }
}