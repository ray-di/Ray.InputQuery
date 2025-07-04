<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

final class ScalarConversionTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testConvertScalarWithNull(): void
    {
        $stringType = $this->getReflectionType('string');
        $result = $this->callPrivateMethod($this->inputQuery, 'convertScalar', [null, $stringType]);
        $this->assertNull($result);
    }

    public function testConvertScalarStringFromNonScalar(): void
    {
        // Test converting non-scalar to string
        $stringType = $this->getReflectionType('string');
        $result = $this->callPrivateMethod($this->inputQuery, 'convertScalar', [[], $stringType]);
        $this->assertSame('', $result); // Non-scalar becomes empty string
    }

    public function testConvertScalarIntFromNonNumeric(): void
    {
        // Test converting non-numeric to int
        $intType = $this->getReflectionType('int');
        $result = $this->callPrivateMethod($this->inputQuery, 'convertScalar', ['not-a-number', $intType]);
        $this->assertSame(0, $result); // Non-numeric becomes 0
    }

    public function testConvertScalarFloatFromNonNumeric(): void
    {
        // Test converting non-numeric to float
        $floatType = $this->getReflectionType('float');
        $result = $this->callPrivateMethod($this->inputQuery, 'convertScalar', ['not-a-number', $floatType]);
        $this->assertSame(0.0, $result); // Non-numeric becomes 0.0
    }

    public function testConvertScalarBoolFromFalsy(): void
    {
        // Test converting falsy values to bool
        $boolType = $this->getReflectionType('bool');

        $result1 = $this->callPrivateMethod($this->inputQuery, 'convertScalar', [0, $boolType]);
        $this->assertFalse($result1);

        $result2 = $this->callPrivateMethod($this->inputQuery, 'convertScalar', ['', $boolType]);
        $this->assertFalse($result2);

        $result3 = $this->callPrivateMethod($this->inputQuery, 'convertScalar', [[], $boolType]);
        $this->assertFalse($result3);
    }

    public function testConvertScalarDefaultCase(): void
    {
        // Test default case for unknown types
        $mixedType = $this->getReflectionType('mixed');
        $value = ['some' => 'array'];
        $result = $this->callPrivateMethod($this->inputQuery, 'convertScalar', [$value, $mixedType]);
        $this->assertSame($value, $result); // Returns value unchanged for unknown types
    }

    public function testConvertScalarStringFromScalar(): void
    {
        // Test converting scalar values to string
        $stringType = $this->getReflectionType('string');

        $result1 = $this->callPrivateMethod($this->inputQuery, 'convertScalar', [123, $stringType]);
        $this->assertSame('123', $result1);

        $result2 = $this->callPrivateMethod($this->inputQuery, 'convertScalar', [true, $stringType]);
        $this->assertSame('1', $result2);
    }

    public function testConvertScalarIntFromNumeric(): void
    {
        // Test converting numeric string to int
        $intType = $this->getReflectionType('int');
        $result = $this->callPrivateMethod($this->inputQuery, 'convertScalar', ['42', $intType]);
        $this->assertSame(42, $result);
    }

    public function testConvertScalarFloatFromNumeric(): void
    {
        // Test converting numeric string to float
        $floatType = $this->getReflectionType('float');
        $result = $this->callPrivateMethod($this->inputQuery, 'convertScalar', ['3.14', $floatType]);
        $this->assertSame(3.14, $result);
    }

    // Helper methods
    private function getReflectionType(string $typeName): ReflectionNamedType
    {
        $method = new ReflectionMethod($this, "methodWith{$typeName}Param");
        $param = $method->getParameters()[0];

        return $param->getType();
    }

    private function methodWithStringParam(string $param): void
    {
    }

    private function methodWithIntParam(int $param): void
    {
    }

    private function methodWithFloatParam(float $param): void
    {
    }

    private function methodWithBoolParam(bool $param): void
    {
    }

    private function methodWithMixedParam(mixed $param): void
    {
    }

    private function callPrivateMethod(object $object, string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
