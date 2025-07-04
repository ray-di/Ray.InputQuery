<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\Input;
use ReflectionClass;
use ReflectionMethod;

final class ResolveInputParameterTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testResolveInputParameterWithUnionType(): void
    {
        // Test case: #[Input] string|int $value (ReflectionUnionType)
        // Expected: return $query[$paramName] ?? $this->getDefaultValue($param)
        $method = new ReflectionMethod($this, 'methodWithUnionTypeInput');
        $param = $method->getParameters()[0];
        $inputAttributes = $param->getAttributes(Input::class);

        // Test with value in query
        $query = ['value' => 'union-test'];
        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputParameter',
            [$param, $query, $inputAttributes],
        );

        $this->assertSame('union-test', $result);
    }

    public function testResolveInputParameterWithUnionTypeAndDefault(): void
    {
        // Test case: #[Input] string|int $value with default value
        // Expected: fallback to getDefaultValue when not in query
        $method = new ReflectionMethod($this, 'methodWithUnionTypeInputDefault');
        $param = $method->getParameters()[0];
        $inputAttributes = $param->getAttributes(Input::class);

        // Test without value in query - should use default
        $query = [];
        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputParameter',
            [$param, $query, $inputAttributes],
        );

        $this->assertSame('default-union', $result);
    }

    public function testResolveInputParameterWithNoTypeHint(): void
    {
        // Test case: #[Input] $noType (no type hint = null type)
        // Expected: return $query[$paramName] ?? $this->getDefaultValue($param)
        $method = new ReflectionMethod($this, 'methodWithNoTypeInput');
        $param = $method->getParameters()[0];
        $inputAttributes = $param->getAttributes(Input::class);

        // Test with value in query
        $query = ['data' => 'no-type-test'];
        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputParameter',
            [$param, $query, $inputAttributes],
        );

        $this->assertSame('no-type-test', $result);
    }

    // Helper methods for testing different type scenarios

    /**
     * Union type input parameter
     */
    private function methodWithUnionTypeInput(
        #[Input]
        string|int $value,
    ): void {
        // Union type parameter
    }

    /**
     * Union type input parameter with default value
     */
    private function methodWithUnionTypeInputDefault(
        #[Input]
        string|int $value = 'default-union',
    ): void {
        // Union type parameter with default
    }

    /**
     * No type hint parameter
     */
    private function methodWithNoTypeInput(
        #[Input]
        $data,
    ): void {
        // No type hint parameter
    }

    private function callPrivateMethod(object $object, string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
