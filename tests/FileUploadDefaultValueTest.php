<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\InputFile;
use ReflectionClass;
use ReflectionMethod;

use const UPLOAD_ERR_NO_FILE;

final class FileUploadDefaultValueTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testResolveFileUploadWithNoFileAndDefaultValue(): void
    {
        // Test case: No file uploaded (UPLOAD_ERR_NO_FILE) + parameter has default value
        // Expected: return $param->getDefaultValue(); should be called
        $method = new ReflectionMethod($this, 'methodWithOptionalFileUpload');
        $param = $method->getParameters()[0];

        // Simulate UPLOAD_ERR_NO_FILE
        $_FILES['avatar'] = [
            'name' => '',
            'type' => '',
            'size' => 0,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveFileUpload',
            [$param, []],
        );

        $this->assertNull($result); // Default value is null
    }

    public function testResolveFileUploadWithNoFileAndNullableParameter(): void
    {
        // Test case: No file uploaded + nullable parameter (no explicit default, but allows null)
        // Expected: getDefaultValueOrThrow should handle nullable parameter and return null
        $method = new ReflectionMethod($this, 'methodWithNullableFileUpload');
        $param = $method->getParameters()[0];

        // Simulate UPLOAD_ERR_NO_FILE
        $_FILES['optional'] = [
            'name' => '',
            'type' => '',
            'size' => 0,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveFileUpload',
            [$param, []],
        );

        $this->assertNull($result); // Nullable parameter should return null
    }

    public function testGetDefaultValueOrThrowWithNullableParameter(): void
    {
        // Test case: Direct test of getDefaultValueOrThrow with nullable parameter
        // Expected: Should return null for nullable parameter (line 360-361)
        $method = new ReflectionMethod($this, 'methodWithNullableFileUpload');
        $param = $method->getParameters()[0];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'getDefaultValueOrThrow',
            [$param, 'Custom error message'],
        );

        $this->assertNull($result);
    }

    public function testResolveFileUploadWithNoFilesArrayEntry(): void
    {
        // Test case: No $_FILES entry at all for the parameter (line 542-543)
        // Expected: Should use getDefaultValueOrThrow for nullable parameter
        $method = new ReflectionMethod($this, 'methodWithNullableFileUpload');
        $param = $method->getParameters()[0];

        // Completely clear $_FILES so no entry exists
        $_FILES = [];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveFileUpload',
            [$param, []],
        );

        $this->assertNull($result); // Should return null for nullable parameter
    }

    // Helper methods for testing default value scenarios

    /**
     * Optional file upload with explicit default value
     */
    private function methodWithOptionalFileUpload(
        #[InputFile]
        FileUpload|null $avatar = null,
    ): void {
        // Optional file upload with default null
    }

    /**
     * Nullable file upload parameter (implicit default)
     */
    private function methodWithNullableFileUpload(
        #[InputFile]
        FileUpload|null $optional,
    ): void {
        // Nullable file upload parameter
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
