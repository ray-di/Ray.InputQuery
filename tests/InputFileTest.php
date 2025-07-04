<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use InvalidArgumentException;
use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Fake\ConflictingAttributesInput;
use Ray\InputQuery\Fake\InputFileExtensionValidationInput;
use Ray\InputQuery\Fake\InputFileInput;
use Ray\InputQuery\Fake\InputFileValidationInput;
use Ray\InputQuery\Fake\InputFileWithOptionsInput;
use Ray\InputQuery\Fake\MultipleInputFileAttributesInput;

use const UPLOAD_ERR_OK;

final class InputFileTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector(), new FileUploadFactory());
    }

    public function testCreateFileInputFromQuery(): void
    {
        $fileUpload = FileUpload::create([
            'name' => 'test.txt',
            'type' => 'text/plain',
            'size' => 100,
            'tmp_name' => '/tmp/test',
            'error' => UPLOAD_ERR_OK,
        ]);
        $query = ['name' => 'test user', 'avatar' => $fileUpload];

        $input = $this->inputQuery->create(InputFileInput::class, $query);

        $this->assertInstanceOf(InputFileInput::class, $input);
        $this->assertSame($fileUpload, $input->avatar);
    }

    public function testCreateFileInputWithOptionsFromQuery(): void
    {
        $fileUpload = FileUpload::create([
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => 500,
            'tmp_name' => '/tmp/test',
            'error' => UPLOAD_ERR_OK,
        ]);
        $query = ['name' => 'test user', 'avatar' => $fileUpload];

        $input = $this->inputQuery->create(InputFileWithOptionsInput::class, $query);

        $this->assertInstanceOf(InputFileWithOptionsInput::class, $input);
        $this->assertSame($fileUpload, $input->avatar);
    }

    public function testCreateFileInputFromFiles(): void
    {
        // Simulate $_FILES data
        $_FILES['avatar'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'size' => 100,
            'tmp_name' => '/tmp/test',
            'error' => UPLOAD_ERR_OK,
        ];

        $query = ['name' => 'test user'];
        $input = $this->inputQuery->create(InputFileInput::class, $query);

        $this->assertInstanceOf(InputFileInput::class, $input);
        $this->assertInstanceOf(FileUpload::class, $input->avatar);
        $this->assertSame('test.txt', $input->avatar->name);
        $this->assertSame('text/plain', $input->avatar->type);
        $this->assertSame(100, $input->avatar->size);
    }

    public function testFileValidationMaxSizeError(): void
    {
        // Simulate $_FILES data with large file
        $_FILES['avatar'] = [
            'name' => 'large.jpg',
            'type' => 'image/jpeg',
            'size' => 2048, // 2KB, exceeds 1KB limit
            'tmp_name' => '/tmp/large',
            'error' => UPLOAD_ERR_OK,
        ];

        $query = ['name' => 'test user'];
        $input = $this->inputQuery->create(InputFileValidationInput::class, $query);

        $this->assertInstanceOf(InputFileValidationInput::class, $input);
        $this->assertInstanceOf(ErrorFileUpload::class, $input->avatar);
        $this->assertNotNull($input->avatar->message);
        $this->assertStringContainsString('File size exceeds maximum allowed size', $input->avatar->message);
    }

    public function testFileValidationTypeError(): void
    {
        // Simulate $_FILES data with wrong type
        $_FILES['avatar'] = [
            'name' => 'document.pdf',
            'type' => 'application/pdf',
            'size' => 500,
            'tmp_name' => '/tmp/document',
            'error' => UPLOAD_ERR_OK,
        ];

        $query = ['name' => 'test user'];
        $input = $this->inputQuery->create(InputFileValidationInput::class, $query);

        $this->assertInstanceOf(InputFileValidationInput::class, $input);
        $this->assertInstanceOf(ErrorFileUpload::class, $input->avatar);
        $this->assertNotNull($input->avatar->message);
        $this->assertStringContainsString('File type application/pdf is not allowed', $input->avatar->message);
    }

    public function testFileValidationSuccess(): void
    {
        // Simulate $_FILES data with valid file
        $_FILES['avatar'] = [
            'name' => 'small.jpg',
            'type' => 'image/jpeg',
            'size' => 500,
            'tmp_name' => '/tmp/small',
            'error' => UPLOAD_ERR_OK,
        ];

        $query = ['name' => 'test user'];
        $input = $this->inputQuery->create(InputFileValidationInput::class, $query);

        $this->assertInstanceOf(InputFileValidationInput::class, $input);
        $this->assertInstanceOf(FileUpload::class, $input->avatar);
        $this->assertSame('small.jpg', $input->avatar->name);
        $this->assertSame(500, $input->avatar->size);
    }

    public function testFileValidationExtensionError(): void
    {
        // Simulate $_FILES data with invalid extension
        $_FILES['avatar'] = [
            'name' => 'document.pdf',
            'type' => 'image/jpeg', // Valid type but invalid extension
            'size' => 500,
            'tmp_name' => '/tmp/document',
            'error' => UPLOAD_ERR_OK,
        ];

        $query = ['name' => 'test user'];
        $input = $this->inputQuery->create(InputFileExtensionValidationInput::class, $query);

        $this->assertInstanceOf(InputFileExtensionValidationInput::class, $input);
        $this->assertInstanceOf(ErrorFileUpload::class, $input->avatar);
        $this->assertNotNull($input->avatar->message);
        $this->assertStringContainsString('File extension pdf is not allowed', $input->avatar->message);
    }

    public function testFileValidationExtensionSuccess(): void
    {
        // Simulate $_FILES data with valid extension
        $_FILES['avatar'] = [
            'name' => 'image.jpg',
            'type' => 'image/jpeg',
            'size' => 500,
            'tmp_name' => '/tmp/image',
            'error' => UPLOAD_ERR_OK,
        ];

        $query = ['name' => 'test user'];
        $input = $this->inputQuery->create(InputFileExtensionValidationInput::class, $query);

        $this->assertInstanceOf(InputFileExtensionValidationInput::class, $input);
        $this->assertInstanceOf(FileUpload::class, $input->avatar);
        $this->assertSame('image.jpg', $input->avatar->name);
    }

    public function testFileValidationExtensionCaseInsensitive(): void
    {
        // Test uppercase extension
        $_FILES['avatar'] = [
            'name' => 'image.JPG', // Uppercase extension
            'type' => 'image/jpeg',
            'size' => 500,
            'tmp_name' => '/tmp/image',
            'error' => UPLOAD_ERR_OK,
        ];

        $query = ['name' => 'test user'];
        $input = $this->inputQuery->create(InputFileExtensionValidationInput::class, $query);

        $this->assertInstanceOf(InputFileExtensionValidationInput::class, $input);
        // This should fail because pathinfo() is case-sensitive
        $this->assertInstanceOf(ErrorFileUpload::class, $input->avatar);
        $this->assertStringContainsString('File extension JPG is not allowed', $input->avatar->message);
    }

    public function testMultipleInputFileAttributesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only one #[InputFile] attribute is allowed per parameter');

        $query = ['name' => 'test user'];
        $this->inputQuery->create(MultipleInputFileAttributesInput::class, $query);
    }

    public function testConflictingInputAndInputFileAttributesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter $conflictingParam cannot have both #[Input] and #[InputFile] attributes at the same time.');

        $query = ['name' => 'test user'];
        $this->inputQuery->create(ConflictingAttributesInput::class, $query);
    }

    protected function tearDown(): void
    {
        // Clean up $_FILES
        $_FILES = [];
    }
}
