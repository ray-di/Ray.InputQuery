<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use ReflectionClass;

final class StringUtilsTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testToCamelCaseWithSnakeCase(): void
    {
        $result = $this->callPrivateMethod($this->inputQuery, 'toCamelCase', ['user_name']);
        $this->assertSame('userName', $result);
    }

    public function testToCamelCaseWithKebabCase(): void
    {
        $result = $this->callPrivateMethod($this->inputQuery, 'toCamelCase', ['user-name']);
        $this->assertSame('userName', $result);
    }

    public function testToCamelCaseWithMixedCase(): void
    {
        $result = $this->callPrivateMethod($this->inputQuery, 'toCamelCase', ['User_Name-Field']);
        $this->assertSame('userNameField', $result);
    }

    public function testToCamelCaseWithSingleWord(): void
    {
        $result = $this->callPrivateMethod($this->inputQuery, 'toCamelCase', ['user']);
        $this->assertSame('user', $result);
    }

    public function testToCamelCaseWithUpperCase(): void
    {
        $result = $this->callPrivateMethod($this->inputQuery, 'toCamelCase', ['USER_NAME']);
        $this->assertSame('userName', $result);
    }

    public function testExtractNestedQueryWithMatches(): void
    {
        $query = [
            'user_name' => 'John',
            'user_email' => 'john@example.com',
            'user_age' => 30,
            'other_field' => 'value',
        ];

        $result = $this->callPrivateMethod($this->inputQuery, 'extractNestedQuery', ['user', $query]);
        $expected = [
            'name' => 'John',
            'email' => 'john@example.com',
            'age' => 30,
        ];
        $this->assertSame($expected, $result);
    }

    public function testExtractNestedQueryWithNoMatches(): void
    {
        $query = [
            'name' => 'John',
            'email' => 'john@example.com',
        ];

        $result = $this->callPrivateMethod($this->inputQuery, 'extractNestedQuery', ['user', $query]);
        $this->assertSame([], $result);
    }

    public function testExtractNestedQueryWithExactMatch(): void
    {
        $query = [
            'user' => 'exact-match',
            'user_name' => 'John',
        ];

        $result = $this->callPrivateMethod($this->inputQuery, 'extractNestedQuery', ['user', $query]);
        $expected = ['name' => 'John'];
        $this->assertSame($expected, $result);
    }

    public function testExtractNestedQueryWithEmptyNestedKey(): void
    {
        $query = [
            'user' => 'exact-match',  // This would create empty nestedKey, should be skipped
            'userField' => 'value',
        ];

        $result = $this->callPrivateMethod($this->inputQuery, 'extractNestedQuery', ['user', $query]);
        $expected = ['field' => 'value'];
        $this->assertSame($expected, $result);
    }

    public function testExtractNestedQueryWithKebabCase(): void
    {
        $query = [
            'user-name' => 'John',
            'user-email' => 'john@example.com',
        ];

        $result = $this->callPrivateMethod($this->inputQuery, 'extractNestedQuery', ['user', $query]);
        $expected = [
            'name' => 'John',
            'email' => 'john@example.com',
        ];
        $this->assertSame($expected, $result);
    }

    private function callPrivateMethod(object $object, string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
