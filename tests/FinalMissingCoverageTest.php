<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use ArrayObject;
use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Fake\UserInputWithAttribute;
use ReflectionClass;
use ReflectionMethod;

use const UPLOAD_ERR_NO_FILE;

final class FinalMissingCoverageTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testResolveArrayObjectTypeWithNullInputAttribute(): void
    {
        // Test case: resolveArrayObjectType when Input attribute has null item
        // Expected: cover the return null path when inputAttribute->item is null
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

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveArrayObjectType',
            ['items', [], [$customAttribute], ArrayObject::class],
        );

        $this->assertNull($result);
    }

    public function testCreateArrayOfFileUploadsWithQueryArrayValue(): void
    {
        // Test case: createArrayOfFileUploads when query has array value
        // Expected: returns the query array directly (early return path)
        $fileUpload1 = FileUpload::create([
            'name' => 'file1.txt',
            'type' => 'text/plain',
            'size' => 100,
            'tmp_name' => '/tmp/file1',
            'error' => 0,
        ]);

        $query = ['files' => [$fileUpload1]];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'createArrayOfFileUploads',
            ['files', $query, []],
        );

        $this->assertSame([$fileUpload1], $result);
    }

    public function testCreateArrayOfFileUploadsWithRegularFileArrayFormat(): void
    {
        // Test case: createArrayOfFileUploads with regular file array format
        // Expected: cover the "regular array format" path (line 579-592)
        $_FILES['documents'] = [
            0 => [
                'name' => 'doc1.pdf',
                'type' => 'application/pdf',
                'size' => 1024,
                'tmp_name' => '/tmp/doc1',
                'error' => 0,
            ],
            1 => [
                'name' => 'doc2.pdf',
                'type' => 'application/pdf',
                'size' => 2048,
                'tmp_name' => '/tmp/doc2',
                'error' => UPLOAD_ERR_NO_FILE, // This should be skipped
            ],
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'createArrayOfFileUploads',
            ['documents', [], []],
        );

        $this->assertIsArray($result);
        $this->assertCount(1, $result); // Only first file should be processed
        $this->assertArrayHasKey(0, $result);
        $this->assertInstanceOf(FileUpload::class, $result[0]);
    }

    public function testConvertMultipleFileFormatWithMixedErrors(): void
    {
        // Test case: convertMultipleFileFormat with mixed error conditions
        // Expected: cover all error handling combinations
        $multipleFileData = [
            'name' => ['good.jpg', '', 'bad.txt'],
            'type' => ['image/jpeg', '', 'text/plain'],
            'size' => [1024, 0, 512],
            'tmp_name' => ['/tmp/good', '', '/tmp/bad'],
            'error' => [0, UPLOAD_ERR_NO_FILE, 0], // Middle file has no file error
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'convertMultipleFileFormat',
            [$multipleFileData, []],
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result); // Should skip the middle file
        $this->assertArrayHasKey(0, $result); // First file
        $this->assertArrayHasKey(2, $result); // Third file
        $this->assertArrayNotHasKey(1, $result); // Middle file skipped
    }

    public function testGetDefaultValueOrThrowWithNullableParameter(): void
    {
        // Test case: getDefaultValueOrThrow with nullable parameter
        // Expected: should return null for nullable parameters
        $method = new ReflectionMethod($this, 'methodWithNullableParam');
        $param = $method->getParameters()[0];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'getDefaultValueOrThrow',
            [$param, 'Custom error message'],
        );

        $this->assertNull($result);
    }

    public function testEdgeCaseMethodCoverage(): void
    {
        // Test case: Cover various edge case methods for 100% coverage
        // Expected: reach specific uncovered lines

        // Test isFileUploadType with interface check
        $result1 = $this->callPrivateMethod(
            $this->inputQuery,
            'isFileUploadType',
            ['Koriym\FileUpload\FileUploadInterface'],
        );
        $this->assertFalse($result1);

        // Test toCamelCase with edge cases
        $result2 = $this->callPrivateMethod($this->inputQuery, 'toCamelCase', ['a']);
        $this->assertSame('a', $result2);

        $result3 = $this->callPrivateMethod($this->inputQuery, 'toCamelCase', ['']);
        $this->assertSame('', $result3);
    }

    // Helper methods

    /**
     * User input for testing complex object resolution
     */
    private function methodWithUserInput(
        #[Input]
        UserInputWithAttribute $user,
    ): void {
        // User input object
    }

    /**
     * Nullable parameter for testing default value handling
     */
    private function methodWithNullableParam(string|null $optional): void
    {
        // Nullable parameter
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
