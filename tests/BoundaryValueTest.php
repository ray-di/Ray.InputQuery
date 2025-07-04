<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use ArrayObject;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Fake\UserInputWithAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use stdClass;

final class BoundaryValueTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testComplexTypeJudgmentBoundaries(): void
    {
        // Test case: 複雑な型判定の境界値ケース
        // Expected: 残り未カバー行の特定箇所を攻略

        // Test getDefaultValue method directly with parameter that has no default
        $method = new ReflectionMethod($this, 'methodWithRequiredParam');
        $param = $method->getParameters()[0];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required parameter "data" is missing and has no default value');

        $this->callPrivateMethod($this->inputQuery, 'getDefaultValue', [$param]);
    }

    public function testResolveParameterWithComplexConditions(): void
    {
        // Test case: resolveParameter with various complex parameter conditions
        // Expected: cover different resolution paths

        // Test with Input attribute on object type
        $method = new ReflectionMethod($this, 'methodWithInputObject');
        $param = $method->getParameters()[0];

        $query = [
            'user_id' => '1',
            'user_name' => 'boundary',
            'user_email' => 'boundary@test.com',
        ];

        $result = $this->callPrivateMethod($this->inputQuery, 'resolveParameter', [$param, $query]);

        $this->assertInstanceOf(UserInputWithAttribute::class, $result);
        $this->assertSame('boundary', $result->name);
    }

    public function testResolveObjectTypeWithArrayObject(): void
    {
        // Test case: resolveObjectType with ArrayObject type
        // Expected: cover ArrayObject creation path
        $method = new ReflectionMethod($this, 'methodWithArrayObjectInput');
        $param = $method->getParameters()[0];
        $type = $param->getType();
        $inputAttributes = $param->getAttributes(Input::class);

        $query = [
            'items' => [
                ['id' => '1', 'name' => 'item1'],
                ['id' => '2', 'name' => 'item2'],
            ],
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveObjectType',
            [$param, $query, $inputAttributes, $type],
        );

        $this->assertInstanceOf(ArrayObject::class, $result);
        $this->assertCount(2, $result);
    }

    public function testResolveBuiltinTypeWithScalarConversions(): void
    {
        // Test case: resolveBuiltinType with various scalar type conversions
        // Expected: cover convertScalar boundary cases

        // Test bool conversion with truthy values
        $method = new ReflectionMethod($this, 'methodWithBoolInput');
        $param = $method->getParameters()[0];
        $type = $param->getType();
        $inputAttributes = $param->getAttributes(Input::class);

        $query = ['active' => 'yes']; // Non-boolean truthy value

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveBuiltinType',
            [$param, $query, $inputAttributes, $type],
        );

        $this->assertTrue($result); // 'yes' should convert to true
    }

    public function testCreateArrayOfInputsWithComplexNestedData(): void
    {
        // Test case: createArrayOfInputs with nested complex data structures
        // Expected: cover complex array processing
        $query = [
            'users' => [
                ['id' => '1', 'name' => 'user1', 'email' => 'user1@test.com'],
                ['id' => '2', 'name' => 'user2', 'email' => 'user2@test.com'],
                ['id' => '3', 'name' => 'user3', 'email' => 'user3@test.com'],
            ],
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'createArrayOfInputs',
            ['users', $query, UserInputWithAttribute::class],
        );

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertInstanceOf(UserInputWithAttribute::class, $result[0]);
        $this->assertSame('user1', $result[0]->name);
    }

    public function testExtractNestedQueryComplexPatterns(): void
    {
        // Test case: extractNestedQuery with complex naming patterns
        // Expected: cover various prefix matching scenarios
        $query = [
            'userProfileName' => 'nested_user',
            'userProfileEmail' => 'nested@test.com',
            'userSettingsTheme' => 'dark',
            'otherData' => 'ignored',
        ];

        $result = $this->callPrivateMethod($this->inputQuery, 'extractNestedQuery', ['userProfile', $query]);

        $expected = [
            'name' => 'nested_user',
            'email' => 'nested@test.com',
        ];
        $this->assertSame($expected, $result);
    }

    public function testToCamelCaseComplexStrings(): void
    {
        // Test case: toCamelCase with various complex string patterns
        // Expected: cover string transformation edge cases

        $result1 = $this->callPrivateMethod($this->inputQuery, 'toCamelCase', ['UPPER_SNAKE_CASE']);
        $this->assertSame('upperSnakeCase', $result1);

        $result2 = $this->callPrivateMethod($this->inputQuery, 'toCamelCase', ['mixed-case_String']);
        $this->assertSame('mixedCaseString', $result2);

        $result3 = $this->callPrivateMethod($this->inputQuery, 'toCamelCase', ['already-camelCase']);
        $this->assertSame('alreadyCamelcase', $result3);
    }

    public function testResolveFromDIWithDefaultValue(): void
    {
        // Test case: resolveFromDI with parameter that has default value
        // Expected: cover DI resolution fallback to default
        $method = new ReflectionMethod($this, 'methodWithDefaultDIParam');
        $param = $method->getParameters()[0];

        $result = $this->callPrivateMethod($this->inputQuery, 'resolveFromDI', [$param]);
        $this->assertSame('default-service', $result);
    }

    public function testConvertScalarWithComplexValues(): void
    {
        // Test case: convertScalar with various complex input values
        // Expected: cover all scalar conversion branches

        $stringType = $this->getStringType();
        $intType = $this->getIntType();
        $floatType = $this->getFloatType();
        $boolType = $this->getBoolType();

        // Test object to string conversion (should return empty string)
        $result1 = $this->callPrivateMethod($this->inputQuery, 'convertScalar', [new stdClass(), $stringType]);
        $this->assertSame('', $result1);

        // Test array to int conversion (should return 0)
        $result2 = $this->callPrivateMethod($this->inputQuery, 'convertScalar', [['not', 'numeric'], $intType]);
        $this->assertSame(0, $result2);

        // Test array to float conversion (should return 0.0)
        $result3 = $this->callPrivateMethod($this->inputQuery, 'convertScalar', [['not', 'numeric'], $floatType]);
        $this->assertSame(0.0, $result3);

        // Test empty array to bool conversion (should return false)
        $result4 = $this->callPrivateMethod($this->inputQuery, 'convertScalar', [[], $boolType]);
        $this->assertFalse($result4);
    }

    public function testResolveUnionTypeNonFileUploadPath(): void
    {
        // Test case: resolveUnionType with union that doesn't contain FileUpload
        // Expected: cover the "not a FileUpload union type" fallback path (line 648)
        $method = new ReflectionMethod($this, 'methodWithStringIntUnion');
        $param = $method->getParameters()[0];
        $unionType = $param->getType();

        $query = ['value' => 42];

        $result = $this->callPrivateMethod($this->inputQuery, 'resolveUnionType', [$param, $query, $unionType]);
        $this->assertSame(42, $result);
    }

    // Helper methods representing various boundary cases

    /**
     * Required parameter without default value
     */
    private function methodWithRequiredParam(string $data): void
    {
        // Required parameter for testing getDefaultValue exception
    }

    /**
     * Input object for testing complex resolution
     */
    private function methodWithInputObject(
        #[Input]
        UserInputWithAttribute $user,
    ): void {
        // Input object resolution
    }

    /**
     * ArrayObject with Input attribute
     */
    private function methodWithArrayObjectInput(
        #[Input(item: UserInputWithAttribute::class)]
        ArrayObject $items,
    ): void {
        // ArrayObject creation
    }

    /**
     * Boolean parameter for scalar conversion
     */
    private function methodWithBoolInput(
        #[Input]
        bool $active,
    ): void {
        // Boolean conversion test
    }

    /**
     * DI parameter with default value
     */
    private function methodWithDefaultDIParam(
        string $serviceName = 'default-service',
    ): void {
        // DI with default fallback
    }

    /**
     * Union type without FileUpload
     */
    private function methodWithStringIntUnion(string|int $value): void
    {
        // Non-FileUpload union type
    }

    // Helper methods for getting reflection types
    private function getStringType(): ReflectionNamedType
    {
        $method = new ReflectionMethod($this, 'stringTypeHelper');

        return $method->getParameters()[0]->getType();
    }

    private function getIntType(): ReflectionNamedType
    {
        $method = new ReflectionMethod($this, 'intTypeHelper');

        return $method->getParameters()[0]->getType();
    }

    private function getFloatType(): ReflectionNamedType
    {
        $method = new ReflectionMethod($this, 'floatTypeHelper');

        return $method->getParameters()[0]->getType();
    }

    private function getBoolType(): ReflectionNamedType
    {
        $method = new ReflectionMethod($this, 'boolTypeHelper');

        return $method->getParameters()[0]->getType();
    }

    private function stringTypeHelper(string $param): void
    {
    }

    private function intTypeHelper(int $param): void
    {
    }

    private function floatTypeHelper(float $param): void
    {
    }

    private function boolTypeHelper(bool $param): void
    {
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
