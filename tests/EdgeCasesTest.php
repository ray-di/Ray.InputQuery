<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use InvalidArgumentException;
use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\InputFile;
use Ray\InputQuery\Fake\InputFileInput;
use Ray\InputQuery\Fake\InputFileValidationInput;
use ReflectionClass;
use ReflectionMethod;
use TypeError;

use const UPLOAD_ERR_NO_FILE;

final class EdgeCasesTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testGetDefaultValueOrThrowWithNullableParameter(): void
    {
        $method = new ReflectionMethod($this, 'methodWithNullableParam');
        $param = $method->getParameters()[0];

        // Test nullable parameter returns null
        $result = $this->callPrivateMethod($this->inputQuery, 'getDefaultValueOrThrow', [$param, 'Custom error']);
        $this->assertNull($result);
    }

    public function testGetDefaultValueOrThrowWithDefaultValue(): void
    {
        $method = new ReflectionMethod($this, 'methodWithDefaultParam');
        $param = $method->getParameters()[0];

        // Test parameter with default value
        $result = $this->callPrivateMethod($this->inputQuery, 'getDefaultValueOrThrow', [$param, 'Custom error']);
        $this->assertSame('default', $result);
    }

    public function testGetDefaultValueOrThrowWithRequiredParameter(): void
    {
        $method = new ReflectionMethod($this, 'methodWithRequiredParam');
        $param = $method->getParameters()[0];

        // Test required parameter throws exception with custom message
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom error message');

        $this->callPrivateMethod($this->inputQuery, 'getDefaultValueOrThrow', [$param, 'Custom error message']);
    }

    public function testIsFileUploadTypeWhenFileUploadClassDoesNotExist(): void
    {
        // This is tricky to test since we can't unload classes in PHP
        // We'll test the existing class paths instead
        $result = $this->callPrivateMethod($this->inputQuery, 'isFileUploadType', ['NonExistentClass']);
        $this->assertFalse($result);
    }

    public function testIsFileUploadTypeWithErrorFileUpload(): void
    {
        $result = $this->callPrivateMethod($this->inputQuery, 'isFileUploadType', ['Koriym\FileUpload\ErrorFileUpload']);
        $this->assertTrue($result);
    }

    public function testExtractValidationOptionsWithEmptyAttributes(): void
    {
        $result = $this->callPrivateMethod($this->inputQuery, 'extractValidationOptions', [[]]);
        $this->assertSame([], $result);
    }

    public function testExtractValidationOptionsWithZeroMaxSize(): void
    {
        // Use real InputFile attribute from a real class
        $method = new ReflectionMethod(InputFileValidationInput::class, '__construct');
        $param = $method->getParameters()[1]; // The #[InputFile] parameter
        $attributes = $param->getAttributes();

        // Create a new InputFile with maxSize = 0
        $inputFile = new InputFile(maxSize: 0);

        // We need to test the actual extraction logic, so let's create a custom attribute
        // that returns our InputFile instance
        $customAttribute = new class ($inputFile) {
            public function __construct(private InputFile $inputFile)
            {
            }

            public function newInstance(): InputFile
            {
                return $this->inputFile;
            }
        };

        $result = $this->callPrivateMethod($this->inputQuery, 'extractValidationOptions', [[$customAttribute]]);
        $this->assertSame([], $result); // maxSize = 0 should be filtered out
    }

    public function testFileUploadMissingRequiredParameter(): void
    {
        // Test when $_FILES doesn't contain the required file parameter
        $_FILES = []; // No files

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Required file parameter 'avatar' is missing");

        $this->inputQuery->create(InputFileInput::class, ['name' => 'test']);
    }

    public function testFileUploadWithNoFileError(): void
    {
        // Test UPLOAD_ERR_NO_FILE error handling
        $_FILES['avatar'] = [
            'name' => '',
            'type' => '',
            'size' => 0,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Required file parameter 'avatar' is missing");

        $this->inputQuery->create(InputFileInput::class, ['name' => 'test']);
    }

    public function testCreateArrayOfInputsWithInvalidItemData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array for item at key "0", got string');

        // Test when array item is not an array
        $query = [
            'items' => ['not-an-array'], // Should be array of arrays
        ];

        $this->callPrivateMethod($this->inputQuery, 'createArrayOfInputs', ['items', $query, InputFileInput::class]);
    }

    public function testConvertMultipleFileFormatWithMissingField(): void
    {
        // Test with missing 'name' field causes TypeError when trying to count(null)
        $fileData = [
            'type' => ['image/jpeg'],
            'size' => [1024],
            'tmp_name' => ['/tmp/test'],
            'error' => [0],
        ];

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('count(): Argument #1 ($value) must be of type Countable|array, null given');

        $this->callPrivateMethod($this->inputQuery, 'convertMultipleFileFormat', [$fileData]);
    }

    public function testConvertMultipleFileFormatWithEmptyArrays(): void
    {
        // Test with empty arrays
        $fileData = [
            'name' => [],
            'type' => [],
            'size' => [],
            'tmp_name' => [],
            'error' => [],
        ];

        $result = $this->callPrivateMethod($this->inputQuery, 'convertMultipleFileFormat', [$fileData]);
        $this->assertSame([], $result);
    }

    public function testConvertMultipleFileFormatWithMismatchedArrayLengths(): void
    {
        // Test with mismatched array lengths - this method doesn't validate mismatched lengths
        // It will use the ?? operator to provide defaults, so let's test the actual behavior
        $fileData = [
            'name' => ['file1.jpg', 'file2.jpg'],
            'type' => ['image/jpeg'], // Only one type for two files
            'size' => [1024, 2048],
            'tmp_name' => ['/tmp/test1', '/tmp/test2'],
            'error' => [0, 0],
        ];

        // The method should handle this gracefully with ?? defaults
        $result = $this->callPrivateMethod($this->inputQuery, 'convertMultipleFileFormat', [$fileData]);
        $this->assertCount(2, $result); // Should create 2 FileUpload objects
    }

    public function testGetInterfaceWithNonExistentInterface(): void
    {
        $method = new ReflectionMethod($this, 'methodWithRequiredParam');
        $param = $method->getParameters()[0];

        $result = $this->callPrivateMethod($this->inputQuery, 'getInterface', [$param]);
        $this->assertSame('', $result);
    }

    public function testGetQualifierWithNonExistentQualifier(): void
    {
        $method = new ReflectionMethod($this, 'methodWithRequiredParam');
        $param = $method->getParameters()[0];

        $result = $this->callPrivateMethod($this->inputQuery, 'getQualifier', [$param]);
        $this->assertSame('', $result);
    }

    public function testResolveArrayObjectTypeWithNonExistentClass(): void
    {
        // Test with non-existent class - should return null, not throw exception
        $result = $this->callPrivateMethod($this->inputQuery, 'resolveArrayObjectType', ['items', [], [], 'NonExistentClass']);
        $this->assertNull($result);
    }

    public function testResolveFileUploadWithMissingRequiredFile(): void
    {
        // Test resolveFileUpload with missing required file
        $method = new ReflectionMethod(InputFileInput::class, '__construct');
        $param = $method->getParameters()[1]; // The avatar parameter

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Required file parameter 'avatar' is missing");

        $this->callPrivateMethod($this->inputQuery, 'resolveFileUpload', [$param, [], []]);
    }

    public function testResolveFileUploadWithOptionalMissing(): void
    {
        // Create a real optional parameter - let's use a method that has an optional FileUpload parameter
        $method = new ReflectionMethod($this, 'methodWithOptionalFileUpload');
        $param = $method->getParameters()[0]; // The optional avatar parameter

        $result = $this->callPrivateMethod($this->inputQuery, 'resolveFileUpload', [$param, [], []]);
        $this->assertNull($result);
    }

    // Helper methods for testing
    private function methodWithNullableParam(string|null $param): void
    {
        // Test method
    }

    private function methodWithDefaultParam(string $param = 'default'): void
    {
        // Test method
    }

    private function methodWithRequiredParam(string $param): void
    {
        // Test method
    }

    private function methodWithOptionalFileUpload(FileUpload|null $avatar = null): void
    {
        // Test method for optional file upload
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
