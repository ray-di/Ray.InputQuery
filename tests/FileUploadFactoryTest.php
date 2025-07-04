<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Countable;
use InvalidArgumentException;
use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionUnionType;
use Traversable;

use const PHP_VERSION_ID;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

final class FileUploadFactoryTest extends TestCase
{
    private FileUploadFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new FileUploadFactory();
    }

    public function testCreateFromFiles(): void
    {
        $method = new ReflectionMethod($this, 'dummyMethodForFileUpload');
        $param = $method->getParameters()[0];

        $filesData = [
            'upload' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'size' => 100,
                'tmp_name' => '/tmp/test',
                'error' => UPLOAD_ERR_OK,
            ],
        ];

        $validationOptions = [
            'maxSize' => 1024,
            'allowedTypes' => ['text/plain'],
        ];

        $result = $this->factory->createFromFiles($param, $filesData, $validationOptions);

        $this->assertInstanceOf(FileUpload::class, $result);
    }

    public function testCreateFromFilesWithMissingFile(): void
    {
        $method = new ReflectionMethod($this, 'dummyMethodForNullableFileUpload');
        $param = $method->getParameters()[0];

        $filesData = [];

        $result = $this->factory->createFromFiles($param, $filesData);

        $this->assertNull($result);
    }

    public function testCreateFromFilesWithErrorFile(): void
    {
        $method = new ReflectionMethod($this, 'dummyMethodForFileUpload');
        $param = $method->getParameters()[0];

        $filesData = [
            'upload' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'size' => 100,
                'tmp_name' => '/tmp/test',
                'error' => UPLOAD_ERR_NO_FILE,
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Required file parameter 'upload' is missing");

        $this->factory->createFromFiles($param, $filesData);
    }

    public function testResolveFileUploadUnionTypeWithValidUnion(): void
    {
        $method = new ReflectionMethod($this, 'dummyMethodForUnionType');
        $param = $method->getParameters()[0];
        $unionType = $param->getType();

        $this->assertInstanceOf(ReflectionUnionType::class, $unionType);

        $query = [
            'upload' => FileUpload::create([
                'name' => 'test.txt',
                'type' => 'text/plain',
                'size' => 100,
                'tmp_name' => '/tmp/test',
                'error' => UPLOAD_ERR_OK,
            ]),
        ];

        $result = $this->factory->resolveFileUploadUnionType($param, $query, $unionType);

        $this->assertInstanceOf(FileUpload::class, $result);
    }

    public function testResolveFileUploadUnionTypeWithInvalidUnion(): void
    {
        $method = new ReflectionMethod($this, 'dummyMethodForInvalidUnionType');
        $param = $method->getParameters()[0];
        $unionType = $param->getType();

        $this->assertInstanceOf(ReflectionUnionType::class, $unionType);

        $query = [];

        $result = $this->factory->resolveFileUploadUnionType($param, $query, $unionType);

        $this->assertNull($result);
    }

    public function testIsFileUploadType(): void
    {
        $this->assertTrue($this->factory->isFileUploadType(FileUpload::class));
        $this->assertTrue($this->factory->isFileUploadType(ErrorFileUpload::class));
        $this->assertFalse($this->factory->isFileUploadType('stdClass'));
        $this->assertFalse($this->factory->isFileUploadType('NonExistentClass'));
    }

    public function testCreateArrayWithEmptyQuery(): void
    {
        $inputFileAttributes = [];
        $result = $this->factory->createArray('uploads', [], $inputFileAttributes);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testCreateArrayWithProvidedFileUploads(): void
    {
        $fileUploads = [
            FileUpload::create([
                'name' => 'test1.txt',
                'type' => 'text/plain',
                'size' => 100,
                'tmp_name' => '/tmp/test1',
                'error' => UPLOAD_ERR_OK,
            ]),
            FileUpload::create([
                'name' => 'test2.txt',
                'type' => 'text/plain',
                'size' => 200,
                'tmp_name' => '/tmp/test2',
                'error' => UPLOAD_ERR_OK,
            ]),
        ];

        $query = ['uploads' => $fileUploads];
        $inputFileAttributes = [];

        $result = $this->factory->createArray('uploads', $query, $inputFileAttributes);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame($fileUploads, $result);
    }

    public function testCreateWithUnsupportedUnionType(): void
    {
        $method = new ReflectionMethod($this, 'dummyMethodForInvalidUnionType');
        $param = $method->getParameters()[0];
        $inputFileAttributes = [];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported union type for file upload parameter');

        $this->factory->create($param, [], $inputFileAttributes);
    }

    public function testCreateWithInvalidParameterType(): void
    {
        $method = new ReflectionMethod($this, 'dummyMethodForInvalidParameterType');
        $param = $method->getParameters()[0];
        $inputFileAttributes = [];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter invalidParam is not a valid file upload parameter');

        $this->factory->create($param, [], $inputFileAttributes);
    }

    public function testIsValidFileUploadUnionWithIntersectionType(): void
    {
        // Test case for PHP 8.1+ intersection types in union types
        // This should reach the 'return false' when a non-ReflectionNamedType is encountered
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Intersection types require PHP 8.1+');
        }

        $method = new ReflectionMethod($this, 'dummyMethodForComplexUnionType');
        $param = $method->getParameters()[0];
        $unionType = $param->getType();

        $this->assertInstanceOf(ReflectionUnionType::class, $unionType);

        // Call the private method to test the return false case
        $reflection = new ReflectionClass($this->factory);
        $isValidMethod = $reflection->getMethod('isValidFileUploadUnion');
        $isValidMethod->setAccessible(true);

        $result = $isValidMethod->invoke($this->factory, $unionType);

        // Should return false because ReflectionIntersectionType is not ReflectionNamedType
        $this->assertFalse($result);

        // Verify that this triggers the expected behavior in resolveFileUploadUnionType
        $resolveResult = $this->factory->resolveFileUploadUnionType($param, [], $unionType);
        $this->assertNull($resolveResult);
    }

    /**
     * Dummy methods for reflection testing
     */
    public function dummyMethodForFileUpload(FileUpload $upload): void
    {
    }

    public function dummyMethodForNullableFileUpload(FileUpload|null $upload): void
    {
    }

    public function dummyMethodForUnionType(FileUpload|ErrorFileUpload|null $upload): void
    {
    }

    public function dummyMethodForInvalidUnionType(string|int $value): void
    {
    }

    public function dummyMethodForMixedType(mixed $upload): void
    {
    }

    public function dummyMethodForInvalidParameterType(string $invalidParam): void
    {
    }

    /**
     * Try to create a case with intersection types (PHP 8.1+)
     */
    public function dummyMethodForComplexUnionType((Traversable&Countable)|string|null $param): void
    {
    }
}
