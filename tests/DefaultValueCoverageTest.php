<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use InvalidArgumentException;
use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\InputFile;
use ReflectionClass;
use ReflectionMethod;

final class DefaultValueCoverageTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testGetDefaultValueFromFileUploadParameter(): void
    {
        // Test case: Cover `return $param->getDefaultValue();` line in file upload error handling
        // Expected: Access default value when file upload parameter has one
        $method = new ReflectionMethod($this, 'methodWithFileUploadDefault');
        $param = $method->getParameters()[0];

        // Clear $_FILES to simulate no file uploaded
        $_FILES = [];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'getDefaultValue',
            [$param],
        );

        $this->assertNull($result);
    }

    public function testGetDefaultValueOrThrowFileUploadScenario(): void
    {
        // Test case: Cover getDefaultValueOrThrow with file upload parameter
        // Expected: Returns default value when available
        $method = new ReflectionMethod($this, 'methodWithFileUploadDefault');
        $param = $method->getParameters()[0];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'getDefaultValueOrThrow',
            [$param, 'Custom error for file upload'],
        );

        $this->assertNull($result);
    }

    public function testFileUploadParameterWithNoDefaultThrowsException(): void
    {
        // Test case: File upload parameter without default should throw exception
        // Expected: InvalidArgumentException when no default and no file
        $method = new ReflectionMethod($this, 'methodWithRequiredFileUpload');
        $param = $method->getParameters()[0];

        $_FILES = []; // No files uploaded

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required parameter "required" is missing and has no default value');

        $this->callPrivateMethod(
            $this->inputQuery,
            'getDefaultValue',
            [$param],
        );
    }

    // Helper methods for testing default values

    /**
     * FileUpload parameter with default null value
     */
    private function methodWithFileUploadDefault(
        #[InputFile]
        FileUpload|null $optional = null,
    ): void {
        // FileUpload with default null
    }

    /**
     * Required FileUpload parameter (no default)
     */
    private function methodWithRequiredFileUpload(
        #[InputFile]
        FileUpload $required,
    ): void {
        // Required FileUpload without default
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
