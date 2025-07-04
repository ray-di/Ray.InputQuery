<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

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

        $this->assertInstanceOf(ErrorFileUpload::class, $result);
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

        $result = $this->factory->createFromFiles($param, $filesData);

        $this->assertInstanceOf(ErrorFileUpload::class, $result);
    }

    public function testCreateMultipleWithEmptyQuery(): void
    {
        $method = new ReflectionMethod($this, 'dummyMethodForArrayFileUpload');
        $param = $method->getParameters()[0];

        $result = $this->factory->createMultiple($param, [], null);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testCreateMultipleWithProvidedFileUploads(): void
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

        $method = new ReflectionMethod($this, 'dummyMethodForArrayFileUpload');
        $param = $method->getParameters()[0];

        $result = $this->factory->createMultiple($param, $query, null);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame($fileUploads, $result);
    }

    public function testCreateWithFileUpload(): void
    {
        $method = new ReflectionMethod($this, 'dummyMethodForFileUpload');
        $param = $method->getParameters()[0];

        $query = [
            'upload' => FileUpload::create([
                'name' => 'test.txt',
                'type' => 'text/plain',
                'size' => 100,
                'tmp_name' => '/tmp/test',
                'error' => UPLOAD_ERR_OK,
            ]),
        ];

        $result = $this->factory->create($param, $query, null);

        $this->assertInstanceOf(FileUpload::class, $result);
    }

    public function testCreateWithMissingFile(): void
    {
        $method = new ReflectionMethod($this, 'dummyMethodForFileUpload');
        $param = $method->getParameters()[0];

        $result = $this->factory->create($param, [], null);

        $this->assertInstanceOf(ErrorFileUpload::class, $result);
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

    /** @param array<mixed> $uploads */
    public function dummyMethodForArrayFileUpload(array $uploads): void
    {
    }
}
