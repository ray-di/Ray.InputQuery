<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Exception\InvalidFileUploadAttributeException;
use ReflectionMethod;

final class ArrayFileUploadBuiltinTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testInputAttributeWithFileUploadArrayThrowsException(): void
    {
        // Test case: Using #[Input(item: FileUpload::class)] array through actual public method
        // Expected: InvalidFileUploadAttributeException thrown
        $this->expectException(InvalidFileUploadAttributeException::class);
        $this->expectExceptionMessage('FileUpload array parameter "uploads" must use #[InputFile] attribute, not #[Input]');

        // Use actual public method that will trigger the exception
        $this->inputQuery->getArguments(
            new ReflectionMethod($this, 'methodWithFileUploadArray'),
            [],
        );
    }

    public function testInputAttributeWithErrorFileUploadArrayThrowsException(): void
    {
        // Test case: Using #[Input(item: ErrorFileUpload::class)] array through actual public method
        // Expected: InvalidFileUploadAttributeException thrown for ErrorFileUpload too
        $this->expectException(InvalidFileUploadAttributeException::class);
        $this->expectExceptionMessage('FileUpload array parameter "errors" must use #[InputFile] attribute, not #[Input]');

        // Use actual public method that will trigger the exception
        $this->inputQuery->getArguments(
            new ReflectionMethod($this, 'methodWithErrorFileUploadArray'),
            [],
        );
    }

    // Helper methods representing FileUpload array scenarios

    /**
     * Array of FileUpload objects
     */
    private function methodWithFileUploadArray(
        #[Input(item: FileUpload::class)]
        array $uploads,
    ): void {
        // Array of FileUpload
    }

    /**
     * Array of ErrorFileUpload objects
     */
    private function methodWithErrorFileUploadArray(
        #[Input(item: ErrorFileUpload::class)]
        array $errors,
    ): void {
        // Array of ErrorFileUpload
    }
}
