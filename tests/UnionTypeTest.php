<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\InputFile;
use ReflectionClass;
use ReflectionMethod;

final class UnionTypeTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testResolveUnionTypeWithFileUpload(): void
    {
        // Test union type with FileUpload
        $method = new ReflectionMethod($this, 'methodWithFileUploadUnion');
        $param = $method->getParameters()[0];

        $_FILES['avatar'] = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'tmp_name' => '/tmp/test',
            'error' => 0,
        ];

        $result = $this->callPrivateMethod($this->inputQuery, 'resolveUnionType', [
            $param,
            [],
            $param->getType(),
        ]);

        $this->assertInstanceOf(FileUpload::class, $result);
        $this->assertSame('test.jpg', $result->name);
    }

    public function testResolveUnionTypeWithoutFileUpload(): void
    {
        // Test union type without FileUpload
        $method = new ReflectionMethod($this, 'methodWithStringIntUnion');
        $param = $method->getParameters()[0];

        $result = $this->callPrivateMethod($this->inputQuery, 'resolveUnionType', [
            $param,
            ['value' => 'test'],
            $param->getType(),
        ]);

        $this->assertSame('test', $result);
    }

    public function testResolveUnionTypeWithNonFileUploadUnion(): void
    {
        // Test union type that doesn't contain FileUpload - should handle as regular parameter
        $method = new ReflectionMethod($this, 'methodWithStringIntUnion');
        $param = $method->getParameters()[0];

        $result = $this->callPrivateMethod($this->inputQuery, 'resolveUnionType', [
            $param,
            ['value' => 42],
            $param->getType(),
        ]);

        // For non-FileUpload union types, it should handle as regular parameter
        $this->assertSame(42, $result);
    }

    // Helper methods for testing
    private function methodWithFileUploadUnion(
        #[InputFile]
        FileUpload|string $avatar,
    ): void {
        // Test method
    }

    private function methodWithStringIntUnion(
        string|int $value,
    ): void {
        // Test method
    }

    private function callPrivateMethod(object $object, string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }

    protected function tearDown(): void
    {
        $_FILES = [];
    }
}
