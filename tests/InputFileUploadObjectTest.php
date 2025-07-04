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

final class InputFileUploadObjectTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testInputAttributeWithFileUploadThrowsException(): void
    {
        // Test case: Using #[Input] with FileUpload parameter through actual public method
        // Expected: InvalidFileUploadAttributeException thrown
        $this->expectException(InvalidFileUploadAttributeException::class);
        $this->expectExceptionMessage('FileUpload parameter "document" must use #[InputFile] attribute, not #[Input]');

        // Use actual public method that will trigger the exception
        $this->inputQuery->getArguments(
            new ReflectionMethod($this, 'methodWithInputFileUpload'),
            [],
        );
    }

    public function testInputAttributeWithErrorFileUploadThrowsException(): void
    {
        // Test case: Using #[Input] with ErrorFileUpload parameter through actual public method
        // Expected: InvalidFileUploadAttributeException thrown for ErrorFileUpload too
        $this->expectException(InvalidFileUploadAttributeException::class);
        $this->expectExceptionMessage('FileUpload parameter "error" must use #[InputFile] attribute, not #[Input]');

        // Use actual public method that will trigger the exception
        $this->inputQuery->getArguments(
            new ReflectionMethod($this, 'methodWithInputErrorFileUpload'),
            [],
        );
    }

    public function testInputAttributeWithFileUploadAndQueryDataThrowsException(): void
    {
        // Test case: Using #[Input] with FileUpload parameter even with query data
        // Expected: InvalidFileUploadAttributeException thrown regardless of query content
        $fileUpload = FileUpload::create([
            'name' => 'query-file.txt',
            'type' => 'text/plain',
            'size' => 256,
            'tmp_name' => '/tmp/query',
            'error' => 0,
        ]);

        $query = ['document' => $fileUpload];

        $this->expectException(InvalidFileUploadAttributeException::class);
        $this->expectExceptionMessage('FileUpload parameter "document" must use #[InputFile] attribute, not #[Input]');

        // Use actual public method that will trigger the exception
        $this->inputQuery->getArguments(
            new ReflectionMethod($this, 'methodWithInputFileUpload'),
            $query,
        );
    }

    // Helper methods representing Input + FileUpload scenarios

    /**
     * Input attribute with FileUpload type (not InputFile)
     */
    private function methodWithInputFileUpload(
        #[Input]
        FileUpload $document,
    ): void {
        // Input + FileUpload type
    }

    /**
     * Input attribute with ErrorFileUpload type
     */
    private function methodWithInputErrorFileUpload(
        #[Input]
        ErrorFileUpload $error,
    ): void {
        // Input + ErrorFileUpload type
    }
}
