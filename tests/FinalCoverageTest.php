<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use Ray\InputQuery\Fake\UserInputWithAttribute;
use ReflectionClass;
use ReflectionMethod;

final class FinalCoverageTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testResolveObjectTypeWithNestedObject(): void
    {
        // Test resolveObjectType with nested object creation
        $method = new ReflectionMethod($this, 'methodWithInputObject');
        $param = $method->getParameters()[0];
        $type = $param->getType();
        $inputAttributes = $param->getAttributes(Input::class);

        $query = [
            'user_id' => '1',
            'user_name' => 'test',
            'user_email' => 'test@example.com',
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveObjectType',
            [$param, $query, $inputAttributes, $type],
        );

        $this->assertInstanceOf(UserInputWithAttribute::class, $result);
    }

    public function testGetInterfaceMethod(): void
    {
        // Test getInterface method with parameter that has no special attributes
        $method = new ReflectionMethod($this, 'methodWithUnionType');
        $param = $method->getParameters()[0];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'getInterface',
            [$param],
        );

        $this->assertSame('', $result);
    }

    public function testGetQualifierMethod(): void
    {
        // Test getQualifier method with parameter that has no qualifiers
        $method = new ReflectionMethod($this, 'methodWithUnionType');
        $param = $method->getParameters()[0];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'getQualifier',
            [$param],
        );

        $this->assertSame('', $result);
    }

    public function testIsFileUploadTypeWithErrorFileUpload(): void
    {
        // Test isFileUploadType with ErrorFileUpload
        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'isFileUploadType',
            ['Koriym\FileUpload\ErrorFileUpload'],
        );

        $this->assertTrue($result);
    }

    public function testIsFileUploadTypeWithFileUpload(): void
    {
        // Test isFileUploadType with FileUpload
        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'isFileUploadType',
            ['Koriym\FileUpload\FileUpload'],
        );

        $this->assertTrue($result);
    }

    public function testIsFileUploadTypeWithNonFileUploadClass(): void
    {
        // Test isFileUploadType with non-FileUpload class
        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'isFileUploadType',
            ['stdClass'],
        );

        $this->assertFalse($result);
    }

    public function testCreateArrayOfInputsWithMissingKey(): void
    {
        // Test createArrayOfInputs when key doesn't exist in query
        $query = []; // No 'items' key

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'createArrayOfInputs',
            ['items', $query, UserInputWithAttribute::class],
        );

        $this->assertSame([], $result);
    }

    public function testCreateArrayOfInputsWithNonArrayValue(): void
    {
        // Test createArrayOfInputs when value is not array
        $query = ['items' => 'not-an-array'];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'createArrayOfInputs',
            ['items', $query, UserInputWithAttribute::class],
        );

        $this->assertSame([], $result);
    }

    public function testExtractValidationOptionsWithNullValues(): void
    {
        // Test extractValidationOptions with null values that should be filtered
        $inputFile = new InputFile(maxSize: null, allowedTypes: null);
        $customAttribute = new class ($inputFile) {
            public function __construct(private InputFile $inputFile)
            {
            }

            public function newInstance(): InputFile
            {
                return $this->inputFile;
            }
        };

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'extractValidationOptions',
            [[$customAttribute]],
        );

        $this->assertSame([], $result);
    }

    // Helper methods for testing
    private function methodWithInputObject(
        #[Input]
        UserInputWithAttribute $user,
    ): void {
        // Test method
    }

    private function methodWithUnionType(string|int $value): void
    {
        // Test method
    }

    private function callPrivateMethod(object $object, string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
