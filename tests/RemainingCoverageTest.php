<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use InvalidArgumentException;
use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use Ray\InputQuery\Fake\InputFileInput;
use Ray\InputQuery\Fake\InputFileValidationInput;
use ReflectionClass;
use ReflectionMethod;

final class RemainingCoverageTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testGetDefaultValueMethod(): void
    {
        // Test the getDefaultValue method directly
        $method = new ReflectionMethod($this, 'methodWithDefaultValue');
        $param = $method->getParameters()[0];

        $result = $this->callPrivateMethod($this->inputQuery, 'getDefaultValue', [$param]);
        $this->assertSame('default', $result);
    }

    public function testResolveFileUploadWithValidationMethod(): void
    {
        // Test resolveFileUploadWithValidation method
        $method = new ReflectionMethod(InputFileValidationInput::class, '__construct');
        $param = $method->getParameters()[1]; // avatar parameter with InputFile attribute
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $_FILES['avatar'] = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => 500, // Within the 1KB limit
            'tmp_name' => '/tmp/test',
            'error' => 0,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveFileUploadWithValidation',
            [$param, [], $inputFileAttributes],
        );

        $this->assertInstanceOf(FileUpload::class, $result);
    }

    public function testResolveInputFileParameterMethod(): void
    {
        // Test resolveInputFileParameter method
        $method = new ReflectionMethod(InputFileInput::class, '__construct');
        $param = $method->getParameters()[1]; // avatar parameter
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $_FILES['avatar'] = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'tmp_name' => '/tmp/test',
            'error' => 0,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        $this->assertInstanceOf(FileUpload::class, $result);
    }

    public function testCreateArrayOfFileUploadsWithValidationMethod(): void
    {
        // Test createArrayOfFileUploadsWithValidation method
        $_FILES['images'] = [
            'name' => ['image1.jpg', 'image2.png'],
            'type' => ['image/jpeg', 'image/png'],
            'size' => [500, 600], // Within validation limits
            'tmp_name' => ['/tmp/test1', '/tmp/test2'],
            'error' => [0, 0],
        ];

        // Create fake InputFile attributes
        $inputFile = new InputFile(maxSize: 1024, allowedTypes: ['image/jpeg', 'image/png']);
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
            'createArrayOfFileUploadsWithValidation',
            ['images', [], [$customAttribute]],
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(FileUpload::class, $result[0]);
        $this->assertInstanceOf(FileUpload::class, $result[1]);
    }

    public function testResolveBuiltinTypeWithArrayType(): void
    {
        // Test resolveBuiltinType with array type that has Input attribute
        $method = new ReflectionMethod($this, 'methodWithInputArrayParam');
        $param = $method->getParameters()[0];
        $type = $param->getType();
        $inputAttributes = $param->getAttributes(Input::class);

        $query = ['data' => ['item1', 'item2']];
        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveBuiltinType',
            [$param, $query, $inputAttributes, $type],
        );

        $this->assertSame(['item1', 'item2'], $result);
    }

    public function testResolveBuiltinTypeWithMissingArrayParam(): void
    {
        // Test resolveBuiltinType with missing array parameter - should throw exception
        $method = new ReflectionMethod($this, 'methodWithInputArrayParam');
        $param = $method->getParameters()[0];
        $type = $param->getType();
        $inputAttributes = $param->getAttributes(Input::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required parameter "data" is missing and has no default value');

        $query = []; // No 'data' key
        $this->callPrivateMethod(
            $this->inputQuery,
            'resolveBuiltinType',
            [$param, $query, $inputAttributes, $type],
        );
    }

    // Helper methods for testing
    private function methodWithDefaultValue(string $param = 'default'): void
    {
        // Test method
    }

    private function methodWithInputArrayParam(
        #[Input]
        array $data,
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
